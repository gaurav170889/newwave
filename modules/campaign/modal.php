<?php
/* Modulename_modal */
Class Campaign_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
	}
	
	public function htmlvalidation($form_data){
		$form_data = trim( stripslashes( htmlspecialchars( $form_data ) ) );
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}
	
	public function updatestatus($id, $status, $dpd_from = null, $dpd_to = null) 
	{
        $statusText = ($status == '1') ? 'Running' : 'Stop';
        $now = date('Y-m-d H:i:s');
    
        // Escape values to prevent SQL injection (use only if you control input)
        $id = intval($id);
        $statusText = mysqli_real_escape_string($this->conn, $statusText);
        $now = mysqli_real_escape_string($this->conn, $now);
        
        $dpd_from_sql = $dpd_from !== null ? intval($dpd_from) : "NULL";
        $dpd_to_sql = $dpd_to !== null ? intval($dpd_to) : "NULL";

        if ($status == '1') {
            $sql = "UPDATE campaign SET status = '$statusText', statusupdate = '$now', dpd_filter_from = $dpd_from_sql, dpd_filter_to = $dpd_to_sql, notify_email_sent_at = NULL WHERE id = $id";
            
            // Log the run filter history
            $queryCmp = mysqli_query($this->conn, "SELECT company_id FROM campaign WHERE id = $id LIMIT 1");
            if ($queryCmp && mysqli_num_rows($queryCmp) > 0) {
                $row = mysqli_fetch_assoc($queryCmp);
                $company_id = intval($row['company_id']);
                mysqli_query($this->conn, "INSERT INTO campaign_run_filters (company_id, campaign_id, dpd_from, dpd_to) VALUES ($company_id, $id, $dpd_from_sql, $dpd_to_sql)");
            }
        } else {
            // Unset filters on stop
            $sql = "UPDATE campaign SET status = '$statusText', statusupdate = '$now', dpd_filter_from = NULL, dpd_filter_to = NULL WHERE id = $id";
        }
    
        return mysqli_query($this->conn, $sql) ? true : false;
    }
	
	public function getcampaign($company_id = null)
    {
        $query = "
            SELECT c.*, 
                   u1.user_email as created_by_name, 
                   u2.user_email as updated_by_name 
            FROM campaign c
            LEFT JOIN users u1 ON c.created_by = u1.id
            LEFT JOIN users u2 ON c.updated_by = u2.id
            WHERE c.is_deleted = 0
        ";
        
        if ($company_id !== null) {
            $company_id = intval($company_id);
            $query .= " AND c.company_id = $company_id";
        }
        
        $result = mysqli_query($this->conn, $query);
    
        $data = [];
    
        if ($result && mysqli_num_rows($result) > 0) {
            $index = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                // Save real DB ID as campaignid
                $row['campaignid'] = $row['id'];
    
                // Replace 'id' with index
                $row['id'] = $index++;
    
                // Decode and reformat weekdays
                $row['weekdays'] = json_decode($row['weekdays'], true);
                if (is_array($row['weekdays'])) {
                    $row['weekdays'] = implode(', ', $row['weekdays']);
                } else {
                     $row['weekdays'] = '';
                }
    
                $data[] = $row;
            }
        }
    
        return json_encode($data);
    }
	
	public function addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime, $company_id, $created_by, $dialer_mode, $route_type, $concurrent_calls, $webhook_token = null, $dn_number = null, $notify_no_leads_email = 0, $notify_email = null)
	{
         if (is_array($weekdays)) {
            $weekdays = json_encode($weekdays);
        }
    
        // Escape and sanitize inputs
        $name       = mysqli_real_escape_string($this->conn, $name);
        $routeto    = $routeto != '' ? mysqli_real_escape_string($this->conn, $routeto) : 0;
        $returncall = ($returncall != '') ? $returncall : 0;
        $weekdays   = $weekdays != '' ? mysqli_real_escape_string($this->conn, $weekdays) : '';
        $starttime  = $starttime  !== '' ? "'" . mysqli_real_escape_string($this->conn, $starttime) . "'" : "NULL";
        $stoptime   = $stoptime   !== '' ? "'" . mysqli_real_escape_string($this->conn, $stoptime) . "'" : "NULL";
        $company_id = intval($company_id);
        $created_by = intval($created_by);
        
        $dialer_mode = $dialer_mode !== '' ? "'" . mysqli_real_escape_string($this->conn, $dialer_mode) . "'" : "'Power Dialer'";
        $route_type  = $route_type  !== '' ? "'" . mysqli_real_escape_string($this->conn, $route_type) . "'" : "'Queue'";
        $concurrent_calls = $concurrent_calls !== '' ? intval($concurrent_calls) : 1;
        $webhook_token = $webhook_token ? "'" . mysqli_real_escape_string($this->conn, $webhook_token) . "'" : "NULL";
        $dn_number = $dn_number ? "'" . mysqli_real_escape_string($this->conn, $dn_number) . "'" : "NULL";
        $notify_no_leads_email = intval($notify_no_leads_email) ? 1 : 0;
        $notify_email = ($notify_email !== null && trim($notify_email) !== '') ? "'" . mysqli_real_escape_string($this->conn, trim($notify_email)) . "'" : "NULL";
    
        // Final SQL query
        $query = "
            INSERT INTO campaign (company_id, name, routeto, dn_number, returncall, weekdays, starttime, stoptime, created_by, dialer_mode, route_type, concurrent_calls, webhook_token, notify_no_leads_email, notify_email)
            VALUES ($company_id, '$name', '$routeto', $dn_number, $returncall, '$weekdays', $starttime, $stoptime, $created_by, $dialer_mode, $route_type, $concurrent_calls, $webhook_token, $notify_no_leads_email, $notify_email)
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
        $skippedCount = 0;
        $campaignId = intval($campaignId);
        
        // Fetch Campaign Info (Max Attempts & Company ID)
        $campQuery = mysqli_query($this->conn, "SELECT company_id, returncall, created_by, updated_by FROM campaign WHERE id = $campaignId");
        
        if (!$campQuery || mysqli_num_rows($campQuery) === 0) {
            return ['success' => false, 'message' => "Campaign or Company not found."];
        }
        
        $campaignData = mysqli_fetch_assoc($campQuery);
        $companyId = $campaignData['company_id'];
        $returnCall = intval($campaignData['returncall']);
        $maxAttempts = ($returnCall > 0) ? $returnCall : 3;
        
        $createdBy = $_SESSION['pid'] ?? 0;
        $activeUser = $_SESSION['pid'] ?? 0;

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle); // First line is header
            
            // Normalize headers
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Map headers to data
                $row = [];
                foreach ($headers as $index => $header) {
                    if (isset($data[$index])) {
                        $row[$header] = trim($data[$index]);
                    } else {
                        $row[$header] = '';
                    }
                }

                // Extract fixed fields
                $number = mysqli_real_escape_string($this->conn, $row['home phone'] ?? $row['number'] ?? '');
                $fname  = mysqli_real_escape_string($this->conn, $row['first name'] ?? $row['fname'] ?? '');
                $lname  = mysqli_real_escape_string($this->conn, $row['last name'] ?? $row['lname'] ?? '');
                $email  = mysqli_real_escape_string($this->conn, $row['email address'] ?? $row['email'] ?? '');
                $days_past_due = isset($row['days past due']) ? intval($row['days past due']) : 'NULL';
                $type   = mysqli_real_escape_string($this->conn, $row['type'] ?? '');
                $feedback = mysqli_real_escape_string($this->conn, $row['feedback'] ?? '');
                
                // Scheduling Logic
                $schDate = isset($row['scheduled_date']) ? trim($row['scheduled_date']) : '';
                $schTime = isset($row['scheduled_time']) ? trim($row['scheduled_time']) : '';
                
                $state = 'READY';
                $nextCallAt = "NOW()";
                
                // Detect DD-MM-YYYY format and convert to YYYY-MM-DD
                if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $schDate, $matches)) {
                    $schDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                }

                if (!empty($schDate) && !empty($schTime)) {
                    // Basic validation check? YYYY-MM-DD
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $schDate)) {
                         $fullTime = $schTime;
                         if (strlen($fullTime) == 5) $fullTime .= ":00"; // HH:MM -> HH:MM:00
                         
                         $state = 'SCHEDULED';
                         // Escape the datetime string
                         $dtStr = $schDate . " " . $fullTime;
                         $nextCallAt = "'" . mysqli_real_escape_string($this->conn, $dtStr) . "'";
                    }
                } elseif (!empty($schDate)) {
                    // Only date? Default to 9am? Or treat as READY? 
                    // User said "If either is provided -> schedule". 
                    // Let's assume start of day logic or just keep READY if time missing?
                    // User said: "If either is provided -> schedule using next_call_at".
                    // If time missing, maybe default to 09:00:00?
                    $state = 'SCHEDULED';
                    $dtStr = $schDate . " 09:00:00";
                     $nextCallAt = "'" . mysqli_real_escape_string($this->conn, $dtStr) . "'";
                }

                // Extract Extra Data
                $exdata = [];
                $fixedFields = ['number', 'home phone', 'fname', 'first name', 'lname', 'last name', 'email address', 'days past due', 'type', 'feedback', 'scheduled_date', 'scheduled_time'];
                foreach ($row as $key => $val) {
                    if (!in_array($key, $fixedFields)) {
                        $exdata[$key] = $val;
                    }
                }
                $exdataJson = mysqli_real_escape_string($this->conn, json_encode($exdata));

                if (!empty($number)) {
                    // Check DNC
                    $isDnc = 0;
                    $dncCheck = "SELECT id FROM dialer_dnc WHERE phone_raw='$number' AND company_id='$companyId' LIMIT 1";
                    $dncRes = mysqli_query($this->conn, $dncCheck);
                    if ($dncRes && mysqli_num_rows($dncRes) > 0) {
                        $isDnc = 1;
                        $state = 'DNC';
                        $nextCallAt = "NULL";
                    }
                    
                    // Unique Check: Last 8 digits match + same email + same days past due + same day
                    // The user wants: same number, email, and days past due on the same day = duplicate.
                    $last8 = substr($number, -8);
                    $daysCheck = ($days_past_due === 'NULL') ? "days_past_due IS NULL" : "days_past_due = $days_past_due";
                    
                    $checkQuery = "SELECT id FROM campaignnumbers 
                                   WHERE campaignid = $campaignId 
                                   AND RIGHT(phone_e164, 8) = '$last8' 
                                   AND email_address = '$email'
                                   AND $daysCheck
                                   AND DATE(created_at) = CURDATE()";
                    $checkResult = mysqli_query($this->conn, $checkQuery);

                    if (mysqli_num_rows($checkResult) > 0) {
                        // Duplicate FOUND -> Insert to Skipped
                        $skippedQuery = "INSERT INTO campaign_skipped_numbers 
                                        (company_id, campaignid, number, fname, lname, type, feedback, exdata)
                                        VALUES ($companyId, $campaignId, '$number', '$fname', '$lname', '$type', '$feedback', '$exdataJson')";
                        mysqli_query($this->conn, $skippedQuery);
                        $skippedCount++;
                    } else {
                        // Valid -> Insert to Campaign Numbers
                        $mainQuery = "INSERT INTO campaignnumbers 
                                     (company_id, campaignid, phone_e164, phone_raw, first_name, last_name, email_address, days_past_due, exdata, state, next_call_at, max_attempts, is_dnc, created_by, updated_by)
                                     VALUES ($companyId, $campaignId, '$number', '$number', '$fname', '$lname', '$email', $days_past_due, '$exdataJson', '$state', $nextCallAt, $maxAttempts, $isDnc, $createdBy, $activeUser)";
                        
                        if (mysqli_query($this->conn, $mainQuery)) {
                            $insertCount++;
                        } else {
                            // Can add error logging here if schema changes didn't apply
                            error_log("Failed to insert number: " . mysqli_error($this->conn));
                        }
                    }
                }
            }
            fclose($handle);
        }
        
        return ['success' => true, 'message' => "$insertCount numbers imported. $skippedCount duplicates skipped."];
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
	
	public function updatecampaign($id, $data)
    {
        $id = intval($id);
        $fields = [];
    
        foreach ($data as $key => $value) {
            if ($value === null) {
                $fields[] = "`$key` = NULL";
                continue;
            }

            $escaped = mysqli_real_escape_string($this->conn, (string)$value);
            $fields[] = "`$key` = '$escaped'";
        }
    
        $setClause = implode(', ', $fields);
        $query = "UPDATE campaign SET $setClause WHERE id = $id";
    
        return mysqli_query($this->conn, $query);
    }

    public function getCompanies()
    {
        $query = "SELECT id, name FROM companies ORDER BY name ASC";
        $result = mysqli_query($this->conn, $query);
        $companies = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $companies[] = $row;
            }
        }
        return $companies;
    }

public function getSkippedNumbers($company_id = null)
{
    // Fix: Join with campaign/companies to show names
    $sql = "SELECT s.*, c.name as campaign_name, co.name as company_name 
            FROM campaign_skipped_numbers s 
            LEFT JOIN campaign c ON s.campaignid = c.id
            LEFT JOIN companies co ON s.company_id = co.id
            WHERE 1=1";
            
    if ($company_id) {
        $company_id = intval($company_id);
        $sql .= " AND s.company_id = $company_id";
    }
    
    $sql .= " ORDER BY s.id DESC LIMIT 1000"; // Limit to avoid crash on huge datasets
    
    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function getImportLogs($company_id = null)
{
    $sql = "SELECT i.*, c.name as campaign_name, co.name as company_name, 
            (SELECT user_email FROM users WHERE users.id = i.import_by) as imported_by_name
            FROM importnum i
            LEFT JOIN campaign c ON i.campaign_id = c.id
            LEFT JOIN companies co ON i.company_id = co.id
            WHERE 1=1";
            
    if ($company_id) {
        $company_id = intval($company_id);
        $sql .= " AND i.company_id = $company_id";
    }
    
    $sql .= " ORDER BY i.import_at DESC";
    
    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}
    public function checkDuplicateCampaign($name, $company_id)
    {
        $name = mysqli_real_escape_string($this->conn, $name);
        $company_id = intval($company_id);
        
        $query = "SELECT id FROM campaign WHERE name = '$name' AND company_id = $company_id AND is_deleted = 0";
        $result = mysqli_query($this->conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return true; // Exists
        }
        return false;
    }
    
    public function deleteCampaign($id)
    {
        $id = intval($id);
        $query = "UPDATE campaign SET is_deleted = 1 WHERE id = $id";
        return mysqli_query($this->conn, $query);
    }
    
    public function getCampaignStatus($id)
    {
        $id = intval($id);
        $query = "SELECT status FROM campaign WHERE id = $id";
        $result = mysqli_query($this->conn, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['status'];
        }
        return false;
    }
    
    public function logImport($campaignId, $originalName, $tempName, $userId)
    {
        $campaignId = intval($campaignId);
        $userId = intval($userId);
        
        // Fetch Company ID
        $companyQuery = mysqli_query($this->conn, "SELECT company_id FROM campaign WHERE id = $campaignId");
        if ($companyQuery && $row = mysqli_fetch_assoc($companyQuery)) {
            $companyId = $row['company_id'];
            
            $originalName = mysqli_real_escape_string($this->conn, $originalName);
            $tempName = mysqli_real_escape_string($this->conn, $tempName);
            
            $query = "INSERT INTO importnum (company_id, campaign_id, importfilename, tempname, import_by) 
                      VALUES ($companyId, $campaignId, '$originalName', '$tempName', $userId)";
            
            mysqli_query($this->conn, $query);
        }
    }
	

	
}	
?>