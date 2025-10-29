<?php
session_start();
include('includes/db.php');
include('includes/functions.php'); // ✅ For logActivity()

// --- Handle Login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // ✅ Log activity
        logActivity("User '{$user['username']}' logged into the system");

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "❌ Invalid username or password.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Motor Dealer System</title>
  <link rel="stylesheet" href="assets/login.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <div class="login-container">
    <div class="login-logo">MD</div>
    <h2>Dealer Login</h2>

    <?php if(isset($_GET['error'])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>
      </div>

      <button type="submit">Login</button>
    </form>

    <div class="login-footer">
      <p><a href="forgot_password.php">Forgot your password?</a></p>
    </div>
  </div>

</body>
</html>
