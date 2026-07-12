<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if session is valid
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session expired after 30 minutes
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?expired=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>