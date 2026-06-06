<?php
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// 2. Check Admin Status
$isAdmin = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';

// CSS and HTML Structure for Error Pages (Reused for both Admin check and DB Errors)
$cssStyles = "
    :root { --primary: #6366f1; --danger: #ef4444; --warning: #f59e0b; --dark: #0f172a; --light: #f8fafc; --text-muted: #64748b; --border: #e2e8f0; }
    body { font-family: 'Inter', sans-serif; background-color: var(--light); margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
    .msg-box { background: white; padding: 40px; border-radius: 20px; width: 90%; max-width: 480px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid var(--border); animation: fadeIn 0.3s ease-in-out; }
    .icon-circle { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 20px; font-size: 3rem; }
    
    /* Variants */
    .variant-danger .icon-circle { background: #fee2e2; color: var(--danger); }
    .variant-warning .icon-circle { background: #fef3c7; color: var(--warning); }
    
    h2 { margin: 0 0 10px 0; color: var(--dark); font-size: 1.5rem; }
    p { color: var(--text-muted); line-height: 1.6; margin-bottom: 25px; font-size: 0.95rem; }
    .highlight { font-weight: 600; color: var(--dark); background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    
    .btn { padding: 12px 24px; border-radius: 12px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 500; transition: all 0.2s; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
";

// --- ACCESS DENIED UI ---
if (!$isAdmin) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied | Motor Dealer</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style><?php echo $cssStyles; ?></style>
    </head>
    <body>
        <div class="msg-box variant-danger">
            <div class="icon-circle"><i class="fa-solid fa-lock"></i></div>
            <h2>Access Restricted</h2>
            <p>Sorry, only <strong>Administrators</strong> can delete vehicle records.</p>
            <a href="vehicles.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// 3. Process the Deletion
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Get info for logging and display
    $stmt = $conn->prepare("SELECT make, model FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        $vehicleName = htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); // Sanitize for output
        $stmt->close();

        $delStmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $delStmt->bind_param("i", $id);

        try {
            // Attempt to delete
            $delStmt->execute();
            
            // Log if successful
            if (function_exists('logActivity')) {
                logActivity("Deleted vehicle #$id ($vehicleName) from inventory");
            }
            
            header("Location: vehicles.php?msg=deleted");
            exit();

        } catch (mysqli_sql_exception $e) {
            // --- ERROR 1451 UI: Foreign Key Constraint ---
            if ($e->getCode() == 1451) {
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Deletion Error | Motor Dealer</title>
                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <style><?php echo $cssStyles; ?></style>
                </head>
                <body>
                    <div class="msg-box variant-warning">
                        <div class="icon-circle"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <h2>Action Blocked</h2>
                        <p>
                            You cannot delete the <strong><?php echo $vehicleName; ?></strong> because it is linked to an existing <strong>Sales Record</strong>.
                        </p>
                        <p style="font-size: 0.85rem; background: #fffbeb; padding: 10px; border-radius: 8px; border: 1px dashed #f59e0b;">
                            <i class="fa-solid fa-circle-info"></i> <strong>Solution:</strong> Please go to the Sales section and delete the sale record associated with this vehicle first.
                        </p>
                        <a href="vehicles.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Return to Inventory</a>
                    </div>
                </body>
                </html>
                <?php
            } else {
                // Generic Database Error
                header("Location: vehicles.php?error=" . urlencode("Database error: " . $e->getMessage()));
            }
            exit();
        } finally {
            $delStmt->close();
        }

    } else {
        header("Location: vehicles.php?error=not_found");
        exit();
    }
} else {
    header("Location: vehicles.php");
    exit();
}
?>