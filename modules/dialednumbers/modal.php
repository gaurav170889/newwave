<?php
class Dialednumbers_modal {
    public $conn;

    public function __construct()
    {
        $this->conn = ConnectDB();
    }

    public function htmlvalidation($form_data)
    {
        $form_data = trim(stripslashes(htmlspecialchars($form_data)));
        $form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
        return $form_data;
    }

    private function getSessionRole()
    {
        return strtolower(trim((string) ($_SESSION['prole'] ?? ($_SESSION['role'] ?? ''))));
    }

    private function resolveLoggedInAgentId()
    {
        if ($this->getSessionRole() !== 'uagent') {
            return 0;
        }

        $userId = intval($_SESSION['pid'] ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $rows = $this->fetchRows("SELECT agentid FROM users WHERE id = {$userId} LIMIT 1");
        if (empty($rows)) {
            return 0;
        }

        return intval($rows[0]['agentid'] ?? 0);
    }

    private function fetchRows($query)
    {
        $rows = [];
        $result = mysqli_query($this->conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function getCompanyTimezone($companyId)
    {
        $defaultTimezone = date_default_timezone_get();
        $companyId = intval($companyId);

        if ($companyId <= 0) {
            return $defaultTimezone;
        }

        $query = mysqli_query($this->conn, "SELECT timezone FROM pbxdetail WHERE company_id = {$companyId} LIMIT 1");
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            $timezone = trim((string) ($row['timezone'] ?? ''));
            if ($timezone !== '') {
                try {
                    new DateTimeZone($timezone);
                    return $timezone;
                } catch (Exception $exception) {
                    return $defaultTimezone;
                }
            }
        }

        return $defaultTimezone;
    }

    private function getTodayWindowForCompany($companyId)
    {
        $companyTimezoneName = $this->getCompanyTimezone($companyId);
        $companyTimezone = new DateTimeZone($companyTimezoneName);
        $appTimezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $companyTimezone);
        $start = $now->setTime(0, 0, 0)->setTimezone($appTimezone);
        $end = $now->setTime(23, 59, 59)->setTimezone($appTimezone);

        return [
            'timezone' => $companyTimezoneName,
            'today_label' => $now->format('Y-m-d'),
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
    }

    public function getCompanies($companyId = 0)
    {
        $companyId = intval($companyId);
        $where = $companyId > 0 ? "WHERE id = {$companyId}" : '';
        return $this->fetchRows("SELECT id, name FROM companies {$where} ORDER BY name ASC");
    }

    public function getAgentsByCompany($companyId)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        return $this->fetchRows("SELECT agent_id, agent_ext, agent_name FROM agent WHERE company_id = {$companyId} ORDER BY agent_name ASC, agent_ext ASC");
    }

    private function getAgentById($companyId, $agentId)
    {
        $companyId = intval($companyId);
        $agentId = intval($agentId);
        if ($companyId <= 0 || $agentId <= 0) {
            return null;
        }

        $rows = $this->fetchRows("SELECT agent_id, agent_ext, agent_name FROM agent WHERE company_id = {$companyId} AND agent_id = {$agentId} LIMIT 1");
        return !empty($rows) ? $rows[0] : null;
    }

    public function getCampaignsByCompany($companyId)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        $rows = $this->fetchRows("SELECT id, name, weekdays, starttime, stoptime FROM campaign WHERE company_id = {$companyId} AND is_deleted = 0 ORDER BY name ASC");
        foreach ($rows as &$row) {
            $settings = $this->getCampaignScheduleSettings($companyId, intval($row['id'] ?? 0), $row);
            $row['weekdays'] = $settings['allowed_weekdays'];
            $row['timezone'] = $settings['timezone'];
            $row['today_label'] = $settings['today_label'];
            $row['min_time'] = $settings['min_time'];
            $row['max_time'] = $settings['max_time'];
        }
        unset($row);

        return $rows;
    }

    private function normalizeWeekdays($rawWeekdays)
    {
        $allowedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $values = [];

        if (is_array($rawWeekdays)) {
            $values = $rawWeekdays;
        } else {
            $rawWeekdays = trim((string) $rawWeekdays);
            if ($rawWeekdays !== '') {
                $decoded = json_decode($rawWeekdays, true);
                $values = is_array($decoded) ? $decoded : explode(',', $rawWeekdays);
            }
        }

        $normalized = [];
        foreach ($values as $day) {
            $candidate = ucfirst(strtolower(trim((string) $day)));
            if (in_array($candidate, $allowedDays, true)) {
                $normalized[] = $candidate;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function getCampaignScheduleSettings($companyId, $campaignId = 0, array $campaignRow = [])
    {
        $timezone = $this->getCompanyTimezone($companyId);
        $todayLabel = (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d');
        $allowedWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $minTime = '09:00';
        $maxTime = '18:00';

        if (empty($campaignRow) && $campaignId > 0) {
            $rows = $this->fetchRows("SELECT weekdays, starttime, stoptime FROM campaign WHERE company_id = {$companyId} AND id = {$campaignId} AND is_deleted = 0 LIMIT 1");
            if (!empty($rows)) {
                $campaignRow = $rows[0];
            }
        }

        if (!empty($campaignRow)) {
            $campaignWeekdays = $this->normalizeWeekdays($campaignRow['weekdays'] ?? []);
            if (!empty($campaignWeekdays)) {
                $allowedWeekdays = $campaignWeekdays;
            }

            $campaignStart = substr(trim((string) ($campaignRow['starttime'] ?? '')), 0, 5);
            $campaignStop = substr(trim((string) ($campaignRow['stoptime'] ?? '')), 0, 5);

            if (preg_match('/^\d{2}:\d{2}$/', $campaignStart)) {
                $minTime = $campaignStart;
            }
            if (preg_match('/^\d{2}:\d{2}$/', $campaignStop)) {
                $maxTime = $campaignStop;
            }
            if ($minTime > $maxTime) {
                $minTime = '09:00';
                $maxTime = '18:00';
            }
        }

        return [
            'timezone' => $timezone,
            'today_label' => $todayLabel,
            'allowed_weekdays' => $allowedWeekdays,
            'min_time' => $minTime,
            'max_time' => $maxTime,
        ];
    }

    private function getDialedBaseWhereClauses($companyId, $campaignId = 0)
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $todayWindow = $this->getTodayWindowForCompany($companyId);

        $whereClauses = [
            "c.company_id = {$companyId}",
            "COALESCE(c.is_dnc, 0) = 0",
            "COALESCE(c.state, 'READY') NOT IN ('DNC', 'CLOSED')",
            "(c.created_at < '{$todayWindow['start']}' OR c.created_at > '{$todayWindow['end']}')",
            "(
                COALESCE(c.attempts_used, 0) > 0
                OR NULLIF(TRIM(COALESCE(c.last_call_status, '')), '') IS NOT NULL
                OR EXISTS (
                    SELECT 1
                    FROM dialer_call_log dl
                    WHERE dl.company_id = c.company_id
                      AND dl.campaign_id = c.campaignid
                      AND (
                          dl.campaignnumber_id = c.id
                          OR (
                              NULLIF(TRIM(COALESCE(dl.caller_id, '')), '') IS NOT NULL
                              AND (dl.caller_id = c.phone_e164 OR dl.caller_id = c.phone_raw)
                          )
                      )
                      AND UPPER(COALESCE(dl.call_status, '')) <> 'MANUAL_DISPO'
                )
            )",
        ];

        if ($campaignId > 0) {
            $whereClauses[] = "c.campaignid = {$campaignId}";
        }

        if ($this->getSessionRole() === 'uagent') {
            $agentId = $this->resolveLoggedInAgentId();
            if ($agentId > 0) {
                $whereClauses[] = "CAST(COALESCE(c.agent_connected, '') AS CHAR) = '" . mysqli_real_escape_string($this->conn, (string) $agentId) . "'";
                $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) = 'ANSWERED'";
            } else {
                $whereClauses[] = "1=0";
            }
        }

        return $whereClauses;
    }

    private function normalizeDaysPastDue(array $daysPastDue)
    {
        $normalizedDaysPastDue = [];
        foreach ($daysPastDue as $value) {
            $value = trim((string) $value);
            if ($value === '' || !is_numeric($value)) {
                continue;
            }
            $normalizedDaysPastDue[] = intval($value);
        }

        return array_values(array_unique($normalizedDaysPastDue));
    }

    private function buildDialedWhereSql($companyId, $campaignId = 0, $filterType = '', $filterValue = '', array $daysPastDue = [])
    {
        $whereClauses = $this->getDialedBaseWhereClauses($companyId, $campaignId);
        $filterType = strtolower(trim((string) $filterType));
        $filterValue = trim((string) $filterValue);
        $normalizedDaysPastDue = $this->normalizeDaysPastDue($daysPastDue);

        if (!empty($normalizedDaysPastDue)) {
            $whereClauses[] = "c.days_past_due IN (" . implode(',', $normalizedDaysPastDue) . ")";
        }

        if ($filterType === 'agent' && $filterValue !== '' && $filterValue !== '__all__') {
            $safeFilterValue = mysqli_real_escape_string($this->conn, $filterValue);
            $whereClauses[] = "CAST(COALESCE(c.agent_connected, '') AS CHAR) = '{$safeFilterValue}'";
        } elseif ($filterType === 'answered') {
            $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) = 'ANSWERED'";
            if ($filterValue !== '' && $filterValue !== '__all__') {
                $safeFilterValue = mysqli_real_escape_string($this->conn, strtoupper($filterValue));
                $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) = '{$safeFilterValue}'";
            }
        } elseif ($filterType === 'not_answered') {
            $whereClauses[] = "(NULLIF(TRIM(COALESCE(c.last_call_status, '')), '') IS NULL OR UPPER(COALESCE(c.last_call_status, '')) <> 'ANSWERED')";
            if ($filterValue !== '' && $filterValue !== '__all__') {
                if (strtoupper($filterValue) === 'DIALED_NO_STATUS') {
                    $whereClauses[] = "NULLIF(TRIM(COALESCE(c.last_call_status, '')), '') IS NULL";
                } else {
                    $safeFilterValue = mysqli_real_escape_string($this->conn, strtoupper($filterValue));
                    $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) = '{$safeFilterValue}'";
                }
            }
        }

        return 'WHERE ' . implode(' AND ', $whereClauses);
    }

    public function getFilterValues($companyId, $campaignId = 0, $filterType = '')
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $filterType = strtolower(trim((string) $filterType));

        if ($companyId <= 0 || $campaignId <= 0 || $filterType === '') {
            return [];
        }

        $baseWhere = 'WHERE ' . implode(' AND ', $this->getDialedBaseWhereClauses($companyId, $campaignId));

        if ($filterType === 'agent') {
            $rows = $this->fetchRows("SELECT DISTINCT CAST(COALESCE(c.agent_connected, '') AS CHAR) AS agent_id,
                                             COALESCE(a.agent_name, '') AS agent_name,
                                             COALESCE(a.agent_ext, '') AS agent_ext
                                      FROM campaignnumbers c
                                      LEFT JOIN agent a ON a.company_id = c.company_id AND CAST(a.agent_id AS CHAR) = CAST(c.agent_connected AS CHAR)
                                      {$baseWhere}
                                        AND NULLIF(TRIM(COALESCE(c.agent_connected, '')), '') IS NOT NULL
                                      ORDER BY agent_name ASC, agent_ext ASC");
            $options = [['value' => '__all__', 'label' => 'All Agents']];

            foreach ($rows as $row) {
                $agentId = trim((string) ($row['agent_id'] ?? ''));
                if ($agentId === '') {
                    continue;
                }

                $agentName = trim((string) ($row['agent_name'] ?? ''));
                $agentExt = trim((string) ($row['agent_ext'] ?? ''));
                $label = $agentName !== '' ? $agentName : ('Agent ' . $agentId);
                if ($agentExt !== '') {
                    $label .= ' (' . $agentExt . ')';
                }

                $options[] = ['value' => $agentId, 'label' => $label];
            }

            return $options;
        }

        if ($filterType === 'answered') {
            return [
                ['value' => '__all__', 'label' => 'All Answered'],
                ['value' => 'ANSWERED', 'label' => 'ANSWERED'],
            ];
        }

        if ($filterType === 'not_answered') {
            $rows = $this->fetchRows("SELECT DISTINCT COALESCE(NULLIF(TRIM(c.last_call_status), ''), 'DIALED_NO_STATUS') AS status_value
                                      FROM campaignnumbers c
                                      {$baseWhere}
                                        AND (NULLIF(TRIM(COALESCE(c.last_call_status, '')), '') IS NULL OR UPPER(COALESCE(c.last_call_status, '')) <> 'ANSWERED')
                                      ORDER BY status_value ASC");
            $options = [['value' => '__all__', 'label' => 'All Not Answered']];

            foreach ($rows as $row) {
                $statusValue = trim((string) ($row['status_value'] ?? ''));
                if ($statusValue === '') {
                    continue;
                }

                $label = $statusValue === 'DIALED_NO_STATUS' ? 'Dialed (No Status)' : $statusValue;
                $options[] = ['value' => $statusValue, 'label' => $label];
            }

            return $options;
        }

        return [];
    }

    public function getDaysPastDueOptions($companyId, $campaignId = 0, $filterType = '', $filterValue = '')
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        $where = $this->buildDialedWhereSql($companyId, $campaignId, $filterType, $filterValue);
        $rows = $this->fetchRows("SELECT DISTINCT c.days_past_due FROM campaignnumbers c {$where} AND c.days_past_due IS NOT NULL ORDER BY c.days_past_due DESC");
        $options = [];

        foreach ($rows as $row) {
            $value = $row['days_past_due'];
            if ($value === null || $value === '') {
                continue;
            }

            $options[] = [
                'value' => (string) $value,
                'label' => (string) $value,
            ];
        }

        return $options;
    }

    public function getDialedRows($companyId, $campaignId = 0, $filterType = '', $filterValue = '', array $daysPastDue = [])
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        $where = $this->buildDialedWhereSql($companyId, $campaignId, $filterType, $filterValue, $daysPastDue);
        $query = "SELECT c.id,
                         c.campaignid,
                         COALESCE(cam.name, CONCAT('Campaign #', c.campaignid)) AS campaign_name,
                         c.phone_e164,
                         c.first_name,
                         c.last_name,
                         c.days_past_due,
                         c.state,
                         c.attempts_used,
                         c.max_attempts,
                         c.last_call_status,
                         c.last_call_started_at,
                         c.next_call_at,
                         c.created_at,
                         c.notes,
                         c.last_disposition,
                         COALESCE(a.agent_name, '') AS agent_name,
                         COALESCE(d.color_code, '#808080') AS color_code
                  FROM campaignnumbers c
                  LEFT JOIN campaign cam ON cam.id = c.campaignid
                  LEFT JOIN agent a ON a.company_id = c.company_id AND CAST(a.agent_id AS CHAR) = CAST(c.agent_connected AS CHAR)
                  LEFT JOIN dialer_disposition_master d ON d.company_id = c.company_id AND d.label = c.last_disposition
                  {$where}
                  ORDER BY c.last_call_started_at DESC, c.created_at DESC, c.id DESC
                  LIMIT 5000";

        $rows = $this->fetchRows($query);
        $response = [];

        foreach ($rows as $row) {
            $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $lastStatus = trim((string) ($row['last_call_status'] ?? ''));
            $isAnswered = strtoupper($lastStatus) === 'ANSWERED';

            $response[] = [
                'id' => $row['id'],
                'campaign_name' => $row['campaign_name'],
                'number' => $row['phone_e164'],
                'name' => $fullName,
                'days_past_due' => $row['days_past_due'],
                'type' => $isAnswered ? 'Answered' : 'Not Answered',
                'feedback' => $lastStatus !== '' ? $lastStatus : 'DIALED_NO_STATUS',
                'state' => $row['state'],
                'attempts' => intval($row['attempts_used']) . '/' . intval($row['max_attempts']),
                'attempts_used' => intval($row['attempts_used']),
                'last_try_dt' => $row['last_call_started_at'],
                'agent_name' => $row['agent_name'],
                'next_call_at' => $row['next_call_at'],
                'created_at' => $row['created_at'],
                'disposition' => $row['last_disposition'],
                'color_code' => $row['color_code'] ?? '#808080',
                'notes' => $row['notes'] ?? '',
            ];
        }

        return $response;
    }

    private function ensureScheduledCallsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS scheduled_calls (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            campaignnumber_id INT NOT NULL,
            route_type VARCHAR(20) NOT NULL DEFAULT 'Agent',
            queue_dn VARCHAR(50) DEFAULT NULL,
            agent_id INT DEFAULT NULL,
            agent_ext VARCHAR(50) DEFAULT NULL,
            scheduled_for DATETIME NOT NULL,
            timezone VARCHAR(100) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending_agent',
            source_module VARCHAR(50) NOT NULL DEFAULT 'dialednumbers',
            disposition_label VARCHAR(100) DEFAULT NULL,
            note_text TEXT DEFAULT NULL,
            zoho_schedule_id VARCHAR(100) DEFAULT NULL,
            zoho_activity_id VARCHAR(100) DEFAULT NULL,
            zoho_payload LONGTEXT DEFAULT NULL,
            meta_json LONGTEXT DEFAULT NULL,
            attempt_count INT NOT NULL DEFAULT 0,
            last_attempt_at DATETIME DEFAULT NULL,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            created_by INT DEFAULT NULL,
            updated_by INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_company_status_time (company_id, status, scheduled_for),
            KEY idx_campaignnumber (campaignnumber_id),
            KEY idx_agent_schedule (agent_id, scheduled_for)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return mysqli_query($this->conn, $sql);
    }

    private function createScheduledCallRecord(array $leadRow, $routeType, $routeAgentId, $scheduledFor, $disposition, $notes)
    {
        if (!$this->ensureScheduledCallsTable()) {
            return ['success' => false, 'message' => 'Could not prepare the scheduled call table.'];
        }

        $companyId = intval($leadRow['company_id'] ?? 0);
        $campaignId = intval($leadRow['campaignid'] ?? 0);
        $leadId = intval($leadRow['id'] ?? 0);
        $createdBy = intval($_SESSION['pid'] ?? 0);
        $timezone = $this->getCompanyTimezone($companyId);
        $routeType = 'Agent';

        $queueDn = null;
        $campaignRows = $this->fetchRows("SELECT routeto FROM campaign WHERE company_id = {$companyId} AND id = {$campaignId} LIMIT 1");
        if (!empty($campaignRows)) {
            $queueDn = trim((string) ($campaignRows[0]['routeto'] ?? ''));
        }

        $agentId = null;
        $agentExt = null;
        $agentLabel = 'Assigned agent';
        $agentRow = $this->getAgentById($companyId, $routeAgentId);
        if (!$agentRow) {
            return ['success' => false, 'message' => 'Please select the agent who should receive this scheduled call.'];
        }

        $agentId = intval($agentRow['agent_id'] ?? 0);
        $agentExt = trim((string) ($agentRow['agent_ext'] ?? ''));
        $agentName = trim((string) ($agentRow['agent_name'] ?? ''));
        $agentLabel = $agentName !== '' ? $agentName : ('Agent ' . $agentId);
        if ($agentExt !== '') {
            $agentLabel .= ' (' . $agentExt . ')';
        }

        $scheduledForEsc = mysqli_real_escape_string($this->conn, $scheduledFor);
        $timezoneEsc = mysqli_real_escape_string($this->conn, $timezone);
        $routeTypeEsc = mysqli_real_escape_string($this->conn, $routeType);
        $queueDnSql = $queueDn !== null && $queueDn !== '' ? "'" . mysqli_real_escape_string($this->conn, $queueDn) . "'" : 'NULL';
        $agentIdSql = $agentId !== null ? intval($agentId) : 'NULL';
        $agentExtSql = $agentExt !== null && $agentExt !== '' ? "'" . mysqli_real_escape_string($this->conn, $agentExt) . "'" : 'NULL';
        $dispositionEsc = mysqli_real_escape_string($this->conn, $disposition);
        $notesSql = $notes !== '' ? "'" . mysqli_real_escape_string($this->conn, $notes) . "'" : 'NULL';
        $statusEsc = 'pending_agent';
        $meta = [
            'lead_number' => $leadRow['phone_e164'] ?? null,
            'lead_name' => trim((string) ($leadRow['first_name'] ?? '') . ' ' . (string) ($leadRow['last_name'] ?? '')),
        ];
        $metaSql = "'" . mysqli_real_escape_string($this->conn, json_encode($meta)) . "'";

        $insertSql = "INSERT INTO scheduled_calls
            (company_id, campaign_id, campaignnumber_id, route_type, queue_dn, agent_id, agent_ext,
             scheduled_for, timezone, status, source_module, disposition_label, note_text, meta_json,
             created_by, updated_by)
            VALUES
            ({$companyId}, {$campaignId}, {$leadId}, '{$routeTypeEsc}', {$queueDnSql}, {$agentIdSql}, {$agentExtSql},
             '{$scheduledForEsc}', '{$timezoneEsc}', '{$statusEsc}', 'dialednumbers', '{$dispositionEsc}', {$notesSql}, {$metaSql},
             {$createdBy}, {$createdBy})";

        if (!mysqli_query($this->conn, $insertSql)) {
            return ['success' => false, 'message' => 'Failed to save scheduled call: ' . mysqli_error($this->conn)];
        }

        return [
            'success' => true,
            'scheduled_call_id' => mysqli_insert_id($this->conn),
            'route_type' => $routeType,
            'route_label' => $routeType === 'Agent' ? $agentLabel : ('Queue ' . ($queueDn ?: 'default')),
        ];
    }

    public function updateDispositionSql($id, $disposition, $notes, $callbackDate, $callbackTime, $routeType = 'Queue', $routeAgentId = 0)
    {
        $id = intval($id);
        $disposition = mysqli_real_escape_string($this->conn, $disposition);
        $notes = mysqli_real_escape_string($this->conn, $notes);
        $callbackDate = trim((string) $callbackDate);
        $callbackTime = trim((string) $callbackTime);
        $routeType = 'Agent';
        $routeAgentId = intval($routeAgentId);

        $checkRows = $this->fetchRows("SELECT id, company_id, campaignid, phone_e164, first_name, last_name, agent_connected, last_call_status, notes FROM campaignnumbers WHERE id = {$id} LIMIT 1");
        if (empty($checkRows)) {
            return ['success' => false, 'message' => 'Number not found.'];
        }

        $leadRow = $checkRows[0];
        if ($this->getSessionRole() === 'uagent') {
            $agentId = $this->resolveLoggedInAgentId();
            $leadAgentId = intval($leadRow['agent_connected'] ?? 0);
            $lastStatus = strtoupper(trim((string) ($leadRow['last_call_status'] ?? '')));

            if ($agentId <= 0 || $leadAgentId !== $agentId || $lastStatus !== 'ANSWERED') {
                return ['success' => false, 'message' => 'Agents can only update disposition for their own answered calls.'];
            }
        }

        $state = 'DISPO_SUBMITTED';
        $nextCallAt = 'NULL';
        $validatedScheduleAt = '';
        $actionType = '';

        $dispQuery = mysqli_query($this->conn, "SELECT code, action_type FROM dialer_disposition_master WHERE label='$disposition' LIMIT 1");
        if ($dispQuery && mysqli_num_rows($dispQuery) > 0) {
            $dRow = mysqli_fetch_assoc($dispQuery);
            $actionType = strtolower(trim((string) ($dRow['action_type'] ?? '')));
            if ($actionType == 'callback' || $actionType == 'retry') {
                if ($callbackDate === '' || $callbackTime === '') {
                    return ['success' => false, 'message' => 'Please select both callback date and callback time.'];
                }

                $scheduleValidation = $this->buildScheduledDateTime(
                    intval($leadRow['company_id'] ?? 0),
                    intval($leadRow['campaignid'] ?? 0),
                    $callbackDate,
                    $callbackTime
                );
                if (empty($scheduleValidation['success'])) {
                    return ['success' => false, 'message' => $scheduleValidation['message'] ?? 'Invalid callback schedule.'];
                }

                if ($routeAgentId <= 0) {
                    return ['success' => false, 'message' => 'Scheduled calls from Dialed Numbers must be assigned to a specific agent.'];
                }

                $validatedScheduleAt = (string) ($scheduleValidation['next_call_at'] ?? '');
                $state = 'AGENT_SCHEDULED';
                $nextCallAt = "'" . mysqli_real_escape_string($this->conn, $validatedScheduleAt) . "'";
            } elseif ($actionType == 'dnc') {
                $state = 'DNC';
            } elseif ($actionType == 'closed') {
                $state = 'CLOSED';
            }
        } else {
            $state = 'CLOSED';
        }

        $notesUpdate = '';
        if (!empty($notes)) {
            $userId = intval($_SESSION['pid'] ?? 0);
            $userName = 'Unknown';

            $uQ = mysqli_query($this->conn, "SELECT user_email FROM users WHERE id='$userId'");
            if ($uQ && mysqli_num_rows($uQ) > 0) {
                $uRow = mysqli_fetch_assoc($uQ);
                $userName = $uRow['user_email'];
            }

            $timestamp = date('Y-m-d H:i');
            $rawNotes = $leadRow['notes'] ?? '';
            $decoded = json_decode((string) $rawNotes, true);
            $cNotes = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];

            if (empty($cNotes) && !empty($rawNotes) && !is_array($decoded)) {
                $cNotes[] = [
                    'date' => '',
                    'user' => 'Legacy',
                    'note' => $rawNotes,
                ];
            }

            $cNotes[] = [
                'date' => $timestamp,
                'user' => $userName,
                'note' => $notes,
            ];

            $jsonString = json_encode($cNotes);
            if ($jsonString === false) {
                $jsonString = '[]';
            }

            $jsonNotes = mysqli_real_escape_string($this->conn, $jsonString);
            $notesUpdate = ", notes = '$jsonNotes'";
        }

        mysqli_begin_transaction($this->conn);
        try {
            $scheduledCall = null;
            if ($actionType === 'callback' || $actionType === 'retry') {
                $scheduledCall = $this->createScheduledCallRecord($leadRow, $routeType, $routeAgentId, $validatedScheduleAt, $disposition, $notes);
                if (empty($scheduledCall['success'])) {
                    throw new Exception($scheduledCall['message'] ?? 'Failed to create the scheduled call record.');
                }
            }

            $query = "UPDATE campaignnumbers
                      SET last_disposition='$disposition',
                          state='$state',
                          next_call_at=$nextCallAt$notesUpdate,
                          last_call_ended_at=NOW()
                      WHERE id='$id'";

            if (!mysqli_query($this->conn, $query)) {
                throw new Exception(mysqli_error($this->conn));
            }

            $compId = intval($leadRow['company_id'] ?? 0);
            $campId = intval($leadRow['campaignid'] ?? 0);
            $logDisposition = mysqli_real_escape_string($this->conn, $disposition);
            $logNotes = mysqli_real_escape_string($this->conn, $notes);

            $logQ = "INSERT INTO dialer_call_log SET
                     company_id = '$compId',
                     campaign_id = '$campId',
                     campaignnumber_id = '$id',
                     call_status = 'MANUAL_DISPO',
                     disposition = '$logDisposition',
                     notes = '$logNotes',
                     started_at = NOW()";
            mysqli_query($this->conn, $logQ);

            mysqli_commit($this->conn);

            $message = 'Disposition updated successfully.';
            if ($actionType === 'callback' || $actionType === 'retry') {
                if (!empty($scheduledCall['success'])) {
                    $message .= ' Scheduled for agent callback via ' . ($scheduledCall['route_label'] ?? 'the selected agent') . '.';
                }
            }

            return ['success' => true, 'message' => $message];
        } catch (Exception $exception) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    private function normalizeScheduleTime($scheduleTime, $fallback = '09:00')
    {
        $scheduleTime = trim((string) $scheduleTime);
        if (!preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
            return $fallback;
        }

        return $scheduleTime;
    }

    private function buildScheduledDateTime($companyId, $campaignId, $scheduleDate = '', $scheduleTime = '')
    {
        $scheduleDate = trim((string) $scheduleDate);
        $settings = $this->getCampaignScheduleSettings($companyId, $campaignId);

        if ($scheduleDate === '') {
            return [
                'success' => true,
                'state' => 'READY',
                'next_call_at' => (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s'),
                'display_time' => 'immediate',
                'settings' => $settings,
                'scheduled_date' => '',
                'scheduled_time' => '',
            ];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduleDate)) {
            return ['success' => false, 'message' => 'Please select a valid schedule date.'];
        }

        $scheduleTime = $this->normalizeScheduleTime($scheduleTime, $settings['min_time']);
        if ($scheduleTime < $settings['min_time'] || $scheduleTime > $settings['max_time']) {
            return ['success' => false, 'message' => 'Schedule time must be between ' . $settings['min_time'] . ' and ' . $settings['max_time'] . ' in the PBX timezone.'];
        }

        $companyTimezone = new DateTimeZone($settings['timezone']);
        $appTimezone = new DateTimeZone(date_default_timezone_get());
        $scheduledAt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduleDate . ' ' . $scheduleTime, $companyTimezone);

        if (!$scheduledAt instanceof DateTimeImmutable) {
            return ['success' => false, 'message' => 'Please select a valid schedule date and time.'];
        }

        $dayName = $scheduledAt->format('l');
        if (!empty($settings['allowed_weekdays']) && !in_array($dayName, $settings['allowed_weekdays'], true)) {
            return ['success' => false, 'message' => 'The selected date falls on ' . $dayName . ', but this campaign allows only: ' . implode(', ', $settings['allowed_weekdays']) . '.'];
        }

        $nowCompany = new DateTimeImmutable('now', $companyTimezone);
        if ($scheduledAt <= $nowCompany) {
            return ['success' => false, 'message' => 'Please select a future schedule date/time in the company PBX timezone.'];
        }

        return [
            'success' => true,
            'state' => 'SCHEDULED',
            'next_call_at' => $scheduledAt->setTimezone($appTimezone)->format('Y-m-d H:i:s'),
            'display_time' => $scheduledAt->format('Y-m-d H:i:s'),
            'settings' => $settings,
            'scheduled_date' => $scheduleDate,
            'scheduled_time' => $scheduleTime,
        ];
    }

    private function buildScheduledExdata($rawExdata, $scheduleDate, $scheduleTime, $nextCallAt)
    {
        $decoded = json_decode((string) $rawExdata, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($scheduleDate !== '') {
            $decoded['redial_schedule_date'] = $scheduleDate;
            $decoded['redial_schedule_time'] = $scheduleTime !== '' ? $scheduleTime : '09:00';
            $decoded['redial_next_call_at'] = $nextCallAt;
        } else {
            unset(
                $decoded['redial_schedule_days'],
                $decoded['redial_schedule_date'],
                $decoded['redial_schedule_time'],
                $decoded['redial_next_call_at']
            );
        }

        return mysqli_real_escape_string($this->conn, json_encode($decoded));
    }

    public function moveToContacts($companyId, $campaignId, array $contactIds, $scheduleDate = '', $scheduleTime = '')
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds), function ($value) {
            return $value > 0;
        })));
        $scheduleDate = trim((string) $scheduleDate);
        $scheduleTime = trim((string) $scheduleTime);

        if ($companyId <= 0) {
            return ['success' => false, 'message' => 'Please select a company first.'];
        }

        if ($campaignId <= 0) {
            return ['success' => false, 'message' => 'Please select a campaign first.'];
        }

        if (empty($contactIds)) {
            return ['success' => false, 'message' => 'Please select at least one number.'];
        }

        if (($scheduleDate !== '' && $scheduleTime === '') || ($scheduleDate === '' && $scheduleTime !== '')) {
            return ['success' => false, 'message' => 'Please select both schedule date and time, or leave both empty.'];
        }

        $scheduleBuild = $this->buildScheduledDateTime($companyId, $campaignId, $scheduleDate, $scheduleTime);
        if (empty($scheduleBuild['success'])) {
            return ['success' => false, 'message' => $scheduleBuild['message'] ?? 'Could not validate the selected schedule.'];
        }

        $nextCallAt = $scheduleBuild['next_call_at'] ?? null;
        $state = $scheduleBuild['state'] ?? 'READY';
        $scheduleDate = $scheduleBuild['scheduled_date'] ?? '';
        $scheduleTime = $scheduleBuild['scheduled_time'] ?? '';

        $idsSql = implode(',', $contactIds);
        $campaignClause = $campaignId > 0 ? " AND campaignid = {$campaignId}" : '';
        $query = "SELECT id, exdata FROM campaignnumbers WHERE company_id = {$companyId}{$campaignClause} AND id IN ({$idsSql})";
        $rows = $this->fetchRows($query);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'No matching contacts were found for the selected company/campaign.'];
        }

        $updated = 0;
        foreach ($rows as $row) {
            $contactId = intval($row['id']);
            $exdataJson = $this->buildScheduledExdata($row['exdata'] ?? '', $scheduleDate, $scheduleTime, $nextCallAt);
            $nextCallAtSql = $nextCallAt ? "'" . mysqli_real_escape_string($this->conn, $nextCallAt) . "'" : 'NOW()';
            $sql = "UPDATE campaignnumbers
                    SET state = '" . mysqli_real_escape_string($this->conn, $state) . "',
                        next_call_at = {$nextCallAtSql},
                        created_at = NOW(),
                        updated_at = NOW(),
                        locked_at = NULL,
                        locked_by = NULL,
                        lock_token = NULL,
                        exdata = '{$exdataJson}'
                    WHERE id = {$contactId} AND company_id = {$companyId}";

            if (mysqli_query($this->conn, $sql)) {
                $updated++;
            }
        }

        if ($updated === 0) {
            return ['success' => false, 'message' => 'No numbers were moved to Contacts.'];
        }

        $message = $updated . ' number(s) moved to Contacts.';
        if ($state === 'SCHEDULED') {
            $message .= ' Scheduled for ' . ($scheduleBuild['display_time'] ?? $nextCallAt) . ' (' . ($scheduleBuild['settings']['timezone'] ?? 'PBX timezone') . ').';
        } else {
            $message .= ' They are ready for dialing now.';
        }

        return [
            'success' => true,
            'message' => $message,
            'state' => $state,
            'next_call_at' => $nextCallAt,
            'timezone' => $scheduleBuild['settings']['timezone'] ?? '',
        ];
    }
}
?>