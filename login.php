<?php
session_start();
include('includes/db.php');
include('includes/functions.php'); 

// --- Security: CSRF Token Generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// --- Handle Login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security validation failed.");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Fetch user from DB
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // 3. Verify Password
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Security fix

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if(function_exists('logActivity')) {
                logActivity("User '{$user['username']}' logged into the system");
            }

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    } else {
        $error = "Database error. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Motor Dealer System</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --text-dark: #1e293b;
        --text-gray: #64748b;
        --bg-light: #f1f5f9;
        --white: #ffffff;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    body {
        height: 100vh;
        width: 100%;
        background-color: var(--bg-light);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .main-container {
        display: flex;
        width: 900px;
        max-width: 95%;
        height: 600px;
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Left Side: Image */
    .brand-section {
        flex: 1;
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(30, 41, 59, 0.9)), 
                    url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?q=80&w=1000&auto=format&fit=crop'); 
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: var(--white);
        padding: 40px;
        text-align: center;
    }

    .brand-section h1 { font-size: 2.5rem; margin-bottom: 10px; font-weight: 700; }
    .brand-section p { font-size: 1rem; opacity: 0.8; }

    /* Right Side: Form */
    .form-section {
        flex: 1;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-header { margin-bottom: 30px; }
    .login-header h2 { color: var(--text-dark); font-size: 1.8rem; margin-bottom: 5px; }
    .login-header p { color: var(--text-gray); font-size: 0.9rem; }

    .alert {
        background: #fee2e2;
        color: #ef4444;
        padding: 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        margin-bottom: 20px;
        border-left: 4px solid #ef4444;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; color: var(--text-dark); font-weight: 500; margin-bottom: 8px; font-size: 0.9rem; }

    /* --- INPUT CONTAINER & ICONS (FIXED) --- */
    .input-wrapper { position: relative; width: 100%; }

    /* Input Field Styling */
    .form-control {
        width: 100%;
        padding: 12px 45px 12px 45px; /* Padding left for icon, right for eye */
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: 0.3s ease;
        outline: none;
        color: var(--text-dark);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Left Icon (User/Lock) */
    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-gray);
        pointer-events: none; /* Important: Allows clicking through the icon into the input */
        z-index: 10;
    }

    /* Right Icon (Eye Toggle) */
    .toggle-password {
        position: absolute;
        right: 15px; /* Stick to right */
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-gray);
        cursor: pointer;
        z-index: 10;
        padding: 5px; /* Larger click area */
    }
    
    .toggle-password:hover { color: var(--primary-color); }

    /* --- END FIXED CSS --- */

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        font-size: 0.85rem;
    }

    .remember-me { display: flex; align-items: center; gap: 5px; color: var(--text-gray); cursor: pointer; }
    .forgot-link { color: var(--primary-color); text-decoration: none; font-weight: 500; }
    .forgot-link:hover { text-decoration: underline; }

    .btn-login {
        width: 100%;
        padding: 14px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-login:hover { background-color: var(--primary-hover); transform: translateY(-1px); }
    .btn-login:disabled { background-color: #94a3b8; cursor: not-allowed; }

    @media (max-width: 768px) {
        .main-container { flex-direction: column; height: auto; }
        .brand-section { display: none; }
        .form-section { padding: 40px 20px; }
    }
  </style>
</head>
<body>

  <div class="main-container">
    <div class="brand-section">
      <h1>Motor Dealer</h1>
      <p>Premium System Management</p>
    </div>

    <div class="form-section">
      <div class="login-header">
        <h2>Welcome Back</h2>
        <p>Please enter your details to sign in.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <!-- Added specific class 'input-icon' for the left icon -->
            <i class="fa-regular fa-user input-icon"></i>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <!-- Left Icon -->
            <i class="fa-solid fa-lock input-icon"></i>
            
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            
            <!-- Right Icon (Toggle) -->
            <i class="fa-regular fa-eye toggle-password" id="togglePassword"></i>
          </div>
        </div>

        <div class="form-actions">
          <label class="remember-me">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">Sign In</button>
      </form>
    </div>
  </div>

  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    loginForm.addEventListener('submit', function() {
        loginBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing In...';
        loginBtn.disabled = true;
    });
  </script>

</body>
</html>