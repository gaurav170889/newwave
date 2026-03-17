<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
include_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// Helper to get input data
function get_input($method) {
    if ($method === 'GET') {
        return $_GET;
    } elseif ($method === 'POST') {
         $json = json_decode(file_get_contents('php://input'), true);
         if (is_array($json)) {
             return $json;
         }
         return $_POST; // Fallback to form-data
    }
    return [];
}

$input = get_input($method);

// 1. Identify Context (Token)
if (!isset($input['token'])) {
    // Legacy support or Error? Let's check for direct company_id only if token is missing
    if (isset($input['company_id'])) {
         // Legacy mode (Single generic hook not tied to question, or custom implementation)
         // For now, let's enforce token for the Multi-Q system or assume Q1 if just company_id provided?
         // User asked to "create distinct webhook", so we enforce Token.
         http_response_code(400);
         echo json_encode(['status' => 'error', 'message' => 'Missing webhook token']);
         exit;
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token required']);
    exit;
}

$token = $input['token'];
$callid = $input['callid'] ?? null;
$point = isset($input['point']) ? $input['point'] : null; // Allow null

if (!$callid) {
     http_response_code(400);
     echo json_encode(['status' => 'error', 'message' => 'Missing callid']);
     exit;
}

$conn = ConnectDB();

// 2. Validate Token & Get Company
$stmt = mysqli_prepare($conn, "SELECT company_id, question_number FROM rating_questions WHERE webhook_token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$q_data = mysqli_fetch_assoc($res);

if (!$q_data) {
     http_response_code(404);
     echo json_encode(['status' => 'error', 'message' => 'Invalid Token']);
     mysqli_close($conn);
     exit;
}

$company_id = $q_data['company_id'];
$question_num = $q_data['question_number']; // e.g., 1, 2, 3
$key_name = "q" . $question_num; // "q1", "q2"

// 3. Optional Data Points
$agentid = isset($input['agentid']) ? intval($input['agentid']) : 0;
// Map 'agent' (from user description) to 'agentno' if 'agentno' not explicitly set
$agentno = $input['agentno'] ?? ($input['agent'] ?? '');
$queue = $input['queue'] ?? '';
$callerno = $input['callerno'] ?? ($input['caller'] ?? ''); // Map 'caller' too

// 4. Check for Existing Submission
$check_sql = "SELECT rid, ratings_json, status FROM `rate` WHERE company_id = ? AND callid = ? LIMIT 1";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "is", $company_id, $callid);
mysqli_stmt_execute($check_stmt);
$check_res = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($check_res);

if ($existing) {
    // UPDATE
    $rid = $existing['rid'];
    $current_ratings = json_decode($existing['ratings_json'], true) ?? [];
    
    // Handle existing status: could be String (Legacy) or JSON
    $existing_status_raw = $existing['status'];
    $status_map = [];
    
    // Try to decode as JSON
    $decoded_status = json_decode($existing_status_raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_status)) {
        $status_map = $decoded_status;
    } else {
        // If legacy string (e.g. "Transferred" or "Rated"), map it to "q1" (assumption) or just start fresh
        // Let's assume if we have ratings_json keys, we can map them.
        if (!empty($current_ratings)) {
            foreach ($current_ratings as $k => $v) {
                $status_map[$k] = 'Rated';
            }
        }
    }

    // Only update ratings if point is provided
    if ($point !== null) {
        $current_ratings[$key_name] = $point;
        $status_map[$key_name] = 'Rated';
    } else {
        // If point is null (e.g. just transferred/hook hit), set status to Transferred if not already set or Rated
        // If it's already 'Rated', don't downgrade to 'Transferred' (unless that's desired behavior?)
        // Usually, if we hit this again with no point, it might be a re-transfer? 
        // Let's just set it to Transferred if it doesn't exist.
        if (!isset($status_map[$key_name])) {
            $status_map[$key_name] = 'Transferred';
        }
    }
    
    $new_json = json_encode($current_ratings);
    $new_status_json = json_encode($status_map);
    
    $update_sql = "UPDATE `rate` SET ratings_json = ?, status = ?";
    $types = "ss";
    $params = [$new_json, $new_status_json];
    
    if ($agentid) { $update_sql .= ", agentid = ?"; $types .= "i"; $params[] = $agentid; }
    if ($agentno) { $update_sql .= ", agentno = ?"; $types .= "s"; $params[] = $agentno; }
    if ($queue) { $update_sql .= ", queue = ?"; $types .= "s"; $params[] = $queue; }
    if ($callerno) { $update_sql .= ", callerno = ?"; $types .= "s"; $params[] = $callerno; }
    
    $update_sql .= " WHERE rid = ?";
    $types .= "i";
    $params[] = $rid;
    
    $up_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($up_stmt, $types, ...$params);
    mysqli_stmt_execute($up_stmt);
    
} else {
    // INSERT
    // If point provided, add it. If not, empty array.
    $ratings = ($point !== null) ? [$key_name => $point] : [];
    $json_str = json_encode($ratings);
    
    $current_status = ($point !== null) ? 'Rated' : 'Transferred';
    $status_map = [$key_name => $current_status];
    $status_json = json_encode($status_map);
    
    $ins_sql = "INSERT INTO `rate` (company_id, callid, agentid, agentno, queue, callerno, ratings_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $ins_stmt = mysqli_prepare($conn, $ins_sql);
    // types: i s i s s s s s
    mysqli_stmt_bind_param($ins_stmt, "isisssss", $company_id, $callid, $agentid, $agentno, $queue, $callerno, $json_str, $status_json);
    mysqli_stmt_execute($ins_stmt);
    $rid = mysqli_insert_id($conn);
    
    // Trigger Sentiment Check ONLY on Creation? Or on every update? 
    // Usually only needed once per call.
    
    // Check Settings
    $set_sql = "SELECT enable_sentiment, pbxurl, auth_token FROM pbxdetail WHERE company_id = ?";
    $set_stmt = mysqli_prepare($conn, $set_sql);
    mysqli_stmt_bind_param($set_stmt, "i", $company_id);
    mysqli_stmt_execute($set_stmt);
    $set_res = mysqli_stmt_get_result($set_stmt);
    $settings = mysqli_fetch_assoc($set_res);
    
    if ($settings && $settings['enable_sentiment'] == 1 && $settings['auth_token']) {
         $token_3cx = RefreshTokenIfNeeded($company_id);
         if ($token_3cx) {
             $analytics = Fetch3CXCallAnalytics($settings['pbxurl'], $callid, $token_3cx);
             if (isset($analytics) && !isset($analytics['error'])) {
                 $sent = $analytics['sentiment'] ?? null;
                 $tran = $analytics['transcript'] ?? null;
                 if ($sent || $tran) {
                     $up_an_sql = "UPDATE `rate` SET sentiment = ?, transcript = ? WHERE rid = ?";
                     $up_an_stmt = mysqli_prepare($conn, $up_an_sql);
                     mysqli_stmt_bind_param($up_an_stmt, "ssi", $sent, $tran, $rid);
                     mysqli_stmt_execute($up_an_stmt);
                 }
             }
         }
    }
}

echo json_encode(['status' => 'success', 'rid' => $rid ?? ($existing['rid'] ?? 0)]);
mysqli_close($conn);
?>
