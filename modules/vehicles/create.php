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
    $registration = sanitize($_POST['registration_number']);
    $model = sanitize($_POST['model']);
    $type = sanitize($_POST['vehicle_type']);
    $capacity = floatval($_POST['max_load_capacity']);
    $odometer = floatval($_POST['odometer_reading']);
    $cost = floatval($_POST['acquisition_cost']);
    $status = sanitize($_POST['status']);
    
    // Validate unique registration number
    $checkStmt = $db->prepare("SELECT vehicle_id FROM vehicles WHERE registration_number = ?");
    $checkStmt->bind_param("s", $registration);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = 'Registration number already exists. Please use a unique number.';
    } else {
        $stmt = $db->prepare("INSERT INTO vehicles (registration_number, model, vehicle_type, max_load_capacity, odometer_reading, acquisition_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddds", $registration, $model, $type, $capacity, $odometer, $cost, $status);
        
        if ($stmt->execute()) {
            $success = 'Vehicle added successfully!';
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: "Vehicle added successfully.",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to add vehicle. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-plus-circle"></i> Add New Vehicle</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="registration_number" class="form-label">Registration Number *</label>
                                    <input type="text" class="form-control" id="registration_number" 
                                           name="registration_number" required placeholder="e.g., VAN-001">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">Vehicle Model *</label>
                                    <input type="text" class="form-control" id="model" 
                                           name="model" required placeholder="e.g., Ford Transit 2023">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Van">Van</option>
                                        <option value="Truck">Truck</option>
                                        <option value="Trailer">Trailer</option>
                                        <option value="Bus">Bus</option>
                                        <option value="Car">Car</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_load_capacity" class="form-label">Max Load Capacity (kg) *</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="max_load_capacity" name="max_load_capacity" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="odometer_reading" class="form-label">Odometer Reading (km)</label>
                                    <input type="number" step="0.1" class="form-control" 
                                           id="odometer_reading" name="odometer_reading" value="0">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="acquisition_cost" class="form-label">Acquisition Cost ($)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="acquisition_cost" name="acquisition_cost" value="0">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available">Available</option>
                                        <option value="On_Trip">On Trip</option>
                                        <option value="In_Shop">In Shop</option>
                                        <option value="Retired">Retired</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Vehicle
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