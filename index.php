<?php 
//file_put_contents("trace.txt", "Index.php loaded\n", FILE_APPEND);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log'); // Logs into project root
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Asia/Bangkok');

include("includes/variables.php");
include("includes/functions.php");
if(isset($_GET['type']))
{
	session_destroy();
	header("Location: " . rtrim(BASE_URL, '/'));
	exit();
}
require("modules/checkprivilege.php");

include("modules/login.php");


	


if(!isset($_SESSION['pid'])){
		$class = ucwords('login');
		if(class_exists($class))	{
			$myclass = new $class();
			$myclass->index() ; 
		}
	exit();
}

CheckPrivilege();

//mysqli_close();
?>
