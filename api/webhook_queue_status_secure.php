<?php
// Secure Webhook for Dialer Queue Status (Token Authenticated)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once '../includes/functions.php';

// ====== CONFIG (DB credentials from functions.php/environment) ======
// ConnectDB() from functions.php uses: localhost, root, root, addon3cx
$conn = ConnectDB(); 

// ====== INPUTS ======
$token = $_GET['token'] ?? '';
$queue_dn = trim($_GET['queue_dn'] ?? '');
$available = (int)($_GET['availableagent'] ?? 0);

// Raw debug data
$loogedinnumlist = $_GET['loogedinnumlist'] ?? '';
$loggedinextlist = $_GET['loggedinextlist'] ?? '';
$rawQueryString  = $_SERVER['QUERY_STRING'] ?? '';

// 1. Validate Input
if (empty($token)) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Missing token"]);
    exit;
}
if ($queue_dn === '') {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Missing queue_dn"]);
    exit;
}

// 2. Validate Token & Get Context (Company & PBX)
// We need company_id from campaign, and pbx_id from pbxdetail
$sql = "
    SELECT c.company_id, p.id as pbx_id
    FROM campaign c
    LEFT JOIN pbxdetail p ON c.company_id = p.company_id
    WHERE c.webhook_token = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$context = mysqli_fetch_assoc($res);

if (!$context) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Invalid token"]);
    exit;
}

$company_id = $context['company_id'];
$pbx_id = $context['pbx_id'] ? $context['pbx_id'] : 0; // Default to 0 if not found

// 3. Update Status (UPSERT)
// Using PDO logic from user request but adapted to mysqli since ConnectDB returns mysqli connection
// Or just use mysqli logic.
$insert_sql = "
    INSERT INTO dialer_queue_status
      (company_id, pbx_id, queue_dn, available_agents, loggedin_numlist_raw, loggedin_extlist_raw, raw_querystring, updated_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
      available_agents = VALUES(available_agents),
      loggedin_numlist_raw = VALUES(loggedin_numlist_raw),
      loggedin_extlist_raw = VALUES(loggedin_extlist_raw),
      raw_querystring = VALUES(raw_querystring),
      updated_at = NOW()
";

$upsert_stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($upsert_stmt, "iisisss", 
    $company_id, 
    $pbx_id, 
    $queue_dn, 
    $available, 
    $loogedinnumlist, 
    $loggedinextlist, 
    $rawQueryString
);

if (mysqli_stmt_execute($upsert_stmt)) {
    echo json_encode([
        "ok" => true,
        "company_id" => $company_id,
        "pbx_id" => $pbx_id,
        "queue" => $queue_dn,
        "available" => $available
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "ok" => false, 
        "error" => "DB Error", 
        "details" => mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
