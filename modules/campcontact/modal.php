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
	
	public function deletecontacts()
	{
	    $sql = "DELETE FROM campaignnumbers WHERE DATE(iserttime) = CURDATE()";
        if (mysqli_query($this->conn, $sql)) {
            echo "success";
        } else {
            http_response_code(500);
            echo "Failed to delete contacts.";
        }
	}
	
    public function getallcontact() {
         $where = "";
         // Filter for agents only
         if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'uagent') {
             $zid = $_SESSION['zid'];
             $u_query = "SELECT agentid FROM users WHERE id = '$zid'";
             $u_res = mysqli_query($this->conn, $u_query);
             $agent_id = 0;
             if ($u_res && mysqli_num_rows($u_res) > 0) {
                 $u_row = mysqli_fetch_assoc($u_res);
                 $agent_id = $u_row['agentid'];
             }
             
             if ($agent_id) {
                 $where = "WHERE c.agent_connected = '$agent_id'";
             } else {
                 $where = "WHERE 1=0"; 
             }
         }

         // New Schema Query
         $query = "SELECT c.id, c.phone_e164, c.first_name, c.last_name, c.days_past_due,
                     c.state, c.attempts_used, c.max_attempts,
                     c.last_call_status, c.last_call_started_at,
                     c.agent_connected, c.notes, c.last_disposition,
                     c.next_call_at,
					 a.agent_name, d.color_code
			  FROM campaignnumbers c
			  LEFT JOIN agent a ON c.agent_connected = a.agent_id
              LEFT JOIN dialer_disposition_master d ON c.last_disposition = d.label AND c.company_id = d.company_id
			  $where
              ORDER BY c.id DESC LIMIT 2000";

        $result = mysqli_query($this->conn, $query);

        if (!$result) {
            return json_encode(['error' => mysqli_error($this->conn)]);
        }
    
        $response = [];
    
        while ($row = mysqli_fetch_assoc($result)) {
            // Merge Name
            $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        
            $response[] = [
                'id'          => $row['id'],
                'number'      => $row['phone_e164'],
                'name'        => $fullName,
                'days_past_due' => $row['days_past_due'],
                'type'        => 'Lead',
                'feedback'    => $row['last_call_status'],
                'call_status' => $row['state'],
                'last_try'    => $row['attempts_used'] . '/' . $row['max_attempts'],
                'attempts_used' => $row['attempts_used'],
                'last_try_dt' => $row['last_call_started_at'],
                'last_try_dt' => $row['last_call_started_at'],
                'agent_name'  => $row['agent_name'] ?? '',
                'disposition' => $row['last_disposition'],
                'color_code'  => $row['color_code'] ?? '#808080',
                'notes'       => $row['notes'],
                'next_call_at'=> $row['next_call_at']
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
	    
	     $query = "SELECT * FROM campaign";
        $result = mysqli_query($this->conn, $query);
    
        $data = [];
    
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Decode weekdays from JSON
                $row['weekdays'] = json_decode($row['weekdays'], true);
                $row['weekdays'] = is_array($row['weekdays']) ? implode(', ', $row['weekdays']) : '';
                $data[] = $row;
            }
        }
    
        //header('Content-Type: application/json');
        return json_encode($data);
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
                $nextCallAt = "NOW()";
                
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
                          ON DUPLICATE KEY UPDATE updated_at=NOW()"; 

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
                     $nextCallAt = "'".mysqli_real_escape_string($this->conn, "$callbackDate $callbackTime")."'";
                 } else {
                     $nextCallAt = "DATE_ADD(NOW(), INTERVAL 1 HOUR)"; // Default retry
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
             $userId = $_SESSION['zid']; 
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
             $cnQ = mysqli_query($this->conn, "SELECT notes, company_id, campaignid FROM campaignnumbers WHERE id='$id'");
             if($cnQ && mysqli_num_rows($cnQ) > 0){
                 $cnRow = mysqli_fetch_assoc($cnQ);
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
                      last_call_ended_at=NOW()
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
                     started_at = NOW()";
            
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