<?php
// Database credentials matching includes/functions.php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$db = 'addon3cx';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
CREATE TABLE IF NOT EXISTS dialer_queue_status (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT NOT NULL DEFAULT 0,
  campaign_id BIGINT NOT NULL DEFAULT 0,
  pbx_id BIGINT NOT NULL DEFAULT 0,
  queue_dn VARCHAR(20) NOT NULL,
  available_agents INT NOT NULL DEFAULT 0,

  -- store raw strings for debugging (since currently you only get type-name)
  loggedin_numlist_raw TEXT NULL,
  loggedin_extlist_raw TEXT NULL,
  raw_querystring TEXT NULL,

  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_campaign_queue (company_id, campaign_id, pbx_id, queue_dn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "Table dialer_queue_status created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
