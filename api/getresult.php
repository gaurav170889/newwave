<?php
date_default_timezone_set('Asia/Kolkata');
require_once "database.php";
global $conn;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = isset($_GET['result']) ? mysqli_real_escape_string($conn, $_GET['result']) : '';
$try = isset($_GET['try']) ? mysqli_real_escape_string($conn, $_GET['try']) : ''; // expected: calltry1, calltry2, calltry3

if ($id <= 0) {
    die("Error: Invalid ID provided.");
}
if (empty($result)) {
    die("Error: Result parameter is missing or empty.");
}
if (!in_array($try, ['calltry1', 'calltry2', 'calltry3'])) {
    die("Error: Invalid try parameter.");
}

// --- Build dynamic field name ---
$fieldName = $try . "status";

// --- Build SQL ---
$sql = "UPDATE campaignnumbers 
        SET `$fieldName` = '$result' 
        WHERE id = $id AND DATE(inserttime) = CURDATE()";

// --- Execute query ---
if (mysqli_query($conn, $sql)) {
    if (mysqli_affected_rows($conn) > 0) {
        error_log("Successfully updated ID $id with $fieldName = $result");
    } else {
        error_log("No record updated for ID $id (possibly wrong date or same value)");
    }
} else {
    error_log("Query failed: " . mysqli_error($conn));
}

// --- Close connection ---
mysqli_close($conn);

// --- Output success ---
echo "200";

?>