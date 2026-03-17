<?php
class Outprefix_modal {
    public $conn;

    public function __construct() {
        $this->conn = ConnectDB();
    }

    public function getCampaigns($company_id) {
        $company_id = intval($company_id);
        // Only non-deleted campaigns
        $sql = "SELECT id, name FROM campaign WHERE company_id = $company_id AND is_deleted = 0";
        $result = mysqli_query($this->conn, $sql);
        $campaigns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $campaigns[] = $row;
        }
        return $campaigns;
    }

    public function getPrefixes($company_id, $campaign_id) {
        $company_id = intval($company_id);
        $campaign_id = intval($campaign_id);
        $sql = "SELECT * FROM campaign_prefix_usage WHERE company_id = $company_id AND campaign_id = $campaign_id ORDER BY id ASC";
        $result = mysqli_query($this->conn, $sql);
        $prefixes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $prefixes[] = $row;
        }
        return $prefixes;
    }

    public function savePrefixes($company_id, $data) {
        $company_id = intval($company_id);
        $campaign_id = intval($data['campaign_id']);
        $new_prefixes = $data['prefixes'] ?? []; // Array of strings
        
        // Remove empty prefixes
        $new_prefixes = array_filter($new_prefixes, function($p) { return trim($p) !== ''; });
        $new_prefixes = array_values($new_prefixes);

        if ($campaign_id <= 0) {
            return ['success' => false, 'message' => 'Invalid Campaign ID'];
        }

        // Logic: Sync lists
        // 1. Get existing
        $existing = $this->getPrefixes($company_id, $campaign_id);
        $existing_map = [];
        foreach ($existing as $row) {
            $existing_map[$row['prefix']] = $row['id'];
        }

        // 2. Identify prefixes to Delete (in existing but not in new)
        $to_delete = [];
        foreach ($existing_map as $p_val => $p_id) {
            if (!in_array($p_val, $new_prefixes)) {
                $to_delete[] = $p_id;
            }
        }
        
        if (!empty($to_delete)) {
            $ids_str = implode(',', $to_delete);
            mysqli_query($this->conn, "DELETE FROM campaign_prefix_usage WHERE id IN ($ids_str)");
        }

        // 3. Identify prefixes to Insert (in new but not in existing)
        foreach ($new_prefixes as $p_val) {
            $p_val = trim($p_val);
            if (!isset($existing_map[$p_val])) {
                $safe_p = mysqli_real_escape_string($this->conn, $p_val);
                $sql = "INSERT INTO campaign_prefix_usage (company_id, campaign_id, prefix) VALUES ($company_id, $campaign_id, '$safe_p')";
                mysqli_query($this->conn, $sql);
            }
        }

        return ['success' => true, 'message' => 'Prefixes updated successfully.'];
    }
}
?>
