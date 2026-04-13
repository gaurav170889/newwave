<?php
/* Modulename_modal */
Class Campcontact_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	public function htmlvalidation($form_data){
		$form_data = trim( stripslashes( htmlspecialchars( $form_data ) ) );
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}

    private function getSessionRole()
    {
        return strtolower(trim((string) ($_SESSION['prole'] ?? ($_SESSION['role'] ?? ''))));
    }

    private function isSuperAdmin()
    {
        return $this->getSessionRole() === 'super_admin';
    }

    private function resolveCompanyIdFromRequest()
    {
        if ($this->isSuperAdmin()) {
            $requestCompanyId = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
            if ($requestCompanyId > 0) {
                return $requestCompanyId;
            }
        }

        return isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;
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
                    // Fallback to application timezone below.
                }
            }
        }

        return $defaultTimezone;
    }

    private function getTodayWindowForCompany($companyId)
    {
        $companyTimezoneName = $this->getCompanyTimezone($companyId);
        $companyTimezone = new DateTimeZone($companyTimezoneName);
        $appTimezone = new DateTimeZone('UTC');
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

    private function convertCompanyLocalToUtc($companyId, $datePart = '', $timePart = '')
    {
        $datePart = trim((string) $datePart);
        $timePart = trim((string) $timePart);
        if ($datePart === '') {
            return null;
        }

        if ($timePart === '') {
            $timePart = '09:00:00';
        } elseif (preg_match('/^\d{2}:\d{2}$/', $timePart)) {
            $timePart .= ':00';
        }

        try {
            $companyTimezone = new DateTimeZone($this->getCompanyTimezone($companyId));
            $utcTimezone = new DateTimeZone('UTC');
            $scheduledAt = new DateTimeImmutable($datePart . ' ' . $timePart, $companyTimezone);
            return $scheduledAt->setTimezone($utcTimezone)->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            return null;
        }
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

    public function getFilterValues($companyId, $campaignId = 0, $filterType = '')
    {
        $companyId = intval($companyId);
        $campaignId = intval($campaignId);
        $filterType = strtolower(trim((string) $filterType));

        if ($companyId <= 0 || $campaignId <= 0 || $filterType === '') {
            return [];
        }

        if ($filterType === 'agent') {
            $rows = $this->fetchRows("SELECT agent_id, agent_name, agent_ext FROM agent WHERE company_id = {$companyId} ORDER BY agent_name ASC, agent_ext ASC");
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

        $todayWindow = $this->getTodayWindowForCompany($companyId);
        $baseWhere = "company_id = {$companyId} AND campaignid = {$campaignId} AND created_at >= '{$todayWindow['start']}' AND created_at <= '{$todayWindow['end']}'";
        $options = [];

        if ($filterType === 'answered') {
            $options[] = ['value' => '__all__', 'label' => 'All Answered'];
            $rows = $this->fetchRows("SELECT DISTINCT UPPER(COALESCE(last_call_status, '')) AS status_value FROM campaignnumbers WHERE {$baseWhere} AND UPPER(COALESCE(last_call_status, '')) = 'ANSWERED'");
            foreach ($rows as $row) {
                $statusValue = trim((string) ($row['status_value'] ?? ''));
                if ($statusValue !== '') {
                    $options[] = ['value' => $statusValue, 'label' => $statusValue];
                }
            }
            return $options;
        }

        if ($filterType === 'not_answered') {
            $options[] = ['value' => '__all__', 'label' => 'All Not Answered'];
            $rows = $this->fetchRows("SELECT DISTINCT COALESCE(NULLIF(TRIM(last_call_status), ''), 'NOT_DIALED') AS status_value FROM campaignnumbers WHERE {$baseWhere} AND UPPER(COALESCE(last_call_status, '')) <> 'ANSWERED' ORDER BY status_value ASC");
            foreach ($rows as $row) {
                $statusValue = trim((string) ($row['status_value'] ?? ''));
                if ($statusValue !== '') {
                    $label = $statusValue === 'NOT_DIALED' ? 'NOT_DIALED' : $statusValue;
                    $options[] = ['value' => $statusValue, 'label' => $label];
                }
            }
        }

        return $options;
    }
	
	public function deletecontacts()
	{
        $companyId = $this->resolveCompanyIdFromRequest();
        $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;

        if ($companyId <= 0) {
            http_response_code(400);
            echo "Invalid company selection.";
            return;
        }

        $todayWindow = $this->getTodayWindowForCompany($companyId);
        $campaignClause = $campaignId > 0 ? " AND campaignid = {$campaignId}" : '';
        $sql = "DELETE FROM campaignnumbers WHERE company_id = {$companyId}{$campaignClause} AND created_at >= '{$todayWindow['start']}' AND created_at <= '{$todayWindow['end']}'";
        if (mysqli_query($this->conn, $sql)) {
            echo "success";
        } else {
            http_response_code(500);
            echo "Failed to delete contacts.";
        }
	}
	
    public function getallcontact() {
         $companyId = $this->resolveCompanyIdFromRequest();
         $campaignId = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;
         $filterType = strtolower(trim((string) ($_REQUEST['filter_type'] ?? '')));
         $filterValue = trim((string) ($_REQUEST['filter_value'] ?? ''));

         if ($companyId <= 0 || $campaignId <= 0) {
             return json_encode([]);
         }

         $todayWindow = $this->getTodayWindowForCompany($companyId);
         $whereClauses = [
             "c.company_id = '{$companyId}'",
             "c.campaignid = '{$campaignId}'",
             "c.created_at >= '{$todayWindow['start']}'",
             "c.created_at <= '{$todayWindow['end']}'",
         ];

         // Filter for agents only
         if ($this->getSessionRole() === 'uagent') {
             $zid = intval($_SESSION['pid'] ?? 0);
             $u_query = "SELECT agentid FROM users WHERE id = '{$zid}'";
             $u_res = mysqli_query($this->conn, $u_query);
             $agent_id = 0;
             if ($u_res && mysqli_num_rows($u_res) > 0) {
                 $u_row = mysqli_fetch_assoc($u_res);
                 $agent_id = intval($u_row['agentid'] ?? 0);
             }
             
             if ($agent_id > 0) {
                 $whereClauses[] = "c.agent_connected = '{$agent_id}'";
             } else {
                 $whereClauses[] = "1=0";
             }
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
             if (strtoupper($filterValue) === 'NOT_DIALED') {
                 $whereClauses[] = "NULLIF(TRIM(COALESCE(c.last_call_status, '')), '') IS NULL";
             } else {
                 $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) <> 'ANSWERED'";
                 if ($filterValue !== '' && $filterValue !== '__all__') {
                     $safeFilterValue = mysqli_real_escape_string($this->conn, strtoupper($filterValue));
                     $whereClauses[] = "UPPER(COALESCE(c.last_call_status, '')) = '{$safeFilterValue}'";
                 }
             }
         }

         $where = 'WHERE ' . implode(' AND ', $whereClauses);

         $query = "SELECT c.id, c.phone_e164, c.first_name, c.last_name, c.days_past_due,
                     c.state, c.attempts_used, c.max_attempts,
                     c.last_call_status, c.last_call_started_at,
                     c.agent_connected, c.notes, c.last_disposition,
                     c.next_call_at,
                     COALESCE(c.next_call_at, c.created_at) AS scheduled_at,
                     a.agent_name, d.color_code
                  FROM campaignnumbers c
                  LEFT JOIN agent a ON c.agent_connected = a.agent_id
                  LEFT JOIN dialer_disposition_master d ON c.last_disposition = d.label AND c.company_id = d.company_id
                  {$where}
                  ORDER BY COALESCE(c.next_call_at, c.created_at) ASC, c.id ASC LIMIT 2000";

        $result = mysqli_query($this->conn, $query);

        if (!$result) {
            return json_encode(['error' => mysqli_error($this->conn)]);
        }
    
        $response = [];
    
        while ($row = mysqli_fetch_assoc($result)) {
            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $lastCallStatus = trim((string) ($row['last_call_status'] ?? ''));
            $isAnswered = strtoupper($lastCallStatus) === 'ANSWERED';
        
            $response[] = [
                'id'            => $row['id'],
                'number'        => $row['phone_e164'],
                'name'          => $fullName,
                'days_past_due' => $row['days_past_due'],
                'type'          => $isAnswered ? 'Answered' : 'Not Answered',
                'feedback'      => $lastCallStatus !== '' ? $lastCallStatus : 'NOT_DIALED',
                'call_status'   => $row['state'],
                'last_try'      => $row['attempts_used'] . '/' . $row['max_attempts'],
                'attempts_used' => $row['attempts_used'],
                'last_try_dt'   => $row['last_call_started_at'],
                'agent_name'    => $row['agent_name'] ?? '',
                'disposition'   => $row['last_disposition'],
                'color_code'    => $row['color_code'] ?? '#808080',
                'notes'         => $row['notes'],
                'next_call_at'  => $row['next_call_at'],
                'scheduled_at'  => $row['scheduled_at']
            ];
        }

        $encoded = json_encode($response);
        if ($encoded === false) {
             return json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
        }
        return $encoded;
    }

	
	public function getcampaign()
	{
	     $companyId = $this->resolveCompanyIdFromRequest();
         return json_encode($this->getCampaignsByCompany($companyId));
	}
	
	public function addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime) 
	{
         if (is_array($weekdays)) {
            $weekdays = json_encode($weekdays);
        }
    
        // Escape and sanitize inputs
        $name       = $name       !== '' ? "'" . mysqli_real_escape_string($this->conn, $name) . "'" : "NULL";
        $routeto    = $routeto    !== '' ? intval($routeto) : "NULL";
        $returncall = $returncall !== '' ? intval($returncall) : "NULL";
        $weekdays   = $weekdays   !== '' ? "'" . mysqli_real_escape_string($this->conn, $weekdays) . "'" : "NULL";
        $starttime  = $starttime  !== '' ? "'" . mysqli_real_escape_string($this->conn, $starttime) . "'" : "NULL";
        $stoptime   = $stoptime   !== '' ? "'" . mysqli_real_escape_string($this->conn, $stoptime) . "'" : "NULL";
    
        // Final SQL query
        $query = "
            INSERT INTO campaign (name, routeto, returncall, weekdays, starttime, stoptime)
            VALUES ($name, $routeto, $returncall, $weekdays, $starttime, $stoptime)
        ";
    
        $insert_fire = mysqli_query($this->conn, $query);
    
        if (!$insert_fire) {
            // Return SQL error for debugging
            return ['success' => false, 'error' => mysqli_error($this->conn)];
        }
    
        return  ['success' => true];
    }


    public function importnumbersql($campaignId, $filePath)
    {
        $insertCount = 0;
        
        // 1. Fetch Campaign Info (Max Attempts & Company ID)
        $campQuery = "SELECT company_id, returncall FROM campaign WHERE id='$campaignId' LIMIT 1";
        $campRes = mysqli_query($this->conn, $campQuery);
        $maxAttempts = 3;
        $companyId = 0;
        
        if($campRes && mysqli_num_rows($campRes) > 0){
             $crow = mysqli_fetch_assoc($campRes);
             $maxAttempts = intval($crow['returncall']);
             $companyId = intval($crow['company_id']);
        }
        
        if($companyId == 0 && isset($_SESSION['company_id'])) {
            $companyId = $_SESSION['company_id'];
        }

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Formatting based on new schema
                // 0: Number (phone_e164 - NO NORMALIZATION as requested)
                // 1: First Name
                // 2: Last Name
                // 3: ExData/Type?
                
                $phone = mysqli_real_escape_string($this->conn, trim($data[0]));
                if(empty($phone)) continue;

                $fname = isset($data[1]) ? mysqli_real_escape_string($this->conn, trim($data[1])) : "";
                $lname = isset($data[2]) ? mysqli_real_escape_string($this->conn, trim($data[2])) : "";
                // $type = isset($data[3]) ? ... (Ignored for now or put in exdata?)
                
                // DNC Check
                $isDnc = 0;
                $state = 'READY';
                $nextCallAt = "UTC_TIMESTAMP()";
                
                // Check if in DNC
                $dncCheck = "SELECT id FROM dialer_dnc WHERE phone_raw='$phone' AND company_id='$companyId' LIMIT 1";
                $dncRes = mysqli_query($this->conn, $dncCheck);
                if($dncRes && mysqli_num_rows($dncRes) > 0){
                    $isDnc = 1;
                    $state = 'DNC';
                    $nextCallAt = "NULL";
                }

                $query = "INSERT INTO campaignnumbers 
                          (company_id, campaignid, phone_e164, phone_raw, first_name, last_name, state, max_attempts, is_dnc, next_call_at)
                          VALUES 
                          ('$companyId', '$campaignId', '$phone', '$phone', '$fname', '$lname', '$state', '$maxAttempts', '$isDnc', $nextCallAt)
                          ON DUPLICATE KEY UPDATE updated_at=UTC_TIMESTAMP()"; 

                if (mysqli_query($this->conn, $query)) {
                    $insertCount++;
                }
            }
            fclose($handle);
        }
    
        return ['success' => true, 'message' => "$insertCount numbers imported."];
    }


	public function delete($tblname, $condition, $op='AND'){

		$delete_data = "";

		foreach ($condition as $q_key => $q_value) {
			$delete_data = $delete_data."$q_key='$q_value' $op ";
		}

		$delete_data = rtrim($delete_data,"$op ");		
		$delete = "DELETE FROM $tblname WHERE $delete_data";
		$delete_fire = mysqli_query($this->conn, $delete);
		if($delete_fire){
			return $delete_fire;
		}
		else{
			return false;
		}

	}
	

	
	public function updateDispositionSql($id, $disposition, $notes, $callbackDate, $callbackTime) {
        $id = intval($id);
        $disposition = mysqli_real_escape_string($this->conn, $disposition);
        $notes = mysqli_real_escape_string($this->conn, $notes);
        $cnRow = null;

        $cnInfoQ = mysqli_query($this->conn, "SELECT notes, company_id, campaignid FROM campaignnumbers WHERE id='$id'");
        if ($cnInfoQ && mysqli_num_rows($cnInfoQ) > 0) {
            $cnRow = mysqli_fetch_assoc($cnInfoQ);
        }
        
        // Determine State and Next Call
        $state = 'DISPO_SUBMITTED';
        $nextCallAt = 'NULL';

        // Fetch Disposition Info
        $dispQuery = mysqli_query($this->conn, "SELECT code, action_type FROM dialer_disposition_master WHERE label='$disposition' LIMIT 1");
        if($dispQuery && mysqli_num_rows($dispQuery) > 0){
             $dRow = mysqli_fetch_assoc($dispQuery);
             $actionType = strtolower($dRow['action_type']);
             if($actionType == 'callback' || $actionType == 'retry'){
                 $state = 'SCHEDULED';
                 if($callbackDate && $callbackTime){
                     $utcNextCallAt = $this->convertCompanyLocalToUtc($cnRow['company_id'] ?? 0, $callbackDate, $callbackTime);
                     if ($utcNextCallAt === null) {
                         return ['success' => false, 'error' => 'Invalid callback date/time.'];
                     }
                     $nextCallAt = "'" . mysqli_real_escape_string($this->conn, $utcNextCallAt) . "'";
                 } else {
                     $nextCallAt = "DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR)"; // Default retry
                 }
             } else if($actionType == 'dnc'){
                 $state = 'DNC';
                 // Update is_dnc?
             } else if($actionType == 'closed'){
                 $state = 'CLOSED';
             }
        } else {
             // Fallback logic if disposition not in master
             $state = 'CLOSED'; 
        }

        // Check if new notes are added
        $notesUpdate = "";
        if (!empty($notes)) {
             $userId = $_SESSION['pid']; 
             $userName = "Unknown";
             
             // Get User Name
             $uQ = mysqli_query($this->conn, "SELECT user_email FROM users WHERE id='$userId'");
             if($uQ && mysqli_num_rows($uQ) > 0){
                 $uRow = mysqli_fetch_assoc($uQ);
                 $userName = $uRow['user_email']; 
             }

             $timestamp = date('Y-m-d H:i');
             
             // Fetch existing notes to append to JSON array
             $currentNotesJson = "[]";
             if($cnRow){
                 $rawNotes = $cnRow['notes'];
                 
                 // Try decode
                 $decoded = json_decode($rawNotes, true);
                 if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                     $cNotes = $decoded;
                 } else {
                     // Legacy text or empty
                     $cNotes = [];
                     if(!empty($rawNotes)) {
                         // Preserve legacy note as an entry
                         $cNotes[] = [
                             'date' => '', 
                             'user' => 'Legacy', 
                             'note' => $rawNotes
                         ];
                     }
                 }
             } else {
                 $cNotes = [];
             }

             // Append new note
             $cNotes[] = [
                 'date' => $timestamp,
                 'user' => $userName,
                 'note' => $notes 
             ];
             
             // Ensure we are saving valid JSON
             $jsonString = json_encode($cNotes);
             if($jsonString === false) {
                 // Fallback if encode fails
                 $jsonString = "[]";
             }
             $jsonNotes = mysqli_real_escape_string($this->conn, $jsonString);
             $notesUpdate = ", notes = '$jsonNotes'";
        }

        // Update Campaign Numbers
        $query = "UPDATE campaignnumbers 
                  SET last_disposition='$disposition', 
                      state='$state', 
                      next_call_at=$nextCallAt 
                      $notesUpdate,
                      last_call_ended_at=UTC_TIMESTAMP()
                  WHERE id='$id'";
        
        if(mysqli_query($this->conn, $query)){
            // Insert Log
             if(!isset($cnRow)) {
                 $infoQ = mysqli_query($this->conn, "SELECT company_id, campaignid FROM campaignnumbers WHERE id='$id'");
                 $cnRow = mysqli_fetch_assoc($infoQ);
             }
             $compId = $cnRow['company_id'] ?? 0;
             $campId = $cnRow['campaignid'] ?? 0;
             
             // Use proper escaping for log insert as well (though $notes is original arg)
             $logDisposition = mysqli_real_escape_string($this->conn, $disposition);
             $logNotes = mysqli_real_escape_string($this->conn, $notes); // Use the NEW note text for log, not the JSON blob

            $logQ = "INSERT INTO dialer_call_log SET
                     company_id = '$compId',
                     campaign_id = '$campId',
                     campaignnumber_id = '$id',
                     call_status = 'MANUAL_DISPO',
                     disposition = '$logDisposition',
                     notes = '$logNotes',
                     started_at = UTC_TIMESTAMP()";
            
            if(!mysqli_query($this->conn, $logQ)) {
                // error_log("Dial Log Error: " . mysqli_error($this->conn));
            }

            return ['success' => true];
        } else {
             return ['success' => false, 'error' => mysqli_error($this->conn)];
        }
    }

	
}	
?>
