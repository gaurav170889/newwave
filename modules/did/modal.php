<?php
class Did_modal {
    public $conn;

    public function __construct() {
        $this->conn = ConnectDB();
        $this->ensureTables();
    }

    private function ensureTables() {
        $sql1 = "CREATE TABLE IF NOT EXISTS pbx_dids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            inbound_rule_id INT NOT NULL,
            did VARCHAR(64) NOT NULL,
            trunk VARCHAR(255) DEFAULT NULL,
            rule_name VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_company_inbound_rule (company_id, inbound_rule_id),
            KEY idx_company_did (company_id, did)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sql2 = "CREATE TABLE IF NOT EXISTS campaign_outbound_rule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            outbound_rule_id INT NOT NULL,
            last_used_map_id INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_company_campaign (company_id, campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sql3 = "CREATE TABLE IF NOT EXISTS campaign_did_map (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            did_id INT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_campaign_did (campaign_id, did_id),
            KEY idx_company_campaign (company_id, campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql1);
        mysqli_query($this->conn, $sql2);
        mysqli_query($this->conn, $sql3);
    }

    public function getCompanies() {
        $rows = [];
        $q = mysqli_query($this->conn, "SELECT id, name FROM companies ORDER BY name ASC");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function getPbxUrl($company_id) {
        $company_id = intval($company_id);
        $q = mysqli_query($this->conn, "SELECT pbxurl FROM pbxdetail WHERE company_id = $company_id LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            return $row['pbxurl'];
        }
        return null;
    }

    public function getCampaigns($company_id) {
        $company_id = intval($company_id);
        $rows = [];
        $sql = "SELECT id, name FROM campaign WHERE company_id = $company_id AND is_deleted = 0 ORDER BY name ASC";
        $q = mysqli_query($this->conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function getSyncedDids($company_id) {
        $company_id = intval($company_id);
        $rows = [];
        $sql = "SELECT id, inbound_rule_id, did, trunk, rule_name
                FROM pbx_dids
                WHERE company_id = $company_id
                ORDER BY trunk ASC, did ASC";
        $q = mysqli_query($this->conn, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function syncDids($company_id, $items) {
        $company_id = intval($company_id);
        $count = 0;

        foreach ($items as $item) {
            $inbound_rule_id = isset($item['Id']) ? intval($item['Id']) : 0;
            $did = isset($item['DID']) ? trim($item['DID']) : '';
            $trunk = isset($item['Trunk']) ? trim($item['Trunk']) : '';
            $rule_name = isset($item['RuleName']) ? trim($item['RuleName']) : '';

            if ($inbound_rule_id <= 0 || $did === '') {
                continue;
            }

            $did = mysqli_real_escape_string($this->conn, $did);
            $trunk = mysqli_real_escape_string($this->conn, $trunk);
            $rule_name = mysqli_real_escape_string($this->conn, $rule_name);

            $sql = "INSERT INTO pbx_dids (company_id, inbound_rule_id, did, trunk, rule_name)
                    VALUES ($company_id, $inbound_rule_id, '$did', '$trunk', '$rule_name')
                    ON DUPLICATE KEY UPDATE
                      did = VALUES(did),
                      trunk = VALUES(trunk),
                      rule_name = VALUES(rule_name),
                      updated_at = UTC_TIMESTAMP()";

            if (mysqli_query($this->conn, $sql)) {
                $count++;
            }
        }

        return $count;
    }

    public function getCampaignMapping($company_id, $campaign_id) {
        $company_id = intval($company_id);
        $campaign_id = intval($campaign_id);

        $outbound_rule_id = null;
        $selected_did_ids = [];

        $q1 = mysqli_query(
            $this->conn,
            "SELECT outbound_rule_id
             FROM campaign_outbound_rule
             WHERE company_id = $company_id AND campaign_id = $campaign_id
             LIMIT 1"
        );

        if ($q1 && mysqli_num_rows($q1) > 0) {
            $r1 = mysqli_fetch_assoc($q1);
            $outbound_rule_id = intval($r1['outbound_rule_id']);
        }

        $q2 = mysqli_query(
            $this->conn,
            "SELECT did_id
             FROM campaign_did_map
             WHERE company_id = $company_id AND campaign_id = $campaign_id
             ORDER BY sort_order ASC, id ASC"
        );

        if ($q2) {
            while ($r2 = mysqli_fetch_assoc($q2)) {
                $selected_did_ids[] = intval($r2['did_id']);
            }
        }

        return [
            'outbound_rule_id' => $outbound_rule_id,
            'did_ids' => $selected_did_ids
        ];
    }

    public function saveCampaignMapping($company_id, $campaign_id, $outbound_rule_id, $did_ids) {
        $company_id = intval($company_id);
        $campaign_id = intval($campaign_id);
        $outbound_rule_id = intval($outbound_rule_id);

        if ($campaign_id <= 0 || $outbound_rule_id <= 0) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }

        $checkCamp = mysqli_query(
            $this->conn,
            "SELECT id FROM campaign WHERE id = $campaign_id AND company_id = $company_id AND is_deleted = 0 LIMIT 1"
        );
        if (!$checkCamp || mysqli_num_rows($checkCamp) === 0) {
            return ['success' => false, 'message' => 'Campaign not found for this company'];
        }

        mysqli_begin_transaction($this->conn);

        try {
            $sqlRule = "INSERT INTO campaign_outbound_rule (company_id, campaign_id, outbound_rule_id, last_used_map_id)
                        VALUES ($company_id, $campaign_id, $outbound_rule_id, NULL)
                        ON DUPLICATE KEY UPDATE outbound_rule_id = VALUES(outbound_rule_id), last_used_map_id = NULL, updated_at = UTC_TIMESTAMP()";
            if (!mysqli_query($this->conn, $sqlRule)) {
                throw new Exception(mysqli_error($this->conn));
            }

            $delSql = "DELETE FROM campaign_did_map WHERE company_id = $company_id AND campaign_id = $campaign_id";
            if (!mysqli_query($this->conn, $delSql)) {
                throw new Exception(mysqli_error($this->conn));
            }

            $pos = 0;
            foreach ($did_ids as $did_id_raw) {
                $did_id = intval($did_id_raw);
                if ($did_id <= 0) {
                    continue;
                }

                $checkDid = mysqli_query(
                    $this->conn,
                    "SELECT id FROM pbx_dids WHERE id = $did_id AND company_id = $company_id LIMIT 1"
                );
                if (!$checkDid || mysqli_num_rows($checkDid) === 0) {
                    continue;
                }

                $insMap = "INSERT INTO campaign_did_map (company_id, campaign_id, did_id, sort_order)
                           VALUES ($company_id, $campaign_id, $did_id, $pos)";
                if (!mysqli_query($this->conn, $insMap)) {
                    throw new Exception(mysqli_error($this->conn));
                }
                $pos++;
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'message' => 'Campaign DID mapping saved'];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'message' => 'Save failed: ' . $e->getMessage()];
        }
    }
}
?>
