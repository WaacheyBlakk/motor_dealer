<?php
// 1. Load PHPMailer classes and Composer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
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

        $reset_link = "http://reseller.infinityfree.me/reset_password.php?token=$token";

        // Initialize PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp-relay.brevo.com';                 // Brevo SMTP Host
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'YOUR_BREVO_LOGIN_EMAIL';               // Replace with your Brevo login email
            $mail->Password   = 'YOUR_BREVO_SMTP_KEY';                 // Replace with your Brevo SMTP key
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
            $mail->Port       = 587;                                    

            // Recipients
            $mail->setFrom('YOUR_BREVO_LOGIN_EMAIL', 'Motor Dealer');   // Use your verified Brevo sender email
            $mail->addAddress($email);                                  

            // Content
            $mail->isHTML(true);                                        
            $mail->Subject = 'Password Reset Request - Motor Dealer';
            $mail->Body    = "
                <html>
                <head>
                  <title>Password Reset Request</title>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                  <h2>Password Reset Request</h2>
                  <p>We received a request to reset your password for your Motor Dealer account. Click the button below to set a new password:</p>
                  <p style='margin: 20px 0;'>
                    <a href='$reset_link' style='background-color: #1e40af; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>
                  </p>
                  <p>If the button above does not work, copy and paste the following link into your browser:</p>
                  <p><a href='$reset_link'>$reset_link</a></p>
                  <p><strong>Note:</strong> This link will expire in 1 hour.</p>
                  <p>If you did not request this, you can safely ignore this email.</p>
                </body>
                </html>
            ";

            $mail->send();
            $success_message = "A password reset link has been sent to your email address.";
        } catch (Exception $e) {
            $error_message = "Failed to send reset email. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error_message = "No account found with that email.";
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
            box-sizing: border-box;
        }
        .reset-box {
            background: white;
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 360px;
            text-align: center;
            box-sizing: border-box;
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
            box-sizing: border-box;
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
        <p style="margin-top:15px;"><a href="login.php" class="link">Back to Login</a></p>
    </div>
</body>
</html>