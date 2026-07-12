<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get driver data if user is a driver
$driver_data = null;
if ($user['role'] === 'Driver') {
    $driverStmt = $db->prepare("SELECT * FROM drivers WHERE full_name = ?");
    $driverStmt->bind_param("s", $user['full_name']);
    $driverStmt->execute();
    $driverResult = $driverStmt->get_result();
    $driver_data = $driverResult->fetch_assoc();
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists for other users
        $emailCheck = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $emailCheck->bind_param("si", $email, $user_id);
        $emailCheck->execute();
        $emailResult = $emailCheck->get_result();
        
        if ($emailResult->num_rows > 0) {
            $error = 'Email already in use by another account.';
        } else {
            // Start building update query
            $updateFields = [];
            $params = [];
            $types = "";
            
            // Update name and email
            $updateFields[] = "full_name = ?";
            $params[] = $full_name;
            $types .= "s";
            
            $updateFields[] = "email = ?";
            $params[] = $email;
            $types .= "s";
            
            // Update password if provided
            if (!empty($new_password)) {
                // Verify current password
                $passCheck = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $passCheck->bind_param("i", $user_id);
                $passCheck->execute();
                $passResult = $passCheck->get_result();
                $passData = $passResult->fetch_assoc();
                
                if (!password_verify($current_password, $passData['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } else {
                    $updateFields[] = "password_hash = ?";
                    $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                    $types .= "s";
                }
            }
            
            if (empty($error)) {
                $params[] = $user_id;
                $types .= "i";
                
                $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
                $updateStmt = $db->prepare($sql);
                $updateStmt->bind_param($types, ...$params);
                
                if ($updateStmt->execute()) {
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    
                    $success = 'Profile updated successfully!';
                    
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Profile';
$page_subtitle = 'Manage your account settings';
?>
<?php include '../../includes/header.php'; ?>

<style>
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    color: #fff;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 30px rgba(79,70,229,0.3);
}
.profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}
.profile-stats .stat-box {
    background: #F9FAFB;
    padding: 1rem;
    border-radius: var(--radius);
    text-align: center;
}
.profile-stats .stat-box h5 {
    font-weight: 700;
    color: var(--primary);
    margin: 0;
}
.profile-stats .stat-box small {
    color: #6B7280;
    font-size: 0.75rem;
}
@media (max-width: 768px) {
    .profile-stats {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 576px) {
    .profile-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="row">
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center p-4">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                </div>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted small">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <span class="badge bg-primary bg-gradient px-3 py-2 rounded-pill">
                    <i class="fas fa-user-tag"></i> <?php echo str_replace('_', ' ', $user['role']); ?>
                </span>
                
                <div class="profile-stats">
                    <div class="stat-box">
                        <h5><?php echo date('Y-m-d', strtotime($user['created_at'] ?? 'now')); ?></h5>
                        <small>Joined</small>
                    </div>
                    <div class="stat-box">
                        <h5><?php echo $user['last_login'] ? date('Y-m-d', strtotime($user['last_login'])) : 'Never'; ?></h5>
                        <small>Last Login</small>
                    </div>
                    <div class="stat-box">
                        <h5><?php echo $user['status'] ?? 'Active'; ?></h5>
                        <small>Status</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Driver Info (if driver) -->
        <?php if ($driver_data): ?>
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-id-card text-primary"></i> Driver Information</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">License Number</span>
                    <span class="fw-medium"><?php echo htmlspecialchars($driver_data['license_number']); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">License Category</span>
                    <span class="fw-medium"><?php echo htmlspecialchars($driver_data['license_category']); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">License Expiry</span>
                    <span class="fw-medium <?php echo strtotime($driver_data['license_expiry_date']) < time() ? 'text-danger' : ''; ?>">
                        <?php echo date('Y-m-d', strtotime($driver_data['license_expiry_date'])); ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Safety Score</span>
                    <span class="fw-medium">
                        <span class="badge <?php echo $driver_data['safety_score'] >= 80 ? 'bg-success' : ($driver_data['safety_score'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                            <?php echo $driver_data['safety_score']; ?>%
                        </span>
                    </span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Status</span>
                    <span class="fw-medium"><?php echo str_replace('_', ' ', $driver_data['status']); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-8">
        <!-- Update Profile Form -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary"></i> Edit Profile</h5>
                <small class="text-muted">Update your personal information</small>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="profileForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label fw-medium">Full Name</label>
                            <input type="text" class="form-control" id="full_name" 
                                   name="full_name" required 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-medium">Email Address</label>
                            <input type="email" class="form-control" id="email" 
                                   name="email" required 
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="fw-bold mb-3"><i class="fas fa-key text-primary"></i> Change Password</h6>
                    <p class="text-muted small">Leave fields blank to keep current password</p>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label fw-medium">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" placeholder="Enter current password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label fw-medium">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" placeholder="Min 6 characters">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="../dashboard/index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Activity -->
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-clock text-primary"></i> Account Activity</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Account Created</span>
                    <span><?php echo date('M d, Y H:i', strtotime($user['created_at'] ?? 'now')); ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Last Login</span>
                    <span><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Account Status</span>
                    <span class="badge bg-success"><?php echo $user['status'] ?? 'Active'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    var input = document.getElementById(id);
    var icon = input.parentElement.querySelector('button i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

document.getElementById('profileForm')?.addEventListener('submit', function(e) {
    var newPass = document.getElementById('new_password').value;
    var confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass || confirmPass) {
        if (newPass !== confirmPass) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'New password and confirmation do not match.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        if (newPass.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Password Too Short',
                text: 'Password must be at least 6 characters.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        if (!document.getElementById('current_password').value) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Current Password Required',
                text: 'Please enter your current password to change it.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
    }
    
    // Show loading state
    var btn = document.getElementById('saveBtn');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>