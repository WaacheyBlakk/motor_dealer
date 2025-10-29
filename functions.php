<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Log an activity using the current logged-in username.
 * Usage: logActivity("Made a sale to John for ₵1200");
 */
function logActivity(string $action): bool {
    // ensure DB connection available
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        // try to include db.php as fallback
        $dbPath = __DIR__ . '/db.php';
        if (file_exists($dbPath)) {
            include_once $dbPath;
        }
    }

    if (!isset($conn) || !($conn instanceof mysqli)) {
        error_log("logActivity(): no DB connection available.");
        return false;
    }

    // Obtain username from session (fallback to user_id if needed)
    $username = null;
    if (!empty($_SESSION['username'])) {
        $username = $_SESSION['username'];
    } elseif (!empty($_SESSION['user'])) {
        $username = $_SESSION['user'];
    } elseif (!empty($_SESSION['user_id'])) {
        // optional: try to map id -> username
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $username = $row['username'];
            }
            $stmt->close();
        }
    }

    if (empty($username)) {
        $username = 'Unknown'; // never leave it empty
    }

    // insert log
    $stmt = $conn->prepare("INSERT INTO activity_log (username, action, log_time) VALUES (?, ?, NOW())");
    if (!$stmt) {
        error_log("logActivity(): prepare failed - " . $conn->error);
        return false;
    }

    $stmt->bind_param("ss", $username, $action);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log("logActivity(): execute failed - " . $stmt->error);
    }
    $stmt->close();

    return (bool)$ok;
}
