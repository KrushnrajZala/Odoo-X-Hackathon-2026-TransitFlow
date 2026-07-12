<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$error = '';
$success = '';

// Password for all demo accounts is: password123
$demo_credentials = [
    'Fleet_Manager' => ['email' => 'fleet@transitops.com', 'password' => 'password123'],
    'Driver' => ['email' => 'driver1@transitops.com', 'password' => 'password123'],
    'Safety_Officer' => ['email' => 'safety@transitops.com', 'password' => 'password123'],
    'Financial_Analyst' => ['email' => 'finance@transitops.com', 'password' => 'password123']
];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if email and password are set
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        // Validate email and password are not empty
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Prepare and execute query
                $stmt = $db->prepare("SELECT user_id, full_name, email, role, password_hash, status FROM users WHERE email = ? AND status = 'Active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $user['password_hash'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        
                        // Update last login
                        $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $updateStmt->bind_param("i", $user['user_id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                        
                        // Redirect to dashboard
                        header("Location: ../dashboard/index.php");
                        exit();
                    } else {
                        $error = 'Invalid password. Please try again.';
                    }
                } else {
                    $error = 'User not found or account is inactive.';
                }
                
                $stmt->close();
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TransitOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            padding: 2.5rem;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logo h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logo p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.7rem 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.7rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .demo-btn {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            width: 100%;
        }
        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .demo-btn.active-demo {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .input-group-text {
            background: transparent;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group .form-control:focus {
            border-left: none;
        }
        #togglePassword {
            border: 2px solid #e9ecef;
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .password-hint {
            color: #6c757d;
            font-size: 0.8rem;
            text-align: center;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-truck-fast"></i>
                <h1>TransitOps</h1>
                <p>Smart Transport Operations Platform</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" 
                               id="email" name="email" required 
                               placeholder="Enter your email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" class="form-control" 
                               id="password" name="password" required 
                               placeholder="Enter your password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                
                <div class="text-center mb-2">
                    <a href="#" id="forgotPassword" class="text-decoration-none">
                        <i class="fas fa-question-circle"></i> Forgot Password?
                    </a>
                </div>
            </form>

            <div class="mt-4">
                <h6 class="text-muted mb-3 text-center">Demo Credentials (Click to auto-fill)</h6>
                <div class="row g-2">
                    <?php foreach ($demo_credentials as $role => $creds): ?>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary demo-btn" 
                                    data-email="<?php echo $creds['email']; ?>"
                                    data-password="<?php echo $creds['password']; ?>">
                                <i class="fas fa-user"></i> <?php echo str_replace('_', ' ', $role); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-shield-alt"></i> Secure Login | All passwords: <strong>password123</strong>
                </small>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            var passwordInput = $('#password');
            var icon = $(this).find('i');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Auto-fill demo credentials
        $('.demo-btn').click(function() {
            var email = $(this).data('email');
            var password = $(this).data('password');
            
            $('#email').val(email);
            $('#password').val(password);
            
            // Highlight the selected button
            $('.demo-btn').removeClass('active-demo');
            $(this).addClass('active-demo');
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Credentials Filled!',
                text: 'Password: password123',
                timer: 2000,
                showConfirmButton: false,
                position: 'center'
            });
            
            // Auto-submit after 1.5 seconds
            setTimeout(function() {
                $('#loginForm').submit();
            }, 1500);
        });

        // Form submission with loading state
        $('#loginForm').on('submit', function(e) {
            var email = $('#email').val().trim();
            var password = $('#password').val().trim();
            
            if (!email || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Fields',
                    text: 'Please enter both email and password.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            // Show loading state
            var $btn = $('#loginBtn');
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Signing In...');
            $btn.prop('disabled', true);
            
            // Re-enable after 10 seconds (in case of slow response)
            setTimeout(function() {
                $btn.html(originalHtml);
                $btn.prop('disabled', false);
            }, 10000);
            
            return true;
        });

        // Forgot password
        $('#forgotPassword').click(function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Reset Password',
                html: '<input type="email" id="reset-email" class="form-control" placeholder="Enter your email">',
                showCancelButton: true,
                confirmButtonText: 'Send Reset Link',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6c757d',
                preConfirm: function() {
                    var email = $('#reset-email').val();
                    if (!email) {
                        Swal.showValidationMessage('Please enter your email address.');
                        return false;
                    }
                    return email;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Reset Link Sent',
                        text: 'A password reset link has been sent to your email.',
                        confirmButtonColor: '#667eea'
                    });
                }
            });
        });

        // Check if there's a logout message
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('logout') === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: 'You have been successfully logged out.',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
    </script>
</body>
</html>