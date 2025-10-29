<?php
include 'includes/db.php';
include 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $description = $_POST['description'];

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
            // Resize image if width > 800px
            list($width, $height) = getimagesize($targetFile);
            $maxWidth = 800;

            if ($width > $maxWidth) {
                $ratio = $height / $width;
                $newWidth = $maxWidth;
                $newHeight = $maxWidth * $ratio;

                // Create new resized image
                $src = null;
                switch ($imageFileType) {
                    case 'jpg':
                    case 'jpeg':
                        $src = imagecreatefromjpeg($targetFile);
                        break;
                    case 'png':
                        $src = imagecreatefrompng($targetFile);
                        break;
                    case 'gif':
                        $src = imagecreatefromgif($targetFile);
                        break;
                }

                if ($src) {
                    $dst = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    switch ($imageFileType) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($dst, $targetFile, 85);
                            break;
                        case 'png':
                            imagepng($dst, $targetFile, 8);
                            break;
                        case 'gif':
                            imagegif($dst, $targetFile);
                            break;
                    }

                    imagedestroy($src);
                    imagedestroy($dst);
                }
            }

            $imagePath = $targetFile;
        }
    }
}


    $stmt = $conn->prepare("INSERT INTO vehicles (make, model, price, status, image, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $make, $model, $price, $status, $imagePath, $description);
    if ($stmt->execute()) {
        logActivity("Added a new vehicle ($make $model)");
        $message = "✅ Vehicle added successfully!";
    } else {
        $message = "❌ Error adding vehicle: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vehicle - Motor Dealer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/nav.php'; ?>

<div class="container">
    <h2>Add Vehicle</h2>

    <?php if($message): ?>
        <div class="message"><?= $message; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Make:</label>
        <input type="text" name="make" required><br>

        <label>Model:</label>
        <input type="text" name="model" required><br>

        <label>Price:</label>
        <input type="number" name="price" step="1.00" required><br>

        <label>Status:</label>
        <select name="status" required>
            <option value="available">Available</option>
            <option value="sold">Sold</option>
        </select><br>

        <label>Vehicle Image:</label>
        <input type="file" name="image" accept="image/*"><br>

        <label>Description:</label>
        <textarea name="description" rows="4" cols="50" placeholder="Enter vehicle details..."></textarea><br>

        <button type="submit">Add Vehicle</button>
    </form>
</div>
</body>
</html>
