<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Allow Fleet Manager and Driver to edit trips
if (!in_array($_SESSION['role'], ['Fleet_Manager', 'Driver'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

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
        $updateStmt = $db->prepare("UPDATE trips SET source_location = ?, destination_location = ?, cargo_weight = ?, planned_distance = ?, vehicle_id = ?, driver_id = ?, status = ? WHERE trip_id = ?");
        $updateStmt->bind_param("ssddiisi", $source, $destination, $cargo_weight, $planned_distance, $vehicle_id, $driver_id, $status, $id);
        
        if ($updateStmt->execute()) {
            // If status changed to Dispatched, update vehicle and driver status
            if ($status == 'Dispatched') {
                $db->query("UPDATE vehicles SET status = 'On_Trip' WHERE vehicle_id = $vehicle_id");
                $db->query("UPDATE drivers SET status = 'On_Trip' WHERE driver_id = $driver_id");
                $db->query("UPDATE trips SET dispatched_at = NOW() WHERE trip_id = $id");
            }
            
            // If status changed to Completed
            if ($status == 'Completed') {
                $db->query("UPDATE vehicles SET status = 'Available' WHERE vehicle_id = $vehicle_id");
                $db->query("UPDATE drivers SET status = 'Available' WHERE driver_id = $driver_id");
                $db->query("UPDATE trips SET completed_at = NOW() WHERE trip_id = $id");
            }
            
            // If status changed to Cancelled, restore vehicle and driver
            if ($status == 'Cancelled') {
                $db->query("UPDATE vehicles SET status = 'Available' WHERE vehicle_id = $vehicle_id");
                $db->query("UPDATE drivers SET status = 'Available' WHERE driver_id = $driver_id");
            }
            
            $_SESSION['success_message'] = 'Trip "' . $trip['trip_number'] . '" updated successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Failed to update trip. Please try again.';
        }
        $updateStmt->close();
    }
}

$page_title = 'Edit Trip';
$page_subtitle = 'Update trip details';
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
    .trip-summary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        color: white;
        padding: 16px 20px;
        border-radius: 10px;
    }
    .trip-summary small {
        color: rgba(255,255,255,0.7);
        font-size: 0.75rem;
    }
    .trip-summary strong {
        font-size: 0.95rem;
        color: #fff;
    }
    .vehicle-info, .driver-info {
        background: white;
        padding: 10px 15px;
        border-radius: 8px;
        border-left: 4px solid #4F46E5;
        margin-top: 8px;
        display: none;
    }
    .vehicle-info.show, .driver-info.show {
        display: block;
    }
    .vehicle-info strong, .driver-info strong {
        color: #0F172A;
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
    .form-section .row {
        margin-left: 0;
        margin-right: 0;
    }
    .form-section .col-md-6, .form-section .col-md-12 {
        padding-left: 8px;
        padding-right: 8px;
    }
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-badge.Draft { background: #E2E8F0; color: #475569; }
    .status-badge.Dispatched { background: #DBEAFE; color: #1D4ED8; }
    .status-badge.Completed { background: #D1FAE5; color: #065F46; }
    .status-badge.Cancelled { background: #FEE2E2; color: #991B1B; }
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-edit text-primary"></i> Edit Trip
                    </h4>
                    <small class="text-muted">Update trip details for <?php echo $trip['trip_number']; ?></small>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="tripForm">
                        <!-- Trip Details -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-route"></i> Trip Details
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="source_location" class="form-label">
                                        <i class="fas fa-map-marker-alt text-primary"></i> Source Location <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="source_location" 
                                           name="source_location" required 
                                           value="<?php echo $trip['source_location']; ?>"
                                           placeholder="Enter source city/location">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Enter the starting point of the trip
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="destination_location" class="form-label">
                                        <i class="fas fa-map-pin text-primary"></i> Destination Location <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="destination_location" 
                                           name="destination_location" required 
                                           value="<?php echo $trip['destination_location']; ?>"
                                           placeholder="Enter destination city/location">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Enter the ending point of the trip
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cargo_weight" class="form-label">
                                        <i class="fas fa-weight-hanging text-primary"></i> Cargo Weight (kg) <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="cargo_weight" name="cargo_weight" required 
                                               value="<?php echo $trip['cargo_weight']; ?>"
                                               placeholder="Enter cargo weight in kg">
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Must not exceed vehicle's maximum load capacity
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="planned_distance" class="form-label">
                                        <i class="fas fa-road text-primary"></i> Planned Distance (km) <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-road"></i></span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="planned_distance" name="planned_distance" required 
                                               value="<?php echo $trip['planned_distance']; ?>"
                                               placeholder="Enter planned distance in km">
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Estimated distance for this trip
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vehicle & Driver Selection -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-users"></i> Assign Vehicle & Driver
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_id" class="form-label">
                                        <i class="fas fa-truck text-primary"></i> Select Vehicle <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                        <option value="">-- Select Vehicle --</option>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <option value="<?php echo $vehicle['vehicle_id']; ?>" 
                                                    data-capacity="<?php echo $vehicle['max_load_capacity']; ?>"
                                                    <?php echo $trip['vehicle_id'] == $vehicle['vehicle_id'] ? 'selected' : ''; ?>>
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                                (Max: <?php echo number_format($vehicle['max_load_capacity'], 2); ?> kg)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="vehicle-info <?php echo $trip['vehicle_id'] ? 'show' : ''; ?>" id="vehicleInfo">
                                        <strong><i class="fas fa-truck"></i> Vehicle Details:</strong><br>
                                        <span id="vehicleCapacity">
                                            <?php 
                                            // Get current vehicle capacity
                                            $currentVehicle = $db->query("SELECT max_load_capacity FROM vehicles WHERE vehicle_id = {$trip['vehicle_id']}")->fetch_assoc();
                                            echo 'Capacity: ' . ($currentVehicle ? $currentVehicle['max_load_capacity'] : '0') . ' kg';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Only available vehicles are shown
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="driver_id" class="form-label">
                                        <i class="fas fa-user text-primary"></i> Select Driver <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="driver_id" name="driver_id" required>
                                        <option value="">-- Select Driver --</option>
                                        <?php while ($driver = $drivers->fetch_assoc()): ?>
                                            <option value="<?php echo $driver['driver_id']; ?>" 
                                                    <?php echo $trip['driver_id'] == $driver['driver_id'] ? 'selected' : ''; ?>>
                                                <?php echo $driver['full_name']; ?>
                                                (License: <?php echo $driver['license_number']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="driver-info <?php echo $trip['driver_id'] ? 'show' : ''; ?>" id="driverInfo">
                                        <strong><i class="fas fa-user"></i> Driver Details:</strong><br>
                                        <span id="driverLicense">
                                            <?php 
                                            // Get current driver name
                                            $currentDriver = $db->query("SELECT full_name FROM drivers WHERE driver_id = {$trip['driver_id']}")->fetch_assoc();
                                            echo 'Driver: ' . ($currentDriver ? $currentDriver['full_name'] : 'Not selected');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Only available drivers with valid licenses are shown
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-tag"></i> Trip Status
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-tag text-primary"></i> Status <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Draft" <?php echo $trip['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="Dispatched" <?php echo $trip['status'] == 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                        <option value="Completed" <?php echo $trip['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo $trip['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> 
                                        Current status: <span class="status-badge <?php echo $trip['status']; ?>"><?php echo $trip['status']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trip Summary -->
                        <div class="trip-summary mb-4">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-hashtag"></i> Trip Number</small><br>
                                    <strong><?php echo $trip['trip_number']; ?></strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-calendar"></i> Created</small><br>
                                    <strong><?php echo date('Y-m-d H:i', strtotime($trip['created_at'])); ?></strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-user"></i> Created By</small><br>
                                    <strong>
                                        <?php 
                                        $userQuery = $db->query("SELECT full_name FROM users WHERE user_id = {$trip['created_by']}");
                                        $user = $userQuery->fetch_assoc();
                                        echo $user ? $user['full_name'] : 'Unknown';
                                        ?>
                                    </strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-tag"></i> Status</small><br>
                                    <strong><?php echo $trip['status']; ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Update Trip
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show vehicle info on selection
    $('#vehicle_id').change(function() {
        var selected = $(this).find('option:selected');
        var capacity = selected.data('capacity');
        
        if (capacity) {
            $('#vehicleInfo').addClass('show');
            $('#vehicleCapacity').text('Max Capacity: ' + capacity + ' kg');
        } else {
            $('#vehicleInfo').removeClass('show');
        }
    });
    
    // Show driver info on selection
    $('#driver_id').change(function() {
        var selected = $(this).find('option:selected');
        var text = selected.text();
        
        if (text && text !== '-- Select Driver --') {
            $('#driverInfo').addClass('show');
            $('#driverLicense').text('Driver: ' + text);
        } else {
            $('#driverInfo').removeClass('show');
        }
    });
    
    // Status change confirmation
    $('#status').change(function() {
        var status = $(this).val();
        var currentStatus = '<?php echo $trip['status']; ?>';
        
        if (status !== currentStatus) {
            Swal.fire({
                title: 'Status Change',
                text: 'Are you sure you want to change status from "' + currentStatus + '" to "' + status + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4F46E5',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, Change',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    $(this).val(currentStatus);
                }
            });
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
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        if (!vehicle) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Vehicle Selected',
                text: 'Please select a vehicle for this trip.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        if (!driver) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Driver Selected',
                text: 'Please select a driver for this trip.',
                confirmButtonColor: '#4F46E5'
            });
            return false;
        }
        
        // Show loading state
        var $btn = $('#submitBtn');
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        $btn.prop('disabled', true);
        
        setTimeout(function() {
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        }, 5000);
        
        return true;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>