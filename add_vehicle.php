<?php
include 'includes/db.php';
session_start();
include 'includes/functions.php';



if(!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO vehicles (make, model, year, price, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssids", $make, $model, $year, $price, $status);
    $stmt->execute();
    header("Location: vehicles.php");
    exit();
    logActivity($_SESSION['username'], "Added vehicle: $make $model ($year)");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vehicle - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Add New Vehicle</h2>
    <form method="post">
        <label>Make:</label>
        <input type="text" name="make" required>

        <label>Model:</label>
        <input type="text" name="model" required>

        <label>Year:</label>
        <input type="number" name="year" required>

        <label>Price (₵):</label>
        <input type="number" step="0.01" name="price" required>

        <label>Status:</label>
        <select name="status" required>
            <option value="available">Available</option>
            <option value="sold">Sold</option>
        </select>

        <button type="submit" class="btn">Save Vehicle</button>
    </form>
</div>
</body>
</html>
