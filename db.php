<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // put your MySQL password if you set one
$dbname = 'motor_dealer_db';

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set character set
$conn->set_charset("utf8mb4");
?>
