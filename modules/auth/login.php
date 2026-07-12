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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT user_id, full_name, email, role, password_hash, status FROM users WHERE email = ? AND status = 'Active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password_hash'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        
                        $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $updateStmt->bind_param("i", $user['user_id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                        
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #818CF8;
            --primary-gradient: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: #ffffff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            padding: 2.5rem;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ===== LOGIN HEADER ===== */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-gradient);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: #fff;
            box-shadow: 0 8px 25px rgba(79,70,229,0.3);
        }

        .login-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0.2rem;
            color: #0F172A;
        }

        .login-header h1 span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: #6B7280;
            font-size: 0.9rem;
            margin: 0;
        }

        /* ===== FORM STYLES ===== */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1F2937;
            margin-bottom: 0.4rem;
            display: block;
        }

        .form-group label i {
            margin-right: 6px;
            color: var(--primary);
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            transition: var(--transition);
            background: #F9FAFB;
            font-family: 'Inter', sans-serif;
            color: #0F172A;
        }

        .input-group-custom .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
            background: #fff;
            outline: none;
        }

        .input-group-custom .form-control::placeholder {
            color: #94A3B8;
        }

        .input-group-custom .form-control.is-invalid {
            border-color: #EF4444;
            box-shadow: 0 0 0 4px rgba(239,68,68,0.1);
        }

        .input-group-custom .form-control.is-valid {
            border-color: #10B981;
            box-shadow: 0 0 0 4px rgba(16,185,129,0.1);
        }

        .input-group-custom .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            z-index: 10;
            background: transparent;
            border: none;
            padding: 5px;
        }

        .input-group-custom .input-icon:hover {
            color: var(--primary);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            background: var(--primary-gradient);
            color: #fff;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(79,70,229,0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-right: 8px;
        }

        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 0.8rem;
            color: #6B7280;
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: var(--primary);
        }

        .form-check {
            margin-top: 0.3rem;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            font-size: 0.85rem;
            color: #4B5563;
        }

        /* ===== ALERT STYLES ===== */
        .alert-custom {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            border: none;
        }

        .alert-custom.alert-danger {
            background: #FEF2F2;
            color: #DC2626;
        }

        .alert-custom.alert-success {
            background: #F0FDF4;
            color: #16A34A;
        }

        /* ===== DEMO CREDENTIALS SECTION ===== */
        .demo-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #F3F4F6;
        }

        .demo-section h6 {
            font-size: 0.85rem;
            color: #1F2937;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .demo-section h6 i {
            color: var(--primary);
        }

        .demo-grid-text {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .credential-item {
            background: #F8FAFC;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid #E2E8F0;
            display: flex;
            flex-direction: column;
            gap: 2px;
            transition: var(--transition);
        }

        .credential-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(79,70,229,0.08);
        }

        .credential-item .role {
            font-weight: 600;
            color: #0F172A;
            font-size: 0.85rem;
        }

        .credential-item .role i {
            color: var(--primary);
            margin-right: 6px;
        }

        .credential-item .email {
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 500;
            word-break: break-all;
        }

        .credential-item .password {
            color: #6B7280;
            font-size: 0.7rem;
            font-family: 'Inter', monospace;
            background: #F1F5F9;
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
            width: fit-content;
        }

        .password-hint {
            text-align: center;
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 0.5rem;
        }

        .password-hint i {
            color: var(--primary);
        }

        .password-hint strong {
            color: var(--primary);
            background: rgba(79,70,229,0.08);
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem;
            }
            .login-header h1 {
                font-size: 1.3rem;
            }
            .demo-grid-text {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- ===== HEADER ===== -->
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <h1>Welcome to <span>TransitOps</span></h1>
                <p>Sign in to manage your fleet operations</p>
            </div>

            <!-- ===== ERROR ALERT ===== -->
            <?php if ($error): ?>
                <div class="alert-custom alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- ===== LOGIN FORM ===== -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <div class="input-group-custom">
                        <input type="email" class="form-control" 
                               id="email" name="email" required 
                               placeholder="Enter your email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" 
                               id="password" name="password" required 
                               placeholder="Enter your password"
                               autocomplete="current-password">
                        <button type="button" class="input-icon" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link" id="forgotPassword" style="margin:0;font-size:0.8rem;">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <!-- ===== DEMO CREDENTIALS (STATIC TEXT-ONLY) ===== -->
            <div class="demo-section">
                <h6><i class="fas fa-info-circle"></i> Demo Credentials</h6>
                <div class="demo-grid-text">
                    <div class="credential-item">
                        <span class="role"><i class="fas fa-user-tie"></i> Fleet Manager</span>
                        <span class="email">niraj@gmail.com</span>
                        <span class="password">niraj@123</span>
                    </div>
                    <div class="credential-item">
                        <span class="role"><i class="fas fa-user"></i> Driver</span>
                        <span class="email">anand@gmail.com</span>
                        <span class="password">anand@123</span>
                    </div>
                    <div class="credential-item">
                        <span class="role"><i class="fas fa-shield-alt"></i> Safety Officer</span>
                        <span class="email">krushnraj@gmail.com</span>
                        <span class="password">krushnraj@123</span>
                    </div>
                    <div class="credential-item">
                        <span class="role"><i class="fas fa-chart-line"></i> Financial Analyst</span>
                        <span class="email">krishn@gmail.com</span>
                        <span class="password">krishn@123</span>
                    </div>
                </div>
                <div class="password-hint">
                    <i class="fas fa-key"></i> Use these credentials to login
                </div>
            </div>
        </div>
    </div>

    <!-- ===== SCRIPTS ===== -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // ===== TOGGLE PASSWORD VISIBILITY =====
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

            // ===== FORGOT PASSWORD =====
            $('#forgotPassword').click(function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Reset Password',
                    html: '<input type="email" id="reset-email" class="form-control" placeholder="Enter your email" style="padding:0.75rem 1rem;border:2px solid #E5E7EB;border-radius:10px;font-family:Inter;">',
                    showCancelButton: true,
                    confirmButtonText: 'Send Reset Link',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
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
                            confirmButtonColor: '#4F46E5'
                        });
                    }
                });
            });

            // ===== FORM SUBMISSION =====
            $('#loginForm').on('submit', function(e) {
                var email = $('#email').val().trim();
                var password = $('#password').val().trim();
                
                if (!email || !password) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Fields',
                        text: 'Please enter both email and password.',
                        confirmButtonColor: '#4F46E5'
                    });
                    return false;
                }
                
                var $btn = $('#loginBtn');
                var originalHtml = $btn.html();
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Signing In...');
                $btn.prop('disabled', true);
                
                setTimeout(function() {
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }, 10000);
                
                return true;
            });

            // ===== CHECK FOR LOGOUT MESSAGE =====
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