<?php
// includes/db.php
$servername = "	sql103.infinityfree.com";
$dbuser     = "	if0_42110527";
$dbpass     = "OyqUFv7j2N3TKd";
$dbname     = "if0_42110527_dealer_db";

$conn = new mysqli($servername, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    // stop and show a helpful message in dev; in production log instead
    die("Database connection failed: " . $conn->connect_error);
}
?>
