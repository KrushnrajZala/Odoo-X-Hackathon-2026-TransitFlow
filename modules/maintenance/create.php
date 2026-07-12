<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$error = '';

// Get vehicles not in maintenance
$vehicleQuery = "SELECT * FROM vehicles WHERE status IN ('Available', 'On_Trip') ORDER BY registration_number";
$vehicles = $db->query($vehicleQuery);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = intval($_POST['vehicle_id']);
    $maintenance_type = sanitize($_POST['maintenance_type']);
    $description = sanitize($_POST['description']);
    $cost = floatval($_POST['cost']);
    $maintenance_date = sanitize($_POST['maintenance_date']);
    $status = 'Active';
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Insert maintenance record
        $stmt = $db->prepare("INSERT INTO maintenance_logs (vehicle_id, maintenance_type, description, cost, maintenance_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $vehicle_id, $maintenance_type, $description, $cost, $maintenance_date, $status);
        
        if ($stmt->execute()) {
            // Update vehicle status to In_Shop
            $updateVehicle = $db->prepare("UPDATE vehicles SET status = 'In_Shop' WHERE vehicle_id = ?");
            $updateVehicle->bind_param("i", $vehicle_id);
            $updateVehicle->execute();
            
            $db->commit();
            
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Maintenance Added!",
                    text: "Vehicle status changed to In Shop.",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            throw new Exception('Failed to add maintenance record');
        }
    } catch (Exception $e) {
        $db->rollback();
        $error = 'Failed to add maintenance. Please try again.';
    }
}

$page_title = 'Add Maintenance';
$page_subtitle = 'Schedule vehicle maintenance';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .form-container {
        max-width: 700px;
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
    .info-box {
        background: #EFF6FF;
        padding: 12px 16px;
        border-radius: 8px;
        border-left: 4px solid #4F46E5;
        margin-bottom: 16px;
    }
    .info-box i {
        color: #4F46E5;
        margin-right: 8px;
    }
    .info-box .text {
        color: #1E293B;
        font-size: 0.9rem;
    }
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-tools text-primary"></i> Add Maintenance Record
                    </h4>
                    <small class="text-muted">Schedule maintenance for a vehicle</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <span class="text">Creating a maintenance record will automatically change the vehicle status to <strong>"In Shop"</strong> and remove it from dispatch selection.</span>
                    </div>
                    
                    <form method="POST" action="">
                        <!-- Maintenance Details -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-wrench"></i> Maintenance Details
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="vehicle_id" class="form-label">
                                        Select Vehicle <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">-- Select Vehicle --</option>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                                (<?php echo $vehicle['status']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Vehicle will be set to "In Shop" status
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="maintenance_type" class="form-label">
                                        Maintenance Type <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="Oil Change">Oil Change</option>
                                        <option value="Tire Replacement">Tire Replacement</option>
                                        <option value="Engine Repair">Engine Repair</option>
                                        <option value="Brake Service">Brake Service</option>
                                        <option value="Transmission Service">Transmission Service</option>
                                        <option value="General Service">General Service</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">
                                        Description
                                    </label>
                                    <textarea class="form-control" id="description" 
                                              name="description" rows="3" 
                                              placeholder="Describe the maintenance work needed"></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cost" class="form-label">
                                        Cost ($)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="cost" name="cost" placeholder="Estimated cost" value="0">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="maintenance_date" class="form-label">
                                        Maintenance Date <span class="required">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="maintenance_date" 
                                           name="maintenance_date" required 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Add Maintenance
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    btn.disabled = true;
    
    setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>