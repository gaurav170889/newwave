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

function columnExists($conn, $table, $column)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $checkSql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
    $checkResult = mysqli_query($conn, $checkSql);

    return ($checkResult && mysqli_num_rows($checkResult) > 0);
}

function addColumnIfMissing($conn, $table, $column, $definition)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if (columnExists($conn, $table, $column)) {
        echo "Column {$table}.{$column} already exists.<br>";
        return;
    }

    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}";
    if (mysqli_query($conn, $sql)) {
        echo "Column {$table}.{$column} added successfully.<br>";
    } else {
        echo "Error adding {$table}.{$column}: " . mysqli_error($conn) . "<br>";
    }
}

function migrateLegacyNotifyEmailColumn($conn)
{
    $hasOldColumn = columnExists($conn, 'campaign', 'notify_to_leads_email');
    $hasNewColumn = columnExists($conn, 'campaign', 'notify_no_leads_email');

    if (!$hasOldColumn) {
        return;
    }

    if (!$hasNewColumn) {
        addColumnIfMissing($conn, 'campaign', 'notify_no_leads_email', 'TINYINT(1) NOT NULL DEFAULT 0');
        $hasNewColumn = columnExists($conn, 'campaign', 'notify_no_leads_email');
    }

    if ($hasNewColumn) {
        $sql = "UPDATE `campaign`
                SET `notify_no_leads_email` = CASE
                    WHEN COALESCE(`notify_no_leads_email`, 0) = 0 AND COALESCE(`notify_to_leads_email`, 0) <> 0
                    THEN `notify_to_leads_email`
                    ELSE `notify_no_leads_email`
                END";

        if (mysqli_query($conn, $sql)) {
            echo "Legacy campaign email flag migrated from notify_to_leads_email to notify_no_leads_email.<br>";
        } else {
            echo "Error migrating legacy campaign email flag: " . mysqli_error($conn) . "<br>";
        }
    }
}

addColumnIfMissing($conn, 'campaign', 'dpd_filter_from', 'INT DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'dpd_filter_to', 'INT DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'dn_number', 'VARCHAR(50) DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'dialer_mode', "VARCHAR(50) NOT NULL DEFAULT 'Power Dialer'");
addColumnIfMissing($conn, 'campaign', 'route_type', "VARCHAR(20) NOT NULL DEFAULT 'Queue'");
addColumnIfMissing($conn, 'campaign', 'concurrent_calls', 'INT NOT NULL DEFAULT 1');
addColumnIfMissing($conn, 'campaign', 'webhook_token', 'VARCHAR(255) DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'notify_no_leads_email', 'TINYINT(1) NOT NULL DEFAULT 0');
addColumnIfMissing($conn, 'campaign', 'notify_email', 'VARCHAR(255) DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'notify_email_sent_at', 'DATETIME DEFAULT NULL');
addColumnIfMissing($conn, 'campaign', 'updated_by', 'INT DEFAULT NULL');
migrateLegacyNotifyEmailColumn($conn);

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
