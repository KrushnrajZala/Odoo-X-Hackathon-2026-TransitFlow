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
            $success = 'Vehicle updated successfully!';
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: "Vehicle updated successfully.",
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to update vehicle. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-edit"></i> Edit Vehicle</h4>
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
                                           name="registration_number" required 
                                           value="<?php echo $vehicle['registration_number']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">Vehicle Model *</label>
                                    <input type="text" class="form-control" id="model" 
                                           name="model" required 
                                           value="<?php echo $vehicle['model']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_type" class="form-label">Vehicle Type *</label>
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
                                    <label for="max_load_capacity" class="form-label">Max Load Capacity (kg) *</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="max_load_capacity" name="max_load_capacity" required
                                           value="<?php echo $vehicle['max_load_capacity']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="odometer_reading" class="form-label">Odometer Reading (km)</label>
                                    <input type="number" step="0.1" class="form-control" 
                                           id="odometer_reading" name="odometer_reading"
                                           value="<?php echo $vehicle['odometer_reading']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="acquisition_cost" class="form-label">Acquisition Cost ($)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="acquisition_cost" name="acquisition_cost"
                                           value="<?php echo $vehicle['acquisition_cost']; ?>">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available" <?php echo $vehicle['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="On_Trip" <?php echo $vehicle['status'] == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                        <option value="In_Shop" <?php echo $vehicle['status'] == 'In_Shop' ? 'selected' : ''; ?>>In Shop</option>
                                        <option value="Retired" <?php echo $vehicle['status'] == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Vehicle
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