<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "marketplace";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
  die("Koneksi gagal: " . $conn->connect_error);
}

mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if(!isset($_SESSION['keranjang'])){
  $_SESSION['keranjang'] = [];
}
?>