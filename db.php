<?php
// includes/db.php
$servername = "localhost";
$dbuser     = "root";
$dbpass     = "";
$dbname     = "motor_dealer_db";

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    // stop and show a helpful message in dev; in production log instead
    die("Database connection failed: " . $conn->connect_error);
}
?>
