<?php
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in (Consistent with vehicles.php)
if(!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Redirect if ID is missing
if (!isset($_GET['id'])) {
    header("Location: vehicles.php");
    exit();
}

// Fetch Vehicle Data
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Handle Not Found
if ($result->num_rows === 0) {
    header("Location: vehicles.php?msg=notfound");
    exit();
}

$vehicle = $result->fetch_assoc();

// Check Admin Status
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($vehicle['make'] . " " . $vehicle['model']); ?> | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette matched to Vehicles/Dashboard */
            --primary: #6366f1;       
            --primary-dark: #4f46e5;
            --secondary: #10b981;     
            --danger: #ef4444;
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

        /* --- SIDEBAR (Exact Copy) --- */
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

        /* Top Navigation / Breadcrumb */
        .top-bar {
            margin-bottom: 30px;
        }
        .btn-back {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .btn-back:hover { color: var(--primary); }

        /* Vehicle Card Layout */
        .vehicle-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
            display: grid;
            grid-template-columns: 45% 55%; /* Split view */
        }

        /* Left Side: Image */
        .image-section {
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
            position: relative;
            border-right: 1px solid var(--border);
        }
        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .no-image {
            color: #cbd5e1;
            text-align: center;
        }
        .no-image i { font-size: 4rem; margin-bottom: 15px; }
        
        /* Right Side: Details */
        .details-section { padding: 40px; display: flex; flex-direction: column; }
        
        .header-group {
            border-bottom: 1px solid var(--border);
            padding-bottom: 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .title-area h1 { margin: 0; font-size: 2rem; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
        .title-area .subtitle { color: var(--text-muted); font-size: 1.1rem; margin-top: 5px; font-weight: 500; }

        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            text-align: right;
            line-height: 1;
        }
        
        /* Badges */
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize; display: inline-block; }
        .badge-available { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-sold { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-unknown { background: #f1f5f9; color: var(--text-muted); border: 1px solid #e2e8f0; }

        /* Specs Grid */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        .spec-item {
            display: flex;
            flex-direction: column;
        }
        .spec-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; letter-spacing: 0.5px; margin-bottom: 6px; }
        .spec-value { font-size: 1.1rem; color: var(--dark); font-weight: 600; }

        /* Description */
        .description-box {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border);
            color: var(--text-main);
            line-height: 1.7;
            margin-bottom: 30px;
            flex-grow: 1;
        }
        .description-label { display: block; font-weight: 700; margin-bottom: 10px; color: var(--dark); font-size: 0.9rem; text-transform: uppercase; }

        /* Actions */
        .action-buttons { display: flex; gap: 15px; margin-top: auto; }
        .btn-action {
            padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 0.95rem;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s;
            flex: 1;
        }
        
        .btn-edit { background: var(--dark); color: white; border: 1px solid var(--dark); }
        .btn-edit:hover { background: #334155; transform: translateY(-1px); }
        
        .btn-delete { background: white; color: var(--danger); border: 1px solid #fee2e2; }
        .btn-delete:hover { background: #fef2f2; border-color: #fecaca; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span, .role-badge { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .nav-links a i { margin: 0; width: auto; font-size: 1.4rem; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
        @media (max-width: 900px) {
            .vehicle-card { grid-template-columns: 1fr; }
            .image-section { min-height: 350px; border-right: none; border-bottom: 1px solid var(--border); }
            .header-group { flex-direction: column; gap: 15px; }
            .price-tag { text-align: left; font-size: 1.8rem; }
            .action-buttons { flex-direction: column; }
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
        
        <!-- Vehicles Active State maintained since this is a child page of Vehicles -->
        <li><a href="vehicles.php" class="active"><i class="fa-solid fa-car"></i> <span>Vehicles</span></a></li>
        
        <li><a href="sales.php"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>

        <!-- Admin Links -->
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
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'])); ?></span>
            </div>
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    
    <!-- Top Navigation -->
    <div class="top-bar">
        <a href="vehicles.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Inventory
        </a>
    </div>

    <!-- Details Card -->
    <div class="vehicle-card">
        
        <!-- Left Column: Image -->
        <div class="image-section">
            <?php if (!empty($vehicle['image'])): ?>
                <!-- Ensure path is correct relative to this file -->
                <img src="<?= htmlspecialchars($vehicle['image']); ?>" alt="<?= htmlspecialchars($vehicle['make']); ?>">
            <?php else: ?>
                <div class="no-image">
                    <i class="fa-solid fa-image"></i>
                    <p>No Image Available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Details -->
        <div class="details-section">
            
            <div class="header-group">
                <div class="title-area">
                    <h1><?= htmlspecialchars($vehicle['make']); ?></h1>
                    <div class="subtitle"><?= htmlspecialchars($vehicle['model']); ?></div>
                </div>
                <div>
                    <div class="price-tag">₵<?= number_format($vehicle['price'], 2); ?></div>
                </div>
            </div>

            <!-- Specs Grid -->
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Year</span>
                    <span class="spec-value"><?= htmlspecialchars($vehicle['year']); ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Stock ID</span>
                    <span class="spec-value">#<?= htmlspecialchars($vehicle['id']); ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Status</span>
                    <span>
                        <?php 
                        // Badge Logic matched to vehicles.php
                        $status = strtolower($vehicle['status']);
                        $badgeClass = 'badge-unknown';
                        if (strpos($status, 'avail') !== false) $badgeClass = 'badge-available';
                        elseif (strpos($status, 'sold') !== false) $badgeClass = 'badge-sold';
                        elseif (strpos($status, 'pend') !== false) $badgeClass = 'badge-pending';
                        ?>
                        <span class="badge <?= $badgeClass; ?>"><?= ucfirst($vehicle['status']); ?></span>
                    </span>
                </div>
                <!-- Placeholder for Mileage or Color if added later -->
                <div class="spec-item">
                    <span class="spec-label">Category</span>
                    <span class="spec-value">Sedan/SUV</span> 
                </div>
            </div>

            <div class="description-box">
                <span class="description-label">Description</span>
                <?= nl2br(htmlspecialchars($vehicle['description'])); ?>
            </div>

            <div class="action-buttons">
                <a href="edit_vehicle.php?id=<?= $vehicle['id']; ?>" class="btn-action btn-edit">
                    <i class="fa-solid fa-pen"></i> Edit Vehicle
                </a>
                
                <?php if($isAdmin): ?>
                    <a href="vehicles.php?delete_id=<?= $vehicle['id']; ?>" 
                       class="btn-action btn-delete" 
                       onclick="return confirm('Are you sure you want to delete this record? This cannot be undone.');">
                        <i class="fa-solid fa-trash"></i> Delete
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

</main>

</body>
</html>