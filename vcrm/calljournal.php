<?php
date_default_timezone_set("America/Chicago");
include_once('database.php');
global $connect;
$connect = $conn;

$tests = file_get_contents('php://input');
$journalDirectory = __DIR__ . '/journal/';
if (!is_dir($journalDirectory)) {
    mkdir($journalDirectory, 0775, true);
}
$get = file_put_contents($journalDirectory . date('Y-m-d') . "_journal_rawdata.txt", "Data is : " . $tests . PHP_EOL, FILE_APPEND);
$data= json_decode($tests,true);

if(!empty($data['phone']))
{
	//echo $data['phone'];
	$phone = $data['number'];
	$type   = $data['calltype'];
	$direction   = $data['direction'];
	$agent  = $data['agent'];
	$queue = $data['queue'];
	$agentfname  = $data['agentfname'];
	$agentlname  = $data['agentlname'];
	$duration   = $data['duration'];
	$datetime   = $data['stdatetime'];
	$starttime   = $data['endatetime'];
	
	http_response_code(200);
	
}


?>