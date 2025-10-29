<?php
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

// --- DAILY SALES TOTAL ---
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE(created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$todaySales = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;

// --- VEHICLE COUNTS ---
$totalVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles")->fetch_assoc()['count'];
$availableVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles WHERE status='available'")->fetch_assoc()['count'];
$soldVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles WHERE status='sold'")->fetch_assoc()['count'];

// --- SALES TREND (PAST 7 DAYS) ---
$salesData = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE(created_at) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total = $row['total_sales'] ?? 0;
    $salesData[] = (float)$total;
    $dates[] = date('M j', strtotime($date));
}

// --- MONTHLY SALES TREND (LAST 12 MONTHS) ---
$monthlySalesData = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total = $row['total_sales'] ?? 0;
    $monthlySalesData[] = (float)$total;
    $months[] = date('M Y', strtotime($month . '-01'));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f9;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }
        h2 {
            color: #1e3a8a;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: 0.3s ease;
        }
        .card:hover {
            transform: translateY(-3px);
        }
        .card h3 {
            font-size: 1em;
            color: #555;
        }
        .card .number {
            font-size: 2em;
            font-weight: 700;
            color: #1e40af;
        }
        .chart-container {
            margin-top: 50px;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            color: #666;
        }
    </style>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']); ?> 👋</h2>
    <p>Here’s an overview of today’s performance.</p>

    <!-- Dashboard Summary Cards -->
    <div class="dashboard-grid">
        <div class="card">
            <h3>💰 Today's Sales</h3>
            <div class="number counter" data-target="<?= $todaySales; ?>">0</div>
        </div>
        <div class="card">
            <h3>🚗 Total Vehicles</h3>
            <div class="number counter" data-target="<?= $totalVehicles; ?>">0</div>
        </div>
        <div class="card">
            <h3>🟢 Available Vehicles</h3>
            <div class="number counter" data-target="<?= $availableVehicles; ?>">0</div>
        </div>
        <div class="card">
            <h3>🔴 Sold Vehicles</h3>
            <div class="number counter" data-target="<?= $soldVehicles; ?>">0</div>
        </div>
    </div>

    <!-- Daily Sales Chart -->
    <div class="chart-container">
        <h3>📈 Sales in the Last 7 Days</h3>
        <canvas id="salesChart" height="90"></canvas>
    </div>

    <!-- Monthly Sales Chart -->
    <div class="chart-container">
        <h3>📊 Monthly Sales (Last 12 Months)</h3>
        <canvas id="monthlySalesChart" height="90"></canvas>
    </div>
</div>

<footer>
    &copy; <?= date('Y'); ?> Motor Dealer Management System
</footer>

<script>
/* --- Counter Animation --- */
const counters = document.querySelectorAll('.counter');
const speed = 150;
counters.forEach(counter => {
    const updateCount = () => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const inc = target / speed;
        if(count < target) {
            counter.innerText = Math.ceil(count + inc);
            setTimeout(updateCount, 15);
        } else {
            counter.innerText = target.toLocaleString();
        }
    };
    updateCount();
});

/* --- Chart.js Setup --- */
const ctx = document.getElementById('salesChart').getContext('2d');
const ctx2 = document.getElementById('monthlySalesChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(37, 99, 235, 0.4)');
gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates); ?>,
        datasets: [{
            label: 'Daily Sales (₵)',
            data: <?= json_encode($salesData); ?>,
            borderColor: '#2563eb',
            backgroundColor: gradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#1e3a8a'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => '₵ ' + context.parsed.y.toLocaleString()
                }
            }
        },
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months); ?>,
        datasets: [{
            label: 'Monthly Sales (₵)',
            data: <?= json_encode($monthlySalesData); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => '₵ ' + context.parsed.y.toLocaleString()
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { callback: value => '₵' + value }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>

</body>
</html>
