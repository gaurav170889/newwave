<?php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$db = 'dialerwave';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = [];

$sql[] = "CREATE TABLE IF NOT EXISTS pbx_dids (
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

$sql[] = "CREATE TABLE IF NOT EXISTS campaign_outbound_rule (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  campaign_id INT NOT NULL,
  outbound_rule_id INT NOT NULL,
  last_used_map_id INT DEFAULT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_company_campaign (company_id, campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sql[] = "CREATE TABLE IF NOT EXISTS campaign_did_map (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  campaign_id INT NOT NULL,
  did_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_campaign_did (campaign_id, did_id),
  KEY idx_company_campaign (company_id, campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

foreach ($sql as $stmt) {
    $conn->query($stmt);
}

echo "DID tables created or already exist.";
$conn->close();
?>