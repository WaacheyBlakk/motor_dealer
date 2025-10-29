<?php
include 'includes/db.php';

if (!isset($_GET['token'])) {
    die("Invalid password reset link.");
}

$token = $_GET['token'];

$query = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW()");
$query->bind_param("s", $token);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("<div style='text-align:center;margin-top:100px;font-family:Poppins;'>❌ Invalid or expired token.</div>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
    $update->bind_param("ss", $password, $token);
    $update->execute();

    echo "<div style='text-align:center;margin-top:100px;font-family:Poppins;'>
            ✅ Password reset successfully. <a href='index.php'>Login here</a>.
          </div>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Motor Dealer</title>
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
        input[type="password"] {
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
        .link {
            color: #1d4ed8;
            text-decoration: none;
        }
        /* Container styling */
        .reset-container {
            max-width: 400px;
            margin: 80px auto;
            background: #fff;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
        }

        /* Form input fields */
        .reset-container input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.2s, box-shadow 0.2s;
        }

        .reset-container input[type="password"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 5px rgba(37, 99, 235, 0.4);
            outline: none;
        }

        /* Submit button */
        .reset-container button {
            width: 100%;
            padding: 12px;
            background-color: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
        }

        .reset-container button:hover {
            background-color: #1e40af;
            transform: translateY(-1px);
        }

        /* Back to login link */
        .reset-container .link {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .reset-container .link:hover {
            color: #1e40af;
            text-decoration: underline;
        }

    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
        <p style="margin-top:15px;">
            <a href="index.php" class="link">Back to Login</a>
        </p>
    </div>

</body>
</html>
