<?php
$host ='localhost';
$dbname = 'uplift';
$username = 'uplift';
$password = 'TheUpLift123';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$username,$password);
	// Set PDO error mode to exception
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch (PDOException $e) {
	die("Connection failed: " . $e->getMessage());
}
?>