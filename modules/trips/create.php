<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$error = '';

// Get available vehicles (not in shop or retired, not on trip)
$vehicleQuery = "SELECT * FROM vehicles WHERE status = 'Available' ORDER BY registration_number";
$vehicles = $db->query($vehicleQuery);

// Get available drivers (not suspended, not on trip, with valid license)
$driverQuery = "SELECT * FROM drivers WHERE status IN ('Available', 'Off_Duty') AND license_expiry_date > CURDATE() ORDER BY full_name";
$drivers = $db->query($driverQuery);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_number = generateTripNumber();
    $source = sanitize($_POST['source_location']);
    $destination = sanitize($_POST['destination_location']);
    $cargo_weight = floatval($_POST['cargo_weight']);
    $planned_distance = floatval($_POST['planned_distance']);
    $vehicle_id = intval($_POST['vehicle_id']);
    $driver_id = intval($_POST['driver_id']);
    $status = 'Draft';
    $created_by = $_SESSION['user_id'];
    
    // Validate cargo weight against vehicle capacity
    $vehicleCheck = $db->prepare("SELECT max_load_capacity FROM vehicles WHERE vehicle_id = ?");
    $vehicleCheck->bind_param("i", $vehicle_id);
    $vehicleCheck->execute();
    $vehicleResult = $vehicleCheck->get_result();
    $vehicle = $vehicleResult->fetch_assoc();
    
    if ($cargo_weight > $vehicle['max_load_capacity']) {
        $error = 'Cargo weight exceeds vehicle maximum load capacity.';
    } else {
        // FIXED: Correct number of parameters matching the query
        $stmt = $db->prepare("INSERT INTO trips (trip_number, source_location, destination_location, cargo_weight, planned_distance, vehicle_id, driver_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // 9 placeholders, so 9 parameters: 3 strings + 6 integers
        $stmt->bind_param("sssddiiis", $trip_number, $source, $destination, $cargo_weight, $planned_distance, $vehicle_id, $driver_id, $created_by, $status);
        
        if ($stmt->execute()) {
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Trip Created!",
                    text: "Trip created successfully.",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to create trip. Please try again. Error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Trip - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
    <style>
        .form-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .form-section h6 {
            color: #667eea;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .vehicle-info, .driver-info {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
        }
        .vehicle-info small, .driver-info small {
            color: #6c757d;
        }
        .vehicle-info strong, .driver-info strong {
            color: #333;
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-plus-circle"></i> Create New Trip</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="tripForm">
                            <div class="row">
                                <!-- Trip Details -->
                                <div class="col-md-12">
                                    <div class="form-section">
                                        <h6><i class="fas fa-route"></i> Trip Details</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="source_location" class="form-label">Source Location *</label>
                                                <input type="text" class="form-control" id="source_location" 
                                                       name="source_location" required placeholder="Enter source location">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="destination_location" class="form-label">Destination Location *</label>
                                                <input type="text" class="form-control" id="destination_location" 
                                                       name="destination_location" required placeholder="Enter destination">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="cargo_weight" class="form-label">Cargo Weight (kg) *</label>
                                                <input type="number" step="0.01" class="form-control" 
                                                       id="cargo_weight" name="cargo_weight" required 
                                                       placeholder="Enter cargo weight">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="planned_distance" class="form-label">Planned Distance (km) *</label>
                                                <input type="number" step="0.01" class="form-control" 
                                                       id="planned_distance" name="planned_distance" required 
                                                       placeholder="Enter planned distance">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Vehicle Selection -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="fas fa-truck"></i> Select Vehicle</h6>
                                        <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                            <option value="">-- Select Vehicle --</option>
                                            <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                                <option value="<?php echo $vehicle['vehicle_id']; ?>" 
                                                        data-capacity="<?php echo $vehicle['max_load_capacity']; ?>">
                                                    <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                                    (Max: <?php echo number_format($vehicle['max_load_capacity'], 2); ?> kg)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div id="vehicleInfo" class="vehicle-info mt-2" style="display:none;">
                                            <strong>Vehicle Details:</strong><br>
                                            <small id="vehicleCapacity">Capacity: 0 kg</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Driver Selection -->
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h6><i class="fas fa-user"></i> Select Driver</h6>
                                        <select class="form-select" id="driver_id" name="driver_id" required>
                                            <option value="">-- Select Driver --</option>
                                            <?php while ($driver = $drivers->fetch_assoc()): ?>
                                                <option value="<?php echo $driver['driver_id']; ?>">
                                                    <?php echo $driver['full_name']; ?>
                                                    (License: <?php echo $driver['license_number']; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div id="driverInfo" class="driver-info mt-2" style="display:none;">
                                            <strong>Driver Details:</strong><br>
                                            <small id="driverLicense">License: ---</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Summary -->
                                <div class="col-md-12">
                                    <div class="form-section bg-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;">
                                        <h6 class="text-white"><i class="fas fa-info-circle"></i> Trip Summary</h6>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <small>Trip Number</small><br>
                                                <strong id="summaryTripNumber"><?php echo generateTripNumber(); ?></strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Status</small><br>
                                                <strong>Draft</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Created By</small><br>
                                                <strong><?php echo $_SESSION['full_name']; ?></strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Date</small><br>
                                                <strong><?php echo date('Y-m-d H:i'); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Create Trip
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
        // Show vehicle info on selection
        $('#vehicle_id').change(function() {
            var selected = $(this).find('option:selected');
            var capacity = selected.data('capacity');
            
            if (capacity) {
                $('#vehicleInfo').show();
                $('#vehicleCapacity').text('Max Capacity: ' + capacity + ' kg');
            } else {
                $('#vehicleInfo').hide();
            }
        });
        
        // Show driver info on selection
        $('#driver_id').change(function() {
            var selected = $(this).find('option:selected');
            var text = selected.text();
            
            if (text && text !== '-- Select Driver --') {
                $('#driverInfo').show();
                $('#driverLicense').text('Driver: ' + text);
            } else {
                $('#driverInfo').hide();
            }
        });
        
        // Form validation
        $('#tripForm').on('submit', function(e) {
            var vehicle = $('#vehicle_id').val();
            var driver = $('#driver_id').val();
            var source = $('#source_location').val().trim();
            var destination = $('#destination_location').val().trim();
            var cargo = $('#cargo_weight').val();
            var distance = $('#planned_distance').val();
            
            if (!source || !destination || !cargo || !distance) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Fields',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            if (!vehicle) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No Vehicle Selected',
                    text: 'Please select a vehicle for this trip.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            if (!driver) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No Driver Selected',
                    text: 'Please select a driver for this trip.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            // Show loading state
            var $btn = $('#submitBtn');
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Creating...');
            $btn.prop('disabled', true);
            
            // Re-enable after 10 seconds
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