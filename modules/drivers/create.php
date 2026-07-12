<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $license_number = sanitize($_POST['license_number']);
    $license_category = sanitize($_POST['license_category']);
    $license_expiry = sanitize($_POST['license_expiry_date']);
    $contact = sanitize($_POST['contact_number']);
    $safety_score = floatval($_POST['safety_score']);
    $status = sanitize($_POST['status']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    // Validate password
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    }
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    }
    else {
        // Check if license number already exists
        $checkStmt = $db->prepare("SELECT driver_id FROM drivers WHERE license_number = ?");
        $checkStmt->bind_param("s", $license_number);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'License number already exists. Please use a unique number.';
        } else {
            // Check if email already exists in users
            $emailCheck = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $emailCheck->bind_param("s", $email);
            $emailCheck->execute();
            $emailResult = $emailCheck->get_result();
            
            if ($emailResult->num_rows > 0) {
                $error = 'Email already registered. Please use a different email.';
            } else {
                // Start transaction
                $db->begin_transaction();
                
                try {
                    // 1. Insert driver
                    $stmt = $db->prepare("INSERT INTO drivers (full_name, license_number, license_category, license_expiry_date, contact_number, safety_score, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssds", $full_name, $license_number, $license_category, $license_expiry, $contact, $safety_score, $status);
                    
                    if ($stmt->execute()) {
                        $driver_id = $db->insert_id;
                        
                        // 2. Create user account for driver
                        $username = strtolower(str_replace(' ', '_', $full_name)) . '_' . rand(100, 999);
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $role = 'Driver';
                        
                        $userStmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?, 'Active')");
                        $userStmt->bind_param("sssss", $username, $email, $password_hash, $full_name, $role);
                        
                        if ($userStmt->execute()) {
                            $db->commit();
                            $success = 'Driver and user account created successfully!';
                            echo '<script>
                                Swal.fire({
                                    icon: "success",
                                    title: "Success!",
                                    html: "Driver <strong>' . $full_name . '</strong> created successfully!<br><br>Email: <strong>' . $email . '</strong><br>Password: <strong>' . $password . '</strong>",
                                    timer: 5000,
                                    showConfirmButton: true,
                                    confirmButtonColor: "#667eea"
                                }).then(function() {
                                    window.location.href = "index.php";
                                });
                            </script>';
                        } else {
                            throw new Exception('Failed to create user account');
                        }
                    } else {
                        throw new Exception('Failed to create driver');
                    }
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Failed to create driver. Please try again. Error: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Driver - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        .form-section h6 {
            color: #4a5568;
            margin-bottom: 18px;
            font-weight: 700;
            font-size: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-section h6 i {
            color: #667eea;
            margin-right: 8px;
        }
        .form-label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .form-label .required {
            color: #dc3545;
            margin-left: 4px;
        }
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        .form-control::placeholder {
            color: #a0aec0;
            font-size: 0.9rem;
        }
        .text-muted-small {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 4px;
        }
        .text-muted-small i {
            margin-right: 4px;
        }
        .password-hint {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 4px;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            transition: all 0.3s ease;
            background: #e2e8f0;
        }
        .password-strength.weak { background: #fc8181; width: 25%; }
        .password-strength.fair { background: #f6ad55; width: 50%; }
        .password-strength.good { background: #68d391; width: 75%; }
        .password-strength.strong { background: #48bb78; width: 100%; }
        
        .login-credentials-section {
            background: linear-gradient(135deg, #ebf4ff 0%, #f0f4ff 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 20px;
        }
        .login-credentials-section h6 {
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 700;
            font-size: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .login-credentials-section h6 i {
            color: #667eea;
            margin-right: 8px;
        }
        .login-credentials-section .info-text {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #4a5568;
        }
        .login-credentials-section .info-text i {
            color: #667eea;
            margin-right: 6px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.7rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            border-radius: 10px;
            padding: 0.7rem 2rem;
            font-weight: 600;
        }
        
        .input-group .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: white;
        }
        .input-group .btn-outline-secondary:hover {
            background: #f7fafc;
        }
        .input-group .form-control {
            border-radius: 10px 0 0 10px;
        }
        
        @media (max-width: 768px) {
            .form-section, .login-credentials-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h4 class="fw-bold">
                            <i class="fas fa-user-plus text-primary"></i> 
                            Add New Driver
                        </h4>
                        <p class="text-muted small">Create a new driver profile and user account</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show rounded-3">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="driverForm">
                            <!-- Personal Information -->
                            <div class="form-section">
                                <h6><i class="fas fa-user-circle"></i> Personal Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">
                                            Full Name <span class="required">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="full_name" 
                                               name="full_name" required 
                                               placeholder="Enter full name (e.g., John Doe)">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">
                                            Contact Number <span class="required">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="contact_number" 
                                               name="contact_number" required 
                                               placeholder="Enter contact number (e.g., +1 555-0101)">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- License Information -->
                            <div class="form-section">
                                <h6><i class="fas fa-id-card"></i> License Information</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="license_number" class="form-label">
                                            License Number <span class="required">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="license_number" 
                                               name="license_number" required 
                                               placeholder="e.g., DL2024-001">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="license_category" class="form-label">
                                            License Category <span class="required">*</span>
                                        </label>
                                        <select class="form-select" id="license_category" name="license_category" required>
                                            <option value="">-- Select Category --</option>
                                            <option value="Class A">Class A</option>
                                            <option value="Class B">Class B</option>
                                            <option value="Class C">Class C</option>
                                            <option value="Class D">Class D</option>
                                            <option value="Class E">Class E</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="license_expiry_date" class="form-label">
                                            License Expiry Date <span class="required">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="license_expiry_date" 
                                               name="license_expiry_date" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Login Credentials -->
                            <div class="login-credentials-section">
                                <h6><i class="fas fa-key"></i> Login Credentials</h6>
                                <div class="info-text">
                                    <i class="fas fa-info-circle"></i> 
                                    These credentials will be used by the driver to login to the system.
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            Email Address <span class="required">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="email" 
                                               name="email" required 
                                               placeholder="driver@example.com">
                                        <div class="text-muted-small">
                                            <i class="fas fa-envelope"></i> This will be used as username for login
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">
                                            Status <span class="required">*</span>
                                        </label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Available">Available</option>
                                            <option value="Off_Duty">Off Duty</option>
                                            <option value="Suspended">Suspended</option>
                                        </select>
                                        <div class="text-muted-small">
                                            <i class="fas fa-info-circle"></i> Driver availability status
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">
                                            Password <span class="required">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" 
                                                   name="password" required 
                                                   placeholder="Min 6 characters">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <div class="password-hint">
                                            <i class="fas fa-shield-alt"></i> Password must be at least 6 characters
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">
                                            Confirm Password <span class="required">*</span>
                                        </label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required 
                                               placeholder="Re-enter password">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="safety_score" class="form-label">
                                            Safety Score (%)
                                        </label>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="safety_score" name="safety_score" 
                                               value="100" min="0" max="100"
                                               placeholder="Enter safety score">
                                        <div class="text-muted-small">
                                            <i class="fas fa-chart-line"></i> Default: 100%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Create Driver & User Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
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
        
        // Password strength checker
        $('#password').on('input', function() {
            var password = $(this).val();
            var strengthBar = $('#passwordStrength');
            
            if (password.length === 0) {
                strengthBar.removeClass('weak fair good strong');
                return;
            }
            
            var strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.removeClass('weak fair good strong');
            
            if (strength <= 2) {
                strengthBar.addClass('weak');
            } else if (strength <= 4) {
                strengthBar.addClass('fair');
            } else if (strength <= 5) {
                strengthBar.addClass('good');
            } else {
                strengthBar.addClass('strong');
            }
        });
        
        // Form validation
        $('#driverForm').on('submit', function(e) {
            var password = $('#password').val();
            var confirm = $('#confirm_password').val();
            
            if (password !== confirm) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match. Please try again.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Too Short',
                    text: 'Password must be at least 6 characters long.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            // Show loading state
            var $btn = $('#submitBtn');
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            $btn.prop('disabled', true);
            
            setTimeout(function() {
                $btn.html(originalHtml);
                $btn.prop('disabled', false);
            }, 10000);
            
            return true;
        });
    });
    </script>
</body>
</html>