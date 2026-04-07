<?php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$db = 'dialerwave';
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h3>Updating Database Schema</h3>";

$sql1 = "ALTER TABLE campaign ADD COLUMN dpd_filter_from INT DEFAULT NULL, ADD COLUMN dpd_filter_to INT DEFAULT NULL";
if (mysqli_query($conn, $sql1)) {
    echo "Columns dpd_filter_from and dpd_filter_to added to campaign successfully.<br>";
} else {
    echo "Error adding columns: " . mysqli_error($conn) . "<br>";
}

$sql2 = "CREATE TABLE IF NOT EXISTS campaign_run_filters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    campaign_id INT NOT NULL,
    dpd_from INT NULL,
    dpd_to INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql2)) {
    echo "Table campaign_run_filters created successfully.<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

$sql3 = "CREATE TABLE IF NOT EXISTS scheduled_calls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT NOT NULL,
    campaign_id INT NOT NULL,
    campaignnumber_id INT NOT NULL,
    route_type VARCHAR(20) NOT NULL DEFAULT 'Agent',
    queue_dn VARCHAR(50) DEFAULT NULL,
    agent_id INT DEFAULT NULL,
    agent_ext VARCHAR(50) DEFAULT NULL,
    scheduled_for DATETIME NOT NULL,
    timezone VARCHAR(100) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending_agent',
    source_module VARCHAR(50) NOT NULL DEFAULT 'dialednumbers',
    disposition_label VARCHAR(100) DEFAULT NULL,
    note_text TEXT DEFAULT NULL,
    zoho_schedule_id VARCHAR(100) DEFAULT NULL,
    zoho_activity_id VARCHAR(100) DEFAULT NULL,
    zoho_payload LONGTEXT DEFAULT NULL,
    meta_json LONGTEXT DEFAULT NULL,
    attempt_count INT NOT NULL DEFAULT 0,
    last_attempt_at DATETIME DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_company_status_time (company_id, status, scheduled_for),
    KEY idx_campaignnumber (campaignnumber_id),
    KEY idx_agent_schedule (agent_id, scheduled_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if (mysqli_query($conn, $sql3)) {
    echo "Table scheduled_calls created successfully.<br>";
} else {
    echo "Error creating scheduled_calls table: " . mysqli_error($conn) . "<br>";
}

$sql4 = "DELETE FROM scheduled_calls WHERE route_type <> 'Agent' OR source_module = 'backfill'";
if (mysqli_query($conn, $sql4)) {
    echo mysqli_affected_rows($conn) . " incorrect scheduled_calls row(s) cleaned up.<br>";
} else {
    echo "Error cleaning scheduled_calls rows: " . mysqli_error($conn) . "<br>";
}

echo "<h3>Database patch complete!</h3>";
?>
