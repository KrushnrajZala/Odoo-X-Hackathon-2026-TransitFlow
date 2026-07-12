<?php
// fix_passwords.php - Fix all passwords
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>🔧 TransitOps - Password Fixer</h1>";
echo "<hr>";

$db = Database::getInstance()->getConnection();

// The password we want
$password = 'password123';

// Generate a NEW hash using PHP
$new_hash = password_hash($password, PASSWORD_BCRYPT);

echo "<h3>Generating new password hash for: <strong>{$password}</strong></h3>";
echo "<p>New hash: <code>{$new_hash}</code></p>";
echo "<hr>";

// Update all users with the new hash
$emails = [
    'fleet@transitops.com',
    'driver1@transitops.com', 
    'driver2@transitops.com',
    'safety@transitops.com',
    'finance@transitops.com'
];

$success = 0;
$failed = 0;

echo "<h3>Updating users...</h3>";

foreach ($emails as $email) {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $new_hash, $email);
    
    if ($stmt->execute()) {
        echo "✅ Updated: <strong>{$email}</strong><br>";
        $success++;
    } else {
        echo "❌ Failed: <strong>{$email}</strong> - " . $db->error . "<br>";
        $failed++;
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3>Results: {$success} updated, {$failed} failed</h3>";

// Now test the passwords
echo "<hr>";
echo "<h3>Testing Updated Passwords:</h3>";

$test_email = 'fleet@transitops.com';
$stmt = $db->prepare("SELECT email, full_name, role, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $verified = password_verify($password, $user['password_hash']);
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Email</th><th>Name</th><th>Role</th><th>Verification</th></tr>";
    echo "<tr>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['full_name']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td><strong>" . ($verified ? "✅ PASS" : "❌ FAIL") . "</strong></td>";
    echo "</tr>";
    echo "</table>";
    
    if ($verified) {
        echo "<br><div style='color:green;font-size:18px;'>✅ SUCCESS! Password is now working!</div>";
    } else {
        echo "<br><div style='color:red;font-size:18px;'>❌ Still failing. Please check the database.</div>";
    }
}

echo "<hr>";
echo "<h3>🔑 Login Now:</h3>";
echo "<a href='modules/auth/login.php' class='btn btn-primary' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Go to Login Page</a>";
echo "<br><br>";
echo "<strong>Email:</strong> fleet@transitops.com<br>";
echo "<strong>Password:</strong> password123";
?>