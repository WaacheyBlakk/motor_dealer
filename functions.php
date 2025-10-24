<?php
include_once 'db.php';

function getDailySales($conn) {
    $date = date('Y-m-d');
    $query = "SELECT SUM(amount) as total FROM sales WHERE sale_date='$date'";
    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    return $data['total'] ?? 0;
}



function logActivity($username, $action) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_log (username, action) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $action);
    $stmt->execute();
}
?>

