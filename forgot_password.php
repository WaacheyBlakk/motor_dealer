<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $reset_link = "http://localhost/motor_dealer/reset_password.php?token=$token";
        $success_message = "✅ A password reset link has been generated:<br><a href='$reset_link' class='link'>$reset_link</a>";
    } else {
        $error_message = "❌ No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            font-family: "Poppins", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .reset-box {
            background: white;
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 360px;
            text-align: center;
        }
        .reset-box h2 {
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            outline: none;
            transition: border 0.3s;
        }
        input:focus {
            border-color: #3b82f6;
        }
        button {
            background-color: #1e40af;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #1d4ed8;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9em;
        }
        .success {
            background: #dcfce7;
            color: #166534;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
        .link {
            color: #1d4ed8;
            text-decoration: none;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <h2>Forgot Password</h2>
        <p>Enter your email to receive a password reset link.</p>

        <?php if(isset($success_message)): ?>
            <div class="message success"><?= $success_message; ?></div>
        <?php elseif(isset($error_message)): ?>
            <div class="message error"><?= $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <p style="margin-top:15px;"><a href="index.php" class="link">Back to Login</a></p>
    </div>
</body>
</html>
