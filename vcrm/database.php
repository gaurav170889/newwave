<?php
$servername='localhost';
$username='u697766864_vantrap';
$password='&Gi6hlDj=';
$dbname = "u697766864_vantrapdb";
$conn=mysqli_connect($servername,$username,$password,"$dbname");
if(!$conn){
   die('Could not Connect My Sql:' .mysql_error());
}
?>