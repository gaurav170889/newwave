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

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM `campaign` LIKE 'webhook_token'");
if ($result->num_rows == 0) {
    // Add column
    $sql = "ALTER TABLE `campaign` ADD COLUMN `webhook_token` VARCHAR(100) DEFAULT NULL AFTER `concurrent_calls`";
    if ($conn->query($sql) === TRUE) {
        echo "Column webhook_token added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column webhook_token already exists.";
}

// Add index for performance
$indexCheck = $conn->query("SHOW INDEX FROM `campaign` WHERE Key_name = 'idx_webhook_token'");
if ($indexCheck->num_rows == 0) {
    $conn->query("ALTER TABLE `campaign` ADD INDEX `idx_webhook_token` (`webhook_token`)");
    echo " Index added.";
}

$conn->close();
?>
