<?php
class Admindashboard_modal {
    
    public function __construct() {
        $this->conn = ConnectDB();
    }
    
    public function getCompanies() {
        // Fetch companies with their admin user (assuming one admin per company for now, or picking the first one found)
        // We use check for user_type='company_admin'
        $sql = "SELECT c.*, u.id as admin_id, u.user_email as admin_email 
                FROM companies c 
                LEFT JOIN users u ON c.id = u.company_id AND u.user_type = 'company_admin' 
                ORDER BY c.created_at DESC";
                
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    public function getUserById($user_id) {
        $user_id = intval($user_id);
        $sql = "SELECT * FROM users WHERE id = $user_id";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }
    
    public function updatePassword($user_id, $new_password) {
        $user_id = intval($user_id);
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = '$password_hash' WHERE id = $user_id";
        return mysqli_query($this->conn, $sql);
    }
    
    public function addCompany($name, $status) {
        $name = mysqli_real_escape_string($this->conn, $name);
        $status = mysqli_real_escape_string($this->conn, $status);
        $sql = "INSERT INTO companies (name, status) VALUES ('$name', '$status')";
        return mysqli_query($this->conn, $sql);
    }
    
    public function addCompanyAdmin($company_id, $email, $password) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (company_id, user_email, password_hash, user_type) 
                VALUES ($company_id, '$email', '$password_hash', 'company_admin')";
        
        return mysqli_query($this->conn, $sql);
    }
    public function getCompanySettings($company_id) {
        $company_id = intval($company_id);
        $sql = "SELECT * FROM pbxdetail WHERE company_id = $company_id LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }
    
    public function getCompanyQuestions($company_id) {
        $company_id = intval($company_id);
        $sql = "SELECT * FROM rating_questions WHERE company_id = $company_id ORDER BY question_number ASC";
        $result = mysqli_query($this->conn, $sql);
        $qs = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $qs[] = $row;
            }
        }
        return $qs;
    }

    public function updateCompanySettings($company_id, $data) {
        $company_id = intval($company_id);
        
        // Settings to update
        $outbound_prefix = isset($data['outbound_prefix']) ? 'Yes' : 'No'; 
        $enable_rating_recording = isset($data['enable_rating_recording']) ? 1 : 0;
        $enable_sentiment = isset($data['enable_sentiment']) ? 1 : 0;
        $rating_questions_count = intval($data['rating_questions_count'] ?? 0);
        
        // Validation Rule: If Sentiment or Recording is enabled, enforce at least 1 question.
        if (($enable_rating_recording == 1 || $enable_sentiment == 1) && $rating_questions_count < 1) {
            $rating_questions_count = 1; 
        }

        // Check if record exists
        $check = mysqli_query($this->conn, "SELECT id FROM pbxdetail WHERE company_id = $company_id");
        
        if (mysqli_num_rows($check) > 0) {
            // Update existing record - preserve other fields
            $sql = "UPDATE pbxdetail SET 
                    outbound_prefix = '$outbound_prefix',
                    enable_rating_recording = $enable_rating_recording,
                    enable_sentiment = $enable_sentiment,
                    rating_questions_count = $rating_questions_count,
                    updated_at = NOW()
                    WHERE company_id = $company_id";
        } else {
            // Create record if not exists (unlikely if they set PBX details, but possible for new companies)
            // We initialize empty PBX connection fields
             $sql = "INSERT INTO pbxdetail (company_id, inbound_prefix, outbound_prefix, enable_rating_recording, enable_sentiment, rating_questions_count, created_at)
                    VALUES ($company_id, 'No', '$outbound_prefix', $enable_rating_recording, $enable_sentiment, $rating_questions_count, NOW())";
        }

        if (mysqli_query($this->conn, $sql)) {
            // Handle Rating Questions Sync
            if ($rating_questions_count >= 0 && $rating_questions_count <= 10) {
                $this->syncRatingQuestions($company_id, $rating_questions_count);
            }
            
            // Handle Question Labels Update
            if (isset($data['question_labels']) && is_array($data['question_labels'])) {
                foreach ($data['question_labels'] as $qid => $label) {
                    $qid = intval($qid);
                    $label = mysqli_real_escape_string($this->conn, trim($label));
                    // Update label for specific question belonging to this company
                    $lsql = "UPDATE rating_questions SET label = '$label' WHERE id = $qid AND company_id = $company_id";
                    mysqli_query($this->conn, $lsql);
                }
            }
            return true;
        } else {
            error_log("Update Company Settings Failed: " . mysqli_error($this->conn) . " | SQL: " . $sql);
            // Also append to a debug file we can read
            file_put_contents('debug_error.log', "SQL Error: " . mysqli_error($this->conn) . "\nSQL: $sql\n", FILE_APPEND);
            return false;
        }
    }

    public function syncRatingQuestions($company_id, $target_count) {
        $existing = $this->getCompanyQuestions($company_id);
        $current_count = count($existing);
        
        if ($target_count > $current_count) {
            // Add new ones
            for ($i = $current_count + 1; $i <= $target_count; $i++) {
                $token = bin2hex(random_bytes(16)); // 32 char unique token
                $sql = "INSERT INTO rating_questions (company_id, question_number, webhook_token) VALUES ($company_id, $i, '$token')";
                if (!mysqli_query($this->conn, $sql)) {
                    error_log("Failed to insert rating question $i for company $company_id: " . mysqli_error($this->conn));
                }
            }
        } elseif ($target_count < $current_count) {
            // Remove extras
            $sql = "DELETE FROM rating_questions WHERE company_id = $company_id AND question_number > $target_count";
            if (!mysqli_query($this->conn, $sql)) {
                 error_log("Failed to delete rating question > $target_count for company $company_id: " . mysqli_error($this->conn));
            }
        }
    }
}
?>
