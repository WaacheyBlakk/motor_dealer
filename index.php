<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];


        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
    } else {
        $error = "Invalid credentials!";
    }
}
logActivity($user, 'Logged into the system');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motor Dealer Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
