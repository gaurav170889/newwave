<?php
class Notdialed_modal {
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

            if (preg_match('/^\d{2}:\d{2}$/', $campaignStart) && $campaignStart > $minTime) {
                $minTime = $campaignStart;
            }
            if (preg_match('/^\d{2}:\d{2}$/', $campaignStop) && $campaignStop < $maxTime) {
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

    private function buildNotDialedWhereSql($companyId, $campaignId = 0, array $daysPastDue = [])
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $todayWindow = $this->getTodayWindowForCompany($companyId);

        $whereClauses = [
            "c.company_id = {$companyId}",
            "COALESCE(c.attempts_used, 0) = 0",
            "COALESCE(c.is_dnc, 0) = 0",
            "COALESCE(c.state, 'READY') NOT IN ('DNC', 'CLOSED')",
            "(c.created_at < '{$todayWindow['start']}' OR c.created_at > '{$todayWindow['end']}')",
            "NOT EXISTS (
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
            )",
        ];

        if ($campaignId > 0) {
            $whereClauses[] = "c.campaignid = {$campaignId}";
        }

        $normalizedDaysPastDue = [];
        foreach ($daysPastDue as $value) {
            $value = trim((string) $value);
            if ($value === '' || !is_numeric($value)) {
                continue;
            }
            $normalizedDaysPastDue[] = intval($value);
        }
        $normalizedDaysPastDue = array_values(array_unique($normalizedDaysPastDue));

        if (!empty($normalizedDaysPastDue)) {
            $whereClauses[] = "c.days_past_due IN (" . implode(',', $normalizedDaysPastDue) . ")";
        }

        return 'WHERE ' . implode(' AND ', $whereClauses);
    }

    public function getDaysPastDueOptions($companyId, $campaignId = 0)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        $where = $this->buildNotDialedWhereSql($companyId, $campaignId);
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

    public function getNotDialedRows($companyId, $campaignId = 0, array $daysPastDue = [])
    {
        $companyId = intval($companyId);
        if ($companyId <= 0) {
            return [];
        }

        $where = $this->buildNotDialedWhereSql($companyId, $campaignId, $daysPastDue);
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
                  ORDER BY c.days_past_due DESC, c.created_at ASC, c.id DESC
                  LIMIT 5000";

        $rows = $this->fetchRows($query);
        $response = [];

        foreach ($rows as $row) {
            $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $lastStatus = trim((string) ($row['last_call_status'] ?? ''));
            $response[] = [
                'id' => $row['id'],
                'campaign_name' => $row['campaign_name'],
                'number' => $row['phone_e164'],
                'name' => $fullName,
                'days_past_due' => $row['days_past_due'],
                'state' => $row['state'],
                'attempts' => intval($row['attempts_used']) . '/' . intval($row['max_attempts']),
                'last_call_status' => $lastStatus !== '' ? $lastStatus : 'NOT_DIALED',
                'last_try_dt' => $row['last_call_started_at'],
                'next_call_at' => $row['next_call_at'],
                'created_at' => $row['created_at'],
                'agent_name' => $row['agent_name'],
            ];
        }

        return $response;
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

        if ($companyId <= 0) {
            return ['success' => false, 'message' => 'Please select a company first.'];
        }

        if (empty($contactIds)) {
            return ['success' => false, 'message' => 'Please select at least one number.'];
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