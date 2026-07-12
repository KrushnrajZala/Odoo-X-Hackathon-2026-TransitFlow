<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if session is valid (30 minutes timeout)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session expired after 30 minutes
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?expired=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Get user role for access control
$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';

// Define role-based access
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Fleet_Manager';
}

function isDriver() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Driver';
}

function isSafetyOfficer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Safety_Officer';
}

function isFinancialAnalyst() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Financial_Analyst';
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}
?>