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

    public function getCampaignsByCompany($companyId)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        return $this->fetchRows("SELECT id, name FROM campaign WHERE company_id = {$companyId} AND is_deleted = 0 ORDER BY name ASC");
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
                         COALESCE(a.agent_name, '') AS agent_name
                  FROM campaignnumbers c
                  LEFT JOIN campaign cam ON cam.id = c.campaignid
                  LEFT JOIN agent a ON a.company_id = c.company_id AND CAST(a.agent_id AS CHAR) = CAST(c.agent_connected AS CHAR)
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
                'last_try_dt' => $row['last_call_started_at'],
                'agent_name' => $row['agent_name'],
                'next_call_at' => $row['next_call_at'],
                'created_at' => $row['created_at'],
            ];
        }

        return $response;
    }

    private function normalizeScheduleDays(array $scheduleDays)
    {
        $allowedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $normalized = [];

        foreach ($scheduleDays as $day) {
            $candidate = ucfirst(strtolower(trim((string) $day)));
            if (in_array($candidate, $allowedDays, true)) {
                $normalized[] = $candidate;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function buildNextScheduleDateTime($companyId, array $scheduleDays, $scheduleTime = '')
    {
        $scheduleDays = $this->normalizeScheduleDays($scheduleDays);
        if (empty($scheduleDays)) {
            return null;
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
            $scheduleTime = '09:00';
        }

        list($hours, $minutes) = array_map('intval', explode(':', $scheduleTime));
        $companyTimezone = new DateTimeZone($this->getCompanyTimezone($companyId));
        $appTimezone = new DateTimeZone(date_default_timezone_get());
        $now = new DateTimeImmutable('now', $companyTimezone);
        $candidates = [];

        foreach ($scheduleDays as $dayName) {
            $candidate = new DateTimeImmutable('today', $companyTimezone);
            $candidate = $candidate->setTime($hours, $minutes, 0);

            if ($candidate->format('l') !== $dayName) {
                $candidate = $candidate->modify('next ' . $dayName);
            } elseif ($candidate <= $now) {
                $candidate = $candidate->modify('next ' . $dayName);
            }

            $candidates[] = $candidate;
        }

        usort($candidates, function ($left, $right) {
            return $left <=> $right;
        });

        $nextSlot = reset($candidates);
        if (!$nextSlot instanceof DateTimeImmutable) {
            return null;
        }

        return $nextSlot->setTimezone($appTimezone)->format('Y-m-d H:i:s');
    }

    private function buildScheduledExdata($rawExdata, array $scheduleDays, $scheduleTime, $nextCallAt)
    {
        $decoded = json_decode((string) $rawExdata, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if (!empty($scheduleDays)) {
            $decoded['redial_schedule_days'] = array_values($scheduleDays);
            $decoded['redial_schedule_time'] = $scheduleTime !== '' ? $scheduleTime : '09:00';
            $decoded['redial_next_call_at'] = $nextCallAt;
        } else {
            unset($decoded['redial_schedule_days'], $decoded['redial_schedule_time'], $decoded['redial_next_call_at']);
        }

        return mysqli_real_escape_string($this->conn, json_encode($decoded));
    }

    public function moveToContacts($companyId, $campaignId, array $contactIds, array $scheduleDays = [], $scheduleTime = '')
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $contactIds = array_values(array_unique(array_filter(array_map('intval', $contactIds), function ($value) {
            return $value > 0;
        })));

        if ($companyId <= 0) {
            return ['success' => false, 'message' => 'Please select a company first.'];
        }

        if (empty($contactIds)) {
            return ['success' => false, 'message' => 'Please select at least one number.'];
        }

        $scheduleDays = $this->normalizeScheduleDays($scheduleDays);
        if ($scheduleTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
            $scheduleTime = '09:00';
        }

        $nextCallAt = !empty($scheduleDays)
            ? $this->buildNextScheduleDateTime($companyId, $scheduleDays, $scheduleTime)
            : (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s');
        $state = !empty($scheduleDays) ? 'SCHEDULED' : 'READY';
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
            $exdataJson = $this->buildScheduledExdata($row['exdata'] ?? '', $scheduleDays, $scheduleTime, $nextCallAt);
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
        if (!empty($scheduleDays)) {
            $message .= ' Next dial slot: ' . $nextCallAt . ' (' . implode(', ', $scheduleDays) . ').';
        } else {
            $message .= ' They are ready for dialing now.';
        }

        return [
            'success' => true,
            'message' => $message,
            'state' => $state,
            'next_call_at' => $nextCallAt,
        ];
    }
}
?>