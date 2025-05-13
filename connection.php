<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "pnhs";

$conn = new mysqli($servername, $username, $password, $database);

if($conn->connect_error){
  die("Connection failed: " . $conn->connect_error);
}
?>