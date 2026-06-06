<?php
include 'includes/auth.php'; // Keep your auth check
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// --- LOGIC: ADD/DELETE USERS ---
$msg = "";
$msgType = ""; // 'success' or 'error'

// 1. ADD USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $email, $password, $role);
        if ($stmt->execute()) {
            if(function_exists('logActivity')) logActivity("Added new user '$username'");
            header("Location: manage_users.php?msg=added");
            exit();
        } else {
            $msg = "Error adding user: " . $stmt->error;
            $msgType = "error";
        }
        $stmt->close();
    } else {
        $msg = "Database error: " . $conn->error;
        $msgType = "error";
    }
}

// 2. DELETE USER
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id == $_SESSION['user_id']) {
        $msg = "You cannot delete your own account.";
        $msgType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if(function_exists('logActivity')) logActivity("Deleted user ID: $id");
            header("Location: manage_users.php?msg=deleted");
            exit();
        } else {
            $msg = "Error deleting user.";
            $msgType = "error";
        }
        $stmt->close();
    }
}

// 3. HANDLE URL MESSAGES
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') {
        $msg = "User created successfully.";
        $msgType = "success";
    } elseif ($_GET['msg'] == 'deleted') {
        $msg = "User deleted successfully.";
        $msgType = "success";
    }
}

// 4. FETCH USERS
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Motor Dealer</title>
    
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

        /* --- SIDEBAR (Copied from Dashboard) --- */
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

        /* --- CONTENT BOXES (Reusing Dashboard style) --- */
        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 24px;
        }

        .content-box {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .content-header {
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }
        .content-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--dark); }

        /* --- FORM STYLING --- */
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            color: var(--text-main);
            transition: all 0.2s;
            background: #f8fafc;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn-primary {
            width: 100%;
            background-color: var(--primary);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.9rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { background-color: var(--primary-dark); }

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
        td { padding: 14px 12px; border-bottom: 1px solid var(--border); color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        /* Badges & Buttons */
        .badge-pill {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
        }
        .badge-admin { background-color: #e0e7ff; color: #4338ca; }
        .badge-sales { background-color: #d1fae5; color: #065f46; }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.2s;
            font-size: 0.8rem;
            margin-left: 4px;
        }
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #dbeafe; }
        .btn-del { background: #fef2f2; color: #ef4444; }
        .btn-del:hover { background: #fee2e2; }

        /* --- ALERTS --- */
        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span, .role-badge { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .nav-links a i { margin: 0; width: auto; font-size: 1.4rem; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
            .content-grid { grid-template-columns: 1fr; }
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
        <li><a href="sales.php"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>
        
        <!-- Admin Links -->
        <li><a href="reports.php"><i class="fa-solid fa-chart-pie"></i> <span>Report</span></a></li>
        <!-- Marked Active -->
        <li><a href="manage_users.php" class="active"><i class="fa-solid fa-users"></i> <span>Users</span></a></li>
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
    
    <div class="header-area">
        <div>
            <h1>User Management</h1>
            <p>Add, edit, or remove system access accounts.</p>
        </div>
        <div class="date-badge">
            <i class="fa-regular fa-calendar"></i> &nbsp; <?= date('l, F j, Y'); ?>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType; ?>">
            <?php if($msgType == 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php endif; ?>
            <?= htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="content-grid">
        
        <!-- Left Column: Add User Form -->
        <div class="content-box">
            <div class="content-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. john_doe" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@motordealer.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="sales">Sales Representative</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <button type="submit" name="add_user" class="btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
            </form>
        </div>

        <!-- Right Column: User List -->
        <div class="content-box">
            <div class="content-header">
                <h3><i class="fas fa-users"></i> Existing Users</h3>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User Details</th>
                            <th>Role</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color: var(--text-muted); font-weight: 500;">#<?= $user['id']; ?></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($user['username']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $roleClass = ($user['role'] === 'admin') ? 'badge-admin' : 'badge-sales';
                                    ?>
                                    <span class="badge-pill <?= $roleClass; ?>"><?= htmlspecialchars($user['role']); ?></span>
                                </td>
                                <td style="text-align:right">
                                    <a href="edit_user.php?id=<?= $user['id']; ?>" class="action-btn btn-edit" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="manage_users.php?delete=<?= $user['id']; ?>" 
                                       class="action-btn btn-del" 
                                       onclick="return confirm('Are you sure you want to permanently delete this user?');" 
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 2rem; color: var(--text-muted);">
                                    No users found in the system.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

</body>
</html>