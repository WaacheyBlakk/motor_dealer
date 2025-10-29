<?php
include 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Make sure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Fetch all vehicles from the database
$sql = "SELECT * FROM vehicles ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Vehicles - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 25px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #2563eb;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        img {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
        .actions a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="container">
    <a href="add_vehicle.php" class="btn">+ Add New Vehicle</a>
    <h2>🚗 Vehicle List</h2>
    <p>Below is a list of all vehicles in the system.</p>

    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Make</th>
            <th>Model</th>
            <th>Price (₵)</th>
            <th>Status</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td>
                <?php if (!empty($row['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($row['image']); ?>" alt="Vehicle Image">
                <?php else: ?>
                    <em>No image</em>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['make']); ?></td>
            <td><?= htmlspecialchars($row['model']); ?></td>
            <td><?= number_format($row['price'], 2); ?></td>
            <td><?= htmlspecialchars($row['status']); ?></td>
            <td><?= nl2br(htmlspecialchars($row['description'])); ?></td>
            <td class="actions">
                <a href="view_vehicle.php?id=<?= $row['id']; ?>">View</a> |
                <a href="edit_vehicle.php?id=<?= $row['id']; ?>">Edit</a> |
                <a href="delete_vehicle.php?id=<?= $row['id']; ?>" onclick="return confirm('Delete this vehicle?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>

        <?php if ($result->num_rows === 0): ?>
        <tr>
            <td colspan="8" style="text-align:center;"><em>No vehicles found.</em></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<footer>
    &copy; <?= date('Y'); ?> Motor Dealer Management System
</footer>

</body>
</html>
