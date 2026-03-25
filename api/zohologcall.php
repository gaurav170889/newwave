<?php
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'status' => 'error',
		'message' => 'Only POST requests are allowed'
	]);
	exit;
}

$expectedFields = [
	'callid',
	'callownerid',
	'callowner',
	'subject',
	'calltype',
	'purpose',
	'callstarttime',
	'createdtime',
	'callresult',
	'outcallstatus',
	'tonumber',
	'duration'
];

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawBody = file_get_contents('php://input');
$receivedData = [];
$logEntry = '';

if (!empty($_POST)) {
	$receivedData = $_POST;
	$logEntry = json_encode($receivedData, JSON_UNESCAPED_SLASHES);
} elseif ($rawBody !== false && trim($rawBody) !== '') {
	$decodedJson = json_decode($rawBody, true);
	if (is_array($decodedJson)) {
		$receivedData = $decodedJson;
	}
	$logEntry = $rawBody;
}

if ($logEntry === '') {
	http_response_code(400);
	echo json_encode([
		'status' => 'error',
		'message' => 'Empty POST body or form-data'
	]);
	exit;
}

$missingFields = [];
foreach ($expectedFields as $fieldName) {
	if (!array_key_exists($fieldName, $receivedData)) {
		$missingFields[] = $fieldName;
	}
}

$logFile = __DIR__ . '/zohologcall_' . date('Y-m-d') . '.txt';
$bytesWritten = file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);

if ($bytesWritten === false) {
	http_response_code(500);
	echo json_encode([
		'status' => 'error',
		'message' => 'Failed to write log file'
	]);
	exit;
}

echo json_encode([
	'status' => 'success',
	'message' => 'POST data logged successfully',
	'content_type' => $contentType,
	'received_as' => !empty($_POST) ? 'form-data' : 'raw-body',
	'received_fields' => array_keys($receivedData),
	'missing_fields' => $missingFields
]);
?>