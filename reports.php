<?php
include 'includes/db.php';

// --- SECURITY CHECK ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ensure Admin Role
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Logic: Fetch Sales Data
// Fetch sales data joined with vehicles and users
$sales = $conn->query("
    SELECT s.id, v.make, v.model, s.customer_name, s.amount, s.sale_date, u.username
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN users u ON s.sold_by = u.id
    ORDER BY s.sale_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette matched to Dashboard */
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

        /* --- CONTENT BOXES --- */
        .content-box {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        .content-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--dark); }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        /* --- TABLE STYLING --- */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th {
            text-align: left;
            padding: 12px;
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }
        td { padding: 16px 12px; border-bottom: 1px solid var(--border); color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
            text-transform: capitalize;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        /* Buttons */
        .btn-sm-danger {
            background-color: #fff;
            border: 1px solid #fee2e2;
            color: #ef4444;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-sm-danger:hover {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .btn-sm-success {
            background-color: #fff;
            border: 1px solid #dcfce7;
            color: #166534;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-sm-success:hover {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        /* User Avatar Small */
        .avatar-circle {
            width: 28px; height: 28px;
            background: #e0e7ff;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            margin-right: 8px;
        }
        .user-cell { display: flex; align-items: center; }

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
            .header-area { flex-direction: column; align-items: flex-start; gap: 15px; }
            .content-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .action-buttons { width: 100%; }
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
        <li><a href="dashboard.php"><i class="fa-solid fa-grid-2"></i> <span>Dashboard</span></a></li>
        <li><a href="vehicles.php"><i class="fa-solid fa-car"></i> <span>Vehicles</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>
        
        <!-- Admin Links -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- Active State -->
            <li><a href="reports.php" class="active"><i class="fa-solid fa-chart-pie"></i> <span>Report</span></a></li>
            <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Users</span></a></li>
            <li><a href="activity_log.php"><i class="fa-solid fa-sliders"></i> <span>Activity Log</span></a></li>
        <?php endif; ?>
    </ul>
    
    <div class="user-panel">
        <a href="logout.php" class="user-btn">
            <div>
                <span><i class="fa-solid fa-circle-user"></i> &nbsp; <?= htmlspecialchars($_SESSION['username']); ?></span>
                <br>
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
            <h1>Sales Reports</h1>
            <p>Overview of vehicle sales history and transactions.</p>
        </div>
        <div class="date-badge">
            <i class="fa-regular fa-calendar"></i> &nbsp; <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Log Table -->
    <div class="content-box">
        <div class="content-header">
            <h3><i class="fa-solid fa-file-invoice-dollar"></i> Transaction History</h3>
            
            <div class="action-buttons">
                <a href="export_excel.php" class="btn-sm-success">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <a href="export_pdf.php" class="btn-sm-danger">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th width="8%">ID</th>
                        <th width="25%">Vehicle</th>
                        <th width="20%">Customer</th>
                        <th width="15%">Sold By</th>
                        <th>Amount</th>
                        <th width="15%">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sales->num_rows > 0): ?>
                        <?php while ($row = $sales->fetch_assoc()): ?>
                            <?php 
                                // Initial for the "Sold By" user avatar
                                $initial = strtoupper(substr($row['username'], 0, 1));
                            ?>
                            <tr>
                                <td style="color: var(--text-muted);">#<?= htmlspecialchars($row['id']); ?></td>
                                
                                <td>
                                    <span style="font-weight: 600; color: var(--dark);">
                                        <?= htmlspecialchars($row['make'] . ' ' . $row['model']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="user-cell">
                                        <div class="avatar-circle" style="background: #f1f5f9; color: var(--text-muted);">
                                            <i class="fa-regular fa-user" style="font-size: 0.7rem;"></i>
                                        </div>
                                        <span><?= htmlspecialchars($row['customer_name']); ?></span>
                                    </div>
                                </td>

                                <td>
                                    <!-- Reusing the Avatar styling from Activity Log for the Agent -->
                                    <div class="user-cell">
                                        <div class="avatar-circle"><?= $initial; ?></div>
                                        <span><?= htmlspecialchars($row['username']); ?></span>
                                    </div>
                                </td>

                                <td>
                                    <span style="font-weight: 700; color: var(--primary-dark);">
                                        ₵ <?= number_format($row['amount'], 2); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="font-weight: 500; color: var(--text-main);">
                                        <?= date('M j, Y', strtotime($row['sale_date'])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p>No sales records found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>