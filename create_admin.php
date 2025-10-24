<?php
include 'includes/db.php'; // uses mysqli $conn from the fixed db.php

$username = 'admin';
$password_plain = 'admin123'; // change this
$role = 'admin';
$hash = password_hash($password_plain, PASSWORD_DEFAULT);

// check if admin exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $hash, $role);
    $stmt->execute();
    echo "Admin user created (username: $username)";
} else {
    echo "Admin user already exists.";
}
?>
