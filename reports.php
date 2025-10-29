<?php
include 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$sales = $conn->query("
    SELECT s.id, v.make, v.model, s.customer_name, s.amount, s.sale_date, u.username
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN users u ON s.sold_by = u.id
    ORDER BY s.sale_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Reports - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Sales Reports</h2>
    <a href="export_excel.php" class="btn">📊 Export to Excel</a>
    <a href="export_pdf.php" class="btn">📄 Export to PDF</a>
    <br><br>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <tr>
            <th>ID</th>
            <th>Vehicle</th>
            <th>Customer</th>
            <th>Sold By</th>
            <th>Amount (₵)</th>
            <th>Date</th>
        </tr>

        <?php while($row = $sales->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['make'].' '.$row['model']); ?></td>
            <td><?= htmlspecialchars($row['customer_name']); ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= number_format($row['amount'], 2); ?></td>
            <td><?= $row['sale_date']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
