<?php
// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return $_SESSION;
    }
    return null;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Role checking
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isAdmin() {
    return hasRole('Fleet_Manager');
}

function isDriver() {
    return hasRole('Driver');
}

function isSafetyOfficer() {
    return hasRole('Safety_Officer');
}

function isFinancialAnalyst() {
    return hasRole('Financial_Analyst');
}

// Sanitization
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Generate trip number
function generateTripNumber() {
    return 'TRP-' . date('Ymd') . '-' . rand(1000, 9999);
}

// Status badges
function getStatusBadge($status) {
    $badges = [
        'Available' => 'success',
        'On_Trip' => 'warning',
        'In_Shop' => 'danger',
        'Retired' => 'secondary',
        'Off_Duty' => 'info',
        'Suspended' => 'danger',
        'Draft' => 'secondary',
        'Dispatched' => 'primary',
        'Completed' => 'success',
        'Cancelled' => 'danger',
        'Active' => 'success',
        'Closed' => 'secondary'
    ];
    
    $color = isset($badges[$status]) ? $badges[$status] : 'secondary';
    $displayStatus = str_replace('_', ' ', $status);
    
    return "<span class='badge bg-{$color}'>{$displayStatus}</span>";
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>