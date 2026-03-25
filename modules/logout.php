<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/newwave/includes/variables.php');
session_start();
session_destroy();
header("Location: ".BASE_URL);
?>