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

// Debug mode - uncomment to see errors
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration = sanitize($_POST['registration_number']);
    $model = sanitize($_POST['model']);
    $type = sanitize($_POST['vehicle_type']);
    $capacity = floatval($_POST['max_load_capacity']);
    $odometer = floatval($_POST['odometer_reading']);
    $cost = floatval($_POST['acquisition_cost']);
    $status = sanitize($_POST['status']);
    
    // Check if fields are empty
    if (empty($registration) || empty($model) || empty($type) || empty($capacity)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Validate unique registration number
        $checkStmt = $db->prepare("SELECT vehicle_id FROM vehicles WHERE registration_number = ?");
        $checkStmt->bind_param("s", $registration);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Registration number "' . $registration . '" already exists. Please use a unique number.';
        } else {
            // FIXED: Proper SQL with correct column count
            $stmt = $db->prepare("INSERT INTO vehicles (registration_number, model, vehicle_type, max_load_capacity, odometer_reading, acquisition_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // 7 parameters: 3 strings + 4 decimals
            $stmt->bind_param("sssddds", $registration, $model, $type, $capacity, $odometer, $cost, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Vehicle "' . $registration . '" added successfully!';
                header("Location: index.php");
                exit();
            } else {
                $error = 'Failed to add vehicle. Database error: ' . $db->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

$page_title = 'Add Vehicle';
$page_subtitle = 'Register a new vehicle in the fleet';
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
        display: block;
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
        width: 100%;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        outline: none;
    }
    .form-control::placeholder {
        color: #94A3B8;
        font-size: 0.9rem;
    }
    .form-text {
        color: #64748B;
        font-size: 0.8rem;
        margin-top: 4px;
    }
    .form-text i {
        margin-right: 4px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border: none;
        padding: 0.7rem 2rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
        color: #fff;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79,70,229,0.4);
        color: #fff;
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
    .input-group-text {
        background: #F1F5F9;
        border: 2px solid #E2E8F0;
        border-right: none;
        border-radius: 10px 0 0 10px;
        color: #64748B;
        font-weight: 500;
    }
    .input-group .form-control {
        border-radius: 0 10px 10px 0;
        border-left: none;
    }
    .input-group .form-control:focus {
        border-left: none;
    }
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-plus-circle text-primary"></i> Add New Vehicle
                    </h4>
                    <small class="text-muted">Register a vehicle in the fleet management system</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-custom alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="vehicleForm">
                        <!-- Vehicle Details -->
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
                                           placeholder="e.g., VAN-001">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Unique vehicle identifier
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">
                                        Vehicle Model <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="model" 
                                           name="model" required 
                                           placeholder="e.g., Ford Transit 2023">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_type" class="form-label">
                                        Vehicle Type <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="Van">Van</option>
                                        <option value="Truck">Truck</option>
                                        <option value="Trailer">Trailer</option>
                                        <option value="Bus">Bus</option>
                                        <option value="Car">Car</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_load_capacity" class="form-label">
                                        Max Load Capacity (kg) <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="max_load_capacity" name="max_load_capacity" required 
                                               placeholder="Enter capacity in kg">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-info-circle"></i> Additional Information
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="odometer_reading" class="form-label">
                                        Odometer Reading (km)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tachometer-alt"></i></span>
                                        <input type="number" step="0.1" class="form-control" 
                                               id="odometer_reading" name="odometer_reading" 
                                               placeholder="Current odometer reading" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="acquisition_cost" class="form-label">
                                        Acquisition Cost ($)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="acquisition_cost" name="acquisition_cost" 
                                               placeholder="Purchase cost" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">
                                        Status <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available">Available</option>
                                        <option value="On_Trip">On Trip</option>
                                        <option value="In_Shop">In Shop</option>
                                        <option value="Retired">Retired</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Current operational status of the vehicle
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Add Vehicle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('vehicleForm')?.addEventListener('submit', function(e) {
    var registration = document.getElementById('registration_number').value.trim();
    var model = document.getElementById('model').value.trim();
    var type = document.getElementById('vehicle_type').value;
    var capacity = document.getElementById('max_load_capacity').value;
    
    if (!registration || !model || !type || !capacity) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Missing Fields',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#4F46E5'
        });
        return false;
    }
    
    // Show loading state
    var btn = document.getElementById('submitBtn');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    btn.disabled = true;
    
    // Re-enable after 10 seconds (in case of slow response)
    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }, 10000);
    
    return true;
});
</script>

<?php include '../../includes/footer.php'; ?>