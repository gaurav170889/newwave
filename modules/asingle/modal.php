<?php
Class Asingle_modal {
    
    public function __construct() {
        $this->conn = ConnectDB();
    }
    
    // Fetch Ratings for DataTable
    public function getRatings($company_id, $start_date, $end_date) {
        $company_id = intval($company_id);
        $start_date = mysqli_real_escape_string($this->conn, $start_date);
        $end_date = mysqli_real_escape_string($this->conn, $end_date);
        
        // Base Query
        $sql = "SELECT r.rid, r.callid, r.agentid, a.agent_name, r.callerno, r.created_at, r.ratings_json,
                       r.sentiment, r.transcript, r.recording_url 
                FROM `rate` r 
                LEFT JOIN `agent` a ON r.agentid = a.agent_id
                WHERE r.company_id = $company_id 
                AND r.created_at >= '$start_date 00:00:00' 
                AND r.created_at <= '$end_date 23:59:59'
                ORDER BY r.created_at DESC";
                
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate Average Score
                $ratings = json_decode($row['ratings_json'], true);
                $total_score = 0;
                $count = 0;
                
                if (is_array($ratings)) {
                    foreach ($ratings as $key => $val) {
                        $score = intval($val);
                        if ($score > 0) { // Assuming 0 or null is skipped or treated as 0
                            $total_score += $score;
                            $count++;
                        }
                    }
                }
                
                $avg = ($count > 0) ? round($total_score / $count, 1) : 0;
                $row['avg_score'] = $avg;
                
                // Format Date
                $row['call_date'] = date('Y-m-d H:i', strtotime($row['created_at']));
                
                $data[] = $row;
            }
        }
        return $data;
    }
    
    // Fetch Details for Modal
    public function getRatingDetails($rid, $company_id) {
        $rid = intval($rid);
        $company_id = intval($company_id);
        
        // Get Rating Row
        $sql = "SELECT * FROM `rate` WHERE rid = $rid AND company_id = $company_id";
        $res = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($res);
        
        if (!$row) return ['status' => 'error', 'message' => 'Record not found'];
        
        // Get Questions Labels
        $q_sql = "SELECT question_number, label FROM rating_questions WHERE company_id = $company_id ORDER BY question_number ASC";
        $q_res = mysqli_query($this->conn, $q_sql);
        $labels = [];
        while ($q = mysqli_fetch_assoc($q_res)) {
            $labels['q' . $q['question_number']] = $q['label']; // Map "q1" => "Agent Knowledge"
        }
        
        // Parse Ratings
        $ratings_json = json_decode($row['ratings_json'], true);
        $details = [];
        
        if (is_array($ratings_json)) {
            foreach ($ratings_json as $key => $score) {
                // Clean Key (q1 -> Q1)
                $q_num = str_replace('q', '', $key);
                $label = $labels[$key] ?? "Question $q_num";
                
                $details[] = [
                    'key' => $key,
                    'label' => $label,
                    'score' => $score
                ];
            }
        }
        
        return [
            'status' => 'success',
            'details' => $details,
            'transcript' => $row['transcript'],
            'sentiment' => $row['sentiment'],
            'recording_url' => $row['recording_url'] // Needs to be populated by sync or something
        ];
    }
}
?>