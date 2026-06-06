<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check Admin Status
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// IF ADMIN: Process the delete and redirect immediately
if ($isAdmin) {
    $id = $_GET['id'] ?? 0;

    if ($id) {
        // Get sale for logging
        $stmt = $conn->prepare("SELECT customer_name FROM sales WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sale = $result->fetch_assoc();
        $customer = $sale['customer_name'] ?? 'Unknown';
        $stmt->close();

        // Delete sale record
        $delStmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
        $delStmt->bind_param("i", $id);
        
        if ($delStmt->execute()) {
            if(function_exists('logActivity')) {
                logActivity("Deleted sale #$id ($customer)");
            }
        }
        $delStmt->close();
    }

    header("Location: sales.php?msg=Sale+deleted+successfully");
    exit();
}

// IF NOT ADMIN: The script continues below to show the Access Denied screen
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1;
            --dark: #0f172a;
            --light: #f8fafc;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        /* --- ACCESS DENIED MODAL CSS --- */
        .access-denied-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .access-denied-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideUp 0.3s ease-out;
            border: 1px solid var(--border);
        }

        .lock-icon-container {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            font-size: 2rem;
        }

        .access-denied-box h2 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .access-denied-box p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }
        .btn-primary:hover { background-color: #4f46e5; transform: translateY(-1px); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

    <div class="access-denied-overlay">
        <div class="access-denied-box">
            <div class="lock-icon-container">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h2>Access Restricted</h2>
            <p>
                Sorry, only <strong>Administrators</strong> can delete sales records. 
                This action is restricted to prevent accidental data loss.
            </p>
            <a href="sales.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left"></i> Return to Sales
            </a>
        </div>
    </div>

</body>
</html>