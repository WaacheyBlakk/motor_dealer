<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $customer_name = $_POST['customer_name'];
    $amount = $_POST['amount'];
    $sold_by = $_SESSION['user_id'];

    $sql = "INSERT INTO sales(vehicle_id, sold_by, customer_name, amount)
            VALUES ('$vehicle_id', '$sold_by', '$customer_name', '$amount')";
    $conn->query($sql);
    $conn->query("UPDATE vehicles SET status='sold' WHERE id='$vehicle_id'");
    $msg = "Sale recorded successfully!";
    logActivity($_SESSION['username'], "Made a sale to $customer_name for ₵$amount");
}

$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='available'");
?>
<!DOCTYPE html>
<html>
<head>
<title>Sales - Motor Dealer</title>
</head>
<body>
<h2>Record New Sale</h2>
<?php if(isset($msg)) echo "<p style='color:green;'>$msg</p>"; ?>

<form method="POST">
    <label>Vehicle:</label>
    <select name="vehicle_id" required>
        <?php while($row = $vehicles->fetch_assoc()): ?>
        <option value="<?php echo $row['id']; ?>">
            <?php echo $row['make'].' '.$row['model'].' - ₵'.$row['price']; ?>
        </option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Customer Name:</label>
    <input type="text" name="customer_name" required><br><br>

    <label>Amount (₵):</label>
    <input type="number" name="amount" required><br><br>

    <button type="submit">Submit Sale</button>
</form>
</body>
</html>
