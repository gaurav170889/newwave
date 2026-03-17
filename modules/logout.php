<?php 
session_start();
session_destroy(); // or  session_unset('session_name'); to dystroy individual session
header("Location: http://192.168.1.234/smartlife"); // to redirect user after logout
?>