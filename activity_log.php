<?php
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Only admin can clear logs
if (isset($_GET['clear']) && $_SESSION['role'] === 'admin') {
    $conn->query("TRUNCATE TABLE activity_log");
    header("Location: activity_log.php?cleared=1");
    exit();
}

// Get activity logs
$query = "SELECT id, username, action, log_time FROM activity_log ORDER BY log_time DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .btn-danger {
            background-color: #dc2626;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        .success-msg {
            background: #ecfdf5;
            color: #065f46;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        th {
            background: #1e3a8a;
            color: #fff;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>👤 User Activity Log</h2>

    <?php if (isset($_GET['cleared'])): ?>
        <div class="success-msg">✅ Activity log cleared successfully.</div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="activity_log.php?clear=1" class="btn-danger" onclick="return confirm('Are you sure you want to clear ALL logs?')">🧹 Clear Log</a>
        <br><br>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Action</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['username'] ?: 'Unknown'); ?></td>
                    <td><?= htmlspecialchars($row['action']); ?></td>
                    <td><?= htmlspecialchars($row['log_time']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center; color:#555;">No logs found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
