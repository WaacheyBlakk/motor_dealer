<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

$daily_sales = getDailySales($conn);
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard - Motor Dealer</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Welcome, <?php echo $_SESSION['username']; ?></h2>

<div class="dashboard">
    <div class="card">
        <h3>Daily Sales</h3>
        <p>₵<?php echo number_format($daily_sales, 2); ?></p>
    </div>
    <div class="card">
        <a href="sales.php">Make Sale</a>
    </div>
    <div class="card">
        <a href="reports.php">View Reports</a>
    </div>
    <div class="card">
        <a href="vehicles.php">Vehicles</a>
    </div>
    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div class="card">
        <a href="users.php">Manage Users</a>
    </div>
    <?php endif; ?>
</div>
<h3 style="margin-top:40px;">Sales Overview</h3>

<div style="width:80%;margin:auto;">
  <canvas id="dailySalesChart" height="100"></canvas>
  <canvas id="monthlySalesChart" height="100" style="margin-top:50px;"></canvas>
</div>


<a href="logout.php">Logout</a>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>
