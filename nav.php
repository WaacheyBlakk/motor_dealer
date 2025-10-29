<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        /* === Global Navigation Styles === */
        nav {
            background: linear-gradient(90deg, #2c3e50, #34495e);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ecf0f1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            flex-wrap: wrap;
        }

        /* Left Section (links) */
        .nav-links {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s, transform 0.2s;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .nav-links a:hover {
            color: #f1c40f;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Right Section (user info) */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .user-info strong {
            color: #f1c40f;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links {
                flex-direction: column;
                width: 100%;
            }
            .user-info {
                margin-top: 10px;
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-links">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="view_vehicles.php">📦 View Vehicles</a>
        <a href="sales.php">💰 Sales</a>
        <a href="reports.php">Reports</a>
        <a href="activity_log.php">🧾 Activity Log</a>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="manage_users.php">👤 Manage Users</a>
        <?php endif; ?>
    </div>

    <div class="user-info">
        👋 <span>Logged in as <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> (<?= htmlspecialchars($_SESSION['role']); ?>)</span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

</body>
</html>


