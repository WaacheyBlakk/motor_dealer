<?php
include 'includes/auth.php';
include 'includes/db.php';

if ($_SESSION['role'] != 'admin') {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $conn->query("INSERT INTO users(username, password, role) VALUES('$username', '$password', '$role')");
    $msg = "User created successfully!";
}

$users = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<h2>Manage Users</h2>
<?php if(isset($msg)) echo "<p>$msg</p>"; ?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="sales">Sales</option>
        <option value="admin">Admin</option>
    </select>
    <button type="submit">Add User</button>
</form>

<h3>Existing Users</h3>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Username</th><th>Role</th></tr>
<?php while($row = $users->fetch_assoc()): ?>
<tr><td><?php echo $row['id']; ?></td><td><?php echo $row['username']; ?></td><td><?php echo $row['role']; ?></td></tr>
<?php endwhile; ?>
</table>
</body>
</html>
