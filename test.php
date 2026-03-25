<?php
$host = 'localhost';
$user = 'root';
$pass = 'root';
$db = 'dialerwave';
$conn = mysqli_connect($host, $user, $pass, $db);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('modules/campaign/modal.php');
$modal = new Campaign_modal();

// Simulate session variables
$_SESSION['pid'] = 1;

// Assuming campaign 1 exists based on the SQL dump where company 1 has a campaign or we can insert one
mysqli_query($conn, "INSERT INTO campaign (id, company_id, name, routeto) VALUES (999, 1, 'Test Campaign', 'test') ON DUPLICATE KEY UPDATE id=id");

// 1. First import
$res1 = $modal->importnumbersql(999, 'zoho_csv_file.csv');
print_r($res1);

echo "\n---\n";

// 2. Second import (should be skilled)
$res2 = $modal->importnumbersql(999, 'zoho_csv_file.csv');
print_r($res2);

echo "\n---\n";

// Verify DB logic
$q = mysqli_query($conn, "SELECT phone_e164, email_address, days_past_due FROM campaignnumbers WHERE campaignid=999");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
