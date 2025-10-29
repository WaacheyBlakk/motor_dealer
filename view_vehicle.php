<?php
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_GET['id'])) {
    header("Location: view_vehicles.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Vehicle not found!</p>";
    exit();
}

$vehicle = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($vehicle['make'] . " " . $vehicle['model']); ?> - Details</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .vehicle-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        .vehicle-container img {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .vehicle-info {
            font-size: 1.1em;
        }
        .back-btn {
            background: #007BFF;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="vehicle-container">
    <h2><?= htmlspecialchars($vehicle['make'] . " " . $vehicle['model']); ?></h2>

    <?php if ($vehicle['image']): ?>
        <img src="<?= htmlspecialchars($vehicle['image']); ?>" alt="Vehicle Image">
    <?php else: ?>
        <p><em>No image available.</em></p>
    <?php endif; ?>

    <div class="vehicle-info">
        <p><strong>Price:</strong> ₵<?= number_format($vehicle['price'], 2); ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($vehicle['status']); ?></p>
        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($vehicle['description'])); ?></p>
    </div>

    <a href="view_vehicles.php" class="back-btn">← Back to Vehicle List</a>
</div>

</body>
</html>
