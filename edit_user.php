<?php
session_start();
include('includes/db.php');
include('includes/functions.php'); // ✅ Include logActivity()

// --- Access Control ---
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// --- Check for User ID ---
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$id = intval($_GET['id']);

// --- Fetch User Details ---
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// --- Handle Update Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    $newRole = $_POST['role'];
    $newPassword = $_POST['password'] ?? '';

    if (!empty($newUsername)) {
        // Update with or without password
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
            $update->bind_param("sssi", $newUsername, $newRole, $hashedPassword, $id);
        } else {
            $update = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $update->bind_param("ssi", $newUsername, $newRole, $id);
        }

        if ($update->execute()) {
            // ✅ Log this activity
            $adminUser = $_SESSION['username'];
            logActivity("Admin '$adminUser' updated user '$newUsername' (ID: $id) — Role: $newRole" . 
                        (!empty($newPassword) ? ", Password changed." : "."));

            header("Location: manage_users.php?msg=User updated successfully");
            exit();
        } else {
            $error = "❌ Failed to update user: " . $conn->error;
        }
    } else {
        $error = "❌ Username cannot be empty.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 50%;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 25px 40px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .btn-submit {
            background-color: #1e90ff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            margin-top: 20px;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit:hover {
            background-color: #0d6efd;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #1e90ff;
        }

        .error {
            color: red;
            text-align: center;
        }

        .success {
            color: green;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?= $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

            <label>Role:</label>
            <select name="role" required>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="sales" <?= $user['role'] === 'sales' ? 'selected' : ''; ?>>Sales</option>
            </select>

            <label>New Password (optional):</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password">

            <button type="submit" class="btn-submit">Update User</button>
        </form>

        <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
    </div>
</body>
</html>
