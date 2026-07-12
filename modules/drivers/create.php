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
    $full_name = sanitize($_POST['full_name']);
    $license_number = sanitize($_POST['license_number']);
    $license_category = sanitize($_POST['license_category']);
    $license_expiry = sanitize($_POST['license_expiry_date']);
    $contact = sanitize($_POST['contact_number']);
    $safety_score = floatval($_POST['safety_score']);
    $status = sanitize($_POST['status']);
    
    // Validate unique license number
    $checkStmt = $db->prepare("SELECT driver_id FROM drivers WHERE license_number = ?");
    $checkStmt->bind_param("s", $license_number);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = 'License number already exists. Please use a unique number.';
    } else {
        $stmt = $db->prepare("INSERT INTO drivers (full_name, license_number, license_category, license_expiry_date, contact_number, safety_score, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", $full_name, $license_number, $license_category, $license_expiry, $contact, $safety_score, $status);
        
        if ($stmt->execute()) {
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Success!",
                    text: "Driver added successfully.",
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to add driver. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Driver - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-plus"></i> Add New Driver</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" 
                                           name="full_name" required placeholder="e.g., John Doe">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_number" class="form-label">License Number *</label>
                                    <input type="text" class="form-control" id="license_number" 
                                           name="license_number" required placeholder="e.g., DL2024-001">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_category" class="form-label">License Category *</label>
                                    <select class="form-select" id="license_category" name="license_category" required>
                                        <option value="">Select Category</option>
                                        <option value="Class A">Class A</option>
                                        <option value="Class B">Class B</option>
                                        <option value="Class C">Class C</option>
                                        <option value="Class D">Class D</option>
                                        <option value="Class E">Class E</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_expiry_date" class="form-label">License Expiry Date *</label>
                                    <input type="date" class="form-control" id="license_expiry_date" 
                                           name="license_expiry_date" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contact_number" class="form-label">Contact Number *</label>
                                    <input type="text" class="form-control" id="contact_number" 
                                           name="contact_number" required placeholder="e.g., +1 555-0101">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="safety_score" class="form-label">Safety Score (%)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="safety_score" name="safety_score" value="100" min="0" max="100">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available">Available</option>
                                        <option value="On_Trip">On Trip</option>
                                        <option value="Off_Duty">Off Duty</option>
                                        <option value="Suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Driver
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