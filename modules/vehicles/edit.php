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

// Get vehicle data
$stmt = $db->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    $_SESSION['error_message'] = 'Vehicle not found.';
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration = sanitize($_POST['registration_number']);
    $model = sanitize($_POST['model']);
    $type = sanitize($_POST['vehicle_type']);
    $capacity = floatval($_POST['max_load_capacity']);
    $odometer = floatval($_POST['odometer_reading']);
    $cost = floatval($_POST['acquisition_cost']);
    $status = sanitize($_POST['status']);
    
    // Check if registration number is unique (excluding current vehicle)
    $checkStmt = $db->prepare("SELECT vehicle_id FROM vehicles WHERE registration_number = ? AND vehicle_id != ?");
    $checkStmt->bind_param("si", $registration, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = 'Registration number already exists. Please use a unique number.';
    } else {
        $updateStmt = $db->prepare("UPDATE vehicles SET registration_number = ?, model = ?, vehicle_type = ?, max_load_capacity = ?, odometer_reading = ?, acquisition_cost = ?, status = ? WHERE vehicle_id = ?");
        $updateStmt->bind_param("sssdddsi", $registration, $model, $type, $capacity, $odometer, $cost, $status, $id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = 'Vehicle "' . $registration . '" updated successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Failed to update vehicle. Please try again.';
        }
    }
}

$page_title = 'Edit Vehicle';
$page_subtitle = 'Update vehicle details';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .form-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .form-section {
        background: #F8FAFC;
        padding: 20px 24px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #E2E8F0;
    }
    .form-section .section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0F172A;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid #4F46E5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section .section-title i {
        color: #4F46E5;
    }
    .form-label {
        font-weight: 600;
        color: #1E293B;
        font-size: 0.9rem;
        margin-bottom: 6px;
    }
    .form-label .required {
        color: #EF4444;
        margin-left: 4px;
    }
    .form-control, .form-select {
        border: 2px solid #E2E8F0;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #FFFFFF;
        color: #0F172A;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        outline: none;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border: none;
        padding: 0.7rem 2rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79,70,229,0.4);
    }
    .btn-secondary {
        border-radius: 10px;
        padding: 0.7rem 2rem;
        font-weight: 600;
    }
    .alert-custom {
        border-radius: 10px;
        border: none;
        padding: 0.8rem 1.2rem;
    }
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-edit text-primary"></i> Edit Vehicle
                    </h4>
                    <small class="text-muted">Update vehicle details</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-truck"></i> Vehicle Details
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="registration_number" class="form-label">
                                        Registration Number <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="registration_number" 
                                           name="registration_number" required 
                                           value="<?php echo $vehicle['registration_number']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">
                                        Vehicle Model <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="model" 
                                           name="model" required 
                                           value="<?php echo $vehicle['model']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_type" class="form-label">
                                        Vehicle Type <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                        <option value="">Select Type</option>
                                        <?php 
                                        $types = ['Van', 'Truck', 'Trailer', 'Bus', 'Car', 'Other'];
                                        foreach ($types as $type):
                                        ?>
                                            <option value="<?php echo $type; ?>" 
                                                <?php echo $vehicle['vehicle_type'] == $type ? 'selected' : ''; ?>>
                                                <?php echo $type; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_load_capacity" class="form-label">
                                        Max Load Capacity (kg) <span class="required">*</span>
                                    </label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="max_load_capacity" name="max_load_capacity" required
                                           value="<?php echo $vehicle['max_load_capacity']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="odometer_reading" class="form-label">
                                        Odometer Reading (km)
                                    </label>
                                    <input type="number" step="0.1" class="form-control" 
                                           id="odometer_reading" name="odometer_reading"
                                           value="<?php echo $vehicle['odometer_reading']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="acquisition_cost" class="form-label">
                                        Acquisition Cost ($)
                                    </label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="acquisition_cost" name="acquisition_cost"
                                           value="<?php echo $vehicle['acquisition_cost']; ?>">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">
                                        Status <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available" <?php echo $vehicle['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="On_Trip" <?php echo $vehicle['status'] == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                        <option value="In_Shop" <?php echo $vehicle['status'] == 'In_Shop' ? 'selected' : ''; ?>>In Shop</option>
                                        <option value="Retired" <?php echo $vehicle['status'] == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Update Vehicle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('submitBtn')?.addEventListener('click', function() {
    var btn = this;
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    btn.disabled = true;
    
    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>