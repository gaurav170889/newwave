<?php
require_once __DIR__ . '/../includes/variables.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = ConnectDB();
if (!$conn) {
    die('Connection failed.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<h3>Campaign notification setup</h3>';

$columns = [
    'notify_no_leads_email' => "ALTER TABLE `campaign` ADD COLUMN `notify_no_leads_email` TINYINT(1) NOT NULL DEFAULT 0 AFTER `webhook_token`",
    'notify_email' => "ALTER TABLE `campaign` ADD COLUMN `notify_email` VARCHAR(255) DEFAULT NULL AFTER `notify_no_leads_email`",
    'notify_email_sent_at' => "ALTER TABLE `campaign` ADD COLUMN `notify_email_sent_at` DATETIME DEFAULT NULL AFTER `notify_email`"
];

foreach ($columns as $column => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM `campaign` LIKE '{$column}'");
    if ($check->num_rows === 0) {
        $conn->query($sql);
        echo 'Added column: ' . $column . '<br>';
    } else {
        echo 'Column already exists: ' . $column . '<br>';
    }
}

$indexCheck = $conn->query("SHOW INDEX FROM `campaign` WHERE Key_name = 'idx_notify_no_leads_email'");
if ($indexCheck->num_rows === 0) {
    $conn->query("ALTER TABLE `campaign` ADD INDEX `idx_notify_no_leads_email` (`notify_no_leads_email`, `notify_email_sent_at`)");
    echo 'Added index: idx_notify_no_leads_email<br>';
} else {
    echo 'Index already exists: idx_notify_no_leads_email<br>';
}

echo '<h3>Setup complete.</h3>';
$conn->close();
