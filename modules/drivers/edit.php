<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Get driver data
$stmt = $db->prepare("SELECT * FROM drivers WHERE driver_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

if (!$driver) {
    header("Location: index.php");
    exit();
}

// Get user account for this driver
$userStmt = $db->prepare("SELECT * FROM users WHERE full_name = ? AND role = 'Driver'");
$userStmt->bind_param("s", $driver['full_name']);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $license_number = sanitize($_POST['license_number']);
    $license_category = sanitize($_POST['license_category']);
    $license_expiry = sanitize($_POST['license_expiry_date']);
    $contact = sanitize($_POST['contact_number']);
    $safety_score = floatval($_POST['safety_score']);
    $status = sanitize($_POST['status']);
    $email = sanitize($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if license number is unique (excluding current driver)
    $checkStmt = $db->prepare("SELECT driver_id FROM drivers WHERE license_number = ? AND driver_id != ?");
    $checkStmt->bind_param("si", $license_number, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = 'License number already exists. Please use a unique number.';
    } else {
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Update driver
            $updateStmt = $db->prepare("UPDATE drivers SET full_name = ?, license_number = ?, license_category = ?, license_expiry_date = ?, contact_number = ?, safety_score = ?, status = ? WHERE driver_id = ?");
            $updateStmt->bind_param("sssssdsi", $full_name, $license_number, $license_category, $license_expiry, $contact, $safety_score, $status, $id);
            
            if ($updateStmt->execute()) {
                // Update user account
                if ($user) {
                    $userUpdate = $db->prepare("UPDATE users SET email = ?, full_name = ? WHERE user_id = ?");
                    $userUpdate->bind_param("ssi", $email, $full_name, $user['user_id']);
                    $userUpdate->execute();
                    
                    // Update password if provided
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            throw new Exception('Password must be at least 6 characters long.');
                        }
                        if ($new_password !== $confirm_password) {
                            throw new Exception('Passwords do not match.');
                        }
                        
                        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $passUpdate = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                        $passUpdate->bind_param("si", $password_hash, $user['user_id']);
                        $passUpdate->execute();
                    }
                }
                
                $db->commit();
                $success = 'Driver updated successfully!';
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Updated!",
                        text: "Driver updated successfully.",
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = "index.php";
                    });
                </script>';
            } else {
                throw new Exception('Failed to update driver');
            }
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driver - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .form-section h6 {
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .password-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 4px;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 6px;
            transition: all 0.3s ease;
        }
        .password-strength.weak { background: #dc3545; width: 25%; }
        .password-strength.fair { background: #ffc107; width: 50%; }
        .password-strength.good { background: #0dcaf0; width: 75%; }
        .password-strength.strong { background: #198754; width: 100%; }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-edit"></i> Edit Driver</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="driverForm">
                            <!-- Personal Information -->
                            <div class="form-section">
                                <h6><i class="fas fa-user"></i> Personal Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" 
                                               name="full_name" required 
                                               value="<?php echo $driver['full_name']; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_number" class="form-label">Contact Number *</label>
                                        <input type="text" class="form-control" id="contact_number" 
                                               name="contact_number" required 
                                               value="<?php echo $driver['contact_number']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- License Information -->
                            <div class="form-section">
                                <h6><i class="fas fa-id-card"></i> License Information</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="license_number" class="form-label">License Number *</label>
                                        <input type="text" class="form-control" id="license_number" 
                                               name="license_number" required 
                                               value="<?php echo $driver['license_number']; ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="license_category" class="form-label">License Category *</label>
                                        <select class="form-select" id="license_category" name="license_category" required>
                                            <option value="">Select Category</option>
                                            <?php 
                                            $categories = ['Class A', 'Class B', 'Class C', 'Class D', 'Class E'];
                                            foreach ($categories as $cat):
                                            ?>
                                                <option value="<?php echo $cat; ?>" 
                                                    <?php echo $driver['license_category'] == $cat ? 'selected' : ''; ?>>
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="license_expiry_date" class="form-label">License Expiry Date *</label>
                                        <input type="date" class="form-control" id="license_expiry_date" 
                                               name="license_expiry_date" required
                                               value="<?php echo $driver['license_expiry_date']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Account Information -->
                            <div class="form-section" style="background: #e8f0fe; border: 2px solid #667eea;">
                                <h6><i class="fas fa-key"></i> Account Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" 
                                               name="email" required 
                                               value="<?php echo $user ? $user['email'] : ''; ?>">
                                        <small class="text-muted">Used for login</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">New Password (Optional)</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" placeholder="Leave blank to keep current">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <small class="password-hint">Min 6 characters. Leave blank to keep current password.</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" placeholder="Confirm new password">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Available" <?php echo $driver['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="On_Trip" <?php echo $driver['status'] == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                            <option value="Off_Duty" <?php echo $driver['status'] == 'Off_Duty' ? 'selected' : ''; ?>>Off Duty</option>
                                            <option value="Suspended" <?php echo $driver['status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="safety_score" class="form-label">Safety Score (%)</label>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="safety_score" name="safety_score" 
                                               value="<?php echo $driver['safety_score']; ?>" min="0" max="100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Update Driver
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
            var passwordInput = $('#new_password');
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
        $('#new_password').on('input', function() {
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
            var password = $('#new_password').val();
            var confirm = $('#confirm_password').val();
            
            // Only validate if password is being changed
            if (password || confirm) {
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
            }
            
            // Show loading state
            var $btn = $('#submitBtn');
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
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