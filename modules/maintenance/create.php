<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$error = '';

// Get vehicles not in maintenance (or already in shop)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Maintenance - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-wrench"></i> Add Maintenance Record</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Vehicle *</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Select Vehicle</option>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Note: Vehicle will be set to "In Shop" status</small>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="maintenance_type" class="form-label">Maintenance Type *</label>
                                    <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                        <option value="">Select Type</option>
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
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" 
                                              name="description" rows="3" 
                                              placeholder="Describe the maintenance work"></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cost" class="form-label">Cost ($)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="cost" name="cost" value="0">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="maintenance_date" class="form-label">Maintenance Date *</label>
                                    <input type="date" class="form-control" id="maintenance_date" 
                                           name="maintenance_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Maintenance
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>