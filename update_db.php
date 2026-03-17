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

echo "<h3>Database patch complete!</h3>";
?>
