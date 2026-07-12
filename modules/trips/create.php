<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Allow Fleet Manager and Driver to create trips
if (!in_array($_SESSION['role'], ['Fleet_Manager', 'Driver'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

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
        $stmt = $db->prepare("INSERT INTO trips (trip_number, source_location, destination_location, cargo_weight, planned_distance, vehicle_id, driver_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $error = 'Failed to create trip. Please try again.';
        }
        $stmt->close();
    }
}

$page_title = 'Create Trip';
$page_subtitle = 'Plan and dispatch a new trip';
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
</style>

<div class="form-container">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-plus-circle text-primary"></i> Create New Trip
                    </h4>
                    <small class="text-muted">Plan and dispatch a new trip</small>
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
                                                    data-capacity="<?php echo $vehicle['max_load_capacity']; ?>">
                                                <?php echo $vehicle['registration_number'] . ' - ' . $vehicle['model']; ?>
                                                (Max: <?php echo number_format($vehicle['max_load_capacity'], 2); ?> kg)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="vehicle-info" id="vehicleInfo">
                                        <strong><i class="fas fa-truck"></i> Vehicle Details:</strong><br>
                                        <span id="vehicleCapacity">Capacity: 0 kg</span>
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
                                            <option value="<?php echo $driver['driver_id']; ?>">
                                                <?php echo $driver['full_name']; ?>
                                                (License: <?php echo $driver['license_number']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="driver-info" id="driverInfo">
                                        <strong><i class="fas fa-user"></i> Driver Details:</strong><br>
                                        <span id="driverLicense">License: ---</span>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> Only available drivers with valid licenses are shown
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Trip Summary -->
                        <div class="trip-summary mb-4">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-hashtag"></i> Trip Number</small><br>
                                    <strong id="summaryTripNumber"><?php echo generateTripNumber(); ?></strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-tag"></i> Status</small><br>
                                    <strong>Draft</strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-user"></i> Created By</small><br>
                                    <strong><?php echo $_SESSION['full_name']; ?></strong>
                                </div>
                                <div class="col-md-3 col-6 mb-2">
                                    <small><i class="fas fa-calendar"></i> Date</small><br>
                                    <strong><?php echo date('Y-m-d H:i'); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
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
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Creating...');
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