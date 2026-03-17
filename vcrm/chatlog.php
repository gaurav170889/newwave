<?php
date_default_timezone_set("Asia/Calcutta");
$tests = file_get_contents('php://input');
$get= file_put_contents($_SERVER['DOCUMENT_ROOT']."/epicpc/crm/".date('Y-m-d')."_chatlogdata.txt","Data is : " .$tests.PHP_EOL,FILE_APPEND);;
//$data= json_decode($tests,true);
http_response_code(200);

?>