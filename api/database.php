<?php
$servername = "localhost"; // Change this to your MySQL server address
$username = "u697766864_vantrap"; // Change this to your MySQL username
$password = "8Vg1m|e["; // Change this to your MySQL password
$database = "u697766864_vantrapdb"; // Change this to your MySQL database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
} 


?>
