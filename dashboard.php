<?php
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role'])) {
    $uStmt = $conn->prepare("SELECT role FROM users WHERE username = ? LIMIT 1");
    $uStmt->bind_param("s", $_SESSION['username']);
    $uStmt->execute();
    $uResult = $uStmt->get_result();
    
    if ($uRow = $uResult->fetch_assoc()) {
        $_SESSION['role'] = $uRow['role'];
    } else {
        $_SESSION['role'] = 'sales'; 
    }
}


$isAdmin = (strtolower($_SESSION['role']) === 'admin');

// --- 3. DATA FETCHING ---

// A. Daily Sales Total
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE(created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$todaySales = $stmt->get_result()->fetch_assoc()['total_sales'] ?? 0;

// B. Vehicle Counts
$totalVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles")->fetch_assoc()['count'];
$availableVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles WHERE status='available'")->fetch_assoc()['count'];
$soldVehicles = $conn->query("SELECT COUNT(*) AS count FROM vehicles WHERE status='sold'")->fetch_assoc()['count'];

// C. Sales Trend (Last 7 Days)
$salesData = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE(created_at) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $salesData[] = (float)($row['total_sales'] ?? 0); 
    $dates[] = date('D', strtotime($date)); 
}

// D. Monthly Sales Trend
$monthlySalesData = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_sales FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $monthlySalesData[] = (float)($row['total_sales'] ?? 0);
    $months[] = date('M', strtotime($month . '-01')); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            /* Palette */
            --primary: #6366f1;       
            --primary-dark: #4f46e5;
            --secondary: #10b981;     
            --dark: #0f172a;          
            --light: #f8fafc;         
            --text-main: #1e293b;     
            --text-muted: #64748b;    
            --border: #e2e8f0;        
            
            /* Dimensions */
            --sidebar-width: 260px;
            
            /* Effects */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 16px;
        }

        * { box-sizing: border-box; outline: none; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 50;
        }

        .brand {
            height: 80px;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        .brand i { margin-right: 10px; font-size: 1.4rem; }

        .nav-links {
            list-style: none;
            padding: 20px 15px;
            margin: 0;
            flex: 1;
        }

        .nav-links li { margin-bottom: 5px; }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .nav-links a:hover {
            background-color: #f1f5f9;
            color: var(--primary);
        }

        .nav-links a.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .nav-links a i { width: 25px; font-size: 1.1rem; }

        .user-panel {
            padding: 25px;
            border-top: 1px solid var(--border);
        }
        
        .user-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .user-btn:hover { color: var(--primary); }
        
        .role-badge {
            font-size: 0.7rem;
            background: var(--light);
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--text-muted);
            border: 1px solid var(--border);
            margin-top: 2px;
            display: inline-block;
            text-transform: uppercase;
        }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .header-area {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-bottom: 40px;
        }
        .header-area h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--dark); }
        .header-area p { margin: 5px 0 0; color: var(--text-muted); }
        
        .date-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        /* --- STAT CARDS --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-content h3 { margin: 0 0 10px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-content .number { font-size: 1.75rem; font-weight: 800; color: var(--dark); letter-spacing: -1px; }

        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }

        /* Card Themes */
        .card-1 .stat-icon { background: #e0e7ff; color: var(--primary); }
        .card-2 .stat-icon { background: #dbeafe; color: #2563eb; } 
        .card-3 .stat-icon { background: #dcfce7; color: var(--secondary); } 
        .card-4 .stat-icon { background: #fee2e2; color: #ef4444; } 

        /* --- CHARTS SECTION --- */
        .charts-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 24px;
        }

        .chart-box {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .chart-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--dark); }

        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span, .role-badge { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .nav-links a i { margin: 0; width: auto; font-size: 1.4rem; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .charts-wrapper { grid-template-columns: 1fr; }
            .header-area { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-layer-group"></i>
        <span>AutoDesk</span>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php" class="active"><i class="fa-solid fa-grid-2"></i> <span>Dashboard</span></a></li>
        <li><a href="vehicles.php"><i class="fa-solid fa-car"></i> <span>Vehicles</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>
        
        <!-- Only show Reports and Activity Log if user is Admin -->
        <?php if ($isAdmin): ?>
            <li><a href="reports.php"><i class="fa-solid fa-chart-pie"></i> <span>Report</span></a></li>
            <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Users</span></a></li>
            <li><a href="activity_log.php"><i class="fa-solid fa-sliders"></i> <span>Activity Log</span></a></li>
        <?php endif; ?>
    </ul>
    
    <div class="user-panel">
        <a href="logout.php" class="user-btn">
            <div>
                <span><i class="fa-solid fa-circle-user"></i> &nbsp; <?= htmlspecialchars($_SESSION['username']); ?></span>
                <br>
                <!-- Visual feedback for the user role -->
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'])); ?></span>
            </div>
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    
    <div class="header-area">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back, here is your dealership's daily activity.</p>
        </div>
        <div class="date-badge">
            <i class="fa-regular fa-calendar"></i> &nbsp; <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card card-1">
            <div class="stat-content">
                <h3>Today's Revenue</h3>
                <div class="number">₵<span class="counter" data-target="<?= $todaySales; ?>">0</span></div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
        </div>
        
        <div class="stat-card card-2">
            <div class="stat-content">
                <h3>Total Inventory</h3>
                <div class="number counter" data-target="<?= $totalVehicles; ?>">0</div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-car-side"></i></div>
        </div>
        
        <div class="stat-card card-3">
            <div class="stat-content">
                <h3>Available</h3>
                <div class="number counter" data-target="<?= $availableVehicles; ?>">0</div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-check"></i></div>
        </div>
        
        <div class="stat-card card-4">
            <div class="stat-content">
                <h3>Sold Units</h3>
                <div class="number counter" data-target="<?= $soldVehicles; ?>">0</div>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-wrapper">
        
        <!-- Line Chart -->
        <div class="chart-box">
            <div class="chart-header">
                <h3>Revenue Trends (7 Days)</h3>
                <i class="fa-solid fa-ellipsis" style="color:#cbd5e1; cursor:pointer;"></i>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Bar Chart -->
        <div class="chart-box">
            <div class="chart-header">
                <h3>Monthly Performance</h3>
                <i class="fa-solid fa-ellipsis" style="color:#cbd5e1; cursor:pointer;"></i>
            </div>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>

    </div>
</main>

<script>
    // --- Counter Animation ---
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText.replace(/,/g, '');
            const inc = Math.max(1, target / 100); 
            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 15);
            } else {
                counter.innerText = target.toLocaleString();
            }
        };
        updateCount();
    });

    // --- Global Chart Settings ---
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#94a3b8';
    
    // Tooltip Styling (Modern)
    const modernTooltip = {
        backgroundColor: '#1e293b',
        titleColor: '#f8fafc',
        bodyColor: '#f8fafc',
        padding: 12,
        cornerRadius: 8,
        displayColors: false,
        titleFont: { weight: '600', size: 13 },
        bodyFont: { weight: '500', size: 13 },
        callbacks: {
            label: (ctx) => 'Sales: ₵ ' + ctx.parsed.y.toLocaleString()
        }
    };

    // --- Line Chart (Gradient & Curves) ---
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Create Fade Gradient
    const gradientFill = ctx.createLinearGradient(0, 0, 0, 350);
    gradientFill.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); 
    gradientFill.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates); ?>,
            datasets: [{
                label: 'Sales',
                data: <?= json_encode($salesData); ?>,
                borderColor: '#6366f1',
                backgroundColor: gradientFill,
                borderWidth: 3,
                tension: 0.4, 
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: modernTooltip
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#f1f5f9', borderDash: [5, 5] }, 
                    ticks: { callback: val => '₵' + val }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });

    // --- Bar Chart (Rounded) ---
    const ctx2 = document.getElementById('monthlySalesChart').getContext('2d');
    
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($monthlySalesData); ?>,
                backgroundColor: '#10b981',
                borderRadius: 6, 
                barPercentage: 0.6,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: modernTooltip
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#f1f5f9', borderDash: [5, 5] },
                    ticks: { callback: val => '₵' + val }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
</script>

</body>
</html>