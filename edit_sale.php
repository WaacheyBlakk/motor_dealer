<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: sales.php?error=unauthorized");
    exit();
}


$id = $_GET['id'] ?? 0;

// Fetch sale details
$stmt = $conn->prepare("
    SELECT s.*, v.make, v.model
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$sale = $result->fetch_assoc();

if (!$sale) {
    die("Sale not found!");
}

// Update record if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $notes = $_POST['notes'];

    $update = $conn->prepare("
        UPDATE sales 
        SET customer_name=?, amount=?, payment_method=?, notes=? 
        WHERE id=?
    ");
    $update->bind_param("sdssi", $customer_name, $amount, $payment_method, $notes, $id);
    $update->execute();

    logActivity("Edited sale #$id for $customer_name");
    header("Location: sales.php?msg=Sale+updated+successfully");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Sale - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background: #f5f5f5; font-family: Arial; }
        .edit-container {
            width: 50%;
            margin: 60px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        label { display: block; margin-top: 10px; font-weight: 600; }
        input, select, textarea {
            width: 100%; padding: 10px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 6px;
        }
        button {
            background: #0078D7; color: white;
            border: none; padding: 10px 16px; border-radius: 6px;
            margin-top: 15px; cursor: pointer;
        }
        button:hover { background: #005fa3; }
    </style>
</head>
<body>

<div class="edit-container">
    <h2>Edit Sale #<?= htmlspecialchars($sale['id']); ?></h2>
    <p><strong>Vehicle:</strong> <?= htmlspecialchars($sale['make'] . ' ' . $sale['model']); ?></p>

    <form method="POST">
        <label>Customer Name</label>
        <input type="text" name="customer_name" value="<?= htmlspecialchars($sale['customer_name']); ?>" required>

        <label>Amount (₵)</label>
        <input type="number" name="amount" value="<?= htmlspecialchars($sale['amount']); ?>" required>

        <label>Payment Method</label>
        <select name="payment_method" required>
            <?php
            $methods = ['Cash', 'Credit Card', 'Bank Transfer'];
            foreach ($methods as $method) {
                $selected = ($sale['payment_method'] === $method) ? 'selected' : '';
                echo "<option $selected>$method</option>";
            }
            ?>
        </select>

        <label>Notes</label>
        <textarea name="notes"><?= htmlspecialchars($sale['notes']); ?></textarea>

        <button type="submit">Update Sale</button>
    </form>
</div>

</body>
</html>
