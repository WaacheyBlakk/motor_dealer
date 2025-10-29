<?php
include 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/functions.php';



// Check if logged in
if(!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM vehicles WHERE id=$id");
    header("Location: vehicles.php");
    exit();
    logActivity("Deleted vehicle ID $id");
}

// Fetch all vehicles
$result = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vehicles - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Vehicle Inventory</h2>
    
    <a href="add_vehicle.php" class="btn">+ Add New Vehicle</a>

    <table border="1" cellspacing="0" cellpadding="10" width="100%">
        <tr>
            <th>ID</th>
            <th>Make</th>
            <th>Model</th>
            <th>Year</th>
            <th>Price (₵)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['make']); ?></td>
            <td><?= htmlspecialchars($row['model']); ?></td>
            <td><?= $row['year']; ?></td>
            <td><?= number_format($row['price'], 2); ?></td>
            <td><?= ucfirst($row['status']); ?></td>
            <td>
                <a href="edit_vehicle.php?id=<?= $row['id']; ?>">✏️ Edit</a> | 
                <a href="vehicles.php?delete=<?= $row['id']; ?>" onclick="return confirm('Delete this vehicle?')">❌ Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
