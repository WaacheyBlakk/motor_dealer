<?php
// --- Include core files ---
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$userRole = $_SESSION['role']; // ✅ Store role for easy access

// --- Handle Sale Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_id     = $_POST['vehicle_id'];
    $customer_name  = $_POST['customer_name'];
    $amount         = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $notes          = $_POST['notes'] ?? '';
    $sold_by        = $_SESSION['user_id'];

    // Insert sale
    $sql = "INSERT INTO sales (vehicle_id, sold_by, customer_name, amount, payment_method, notes, created_at)
            VALUES ('$vehicle_id', '$sold_by', '$customer_name', '$amount', '$payment_method', '$notes', NOW())";
    if ($conn->query($sql)) {
        // Mark vehicle as sold
        $conn->query("UPDATE vehicles SET status='sold' WHERE id='$vehicle_id'");
        $msg = "✅ Sale recorded successfully!";
        logActivity("Made a sale to $customer_name for ₵$amount");
    } else {
        $msg = "❌ Error recording sale: " . $conn->error;
    }
}

// --- Fetch available vehicles ---
$vehicles = $conn->query("SELECT id, make, model, price FROM vehicles WHERE status='available'");

// --- Fetch all sales ---
$sales = $conn->query("
    SELECT 
        s.id,
        CONCAT(v.make, ' ', v.model) AS vehicle,
        s.customer_name,
        s.amount,
        s.payment_method,
        s.notes,
        s.created_at
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    ORDER BY s.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/sales.css">
    <style>
        /* --- Dashboard Layout --- */
        .container {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            flex-wrap: wrap;
        }
        .section {
            flex: 1;
            min-width: 420px;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        h2 {
            color: #1e3a8a;
        }

        /* --- Form and Buttons --- */
        .btn-submit {
            background-color: #2563eb;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background-color: #1e40af;
        }

        /* --- Sales Table --- */
        table {
            width: 50%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 5px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #f9fafb;
            color: #1f2937;
        }

        /* --- Action Buttons --- */
        .action-buttons {
            display: flex;
            gap: 2px; /* reduce spacing between buttons */
            justify-content: flex-start;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background: #10b981;
            color: white;
        }
        .btn-edit:hover {
            background: #059669;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background: #b91c1c;
        }

        .message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 500;
        }
    </style>

</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <!-- LEFT SECTION: ADD SALE -->
    <div class="section">
        <h2>Add New Sale</h2>
        <?php if (!empty($msg)): ?>
            <div class="message"><?= $msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="sales.php">
            <div class="form-grid">
                <div class="form-group">
                    <label for="vehicle">Vehicle</label>
                    <select id="vehicle" name="vehicle_id" required>
                        <option value="">Select Vehicle</option>
                        <?php while ($v = $vehicles->fetch_assoc()): ?>
                            <option value="<?= $v['id']; ?>">
                                <?= $v['make'] . ' ' . $v['model'] . ' (₵' . number_format($v['price'], 2) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>

                <div class="form-group">
                    <label for="amount">Sale Amount (₵)</label>
                    <input type="number" id="amount" name="amount" required>
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Money">Mobile Money</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Optional comments..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Sale</button>
            </div>
        </form>
    </div>

    <!-- RIGHT SECTION: SALES RECORDS -->
    <div class="section">
        <h2>Sales Records</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Customer</th>
                    <th>Amount (₵)</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $sales->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= htmlspecialchars($row['vehicle']); ?></td>
            <td><?= htmlspecialchars($row['customer_name']); ?></td>
            <td><?= htmlspecialchars($row['amount']); ?></td>
            <td><?= htmlspecialchars($row['payment_method']); ?></td>
            <td><?= $row['created_at']; ?></td>

            <?php if ($userRole === 'admin'): ?>
                <td>
                    <div class="action-buttons">
                        <a href="edit_sale.php?id=<?= $row['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                        <a href="delete_sale.php?id=<?= $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this sale?')">🗑️ Delete</a>
                    </div>
                </td>
            <?php else: ?>
    <td>N/A</td>
<?php endif; ?>

        </tr>
        <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
