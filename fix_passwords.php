<?php
// fix_passwords.php - Run this once to fix all passwords
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Password for all users
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h2>Fixing Passwords for TransitOps</h2>";
echo "<p>Setting all users password to: <strong>{$password}</strong></p>";
echo "<p>Hash: <code>{$hash}</code></p><hr>";

// Update all users
$emails = [
    'fleet@transitops.com',
    'driver1@transitops.com',
    'driver2@transitops.com',
    'safety@transitops.com',
    'finance@transitops.com'
];

$success = 0;
$failed = 0;

foreach ($emails as $email) {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    
    if ($stmt->execute()) {
        echo "✅ Updated: <strong>{$email}</strong><br>";
        $success++;
    } else {
        echo "❌ Failed: <strong>{$email}</strong> - " . $db->error . "<br>";
        $failed++;
    }
}

echo "<hr>";
echo "<h3>Results: {$success} updated, {$failed} failed</h3>";

// Now test the passwords
echo "<hr><h3>Testing Updated Passwords:</h3>";

$testEmail = 'fleet@transitops.com';
$testStmt = $db->prepare("SELECT email, full_name, role, password_hash FROM users WHERE email = ?");
$testStmt->bind_param("s", $testEmail);
$testStmt->execute();
$result = $testStmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $verified = password_verify($password, $user['password_hash']);
    echo "Testing: {$user['email']} ({$user['full_name']})<br>";
    echo "Password: {$password}<br>";
    echo "Verification: " . ($verified ? "✅ PASS" : "❌ FAIL") . "<br>";
    echo "Hash: " . substr($user['password_hash'], 0, 50) . "...<br>";
}

echo "<hr>";
echo "<a href='modules/auth/login.php' class='btn btn-primary'>Go to Login</a>";
?>