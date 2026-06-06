<?php
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in
if(!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// --- ACCESS CONTROL & DELETE LOGIC ---

// Check Admin Status
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

// Check if a delete is requested
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // SCENARIO 1: ADMIN - Process Delete
    if ($isAdmin && $id) {
        // Get vehicle info for logging
        $stmt = $conn->prepare("SELECT make, model FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicle = $result->fetch_assoc();
        $vehicleName = ($vehicle) ? $vehicle['make'] . ' ' . $vehicle['model'] : 'Unknown Vehicle';
        $stmt->close();

        // Delete vehicle record
        $delStmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $delStmt->bind_param("i", $id);
        
        if ($delStmt->execute()) {
            if(function_exists('logActivity')) {
                logActivity("Deleted vehicle #$id ($vehicleName)");
            }
        }
        $delStmt->close();

        header("Location: vehicles.php?msg=deleted");
        exit();
    }

    // SCENARIO 2: NOT ADMIN - Show Access Denied Screen
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied | Motor Dealer</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --primary: #6366f1; --dark: #0f172a; --light: #f8fafc; --text-muted: #64748b; --border: #e2e8f0; }
            body { font-family: 'Inter', sans-serif; background-color: var(--light); margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
            .access-denied-box { background: white; padding: 40px; border-radius: 20px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid var(--border); }
            .lock-icon { font-size: 3rem; color: #ef4444; margin-bottom: 20px; background: #fee2e2; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 20px; }
            h2 { margin: 0 0 10px 0; color: var(--dark); }
            p { color: var(--text-muted); line-height: 1.6; margin-bottom: 25px; }
            .btn { background: var(--primary); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; display: inline-block; font-weight: 500; }
        </style>
    </head>
    <body>
        <div class="access-denied-box">
            <div class="lock-icon"><i class="fa-solid fa-lock"></i></div>
            <h2>Access Restricted</h2>
            <p>Sorry, only <strong>Administrators</strong> can delete vehicle records.</p>
            <a href="vehicles.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch all vehicles
$result = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette matched to Sales/Dashboard */
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
            align-items: center;
            margin-bottom: 30px;
        }
        .header-area h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--dark); }
        .header-area p { margin: 5px 0 0; color: var(--text-muted); }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-1px); }

        /* Content Box */
        .content-box {
            background: white;
            padding: 0; /* Padding handled in children */
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .content-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Search Input Styling */
        .search-wrapper {
            position: relative;
            width: 300px;
        }
        .search-wrapper input {
            width: 100%;
            padding: 10px 10px 10px 40px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--light);
            color: var(--text-main);
            font-family: inherit;
        }
        .search-wrapper input:focus {
            background: #fff;
            border-color: var(--primary);
            outline: none;
        }
        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Table */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead th {
            background: #f8fafc;
            padding: 16px 25px;
            color: var(--text-muted);
            font-size: 0.75rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            text-transform: uppercase;
        }
        tbody td {
            padding: 16px 25px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
            vertical-align: middle;
            color: var(--text-main);
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #f8fafc; }

        /* Badges & Actions */
        .badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }
        .badge-available { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-sold { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-unknown { background: #f1f5f9; color: var(--text-muted); }

        .action-btn {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
            margin-left: 6px;
        }

        /* New View Button Style */
        .btn-view { background: #ecfdf5; color: var(--secondary); }
        .btn-view:hover { background: var(--secondary); color: white; }

        .btn-edit { background: #eff6ff; color: var(--primary); }
        .btn-edit:hover { background: var(--primary); color: white; }
        
        .btn-delete { background: #fef2f2; color: var(--danger); }
        .btn-delete:hover { background: var(--danger); color: white; }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            font-weight: 500;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Responsive */
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
            .content-header { flex-direction: column; gap: 15px; align-items: stretch; }
            .search-wrapper { width: 100%; }
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
        
        <!-- Active State Applied Here -->
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
    
    <!-- Page Header -->
    <div class="header-area">
        <div>
            <h1>Vehicle Inventory</h1>
            <p>Manage stock, pricing, and status.</p>
        </div>
        <a href="add_vehicle.php" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Add Vehicle
        </a>
    </div>

    <!-- Notifications -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert">
            <i class="fa-solid fa-circle-check" style="margin-right: 10px;"></i>
            Vehicle successfully deleted from inventory.
        </div>
    <?php endif; ?>

    <!-- Table Container -->
    <div class="content-box">
        
        <!-- Toolbar inside Box -->
        <div class="content-header">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search make, model, or year...">
            </div>
            <div style="color: var(--text-muted); font-size: 0.9rem;">
                Total Vehicles: <strong><?= $result->num_rows; ?></strong>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table id="vehicleTable">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Make & Model</th>
                        <th>Year</th>
                        <th>Price (₵)</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): 
                        // Logic for badge color based on status text
                        $status = strtolower($row['status']);
                        $badgeClass = 'badge-unknown';
                        if (strpos($status, 'avail') !== false) $badgeClass = 'badge-available';
                        elseif (strpos($status, 'sold') !== false) $badgeClass = 'badge-sold';
                        elseif (strpos($status, 'pend') !== false) $badgeClass = 'badge-pending';
                    ?>
                    <tr>
                        <td style="color: var(--text-muted);">#<?= htmlspecialchars($row['id']); ?></td>
                        <td>
                            <div style="font-weight: 600; color: var(--dark);">
                                <?= htmlspecialchars($row['make']); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                <?= htmlspecialchars($row['model']); ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['year']); ?></td>
                        <td style="font-weight: 600;">₵<?= number_format($row['price'], 2); ?></td>
                        <td>
                            <span class="badge <?= $badgeClass; ?>"><?= ucfirst($row['status']); ?></span>
                        </td>
                        <td style="text-align: right;">
                            <a href="view_vehicle.php?id=<?= $row['id']; ?>" class="action-btn btn-view" title="View Description">
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            <a href="edit_vehicle.php?id=<?= $row['id']; ?>" class="action-btn btn-edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="delete_vehicle.php?id=<?= $row['id']; ?>"
                               class="action-btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this vehicle?');" 
                               title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if($result->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                <p>No vehicles found in inventory.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // Search Filter Logic
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let searchText = this.value.toLowerCase();
        let tableRows = document.querySelectorAll('#vehicleTable tbody tr');

        tableRows.forEach(row => {
            // Check inner text of row against search
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
</script>

</body>
</html>