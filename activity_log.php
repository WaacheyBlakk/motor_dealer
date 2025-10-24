<?php
include 'includes/db.php';
include 'includes/functions.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$result = $conn->query("SELECT * FROM activity_log ORDER BY log_time DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>User Activity Log</h2>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Action</th>
            <th>Time</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['username']); ?></td>
            <td><?= htmlspecialchars($row['action']); ?></td>
            <td><?= $row['log_time']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
