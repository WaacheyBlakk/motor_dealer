<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only admins can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// --- ADD USER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        logActivity("Added new user '$username' with role '$role'");
        $msg = "✅ User added successfully!";
    } else {
        $msg = "❌ Error adding user: " . $conn->error;
    }
    $stmt->close();
}

// --- DELETE USER ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Fetch username before deletion
    $res = $conn->query("SELECT username FROM users WHERE id='$id'");
    $userToDelete = $res->fetch_assoc()['username'] ?? 'Unknown';

    if ($conn->query("DELETE FROM users WHERE id='$id'")) {
        logActivity("Deleted user '$userToDelete' (ID: $id)");
        header("Location: manage_user.php?deleted=1");
        exit();
    } else {
        $msg = "❌ Error deleting user: " . $conn->error;
    }
}

// Fetch users
$result = $conn->query("SELECT * FROM users");
?>


<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/manage_users.css">
</head>
<body>

<?php include 'includes/nav.php'; ?>

<div class="main-container">
    <!-- Left Section: Add User Form -->
    <div class="form-section">
        <h2>Add New User</h2>

        <?php if (isset($_GET['added'])): ?>
            <p style="color: green;">✅ User added successfully.</p>
        <?php elseif (isset($_GET['deleted'])): ?>
            <p style="color: red;">🗑️ User deleted successfully.</p>
        <?php endif; ?>

        <form method="POST" action="manage_users.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="sales">Sales</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit">Add User</button>
        </form>
    </div>

    <!-- Right Section: Existing Users Table -->
    <div class="table-section">
        <h2>Existing Users</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>

            <?php while($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $user['id']; ?></td>
                <td><?= htmlspecialchars($user['username']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= htmlspecialchars($user['role']); ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $user['id']; ?>" class="action-btn edit-btn">Edit</a>
                    <a href="manage_users.php?delete=<?= $user['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this user?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</body>
</html>
