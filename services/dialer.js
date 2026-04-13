import mysql from "mysql2/promise";
import axios from "axios";
import qs from "qs";
import fs from "fs";
import path from "path";
import dotenv from "dotenv";
import { fileURLToPath } from "url";
import { v4 as uuidv4 } from 'uuid';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

[
    path.join(__dirname, '..', '.env.local'),
    path.join(__dirname, '..', '.env'),
    path.join(__dirname, '.env.local'),
    path.join(__dirname, '.env')
].forEach((envFile) => {
    if (fs.existsSync(envFile)) {
        dotenv.config({ path: envFile, override: false });
    }
});

console.log("DIALER V3 STARTING - DEBUG MODE");

const LOOP_MS = 1000;
const QUEUE_FRESH_SEC = 10;
const TOKEN_REUSE_SEC = 45;
const PHONE_LOCK_TTL_SEC = 7200; // Keep lock during long calls; stale locks are auto-cleaned
const PHONE_LOCK_CLEANUP_MS = 30000;
const EMAIL_RETRY_COOLDOWN_MS = 300000;
const LOG_FILE = path.join(__dirname, "dialer.log");
const WORKER_ID = `worker-${process.pid}`;
const APP_BASE_URL = (process.env.APP_BASE_URL || "http://127.0.0.1/newwave").replace(/\/$/, "");

// ------------ Helpers ------------
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const normalizeNum = (s) => String(s || "").replace(/[^\d+]/g, "");
const normalizePhoneKey = (s) => String(s || "").replace(/\D/g, "");
const companyTimezoneCache = new Map();

function parseUtcMysqlDate(value) {
    const raw = String(value || "").trim();
    if (!raw || raw === "0000-00-00 00:00:00") return null;

    const normalized = raw.includes("T")
        ? raw
        : raw.replace(" ", "T") + "Z";
    const parsed = new Date(normalized);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function diffSecondsFromUtcDate(value) {
    const parsed = parseUtcMysqlDate(value);
    if (!parsed) return 0;
    return Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 1000));
}

function log(msg, data = null) {
    const ts = new Date().toISOString();
    let entry = `[${ts}] ${msg}`;
    let serializedData = null;

    if (data) {
        try {
            serializedData = JSON.stringify(data, null, 2);
            entry += "\n" + serializedData;
        } catch (e) {
            serializedData = '[Circular/Unserializable Data]';
            entry += ` ${serializedData}`;
        }
    }

    console.log(`[${ts}] ${msg}`);
    if (serializedData) {
        console.log(serializedData);
    }

    fs.appendFileSync(LOG_FILE, entry + "\n");
}

function getAxiosErrorDetails(error) {
    const status = error?.response?.status ?? null;
    const statusText = error?.response?.statusText ?? null;
    let data = error?.response?.data ?? null;

    if (data && typeof data !== 'string') {
        try {
            data = JSON.stringify(data);
        } catch (_) {
            data = '[unserializable-response-data]';
        }
    }

    if (typeof data === 'string' && data.length > 700) {
        data = data.slice(0, 700) + '...';
    }

    const stack = typeof error?.stack === 'string'
        ? error.stack.split('\n').slice(0, 8).join('\n')
        : null;

    return {
        name: error?.name || typeof error,
        message: error?.message || (typeof error === 'string' ? error : 'Unknown error'),
        code: error?.code || null,
        status,
        statusText,
        data,
        stack
    };
}

const logCallAttemptV2 = async (companyId, campaignId, leadId, callId, status, disposition, agentId, attemptNo) => {
    if (!companyId || !campaignId || !leadId) {
        log(`[logCallAttemptV2] Skipped because required IDs are missing.`, {
            companyId,
            campaignId,
            leadId,
            callId,
            status,
            disposition,
            agentId,
            attemptNo
        });
        return;
    }
    try {
        await db.execute(
            `INSERT INTO dialer_call_log 
             (company_id, campaign_id, campaignnumber_id, call_id, call_status, disposition, agent_id, started_at, attempt_no)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)`,
            [companyId, campaignId, leadId, callId, status, disposition, agentId, attemptNo]
        );

        // Update Lead Summary
        await db.execute(
            `UPDATE campaignnumbers 
             SET last_call_status=?, last_disposition=?, agent_connected=?, last_call_id=?, attempts_used = attempts_used + 1, last_call_started_at=UTC_TIMESTAMP()
             WHERE id=? AND company_id=?`,
            [status, disposition, agentId, callId, leadId, companyId]
        );
    } catch (e) {
        let leadSnapshot = null;
        try {
            const [leadRows] = await db.execute(
                `SELECT id, company_id, campaignid, state, locked_by, lock_token, attempts_used, max_attempts
                 FROM campaignnumbers
                 WHERE id=? AND company_id=?
                 LIMIT 1`,
                [leadId, companyId]
            );
            leadSnapshot = leadRows?.[0] || null;
        } catch (lookupErr) {
            leadSnapshot = { lookupError: lookupErr.message };
        }

        log(`[logCallAttemptV2] Insert/update failed: ${e.message}`, {
            companyId,
            campaignId,
            leadId,
            callId,
            status,
            disposition,
            agentId,
            attemptNo,
            leadSnapshot
        });
        console.error("Error in logCallAttemptV2:", e.message);
    }
};

// ------------ DB ------------
const db = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    connectionLimit: 10
});

// ------------ PBXDETAIL: token cache per company ------------
async function getPbxTokenByCompany(companyId) {
    const [rows] = await db.execute(
        `SELECT id AS pbx_id, pbxurl, pbxclientid, pbxsecret, auth_token, auth_updated_at
         FROM pbxdetail WHERE company_id=? LIMIT 1`,
        [companyId]
    );
    if (!rows.length) throw new Error(`pbxdetail not found for company_id=${companyId}`);

    const pbx = rows[0];
    if (!pbx.pbxurl || !pbx.pbxclientid || !pbx.pbxsecret) throw new Error("PBX creds missing");

    let safeUrl = String(pbx.pbxurl).trim().replace(/\/$/, "");
    if (!safeUrl.match(/^https?:\/\//i)) safeUrl = `https://${safeUrl}`;

    if (pbx.auth_token && pbx.auth_updated_at) {
        const ageSec = diffSecondsFromUtcDate(pbx.auth_updated_at);
        if (ageSec < TOKEN_REUSE_SEC) {
            log(`[Company ${companyId}] Reusing cached 3CX token.`, {
                pbxurl: safeUrl,
                authUpdatedAt: pbx.auth_updated_at,
                ageSec,
                reuseWindowSec: TOKEN_REUSE_SEC
            });
            return { pbxurl: safeUrl, token: pbx.auth_token };
        }

        log(`[Company ${companyId}] Cached 3CX token too old; refreshing.`, {
            pbxurl: safeUrl,
            authUpdatedAt: pbx.auth_updated_at,
            ageSec,
            reuseWindowSec: TOKEN_REUSE_SEC
        });
    } else {
        log(`[Company ${companyId}] No cached 3CX token found; refreshing.`, {
            pbxurl: safeUrl,
            hasToken: Boolean(pbx.auth_token),
            authUpdatedAt: pbx.auth_updated_at || null
        });
    }

    log(`[Company ${companyId}] Refreshing 3CX Token...`, { pbxurl: safeUrl });
    const tokenUrl = `${safeUrl}/connect/token`;
    const body = qs.stringify({
        client_id: pbx.pbxclientid,
        client_secret: pbx.pbxsecret,
        grant_type: "client_credentials"
    });

    let resp;
    try {
        resp = await axios.post(tokenUrl, body, {
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            timeout: 10000
        });
    } catch (e) {
        log(`[Company ${companyId}] 3CX token refresh failed.`, {
            pbxurl: safeUrl,
            tokenUrl,
            ...getAxiosErrorDetails(e)
        });
        throw e;
    }

    const token = resp.data?.access_token;
    if (!token) throw new Error("No access_token from 3CX");

    await db.execute(
        `UPDATE pbxdetail SET auth_token=?, auth_updated_at=UTC_TIMESTAMP() WHERE id=? AND company_id=?`,
        [token, pbx.pbx_id, companyId]
    );

    log(`[Company ${companyId}] 3CX token refreshed successfully.`, {
        pbxurl: safeUrl,
        authUpdatedAt: new Date().toISOString()
    });

    return { pbxurl: safeUrl, token };
}

async function getCompanyTimezone(companyId) {
    const cacheKey = Number(companyId || 0);
    if (companyTimezoneCache.has(cacheKey)) {
        return companyTimezoneCache.get(cacheKey);
    }

    let timezone = "UTC";
    try {
        const [rows] = await db.execute(
            `SELECT NULLIF(TRIM(COALESCE(timezone, '')), '') AS timezone
             FROM pbxdetail
             WHERE company_id = ?
             LIMIT 1`,
            [companyId]
        );
        const resolvedTimezone = String(rows?.[0]?.timezone || "").trim();
        if (resolvedTimezone) {
            timezone = resolvedTimezone;
        }
    } catch (e) {
        log(`[Company ${companyId}] Unable to read PBX timezone for queue status checks: ${e.message}`);
    }

    companyTimezoneCache.set(cacheKey, timezone);
    return timezone;
}

async function getCampaignDidRotationConfig(companyId, campaignId) {
    let rows = [];
    try {
        const [qRows] = await db.execute(
            `SELECT cor.outbound_rule_id, cor.last_used_map_id, cdm.id AS map_id, cdm.sort_order, d.did
             FROM campaign_outbound_rule cor
             INNER JOIN campaign_did_map cdm
                ON cdm.company_id = cor.company_id AND cdm.campaign_id = cor.campaign_id
             INNER JOIN pbx_dids d
                ON d.id = cdm.did_id AND d.company_id = cdm.company_id
             WHERE cor.company_id = ? AND cor.campaign_id = ?
             ORDER BY cdm.sort_order ASC, cdm.id ASC`,
            [companyId, campaignId]
        );
        rows = qRows;
    } catch (e) {
        if (e && e.code === 'ER_NO_SUCH_TABLE') return null;
        throw e;
    }

    if (!rows.length) return null;

    return {
        outboundRuleId: Number(rows[0].outbound_rule_id || 0),
        lastUsedMapId: rows[0].last_used_map_id ? Number(rows[0].last_used_map_id) : null,
        didRows: rows.map(r => ({
            mapId: Number(r.map_id),
            did: String(r.did || "").trim()
        })).filter(r => r.did)
    };
}

function selectNextDidEntry(didRows, lastUsedMapId) {
    if (!Array.isArray(didRows) || didRows.length === 0) return null;
    if (!lastUsedMapId) return didRows[0];

    const idx = didRows.findIndex(r => r.mapId === Number(lastUsedMapId));
    if (idx < 0) return didRows[0];

    return didRows[(idx + 1) % didRows.length];
}

function buildOutboundRulePatchPayload(rule, nextDid) {
    const routes = Array.isArray(rule?.Routes) ? rule.Routes : [];
    const dnRanges = Array.isArray(rule?.DNRanges) ? rule.DNRanges : [];
    const groupIds = Array.isArray(rule?.GroupIds) ? rule.GroupIds : [];

    const patchedRoutes = routes.map((r, idx) => ({
        CallerID: idx === 0 ? nextDid : String(r?.CallerID || ""),
        Prepend: String(r?.Prepend || ""),
        StripDigits: Number(r?.StripDigits || 0),
        TrunkId: Number.isFinite(Number(r?.TrunkId)) ? Number(r?.TrunkId) : -1,
        TrunkName: r?.TrunkName ?? null,
        Append: String(r?.Append || "")
    }));

    return {
        Name: String(rule?.Name || ""),
        Prefix: String(rule?.Prefix || ""),
        DNRanges: dnRanges.map(d => ({
            From: String(d?.From || ""),
            To: d?.To ?? null
        })),
        NumberLengthRanges: String(rule?.NumberLengthRanges || ""),
        Routes: patchedRoutes,
        GroupIds: groupIds
    };
}

async function rotateDidForCampaignIfConfigured({ companyId, campaignId, pbxurl, token }) {
    const cfg = await getCampaignDidRotationConfig(companyId, campaignId);
    if (!cfg || !cfg.outboundRuleId || !cfg.didRows.length) return null;

    const nextEntry = selectNextDidEntry(cfg.didRows, cfg.lastUsedMapId);
    if (!nextEntry || !nextEntry.did) return null;

    const getUrl = `${pbxurl}/xapi/v1/OutboundRules(${cfg.outboundRuleId})`;
    const getResp = await axios.get(getUrl, {
        headers: { Authorization: `Bearer ${token}` },
        timeout: 15000
    });

    const payload = buildOutboundRulePatchPayload(getResp.data, nextEntry.did);

    const patchUrl = `${pbxurl}/xapi/v1/OutboundRules(${cfg.outboundRuleId})`;
    await axios.patch(patchUrl, payload, {
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json"
        },
        timeout: 15000
    });

    await db.execute(
        `UPDATE campaign_outbound_rule
         SET last_used_map_id = ?, updated_at = UTC_TIMESTAMP()
         WHERE company_id = ? AND campaign_id = ?`,
        [nextEntry.mapId, companyId, campaignId]
    );

    return nextEntry.did;
}

// ------------ Queue gate ------------
async function queueAllowsDialing(companyId, campaignId, queueDn) {
    const companyTimezone = await getCompanyTimezone(companyId);
    const hasCampaignColumn = await hasQueueStatusCampaignColumn();
    let rows = [];

    if (hasCampaignColumn) {
        const [campaignRows] = await db.execute(
            `SELECT available_agents,
                    updated_at,
                    TIMESTAMPDIFF(SECOND, updated_at, UTC_TIMESTAMP()) AS age_sec
             FROM dialer_queue_status
             WHERE company_id=? AND campaign_id=? AND queue_dn=?
             LIMIT 1`,
            [companyId, campaignId, queueDn]
        );
        rows = campaignRows;
    } else {
        const [legacyRows] = await db.execute(
            `SELECT available_agents,
                    updated_at,
                    TIMESTAMPDIFF(SECOND, updated_at, UTC_TIMESTAMP()) AS age_sec
             FROM dialer_queue_status
             WHERE company_id=? AND queue_dn=?
             LIMIT 1`,
            [companyId, queueDn]
        );
        rows = legacyRows;
    }

    if (!rows.length) return { ok: false, reason: "no_queue_status", timezone: companyTimezone };

    const { available_agents, updated_at } = rows[0];
    const ageSec = Math.max(0, Number(rows[0].age_sec || 0));

    if (ageSec > QUEUE_FRESH_SEC) {
        return { ok: false, reason: "stale_queue_status", age: ageSec, agents: available_agents, timezone: companyTimezone, updated_at_utc: updated_at };
    }
    if (parseInt(available_agents, 10) <= 0) {
        return { ok: false, reason: "no_free_agents", age: ageSec, agents: available_agents, timezone: companyTimezone, updated_at_utc: updated_at };
    }

    return { ok: true, age: ageSec, agents: available_agents, timezone: companyTimezone, updated_at_utc: updated_at };
}



// ------------ Global phone lock ------------
async function cleanupExpiredPhoneLocks() {
    await db.execute(`DELETE FROM active_phone_locks WHERE expires_at < UTC_TIMESTAMP()`);
}

async function tryAcquirePhoneLock(companyId, campaignId, leadId, phoneE164, phoneRaw, lockToken) {
    const sourcePhone = String(phoneE164 || phoneRaw || "").trim();
    const phoneKey = normalizePhoneKey(sourcePhone);
    if (!phoneKey) return { ok: false, reason: "invalid_phone" };

    const [res] = await db.execute(
        `INSERT IGNORE INTO active_phone_locks
         (company_id, phone_key, source_phone, lead_id, campaign_id, lock_token, locked_by, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND))`,
        [companyId, phoneKey, sourcePhone, leadId, campaignId, lockToken, WORKER_ID, PHONE_LOCK_TTL_SEC]
    );

    if (res.affectedRows > 0) return { ok: true, phoneKey };
    return { ok: false, reason: "phone_locked", phoneKey };
}

async function releasePhoneLock(lockToken) {
    if (!lockToken) return;
    await db.execute(`DELETE FROM active_phone_locks WHERE lock_token=?`, [lockToken]);
}

// ------------ Queue Logic: Pick & Lock ------------
let importBatchSchemaChecked = false;
let importBatchSchemaAvailable = false;
let queueStatusCampaignSchemaChecked = false;
let queueStatusCampaignSchemaAvailable = false;
const noNumbersEmailRetryAt = new Map();

async function hasImportBatchColumn() {
    if (importBatchSchemaChecked) return importBatchSchemaAvailable;

    try {
        const [rows] = await db.execute(
            `SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'campaignnumbers'
               AND COLUMN_NAME = 'import_batch_id'
             LIMIT 1`
        );
        importBatchSchemaAvailable = Array.isArray(rows) && rows.length > 0;
    } catch (e) {
        importBatchSchemaAvailable = false;
        log(`[Schema] Unable to verify import_batch_id column: ${e.message}`);
    }

    importBatchSchemaChecked = true;
    return importBatchSchemaAvailable;
}

async function hasQueueStatusCampaignColumn() {
    if (queueStatusCampaignSchemaChecked) return queueStatusCampaignSchemaAvailable;

    try {
        const [rows] = await db.execute(
            `SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'dialer_queue_status'
               AND COLUMN_NAME = 'campaign_id'
             LIMIT 1`
        );
        queueStatusCampaignSchemaAvailable = Array.isArray(rows) && rows.length > 0;
    } catch (e) {
        queueStatusCampaignSchemaAvailable = false;
        log(`[Schema] Unable to verify dialer_queue_status.campaign_id column: ${e.message}`);
    }

    queueStatusCampaignSchemaChecked = true;
    return queueStatusCampaignSchemaAvailable;
}

async function getLatestImportBatchId(companyId, campaignId) {
    if (!(await hasImportBatchColumn())) return null;

    const [rows] = await db.execute(
        `SELECT MAX(import_batch_id) AS latest_batch_id
         FROM campaignnumbers
         WHERE company_id = ?
           AND campaignid = ?
           AND import_batch_id IS NOT NULL`,
        [companyId, campaignId]
    );

    const latestBatchId = Number(rows?.[0]?.latest_batch_id || 0);
    return latestBatchId > 0 ? latestBatchId : null;
}

async function pickLead(companyId, campaignId, dpdFrom, dpdTo) {
    const lockToken = uuidv4();

    let dpdSql = "";
    let sqlParams = [companyId, campaignId];
    let batchSql = "";

    const latestImportBatchId = await getLatestImportBatchId(companyId, campaignId);
    if (latestImportBatchId) {
        batchSql = " AND import_batch_id = ? ";
        sqlParams.push(latestImportBatchId);
    }

    if (dpdFrom !== null && dpdTo !== null) {
        dpdSql = " AND (days_past_due >= ? AND days_past_due <= ?) ";
        sqlParams.push(dpdFrom, dpdTo);
    }

    // 1. Find candidate
    // Rules: READY/SCHEDULED, Time reached, Attempts left, Not DNC, Not Locked (or stale lock)
    const [rows] = await db.execute(
        `SELECT id, phone_e164, phone_raw, attempts_used, max_attempts 
         FROM campaignnumbers
         WHERE company_id=? AND campaignid=?
           AND state IN ('READY','SCHEDULED')
           AND is_dnc=0
           AND attempts_used < max_attempts
           AND (next_call_at IS NULL OR next_call_at <= UTC_TIMESTAMP())
           AND (locked_at IS NULL OR locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE))
           ${batchSql}
           ${dpdSql}
         ORDER BY priority ASC, next_call_at ASC
         LIMIT 1`,
        sqlParams
    );

    if (!rows.length) return null;
    const lead = rows[0];

    // 2. Try to Lock
    const [res] = await db.execute(
        `UPDATE campaignnumbers
         SET locked_at=UTC_TIMESTAMP(), locked_by=?, lock_token=?, state='DIALING'
         WHERE id=? AND company_id=?
           AND state IN ('READY','SCHEDULED')
           AND (locked_at IS NULL OR locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 MINUTE))`, // Optimistic Lock + stale lock guard
        [WORKER_ID, lockToken, lead.id, companyId]
    );

    const affected = Number(res?.affectedRows || 0);
    const changed = Number(res?.changedRows || 0);
    if (affected === 0) {
        log(`[Camp ${campaignId}] Lead ${lead.id} lock failed (lost race or stale state).`, {
            companyId,
            campaignId,
            leadId: lead.id,
            worker: WORKER_ID,
            affectedRows: affected,
            changedRows: changed
        });
        return null;
    }

    let persistedLock = null;
    try {
        const [lockRows] = await db.execute(
            `SELECT state, locked_by, lock_token, locked_at
             FROM campaignnumbers
             WHERE id=? AND company_id=?
             LIMIT 1`,
            [lead.id, companyId]
        );
        persistedLock = lockRows?.[0] || null;
    } catch (e) {
        log(`[Camp ${campaignId}] Lead ${lead.id} lock verify query failed: ${e.message}`);
    }

    log(`[Camp ${campaignId}] Lead ${lead.id} locked for dialing.`, {
        companyId,
        campaignId,
        leadId: lead.id,
        worker: WORKER_ID,
        lockToken,
        affectedRows: affected,
        changedRows: changed,
        persistedLock
    });

    return { ...lead, lockToken };
}

async function verifyCampaignLockSchema() {
    try {
        const [rows] = await db.execute(
            `SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'campaignnumbers'
               AND COLUMN_NAME IN ('locked_at', 'locked_by', 'lock_token')`
        );

        const found = new Set((rows || []).map(r => r.COLUMN_NAME));
        const required = ['locked_at', 'locked_by', 'lock_token'];
        const missing = required.filter(c => !found.has(c));

        if (missing.length > 0) {
            log(`[Schema] campaignnumbers is missing lock columns.`, { missing });
        } else {
            log(`[Schema] campaignnumbers lock columns verified.`);
        }
    } catch (e) {
        log(`[Schema] Unable to verify campaignnumbers lock columns: ${e.message}`);
    }
}

async function unlockLead(companyId, leadId, newState, nextCallAt = null) {
    let sql = `UPDATE campaignnumbers SET locked_at=NULL, locked_by=NULL, lock_token=NULL, state=?`;
    const params = [newState];

    if (nextCallAt) {
        sql += `, next_call_at=?`;
        params.push(nextCallAt);
    }

    sql += ` WHERE id=? AND company_id=?`;
    params.push(leadId, companyId);

    await db.execute(sql, params);
}

async function hasRemainingDialableLeads(companyId, campaignId, dpdFrom, dpdTo) {
    let dpdSql = "";
    const sqlParams = [companyId, campaignId];
    let batchSql = "";

    const latestImportBatchId = await getLatestImportBatchId(companyId, campaignId);
    if (latestImportBatchId) {
        batchSql = " AND import_batch_id = ? ";
        sqlParams.push(latestImportBatchId);
    }

    if (dpdFrom !== null && dpdTo !== null) {
        dpdSql = " AND (days_past_due >= ? AND days_past_due <= ?) ";
        sqlParams.push(dpdFrom, dpdTo);
    }

    const [rows] = await db.execute(
        `SELECT COUNT(*) AS count
         FROM campaignnumbers
         WHERE company_id=? AND campaignid=?
           AND is_dnc=0
           AND attempts_used < max_attempts
           AND state IN ('READY','SCHEDULED','DIALING','DISPO_PENDING')
           ${batchSql}
           ${dpdSql}`,
        sqlParams
    );

    return Number(rows?.[0]?.count || 0) > 0;
}

async function notifyIfCampaignHasNoNumbersLeft(campaign) {
    const hasRemaining = await hasRemainingDialableLeads(
        campaign.company_id,
        campaign.id,
        campaign.dpd_filter_from,
        campaign.dpd_filter_to
    );

    if (hasRemaining) return false;

    const retryAt = Number(noNumbersEmailRetryAt.get(campaign.id) || 0);
    if (retryAt > Date.now()) {
        return false;
    }

    try {
        const response = await axios.post(
            `${APP_BASE_URL}/api/send_campaign_empty_notification.php`,
            {
                company_id: campaign.company_id,
                campaign_id: campaign.id
            },
            {
                headers: { "Content-Type": "application/json" },
                timeout: 15000
            }
        );

        if (response.data?.success) {
            noNumbersEmailRetryAt.delete(campaign.id);
            log(`[Camp ${campaign.id}] No-numbers-left email sent.`, response.data);
            return true;
        }
    } catch (e) {
        noNumbersEmailRetryAt.set(campaign.id, Date.now() + EMAIL_RETRY_COOLDOWN_MS);
        log(`[Camp ${campaign.id}] No-numbers-left email error: ${e.message}`, getAxiosErrorDetails(e));
    }

    return false;
}

// ------------ 3CX & Monitor ------------
async function makeCall({ pbxurl, token, destination, dialerDn }) {
    const dn = String(dialerDn || "").trim();
    if (!dn) throw new Error("Campaign dialer DN is missing");
    const url = `${pbxurl}/callcontrol/${dn}/makecall`;
    const payload = { destination, timeout: 30 };
    const resp = await axios.post(url, payload, {
        headers: { Authorization: `Bearer ${token}` }
    });
    return resp.data?.result?.callid || resp.data?.callid; // Handle 3CX version diffs
}

async function waitThenTransfer({ pbxurl, token, callid, destination, transferTarget, dialerDn, transferLabel = '' }) {
    const deadline = Date.now() + 45000; // 45s Timeout
    const routeLabel = String(transferLabel || transferTarget || '').trim();
    let pollAttempt = 0;
    let logged401Details = false;
    log(`[Call ${callid}] Dialing ${destination} using Extension ${dialerDn}...`, {
        pbxurl,
        dialerDn,
        transferTarget,
        transferLabel: routeLabel || null
    });

    while (Date.now() < deadline) {
        try {
            pollAttempt += 1;
            const url = `${pbxurl}/callcontrol/${dialerDn}/participants`;
            const resp = await axios.get(url, { headers: { Authorization: `Bearer ${token}` } });

            const list = Array.isArray(resp.data) ? resp.data : [];
            const p = list.find(x => String(x.callid) === String(callid) && x.status === 'Connected');

            if (p) {
                log(`[Call ${callid}] Answered! Transferring to ${routeLabel || transferTarget}`, {
                    participantId: p.id,
                    participantStatus: p.status,
                    pollAttempt
                });
                await axios.post(
                    `${pbxurl}/callcontrol/${dialerDn}/participants/${p.id}/transferto`,
                    { destination: transferTarget },
                    { headers: { Authorization: `Bearer ${token}` } }
                );
                return true;
            }
        } catch (e) {
            const err = getAxiosErrorDetails(e);
            if (err.status === 401) {
                if (!logged401Details) {
                    logged401Details = true;
                    log(`[Call ${callid}] participant poll returned 401 Unauthorized.`, {
                        pbxurl,
                        dialerDn,
                        destination,
                        transferTarget,
                        transferLabel: routeLabel || null,
                        pollAttempt,
                        ...err
                    });
                }
            } else if (err.status !== 404) {
                log(`[Call ${callid}] participant poll error: ${e.message}`, {
                    pbxurl,
                    dialerDn,
                    pollAttempt,
                    ...err
                });
            }
        }
        await sleep(1000);
    }
    return false;
}

async function pickScheduledCallback() {
    const lockToken = uuidv4();

    try {
        const [rows] = await db.execute(
            `SELECT sc.id, sc.company_id, sc.campaign_id, sc.campaignnumber_id, sc.route_type,
                    sc.queue_dn, sc.agent_id, sc.agent_ext, sc.scheduled_for,
                    cn.phone_e164, cn.phone_raw, cn.attempts_used, cn.max_attempts,
                    c.dn_number, c.routeto, COALESCE(p.outbound_prefix, 'No') AS outbound_prefix
             FROM scheduled_calls sc
             INNER JOIN campaignnumbers cn
                ON cn.id = sc.campaignnumber_id AND cn.company_id = sc.company_id
             INNER JOIN campaign c
                ON c.id = sc.campaign_id AND c.company_id = sc.company_id
             LEFT JOIN pbxdetail p ON p.company_id = sc.company_id
             WHERE sc.route_type = 'Agent'
               AND sc.status IN ('pending', 'pending_agent')
               AND sc.scheduled_for <= UTC_TIMESTAMP()
               AND c.status = 'Running'
               AND c.is_deleted = 0
               AND COALESCE(cn.is_dnc, 0) = 0
               AND cn.attempts_used < cn.max_attempts
             ORDER BY sc.scheduled_for ASC, sc.id ASC
             LIMIT 1`
        );

        if (!rows.length) return null;
        const scheduled = rows[0];

        if (Number(scheduled.agent_id || 0) > 0) {
            const [busyRows] = await db.execute(
                `SELECT COUNT(*) AS count
                 FROM scheduled_calls
                 WHERE company_id = ?
                   AND agent_id = ?
                   AND status IN ('dialing', 'connected')`,
                [scheduled.company_id, scheduled.agent_id]
            );

            if (Number(busyRows?.[0]?.count || 0) > 0) {
                return null;
            }
        }

        const [res] = await db.execute(
            `UPDATE scheduled_calls
             SET status = 'dialing', last_attempt_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ? AND status IN ('pending', 'pending_agent')`,
            [scheduled.id]
        );

        if (Number(res?.affectedRows || 0) === 0) {
            return null;
        }

        return { ...scheduled, lockToken };
    } catch (e) {
        if (e && e.code === 'ER_NO_SUCH_TABLE') {
            return null;
        }
        log(`[Scheduled] Pick error${e?.message ? `: ${e.message}` : ''}`, getAxiosErrorDetails(e));
        return null;
    }
}

async function spawnScheduledCallFlow(scheduled) {
    let phoneLockAcquired = false;

    try {
        const companyId = Number(scheduled.company_id || 0);
        const campaignId = Number(scheduled.campaign_id || 0);
        const leadId = Number(scheduled.campaignnumber_id || 0);
        const destination = scheduled.phone_e164 || scheduled.phone_raw;
        const dialerDn = String(scheduled.dn_number || '').trim();
        const transferTarget = String(scheduled.agent_ext || '').trim();
        const transferLabel = transferTarget ? `agent ${transferTarget}` : 'assigned agent';

        if (!dialerDn) throw new Error(`Campaign ${campaignId} dn_number is empty for scheduled callback ${scheduled.id}`);
        if (!destination) throw new Error(`Scheduled callback ${scheduled.id} has no customer number`);
        if (!transferTarget) throw new Error(`Scheduled callback ${scheduled.id} has no agent extension`);

        const phoneLock = await tryAcquirePhoneLock(companyId, campaignId, leadId, scheduled.phone_e164, scheduled.phone_raw, scheduled.lockToken);
        if (!phoneLock.ok) {
            if (phoneLock.reason === 'invalid_phone') {
                await db.execute(
                    `UPDATE scheduled_calls
                     SET status = 'failed', completed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                     WHERE id = ?`,
                    [scheduled.id]
                );
                await unlockLead(companyId, leadId, 'INVALID');
            } else {
                await db.execute(
                    `UPDATE scheduled_calls
                     SET status = 'pending_agent', updated_at = UTC_TIMESTAMP()
                     WHERE id = ?`,
                    [scheduled.id]
                );
            }
            return;
        }
        phoneLockAcquired = true;

        await db.execute(
            `UPDATE campaignnumbers
             SET locked_at = UTC_TIMESTAMP(), locked_by = ?, lock_token = ?, state = 'DIALING'
             WHERE id = ? AND company_id = ?`,
            [WORKER_ID, scheduled.lockToken, leadId, companyId]
        );

        const { pbxurl, token } = await getPbxTokenByCompany(companyId);

        if (String(scheduled.outbound_prefix || '').toLowerCase() === 'yes') {
            try {
                const rotatedDid = await rotateDidForCampaignIfConfigured({
                    companyId,
                    campaignId,
                    pbxurl,
                    token
                });
                if (rotatedDid) {
                    log(`[Scheduled ${scheduled.id}] Rotated outbound CallerID to ${rotatedDid}`);
                }
            } catch (e) {
                log(`[Scheduled ${scheduled.id}] DID rotation skipped: ${e.message}`);
            }
        }

        const callid = await makeCall({ pbxurl, token, destination, dialerDn });
        const connected = await waitThenTransfer({
            pbxurl,
            token,
            callid,
            destination,
            transferTarget,
            transferLabel,
            dialerDn
        });

        if (connected) {
            await logCallAttemptV2(
                companyId,
                campaignId,
                leadId,
                callid,
                'ANSWERED',
                'SCHEDULED_CALLBACK',
                scheduled.agent_id || null,
                Number(scheduled.attempts_used || 0) + 1
            );

            await db.execute(
                `UPDATE scheduled_calls
                 SET status = 'connected', started_at = COALESCE(started_at, UTC_TIMESTAMP()), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?`,
                [scheduled.id]
            );

            await unlockLead(companyId, leadId, 'DISPO_PENDING');
            return;
        }

        await logCallAttemptV2(
            companyId,
            campaignId,
            leadId,
            callid,
            'NO_ANSWER',
            'SCHEDULED_CALLBACK_NO_ANSWER',
            scheduled.agent_id || null,
            Number(scheduled.attempts_used || 0) + 1
        );

        const used = Number(scheduled.attempts_used || 0) + 1;
        const maxAttempts = Number(scheduled.max_attempts || 0);

        if (maxAttempts > 0 && used >= maxAttempts) {
            await unlockLead(companyId, leadId, 'CLOSED');
        } else {
            await db.execute(
                `UPDATE campaignnumbers
                 SET next_call_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)
                 WHERE id = ? AND company_id = ?`,
                [leadId, companyId]
            );
            await unlockLead(companyId, leadId, 'READY');
        }

        await db.execute(
            `UPDATE scheduled_calls
             SET status = 'no_answer', completed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE id = ?`,
            [scheduled.id]
        );
        await releasePhoneLock(scheduled.lockToken).catch(() => { });
    } catch (e) {
        log(`[Scheduled ${scheduled.id}] Call flow error: ${e.message}`);

        await db.execute(
            `UPDATE scheduled_calls
             SET status = 'failed', completed_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP(),
                 note_text = CONCAT(COALESCE(note_text, ''), ?)
             WHERE id = ?`,
            [`\n[${new Date().toISOString()}] ${e.message}`, scheduled.id]
        ).catch(() => { });

        await unlockLead(Number(scheduled.company_id || 0), Number(scheduled.campaignnumber_id || 0), 'AGENT_SCHEDULED').catch(() => { });
        if (phoneLockAcquired) {
            await releasePhoneLock(scheduled.lockToken).catch(() => { });
        }
    }
}

// ------------ Global ActiveCalls Monitor ------------
let isGlobalMonitorRunning = false;

async function globalMonitorTick() {
    if (isGlobalMonitorRunning) return;
    isGlobalMonitorRunning = true;

    try {
        const [rows] = await db.execute(
            `SELECT DISTINCT company_id FROM campaignnumbers WHERE state='DISPO_PENDING'`
        );

        for (const row of rows) {
            const companyId = row.company_id;
            const creds = await getPbxTokenByCompany(companyId).catch(() => null);
            if (!creds || !creds.pbxurl || !creds.token) continue;

            const url = `${creds.pbxurl}/xapi/v1/ActiveCalls?$top=100&$skip=0&$count=true`;
            let activeCalls = [];
            try {
                const resp = await axios.get(url, { headers: { Authorization: `Bearer ${creds.token}` } });
                activeCalls = resp.data?.value || [];
            } catch (e) {
                log(`[GlobalMonitor] Error fetching active calls for company ${companyId}: ${e.message}`, {
                    companyId,
                    url,
                    ...getAxiosErrorDetails(e)
                });
                continue;
            }

            const [leads] = await db.execute(
                `SELECT id, company_id, phone_e164, phone_raw, lock_token, last_call_id, agent_connected, last_call_started_at
                 FROM campaignnumbers WHERE state='DISPO_PENDING' AND company_id=?`,
                [companyId]
            );

            for (const lead of leads) {
                const originalCallId = lead.last_call_id;
                const leadPhone = lead.phone_e164 || lead.phone_raw;

                let myCall = activeCalls.find(c => String(c.Id) === String(originalCallId));
                if (!myCall) {
                    const targetPhone = normalizeNum(leadPhone);
                    myCall = activeCalls.find(c => {
                        const callerNum = normalizeNum(c.Caller);
                        const calleeNum = normalizeNum(c.Callee);
                        return callerNum.includes(targetPhone) || calleeNum.includes(targetPhone);
                    });
                }

                if (!myCall) {
                    // Call is gone, mark finished
                    const durationSec = diffSecondsFromUtcDate(lead.last_call_started_at);
                    await db.execute(
                        `UPDATE campaignnumbers SET last_call_ended_at=UTC_TIMESTAMP(), last_call_duration_sec=?, state='DISPO_REQUIRED' WHERE id=? AND company_id=?`,
                        [durationSec, lead.id, companyId]
                    );
                    await db.execute(
                        `UPDATE dialer_call_log SET ended_at=UTC_TIMESTAMP(), duration_sec=? WHERE call_id=? AND company_id=?`,
                        [durationSec, originalCallId, companyId]
                    );
                    await db.execute(
                        `UPDATE scheduled_calls
                         SET status='completed', completed_at=UTC_TIMESTAMP(), updated_at=UTC_TIMESTAMP()
                         WHERE company_id=? AND campaignnumber_id=? AND route_type='Agent' AND status IN ('dialing','connected')`,
                        [companyId, lead.id]
                    ).catch(() => { });
                    log(`[GlobalMonitor] Lead ${lead.id} call ${originalCallId} ended. Lock Released.`);
                    await releasePhoneLock(lead.lock_token).catch(() => { });

                } else {
                    // Call is active. Check Callee for Agent if not already connected
                    if (!lead.agent_connected) {
                        const calleeStr = myCall.Callee || "";
                        const callerStr = myCall.Caller || "";
                        // Usually agents are the ones answering the queue transfer 
                        // It can be formatted as "112 User"
                        const match = calleeStr.match(/^(\d+)\s/) || callerStr.match(/^(\d+)\s/);
                        if (match) {
                            const ext = match[1];
                            const [agents] = await db.execute(`SELECT agent_id FROM agent WHERE agent_ext=? AND company_id=?`, [ext, companyId]);
                            if (agents.length > 0) {
                                const agentId = agents[0].agent_id;
                                log(`[GlobalMonitor] Call ${originalCallId} CONNECTED to AGENT ${agentId} (Ext: ${ext})`);
                                await db.execute(
                                    `UPDATE campaignnumbers SET agent_connected=? WHERE id=? AND company_id=?`,
                                    [agentId, lead.id, companyId]
                                );
                                await db.execute(
                                    `UPDATE dialer_call_log SET agent_id=? WHERE call_id=? AND company_id=?`,
                                    [agentId, originalCallId, companyId]
                                );
                                await db.execute(
                                    `UPDATE scheduled_calls
                                     SET status='connected', updated_at=UTC_TIMESTAMP()
                                     WHERE company_id=? AND campaignnumber_id=? AND route_type='Agent' AND status='dialing'`,
                                    [companyId, lead.id]
                                ).catch(() => { });
                            }
                        }
                    }
                }
            }
        }
    } catch (e) {
        log(`[GlobalMonitor] Error${e?.message ? `: ${e.message}` : ''}`, getAxiosErrorDetails(e));
    } finally {
        isGlobalMonitorRunning = false;
    }
}

// Start global monitor 
setInterval(globalMonitorTick, 2000);


// ------------ Spawn Call Flow (Async Background) ------------
async function spawnCallFlow(c, lead, queueDn, dialerDn) {
    let callStarted = false;
    try {
        const { pbxurl, token } = await getPbxTokenByCompany(c.company_id);
        const destination = lead.phone_e164 || lead.phone_raw;

        if (String(c.outbound_prefix || '').toLowerCase() === 'yes') {
            try {
                const rotatedDid = await rotateDidForCampaignIfConfigured({
                    companyId: c.company_id,
                    campaignId: c.id,
                    pbxurl,
                    token
                });
                if (rotatedDid) {
                    log(`[Camp ${c.id}] Rotated outbound CallerID to ${rotatedDid}`);
                }
            } catch (e) {
                log(`[Camp ${c.id}] DID rotation skipped due to error: ${e.message}`);
            }
        }

        if (!destination) {
            log(`[Camp ${c.id}] Lead ${lead.id} has no phone format. Marking invalid.`);
            await unlockLead(c.company_id, lead.id, 'INVALID');
            await incrementAgentCount(c.company_id, queueDn); // Refund agent 
            await releasePhoneLock(lead.lockToken).catch(() => { });
            return;
        }

        const phoneLock = await tryAcquirePhoneLock(c.company_id, c.id, lead.id, lead.phone_e164, lead.phone_raw, lead.lockToken);
        if (!phoneLock.ok) {
            if (phoneLock.reason === "invalid_phone") {
                log(`[Camp ${c.id}] Lead ${lead.id} has invalid phone format. Marking invalid.`);
                await unlockLead(c.company_id, lead.id, 'INVALID');
            } else {
                log(`[Camp ${c.id}] Lead ${lead.id} skipped; phone already locked (${phoneLock.phoneKey}).`);
                await unlockLead(c.company_id, lead.id, 'READY'); // CRUCIAL FIX: Free the lead so pickLead doesn't get stuck finding a DIALING row next tick
            }
            return;
        }

        callStarted = true;
        const callid = await makeCall({ pbxurl, token, destination, dialerDn });

        // Monitor Answer
        const connected = await waitThenTransfer({
            pbxurl,
            token,
            callid,
            destination,
            transferTarget: queueDn,
            transferLabel: `queue ${queueDn}`,
            dialerDn
        });

        if (connected) {
            await logCallAttemptV2(c.company_id, c.id, lead.id, callid, 'ANSWERED', null, null, lead.attempts_used + 1);

            // Do NOT increment agent count here. Agent is now talking to the contact.

            await unlockLead(c.company_id, lead.id, 'DISPO_PENDING');
            // Background globalMonitorTick will handle detecting end of call and releasing lock
        } else {
            await logCallAttemptV2(c.company_id, c.id, lead.id, callid, 'NO_ANSWER', 'SYSTEM_NO_ANSWER', null, lead.attempts_used + 1);

            const used = lead.attempts_used + 1; // It was updated in logCallAttemptV2 DB trigger

            if (used >= lead.max_attempts) {
                await unlockLead(c.company_id, lead.id, 'CLOSED');
            } else {
                // Retry delay 15 mins instead of 1 hour
                await db.execute(
                    `UPDATE campaignnumbers SET next_call_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE) WHERE id=? AND company_id=?`,
                    [lead.id, c.company_id]
                );
                await unlockLead(c.company_id, lead.id, 'READY');
            }
            await releasePhoneLock(lead.lockToken).catch(() => { });
        }
    } catch (e) {
        log(`[Camp ${c.id}] Spawn Call Error for lead ${lead.id}: ${e.message}`);
        await logCallAttemptV2(c.company_id, c.id, lead.id, 'failed', 'ERROR', null, null, lead.attempts_used + 1);

        await unlockLead(c.company_id, lead.id, 'READY', null); // Retry ?
        await releasePhoneLock(lead.lockToken).catch(() => { });
    }
}

// ------------ Main Loop ------------
let isProcessing = false;
let lastPhoneLockCleanupAt = 0;

async function tick() {
    if (isProcessing) return;
    isProcessing = true;

    try {
        if (Date.now() - lastPhoneLockCleanupAt > PHONE_LOCK_CLEANUP_MS) {
            await cleanupExpiredPhoneLocks().catch(() => { });
            lastPhoneLockCleanupAt = Date.now();
        }

        const scheduled = await pickScheduledCallback();
        if (scheduled) {
            spawnScheduledCallFlow(scheduled).catch(e => {
                log(`[Scheduled ${scheduled.id}] Unhandled scheduled callback error: ${e.message}`);
            });
        }

        const [campaigns] = await db.execute(
              `SELECT c.id, c.company_id, c.routeto, c.dn_number, c.dpd_filter_from, c.dpd_filter_to,
                    COALESCE(p.outbound_prefix, 'No') AS outbound_prefix
               FROM campaign c
               LEFT JOIN pbxdetail p ON p.company_id = c.company_id
               WHERE c.status='Running' AND c.is_deleted=0 AND c.dialer_mode='Predictive Dialer'`
        );

        for (const c of campaigns) {
            const queueDn = String(c.routeto || "").trim();
            const dialerDn = String(c.dn_number || "").trim();

            if (!queueDn) continue;
            if (!dialerDn) {
                log(`[Camp ${c.id}] Skipped - campaign dn_number is empty.`);
                continue;
            }

            // Check Queue
            const gate = await queueAllowsDialing(c.company_id, c.id, queueDn);
            if (!gate.ok) {
                await notifyIfCampaignHasNoNumbersLeft(c).catch(() => { });
                continue;
            }

            // IMPORTANT FIX: Count how many active calls we are ALREADY ringing for this QUEUE across ALL campaigns.
            const [ringing] = await db.execute(
                `SELECT COUNT(*) as count 
                 FROM campaignnumbers cn
                 INNER JOIN campaign cmp ON cn.campaignid = cmp.id
                 WHERE cn.company_id=? 
                   AND cn.state IN ('DIALING', 'DISPO_PENDING') 
                   AND cmp.routeto=? 
                   AND cmp.status='Running' AND cmp.is_deleted=0`,
                [c.company_id, queueDn]
            );

            const activeCallsAllowed = parseInt(gate.agents, 10);
            const currentlyRinging = parseInt(ringing[0].count, 10);

            if (currentlyRinging >= activeCallsAllowed) {
                // We are already dialing the max amount of numbers allowable for the queue agents.
                log(`[Camp ${c.id}] Queue ${queueDn} Skipped - Ringing/Pending: ${currentlyRinging}, Allowed: ${activeCallsAllowed}`);
                continue;
            }

            // Pick Lead
            const lead = await pickLead(c.company_id, c.id, c.dpd_filter_from, c.dpd_filter_to);
            if (!lead) {
                await notifyIfCampaignHasNoNumbersLeft(c).catch(() => { });
                continue;
            }

            // Dial as a concurrent task, don't await blocking `waitThenTransfer`
            spawnCallFlow(c, lead, queueDn, dialerDn).catch(e => {
                log(`[Camp ${c.id}] Unhandled spawnCallFlow error: ${e.message}`);
            });
        }

    } catch (e) {
        log(`Create Tick Error${e?.message ? `: ${e.message}` : ''}`, getAxiosErrorDetails(e));
    } finally {
        isProcessing = false;
    }
}

console.log("Predictive Dialer Service Started (State-Machine Edition)");
verifyCampaignLockSchema().catch(() => { });
setInterval(tick, LOOP_MS);
