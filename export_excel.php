<?php
include 'includes/db.php';

$stmt = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();

if (!$u || strtolower($u['role']) !== 'admin') {
    die("Access Denied: You do not have permission to export data.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=sales_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr>
<th>ID</th>
<th>Vehicle</th>
<th>Customer</th>
<th>Sold By</th>
<th>Amount (₵)</th>
<th>Date</th>
</tr>";

$result = $conn->query("
    SELECT s.id, v.make, v.model, s.customer_name, s.amount, s.sale_date, u.username
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN users u ON s.sold_by = u.id
");

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['make']} {$row['model']}</td>
        <td>{$row['customer_name']}</td>
        <td>{$row['username']}</td>
        <td>{$row['amount']}</td>
        <td>{$row['sale_date']}</td>
    </tr>";
}
echo "</table>";
?>
