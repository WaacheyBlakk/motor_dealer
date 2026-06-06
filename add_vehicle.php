<?php
include 'includes/db.php';
include 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// --- 1. ADMIN CHECK ---
// Update 'role' to whatever your database column/session variable is named (e.g., 'user_type', 'level')
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$message = '';
$msgType = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- 2. BACKEND SECURITY ---
    if (!$isAdmin) {
        $message = "Access Denied: You do not have permission to perform this action.";
        $msgType = "error";
    } else {
        // Only process form if Admin
        $make = trim($_POST['make']);
        $model = trim($_POST['model']);
        $price = $_POST['price'];
        $status = $_POST['status'];
        $description = trim($_POST['description']);

        // Handle image upload and resizing
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["image"]["name"]);
            $targetFile = $targetDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ["jpg", "jpeg", "png", "gif"];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    // Resize image logic
                    list($width, $height) = getimagesize($targetFile);
                    $maxWidth = 800;

                    if ($width > $maxWidth) {
                        $ratio = $height / $width;
                        $newWidth = $maxWidth;
                        $newHeight = $maxWidth * $ratio;

                        $src = null;
                        switch ($imageFileType) {
                            case 'jpg':
                            case 'jpeg': $src = imagecreatefromjpeg($targetFile); break;
                            case 'png': $src = imagecreatefrompng($targetFile); break;
                            case 'gif': $src = imagecreatefromgif($targetFile); break;
                        }

                        if ($src) {
                            $dst = imagecreatetruecolor($newWidth, $newHeight);
                            if($imageFileType == 'png' || $imageFileType == 'gif'){
                                imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                                imagealphablending($dst, false);
                                imagesavealpha($dst, true);
                            }
                            
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                            switch ($imageFileType) {
                                case 'jpg':
                                case 'jpeg': imagejpeg($dst, $targetFile, 85); break;
                                case 'png': imagepng($dst, $targetFile, 8); break;
                                case 'gif': imagegif($dst, $targetFile); break;
                            }

                            imagedestroy($src);
                            imagedestroy($dst);
                        }
                    }
                    $imagePath = $targetFile;
                }
            } else {
                $message = "Invalid file type. Only JPG, PNG & GIF allowed.";
                $msgType = "error";
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("INSERT INTO vehicles (make, model, price, status, image, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsss", $make, $model, $price, $status, $imagePath, $description);
            if ($stmt->execute()) {
                if(function_exists('logActivity')) {
                    logActivity("Added a new vehicle ($make $model)");
                }
                $message = "Vehicle added successfully!";
                $msgType = "success";
            } else {
                $message = "Error adding vehicle: " . $conn->error;
                $msgType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle | Motor Dealer</title>
    
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Palette - Exact match to Inventory Page */
            --primary: #6366f1;       /* Indigo */
            --primary-dark: #4f46e5;
            --secondary: #10b981;     /* Emerald */
            --danger: #ef4444;        /* Red */
            --warning: #f59e0b;       /* Amber */
            --dark: #0f172a;          /* Slate 900 */
            --light: #f8fafc;         /* Slate 50 */
            --text-main: #1e293b;     /* Slate 800 */
            --text-muted: #64748b;    /* Slate 500 */
            --border: #e2e8f0;        /* Slate 200 */
            
            /* Dimensions */
            --sidebar-width: 260px;
            
            /* Effects */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --radius: 16px;
        }

        * { box-sizing: border-box; outline: none; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: var(--text-main);
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 50;
        }

        .brand {
            height: 80px;
            display: flex;
            align-items: center;
            padding: 0 30px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.5px;
        }
        .brand i { margin-right: 10px; font-size: 1.4rem; }

        .nav-links {
            list-style: none;
            padding: 20px 15px;
            margin: 0;
            flex: 1;
        }
        .nav-links li { margin-bottom: 5px; }
        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .nav-links a:hover {
            background-color: #f1f5f9;
            color: var(--primary);
        }
        .nav-links a.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        .nav-links a.active i { color: white; }
        .nav-links a i { width: 25px; font-size: 1.1rem; }

        .user-panel {
            padding: 25px;
            border-top: 1px solid var(--border);
        }
        .user-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .user-btn:hover { color: var(--primary); }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header-title h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--dark); }
        .header-title p { margin: 5px 0 0; color: var(--text-muted); }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }
        .btn-primary:hover { background-color: var(--primary-dark); transform: translateY(-1px); }
        
        .btn-outline {
            background: white;
            border: 1px solid var(--border);
            color: var(--text-main);
        }
        .btn-outline:hover { background: #f8fafc; border-color: var(--text-muted); }

        /* --- FORM STYLES --- */
        .form-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            padding: 40px;
            max-width: 900px;
            margin: 0 auto 0 0; /* Align left with max width */
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 5px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            background-color: var(--light);
            color: var(--text-main);
            transition: all 0.2s;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            background-color: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #ffffff;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-wrapper:hover {
            border-color: var(--primary);
            background: #f5f3ff; /* Light indigo tint */
        }

        input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-content { pointer-events: none; }
        .upload-icon { font-size: 2rem; color: var(--primary); margin-bottom: 15px; display: block; }
        .upload-text { color: var(--text-muted); font-size: 0.9rem; }
        .upload-text span { color: var(--primary); font-weight: 600; }

        .image-preview {
            margin-top: 20px;
            max-width: 100%;
            height: 200px;
            border-radius: 12px;
            display: none;
            box-shadow: var(--shadow-sm);
            object-fit: cover;
            border: 1px solid var(--border);
        }

        /* Alert Messages */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-weight: 500;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        .alert.success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* --- ACCESS DENIED MODAL CSS --- */
        .access-denied-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.75); /* Dark semi-transparent */
            backdrop-filter: blur(8px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .access-denied-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: slideUp 0.3s ease-out;
            border: 1px solid var(--border);
        }

        .lock-icon-container {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            font-size: 2rem;
        }

        .access-denied-box h2 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1.5rem;
        }

        .access-denied-box p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 25px;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; }
            .sidebar .brand span, .nav-links span, .user-btn span { display: none; }
            .sidebar .brand { justify-content: center; padding: 0; }
            .nav-links a { justify-content: center; padding: 15px; }
            .nav-links a i { margin: 0; width: auto; font-size: 1.4rem; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .header-area { flex-direction: column; align-items: flex-start; gap: 15px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body <?php echo (!$isAdmin) ? 'style="overflow:hidden;"' : ''; ?>>

<!-- --- 3. ACCESS DENIED HTML --- -->
<?php if (!$isAdmin): ?>
    <div class="access-denied-overlay">
        <div class="access-denied-box">
            <div class="lock-icon-container">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h2>Access Restricted</h2>
            <p>
                Sorry, only <strong>Administrators</strong> can add new vehicles to the inventory. 
                Please contact your system manager if you believe this is a mistake.
            </p>
            <a href="vehicles.php" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-arrow-left"></i> Return to Inventory
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-layer-group"></i>
        <span>AutoDesk</span>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fa-solid fa-grid-2"></i> <span>Dashboard</span></a></li>
        <!-- Active class kept here to show hierarchy -->
        <li><a href="vehicles.php" class="active"><i class="fa-solid fa-car"></i> <span>Vehicles</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-receipt"></i> <span>Sales</span></a></li>

        <!-- Only show Reports and Activity Log if user is Admin -->
        <?php if ($isAdmin): ?>
            <li><a href="reports.php"><i class="fa-solid fa-users"></i> <span>Report</span></a></li>
            <li><a href="manage_users.php"><i class="fa-solid fa-users"></i> <span>Users</span></a></li>
            <li><a href="activity_log.php"><i class="fa-solid fa-sliders"></i> <span>Activity Log</span></a></li>
        <?php endif; ?>
    </ul>
    <div class="user-panel">
        <a href="logout.php" class="user-btn">
            <div>
                <span><i class="fa-solid fa-circle-user"></i> &nbsp; <?= htmlspecialchars($_SESSION['username']); ?></span>
                <br>
                <!-- Visual feedback for the user role -->
                <span class="role-badge"><?= htmlspecialchars(ucfirst($_SESSION['role'])); ?></span>
            </div>
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Main Content -->
<main class="main-content">
    
    <!-- Header -->
    <div class="header-area">
        <div class="header-title">
            <h1>Add New Vehicle</h1>
            <p>Fill in the details below to add a vehicle to the inventory.</p>
        </div>
        <a href="vehicles.php" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Inventory
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert <?= $msgType; ?>">
            <i class="fa-solid <?= $msgType == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>" style="margin-right: 10px;"></i>
            <?= $message; ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Make -->
                <div class="form-group">
                    <label for="make">Make</label>
                    <input type="text" id="make" name="make" placeholder="e.g. Toyota" required>
                </div>

                <!-- Model -->
                <div class="form-group">
                    <label for="model">Model</label>
                    <input type="text" id="model" name="model" placeholder="e.g. Camry" required>
                </div>

                <!-- Price -->
                <div class="form-group">
                    <label for="price">Price (₵)</label>
                    <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter vehicle features, condition, and history..."></textarea>
                </div>

                <!-- Image Upload -->
                <div class="form-group full-width">
                    <label>Vehicle Image</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="image" id="imageInput" accept="image/*" onchange="previewImage(event)">
                        <div class="upload-content">
                            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                            <div class="upload-text" id="uploadText">
                                <span>Click to upload</span> or drag and drop<br>
                                <small>SVG, PNG, JPG or GIF (max. 800x800px)</small>
                            </div>
                        </div>
                    </div>
                    <img id="preview" class="image-preview">
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-plus"></i> Add Vehicle to Inventory
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // Preview Image Logic
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('preview');
            output.src = reader.result;
            output.style.display = 'block';
            
            // Update text to show file name
            var input = event.target;
            if(input.files.length > 0) {
                document.getElementById('uploadText').innerHTML = 
                    "<span style='color:var(--text-main)'>" + input.files[0].name + "</span> selected.";
            }
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</body>
</html>