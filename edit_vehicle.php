<?php
include 'includes/db.php';
session_start();
include 'includes/functions.php';



if(!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM vehicles WHERE id=$id");
$vehicle = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE vehicles SET make=?, model=?, year=?, price=?, status=? WHERE id=?");
    $stmt->bind_param("ssidsi", $make, $model, $year, $price, $status, $id);
    $stmt->execute();
    header("Location: vehicles.php");
    exit();
    logActivity($_SESSION['username'], "Updated vehicle ID $id - $make $model ($year)");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Vehicle - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Edit Vehicle</h2>
    <form method="post">
        <label>Make:</label>
        <input type="text" name="make" value="<?= $vehicle['make']; ?>" required>

        <label>Model:</label>
        <input type="text" name="model" value="<?= $vehicle['model']; ?>" required>

        <label>Year:</label>
        <input type="number" name="year" value="<?= $vehicle['year']; ?>" required>

        <label>Price (₵):</label>
        <input type="number" step="0.01" name="price" value="<?= $vehicle['price']; ?>" required>

        <label>Status:</label>
        <select name="status" required>
            <option value="available" <?= $vehicle['status']=='available'?'selected':''; ?>>Available</option>
            <option value="sold" <?= $vehicle['status']=='sold'?'selected':''; ?>>Sold</option>
        </select>

        <button type="submit" class="btn">Update Vehicle</button>
    </form>
</div>
</body>
</html>
