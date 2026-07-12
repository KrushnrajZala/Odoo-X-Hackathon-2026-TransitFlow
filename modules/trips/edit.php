<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

// Get trip data
$stmt = $db->prepare("SELECT * FROM trips WHERE trip_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    header("Location: index.php");
    exit();
}

// Get available vehicles (including current vehicle)
$vehicleQuery = "SELECT * FROM vehicles WHERE status IN ('Available') OR vehicle_id = ? ORDER BY registration_number";
$vehicleStmt = $db->prepare($vehicleQuery);
$vehicleStmt->bind_param("i", $trip['vehicle_id']);
$vehicleStmt->execute();
$vehicles = $vehicleStmt->get_result();

// Get available drivers (including current driver)
$driverQuery = "SELECT * FROM drivers WHERE (status IN ('Available', 'Off_Duty') OR driver_id = ?) AND license_expiry_date > CURDATE() ORDER BY full_name";
$driverStmt = $db->prepare($driverQuery);
$driverStmt->bind_param("i", $trip['driver_id']);
$driverStmt->execute();
$drivers = $driverStmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source = sanitize($_POST['source_location']);
    $destination = sanitize($_POST['destination_location']);
    $cargo_weight = floatval($_POST['cargo_weight']);
    $planned_distance = floatval($_POST['planned_distance']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $driver_id = intval($_POST['driver_id']);
    $status = sanitize($_POST['status']);
    
    // Validate cargo weight against vehicle capacity
    $vehicleCheck = $db->prepare("SELECT max_load_capacity FROM vehicles WHERE vehicle_id = ?");
    $vehicleCheck->bind_param("i", $vehicle_id);
    $vehicleCheck->execute();
    $vehicleResult = $vehicleCheck->get_result();
    $vehicle = $vehicleResult->fetch_assoc();
    
    if ($cargo_weight > $vehicle['max_load_capacity']) {
        $error = 'Cargo weight exceeds vehicle maximum load capacity.';
    } else {
        // FIXED: Correct number of parameters
        $updateStmt = $db->prepare("UPDATE trips SET source_location = ?, destination_location = ?, cargo_weight = ?, planned_distance = ?, vehicle_id = ?, driver_id = ?, status = ? WHERE trip_id = ?");
        
        // 8 placeholders: 3 strings + 5 integers
        $updateStmt->bind_param("ssddiisi", $source, $destination, $cargo_weight, $planned_distance, $vehicle_id, $driver_id, $status, $id);
        
        if ($updateStmt->execute()) {
            // If status changed to Dispatched, update vehicle and driver status
            if ($status == 'Dispatched') {
                $db->query("UPDATE vehicles SET status = 'On_Trip' WHERE vehicle_id = $vehicle_id");
                $db->query("UPDATE drivers SET status = 'On_Trip' WHERE driver_id = $driver_id");
                $db->query("UPDATE trips SET dispatched_at = NOW() WHERE trip_id = $id");
            }
            
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: "Trip updated successfully.",
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to update trip. Please try again. Error: ' . $updateStmt->error;
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trip - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-edit"></i> Edit Trip - <?php echo $trip['trip_number']; ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="source_location" class="form-label">Source Location *</label>
                                    <input type="text" class="form-control" id="source_location" 
                                           name="source_location" required 
                                           value="<?php echo $trip['source_location']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="destination_location" class="form-label">Destination Location *</label>
                                    <input type="text" class="form-control" id="destination_location" 
                                           name="destination_location" required 
                                           value="<?php echo $trip['destination_location']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cargo_weight" class="form-label">Cargo Weight (kg) *</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="cargo_weight" name="cargo_weight" required
                                           value="<?php echo $trip['cargo_weight']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="planned_distance" class="form-label">Planned Distance (km) *</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="planned_distance" name="planned_distance" required
                                           value="<?php echo $trip['planned_distance']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_id" class="form-label">Select Vehicle *</label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">Select Vehicle</option>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <option value="<?php echo $vehicle['vehicle_id']; ?>" 
                                                <?php echo $trip['vehicle_id'] == $vehicle['vehicle_id'] ? 'selected' : ''; ?>>
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="driver_id" class="form-label">Select Driver *</label>
                                    <select class="form-select" id="driver_id" name="driver_id" required>
                                        <option value="">Select Driver</option>
                                        <?php while ($driver = $drivers->fetch_assoc()): ?>
                                            <option value="<?php echo $driver['driver_id']; ?>" 
                                                <?php echo $trip['driver_id'] == $driver['driver_id'] ? 'selected' : ''; ?>>
                                                <?php echo $driver['full_name'] . ' - ' . $driver['license_number']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Draft" <?php echo $trip['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="Dispatched" <?php echo $trip['status'] == 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                        <option value="Completed" <?php echo $trip['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo $trip['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Trip
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