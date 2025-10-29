<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: sales.php?error=unauthorized");
    exit();
}


$id = $_GET['id'] ?? 0;

if ($id) {
    // Get sale for logging
    $sale = $conn->query("SELECT customer_name FROM sales WHERE id=$id")->fetch_assoc();
    $customer = $sale['customer_name'] ?? '';

    // Delete sale record
    $conn->query("DELETE FROM sales WHERE id=$id");

    logActivity("Deleted sale #$id ($customer)");
}

header("Location: sales.php?msg=Sale+deleted+successfully");
exit;
