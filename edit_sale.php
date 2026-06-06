<?php
// --- Include core files ---
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure Admin Access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: sales.php?msg=Unauthorized+Access&type=error");
    exit();
}

$isAdmin = true; // For sidebar logic
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$msgType = "";

// --- Fetch Sale Details ---
$stmt = $conn->prepare("
    SELECT s.*, v.make, v.model, v.price as original_price 
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$sale = $result->fetch_assoc();

if (!$sale) {
    // Redirect if sale doesn't exist
    header("Location: sales.php?msg=Sale+not+found&type=error");
    exit();
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize
    $customer_name  = trim($_POST['customer_name']);
    $amount         = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $notes          = trim($_POST['notes']);

    // Validate
    if (empty($customer_name) || empty($amount) || empty($payment_method)) {
        $msg = "Please fill in all required fields.";
        $msgType = "error";
    } else {
        // Update
        $update = $conn->prepare("
            UPDATE sales 
            SET customer_name=?, amount=?, payment_method=?, notes=? 
            WHERE id=?
        ");
        $update->bind_param("sdssi", $customer_name, $amount, $payment_method, $notes, $id);
        
        if ($update->execute()) {
            if(function_exists('logActivity')) {
                logActivity("Edited sale #$id (Customer: $customer_name)");
            }
            // Redirect with success message
            header("Location: sales.php?msg=Sale+updated+successfully&type=success");
            exit;
        } else {
            $msg = "Database error: Could not update sale.";
            $msgType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sale #<?= $id; ?> | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1;       
            --primary-dark: #4f46e5;
            --secondary: #10b981;     
            --dark: #0f172a;          
            --light: #f8fafc;         
            --text-main: #1e293b;     
            --text-muted: #64748b;    
            --border: #e2e8f0;        
            --sidebar-width: 260px;
            --radius: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
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

        /* --- SIDEBAR (Consistent with Sales Page) --- */
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
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex: 1; }
        .nav-links li { margin-bottom: 5px; }
        .nav-links a {
            display: flex; align-items: center; padding: 12px 20px;
            color: var(--text-muted); text-decoration: none; border-radius: 12px;
            font-weight: 500; font-size: 0.95rem; transition: all 0.2s ease;
        }
        .nav-links a:hover { background-color: #f1f5f9; color: var(--primary); }
        .nav-links a.active { background-color: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25); }
        .nav-links a i { width: 25px; font-size: 1.1rem; }
        .user-panel { padding: 25px; border-top: 1px solid var(--border); }
        .user-btn { display: flex; align-items: center; justify-content: space-between; color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .role-badge { font-size: 0.7rem; background: var(--light); padding: 2px 8px; border-radius: 4px; color: var(--text-muted); border: 1px solid var(--border); margin-top: 2px; display: inline-block; text-transform: uppercase; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            justify-content: center; /* Center the edit form */
        }

        .edit-card {
            background: white;
            width: 100%;
            max-width: 600px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fcfcfc;
        }
        .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--dark); font-weight: 700; }
        .id-badge { background: var(--primary); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }

        .card-body { padding: 30px; }

        /* Info Box for Vehicle */
        .info-box {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .info-icon {
            width: 40px; height: 40px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); border: 1px solid var(--border);
        }
        .info-content h4 { margin: 0 0 2px 0; font-size: 0.95rem; color: var(--dark); }
        .info-content p { margin: 0; font-size: 0.85rem; color: var(--text-muted); }

        /* Form Controls */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: var(--text-main); }
        .form-control {
            width: 100%; padding: 12px 16px;
            border: 1px solid var(--border); border-radius: 12px;
            background: var(--light); color: var(--text-main);
            font-family: inherit; font-size: 0.95rem; transition: all 0.2s;
        }
        .form-control:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        /* Buttons */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn {
            padding: 12px 20px; border-radius: 12px; font-weight: 600;
            cursor: pointer; font-size: 0.95rem; border: none; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.2s; flex: 1;
        }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        
        .btn-secondary { background: white; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-secondary:hover { background: #f1f5f9; color: var(--text-main); }

        /* Alert */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span, .role-badge { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
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
        <!-- Active class for Sales -->
        <li><a href="sales.php" class="active"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>
        
        <!-- Admin Links -->
        <li><a href="reports.php"><i class="fa-solid fa-chart-pie"></i> <span>Report</span></a></li>
        <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Users</span></a></li>
        <li><a href="activity_log.php"><i class="fa-solid fa-sliders"></i> <span>Activity Log</span></a></li>
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

    <div class="edit-card">
        <!-- Card Header -->
        <div class="card-header">
            <div>
                <h2>Edit Sale</h2>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Update transaction details below</span>
            </div>
            <span class="id-badge">ID: #<?= str_pad($sale['id'], 4, '0', STR_PAD_LEFT); ?></span>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            
            <?php if(!empty($msg)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <!-- Read-only Vehicle Context -->
            <div class="info-box">
                <div class="info-icon">
                    <i class="fa-solid fa-car"></i>
                </div>
                <div class="info-content">
                    <h4><?= htmlspecialchars($sale['make'] . ' ' . $sale['model']); ?></h4>
                    <p>Vehicle associated with this transaction</p>
                </div>
            </div>

            <!-- Form -->
            <form method="POST">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" 
                           value="<?= htmlspecialchars($sale['customer_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="amount">Sale Amount (₵)</label>
                    <input type="number" id="amount" name="amount" step="0.01" class="form-control" 
                           value="<?= htmlspecialchars($sale['amount']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <?php
                        $methods = ['Cash', 'Credit Card', 'Bank Transfer', 'Mobile Money'];
                        foreach ($methods as $method) {
                            $selected = ($sale['payment_method'] === $method) ? 'selected' : '';
                            echo "<option value='$method' $selected>$method</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="form-control"><?= htmlspecialchars($sale['notes']); ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="sales.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-regular fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

</body>
</html>