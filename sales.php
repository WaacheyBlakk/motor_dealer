<?php
// --- Include core files ---
include 'includes/auth.php'; 
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check role for sidebar logic and button visibility
$isAdmin = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');

$msg = "";
$msgType = ""; 

// --- Handle Sale Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Sanitize Inputs
    $vehicle_id     = intval($_POST['vehicle_id'] ?? 0);
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $notes          = trim($_POST['notes'] ?? '');
    $sold_by        = $_SESSION['user_id'] ?? 0;

    // 2. Validation
    if (empty($vehicle_id) || empty($customer_name) || empty($payment_method)) {
        $msg = "Please fill in all required fields.";
        $msgType = "error";
    } 
    // Validate Name (Alphabets and spaces only)
    elseif (!preg_match("/^[a-zA-Z\s]+$/", $customer_name)) {
        $msg = "Customer name must only contain letters and spaces.";
        $msgType = "error";
    } 
    else {
        $conn->begin_transaction();
        try {
            // 3. Security: Fetch actual price from DB (ignore user input)
            $priceStmt = $conn->prepare("SELECT price, status FROM vehicles WHERE id = ?");
            $priceStmt->bind_param("i", $vehicle_id);
            $priceStmt->execute();
            $res = $priceStmt->get_result();
            
            if ($res->num_rows === 0) {
                throw new Exception("Vehicle not found.");
            }
            
            $vehicleData = $res->fetch_assoc();
            
            if ($vehicleData['status'] !== 'available') {
                throw new Exception("This vehicle is already sold.");
            }

            $real_amount = $vehicleData['price']; 

            // 4. Insert Sale
            $stmt = $conn->prepare("INSERT INTO sales (vehicle_id, sold_by, customer_name, amount, payment_method, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissss", $vehicle_id, $sold_by, $customer_name, $real_amount, $payment_method, $notes);
            $stmt->execute();

            // 5. Update Vehicle Status
            $updateStmt = $conn->prepare("UPDATE vehicles SET status='sold' WHERE id=?");
            $updateStmt->bind_param("i", $vehicle_id);
            $updateStmt->execute();

            $conn->commit();
            $msg = "Sale recorded successfully!";
            $msgType = "success";
            
            // Reset POST so form clears
            $_POST = array(); 
            
            if(function_exists('logActivity')) {
                logActivity("Made a sale to $customer_name for ₵$real_amount");
            }

        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error recording sale: " . $e->getMessage();
            $msgType = "error";
        }
    }
}

// --- Fetch data ---
$vehicles = $conn->query("SELECT id, make, model, price FROM vehicles WHERE status='available'");
$sales = $conn->query("
    SELECT s.id, v.make, v.model, s.customer_name, s.amount, s.payment_method, s.created_at 
    FROM sales s 
    JOIN vehicles v ON s.vehicle_id = v.id 
    ORDER BY s.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette matched to Source */
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
            height: 100%;
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

        /* --- LAYOUT GRID FOR SALES PAGE --- */
        .sales-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* --- FORM STYLING (Matched to Design) --- */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-main);
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--light);
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        /* Readonly Amount Field */
        #amount {
            background-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
            pointer-events: none; 
            font-weight: 600;
        }

        .btn-submit {
            padding: 14px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: white;
            background-color: var(--primary);
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 0.95rem;
            display: flex; justify-content: center; align-items: center; gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .btn-submit:hover { background-color: var(--primary-dark); transform: translateY(-1px); }

        /* --- ALERTS --- */
        .alert { 
            padding: 15px 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

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
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
            text-transform: capitalize;
            background: #f1f5f9; color: #475569;
        }
        .badge-green { background: #dcfce7; color: #166534; }

        /* --- ADMIN ACTION BUTTONS --- */
        .btn-action {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            margin-right: 4px;
            font-size: 0.8rem;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-edit { background-color: #f59e0b; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3); } /* Amber */
        .btn-delete { background-color: #ef4444; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3); } /* Red */
        .btn-action:hover { opacity: 0.85; transform: translateY(-1px); }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span, .role-badge { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .nav-links a i { margin: 0; width: auto; font-size: 1.4rem; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .sales-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
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
        <li><a href="dashboard.php"><i class="fa-solid fa-grid-2"></i> <span>Dashboard</span></a></li>
        <li><a href="vehicles.php"><i class="fa-solid fa-car"></i> <span>Vehicles</span></a></li>
        <!-- Active class applied here -->
        <li><a href="sales.php" class="active"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>
        
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
    
    <div class="header-area">
        <div>
            <h1>Sales Management</h1>
            <p>Process new sales and view recent transaction history.</p>
        </div>
        <div class="date-badge">
            <i class="fa-regular fa-calendar"></i> &nbsp; <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(!empty($msg)): ?>
        <div class="alert <?= ($msgType == 'success') ? 'alert-success' : 'alert-error'; ?>">
            <i class="fa-solid <?= ($msgType == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <?= htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Grid Layout -->
    <div class="sales-grid">
        
        <!-- Left Column: New Sale Form -->
        <div class="content-box">
            <div class="content-header">
                <h3><i class="fa-solid fa-cart-plus"></i> New Sale</h3>
            </div>
            
            <form method="POST" action="sales.php">
                <div class="form-group">
                    <label for="vehicle">Select Vehicle</label>
                    <select id="vehicle" name="vehicle_id" class="form-control" required>
                        <option value="" data-price="">-- Choose Vehicle --</option>
                        <?php 
                        if($vehicles && $vehicles->num_rows > 0) {
                            while ($v = $vehicles->fetch_assoc()): 
                                $selected = (isset($_POST['vehicle_id']) && $_POST['vehicle_id'] == $v['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $v['id']; ?>" data-price="<?= $v['price']; ?>" <?= $selected ?>>
                                <?= htmlspecialchars($v['make'] . ' ' . $v['model']); ?> 
                                (₵<?= number_format($v['price'], 2); ?>)
                            </option>
                        <?php 
                            endwhile; 
                        } else {
                            echo '<option disabled>No stock available</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" 
                           value="<?= htmlspecialchars($_POST['customer_name'] ?? ''); ?>"
                           required pattern="[a-zA-Z\s]+" title="Letters only" placeholder="Full Name">
                </div>

                <div class="form-group">
                    <label for="amount">Sale Amount (₵)</label>
                    <input type="text" id="amount" name="amount" class="form-control" readonly tabindex="-1">
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="">Select Method</option>
                        <?php
                        $methods = ['Cash', 'Credit Card', 'Bank Transfer', 'Mobile Money'];
                        foreach($methods as $method) {
                            $sel = (isset($_POST['payment_method']) && $_POST['payment_method'] == $method) ? 'selected' : '';
                            echo "<option value='$method' $sel>$method</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Additional details..."><?= htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    Complete Sale <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Right Column: Recent Sales Table -->
        <div class="content-box">
            <div class="content-header">
                <h3><i class="fa-solid fa-file-invoice-dollar"></i> Recent Transactions</h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <!-- Added Actions Header for Admin -->
                            <?php if ($isAdmin): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sales && $sales->num_rows > 0): ?>
                            <?php while($row = $sales->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: var(--dark);">
                                        <?= htmlspecialchars($row['make'] . ' ' . $row['model']); ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fa-regular fa-user" style="color: var(--text-muted); font-size: 0.8rem; margin-right: 5px;"></i>
                                    <?= htmlspecialchars($row['customer_name']); ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary-dark);">
                                        ₵ <?= number_format($row['amount'], 2); ?>
                                    </span>
                                </td>
                                <td><span class="badge"><?= htmlspecialchars($row['payment_method']); ?></span></td>
                                <td><?= date('M j, Y', strtotime($row['created_at'])); ?></td>
                                
                                <!-- Added Action Buttons for Admin -->
                                <?php if ($isAdmin): ?>
                                <td>
                                    <a href="edit_sale.php?id=<?= $row['id']; ?>" class="btn-action btn-edit" title="Edit Sale">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="delete_sale.php?id=<?= $row['id']; ?>" class="btn-action btn-delete" title="Delete Sale" onclick="return confirm('Are you sure you want to delete this sale? This action cannot be undone.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? '6' : '5'; ?>" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                    <p>No sales records found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</main>

<script>
    // 1. Vehicle Selection - Auto-populate Price
    function updatePrice() {
        const select = document.getElementById('vehicle');
        const amountInput = document.getElementById('amount');
        
        if (!select || !amountInput) return;

        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption) return;

        const price = selectedOption.getAttribute('data-price');
        amountInput.value = price ? price : '';
    }

    const vehicleSelect = document.getElementById('vehicle');
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', updatePrice);
        updatePrice(); 
    }

    // 2. Customer Name - Enforce Alphabets Only
    const nameInput = document.getElementById('customer_name');
    if (nameInput) {
        nameInput.addEventListener('input', function(e) {
            let cleanValue = this.value.replace(/[^a-zA-Z\s]/g, '');
            if (cleanValue !== this.value) {
                this.value = cleanValue;
            }
        });
    }
</script>

</body>
</html>