import 'dotenv/config';
import mysql from "mysql2/promise";
import axios from "axios";
import qs from "qs";
import fs from "fs";

const OUT_FILE = "test_output.txt";
const TOKEN_REUSE_SEC = 45;

const db = mysql.createPool({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    connectionLimit: 10
});

function log(msg) {
    console.log(msg);
    fs.appendFileSync(OUT_FILE, msg + "\n");
}

async function getPbxTokenByCompany(companyId) {
    const [rows] = await db.execute(
        `SELECT id AS pbx_id, pbxurl, pbxclientid, pbxsecret, auth_token, auth_updated_at
     FROM pbxdetail
     WHERE company_id=? LIMIT 1`,
        [companyId]
    );
    if (!rows.length) throw new Error(`pbxdetail not found for company_id=${companyId}`);

    const pbx = rows[0];
    let safeUrl = String(pbx.pbxurl).trim();
    if (!safeUrl.match(/^https?:\/\//i)) {
        safeUrl = `https://${safeUrl}`;
    }
    safeUrl = safeUrl.replace(/\/$/, "");

    if (pbx.auth_token && pbx.auth_updated_at) {
        const ageSec = Math.floor((new Date() - new Date(pbx.auth_updated_at)) / 1000);
        if (ageSec < TOKEN_REUSE_SEC) {
            return { pbxurl: safeUrl, token: pbx.auth_token };
        }
    }

    const tokenUrl = `${safeUrl}/connect/token`;
    const body = qs.stringify({
        client_id: pbx.pbxclientid,
        client_secret: pbx.pbxsecret,
        grant_type: "client_credentials"
    });

    try {
        const resp = await axios.post(tokenUrl, body, {
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            timeout: 15000
        });
        const token = resp.data?.access_token;
        if (!token) throw new Error("3CX token response missing access_token");

        await db.execute(
            `UPDATE pbxdetail SET auth_token=?, auth_updated_at=NOW() WHERE id=?`,
            [token, pbx.pbx_id]
        );
        return { pbxurl: safeUrl, token };
    } catch (e) {
        log("Token fetch failed: " + e.message);
        throw e;
    }
}

async function testApi() {
    fs.writeFileSync(OUT_FILE, "Starting Users Test...\n");
    try {
        const [rows] = await db.execute("SELECT company_id, routeto FROM campaign WHERE dialer_mode='Predictive Dialer' LIMIT 1");
        if (!rows.length) {
            log("No predictive dialer campaigns found.");
            return;
        }
        const companyId = rows[0].company_id;
        const queueDn = rows[0].routeto;
        log(`Target Queue DN: ${queueDn}`);

        const { pbxurl, token } = await getPbxTokenByCompany(companyId);

        // Test /xapi/v1/Users
        log("--- /xapi/v1/Users ---");
        try {
            const resp = await axios.get(`${pbxurl}/xapi/v1/Users`, { headers: { Authorization: `Bearer ${token}` } });
            const list = Array.isArray(resp.data) ? resp.data : (resp.data.value || []);

            log(`Total Users: ${list.length}`);
            if (list.length > 0) {
                // Inspect first user
                log(`Sample User: ${JSON.stringify(list[0], null, 2)}`);
            }

            // Check if any user status fields exist
            // Try to find status for a user
            const userId = list[0]?.Id;
            if (userId) {
                const statusUrl = `${pbxurl}/xapi/v1/Users/${userId}/Status`;
                log(`Trying ${statusUrl}`);
                try {
                    const sResp = await axios.get(statusUrl, { headers: { Authorization: `Bearer ${token}` } });
                    log(`User Status: ${JSON.stringify(sResp.data, null, 2)}`);
                } catch (e) {
                    log(`User Status Failed: ${e.response?.status}`);
                }
            }

        } catch (e) {
            log(`Users Error: ${e.message}`);
        }

    } catch (e) {
        log("Test failed: " + e);
    } finally {
        await db.end();
    }
}

testApi();
