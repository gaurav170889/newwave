<?php
class Settings_modal {
    public $conn;

    public function __construct() {
        $this->conn = ConnectDB();
    }

    public function getSettings($company_id) {
        $company_id = intval($company_id);
        $sql = "SELECT * FROM pbxdetail WHERE company_id = $company_id LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function saveSettings($company_id, $data) {
        $company_id = intval($company_id);
        
        $pbxurl = mysqli_real_escape_string($this->conn, $data['pbxurl'] ?? '');
        $pbxloginid = mysqli_real_escape_string($this->conn, $data['pbxloginid'] ?? '');
        $pbxloginpass = mysqli_real_escape_string($this->conn, $data['pbxloginpass'] ?? '');
        $pbxclientid = mysqli_real_escape_string($this->conn, $data['pbxclientid'] ?? '');
        $pbxsecret = mysqli_real_escape_string($this->conn, $data['pbxsecret'] ?? '');
        $timezone = mysqli_real_escape_string($this->conn, $data['timezone'] ?? '');
        $simultaneous_calls = intval($data['simultaneous_calls'] ?? 0);

        // Check if exists
        $check = "SELECT id FROM pbxdetail WHERE company_id = $company_id";
        $res = mysqli_query($this->conn, $check);

        // Optional fields
        $logo_update = "";
        $logo_insert_col = "";
        $logo_insert_val = "";
        if (isset($data['logo'])) {
             $logo = mysqli_real_escape_string($this->conn, $data['logo']);
             $logo_update = ", logo='$logo'";
             $logo_insert_col = ", logo";
             $logo_insert_val = ", '$logo'";
        }
        
        $token_update = "";
        $token_insert_col = "";
        $token_insert_val = "";
        if (isset($data['auth_token'])) {
             $auth_token = mysqli_real_escape_string($this->conn, $data['auth_token']);
             $token_update = ", auth_token='$auth_token', auth_updated_at=UTC_TIMESTAMP()";
             $token_insert_col = ", auth_token, auth_updated_at";
             $token_insert_val = ", '$auth_token', UTC_TIMESTAMP()";
        }

        $prefix_update = "";
        $prefix_insert_col = "";
        $prefix_insert_val = "";
        if (isset($data['outbound_prefix'])) {
             $outbound_prefix = mysqli_real_escape_string($this->conn, $data['outbound_prefix']); 
             $prefix_update = ", outbound_prefix='$outbound_prefix'";
             $prefix_insert_col = ", outbound_prefix";
             $prefix_insert_val = ", '$outbound_prefix'";
        }
        
        // Multi-Question Logic
        if (isset($data['rating_questions_count'])) {
             $count = intval($data['rating_questions_count']);
             if ($count > 0 && $count <= 10) { // Limit to 10 for sanity
                 $this->syncRatingQuestions($company_id, $count);
                 
                 // Save count to pbxdetail for UI persistence
                 $count_update = ", rating_questions_count=$count";
                 $count_insert_col = ", rating_questions_count";
                 $count_insert_val = ", $count";
             }
        } else {
             $count_update = "";
             $count_insert_col = "";
             $count_insert_val = ""; 
        }

        $sentiment_update = "";
        $sentiment_insert_col = "";
        $sentiment_insert_val = "";
        // Check if coming from form. Checkbox sends '1' if checked, else nothing. We handle unchecked in controller or here.
        // If not sent, set to 0? Or rely on default? Typically checkboxes are absent if unchecked.
        $enable_sentiment = isset($data['enable_sentiment']) ? 1 : 0;
        $sentiment_update = ", enable_sentiment=$enable_sentiment";
        $sentiment_insert_col = ", enable_sentiment";
        $sentiment_insert_val = ", $enable_sentiment";

        if (mysqli_num_rows($res) > 0) {
            // Update
            $sql = "UPDATE pbxdetail SET 
                    pbxurl='$pbxurl', 
                    pbxloginid='$pbxloginid', 
                    pbxloginpass='$pbxloginpass', 
                    pbxclientid='$pbxclientid', 
                    pbxsecret='$pbxsecret', 
                    timezone='$timezone'
                    $logo_update
                    $token_update
                    $prefix_update
                    $sentiment_update,
                    enable_rating_recording = " . (isset($data['enable_rating_recording']) ? 1 : 0) . "
                    $count_update,
                    simultaneous_calls=$simultaneous_calls,
                    updated_at = UTC_TIMESTAMP()
                    WHERE company_id=$company_id";
        } else {
            // Insert
            $enable_rating_recording = isset($data['enable_rating_recording']) ? 1 : 0;
            $sql = "INSERT INTO pbxdetail (company_id, pbxurl, pbxloginid, pbxloginpass, pbxclientid, pbxsecret, timezone $logo_insert_col $token_insert_col $prefix_insert_col $sentiment_insert_col, enable_rating_recording $count_insert_col, simultaneous_calls, created_at)
                    VALUES ($company_id, '$pbxurl', '$pbxloginid', '$pbxloginpass', '$pbxclientid', '$pbxsecret', '$timezone' $logo_insert_val $token_insert_val $prefix_insert_val $sentiment_insert_val, $enable_rating_recording $count_insert_val, $simultaneous_calls, UTC_TIMESTAMP())";
        }

        if (mysqli_query($this->conn, $sql)) {
            // Update Question Labels
            if (isset($data['question_labels']) && is_array($data['question_labels'])) {
                foreach ($data['question_labels'] as $qid => $label) {
                    $qid = intval($qid);
                    $label = mysqli_real_escape_string($this->conn, $label);
                    // Ensure the question belongs to this company (security check implied by ID, but good to be safe)
                    $up_q_sql = "UPDATE rating_questions SET label = '$label' WHERE id = $qid AND company_id = $company_id";
                    mysqli_query($this->conn, $up_q_sql);
                }
            }
            return ['success' => true, 'message' => 'Settings saved successfully.'];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . mysqli_error($this->conn)];
        }
    }

    public function syncRatingQuestions($company_id, $target_count) {
        $existing = $this->getQuestions($company_id);
        $current_count = count($existing);
        
        if ($target_count > $current_count) {
            // Add new ones
            for ($i = $current_count + 1; $i <= $target_count; $i++) {
                $token = bin2hex(random_bytes(16)); // 32 char unique token
                $sql = "INSERT INTO rating_questions (company_id, question_number, webhook_token) VALUES ($company_id, $i, '$token')";
                mysqli_query($this->conn, $sql);
            }
        } elseif ($target_count < $current_count) {
            // Remove extras
            $sql = "DELETE FROM rating_questions WHERE company_id = $company_id AND question_number > $target_count";
            mysqli_query($this->conn, $sql);
        }
    }

    public function getQuestions($company_id) {
         $sql = "SELECT * FROM rating_questions WHERE company_id = $company_id ORDER BY question_number ASC";
         $res = mysqli_query($this->conn, $sql);
         $qs = [];
         while($row = mysqli_fetch_assoc($res)) {
             $qs[] = $row;
         }
         return $qs;
    }
}
?>
