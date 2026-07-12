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

// Get driver data
$stmt = $db->prepare("SELECT * FROM drivers WHERE driver_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

if (!$driver) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $license_number = sanitize($_POST['license_number']);
    $license_category = sanitize($_POST['license_category']);
    $license_expiry = sanitize($_POST['license_expiry_date']);
    $contact = sanitize($_POST['contact_number']);
    $safety_score = floatval($_POST['safety_score']);
    $status = sanitize($_POST['status']);
    
    // Check if license number is unique (excluding current driver)
    $checkStmt = $db->prepare("SELECT driver_id FROM drivers WHERE license_number = ? AND driver_id != ?");
    $checkStmt->bind_param("si", $license_number, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = 'License number already exists. Please use a unique number.';
    } else {
        $updateStmt = $db->prepare("UPDATE drivers SET full_name = ?, license_number = ?, license_category = ?, license_expiry_date = ?, contact_number = ?, safety_score = ?, status = ? WHERE driver_id = ?");
        $updateStmt->bind_param("sssssdsi", $full_name, $license_number, $license_category, $license_expiry, $contact, $safety_score, $status, $id);
        
        if ($updateStmt->execute()) {
            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: "Driver updated successfully.",
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = "index.php";
                });
            </script>';
        } else {
            $error = 'Failed to update driver. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driver - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-edit"></i> Edit Driver</h4>
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
                                           name="full_name" required 
                                           value="<?php echo $driver['full_name']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_number" class="form-label">License Number *</label>
                                    <input type="text" class="form-control" id="license_number" 
                                           name="license_number" required 
                                           value="<?php echo $driver['license_number']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_category" class="form-label">License Category *</label>
                                    <select class="form-select" id="license_category" name="license_category" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        $categories = ['Class A', 'Class B', 'Class C', 'Class D', 'Class E'];
                                        foreach ($categories as $cat):
                                        ?>
                                            <option value="<?php echo $cat; ?>" 
                                                <?php echo $driver['license_category'] == $cat ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="license_expiry_date" class="form-label">License Expiry Date *</label>
                                    <input type="date" class="form-control" id="license_expiry_date" 
                                           name="license_expiry_date" required
                                           value="<?php echo $driver['license_expiry_date']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contact_number" class="form-label">Contact Number *</label>
                                    <input type="text" class="form-control" id="contact_number" 
                                           name="contact_number" required
                                           value="<?php echo $driver['contact_number']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="safety_score" class="form-label">Safety Score (%)</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="safety_score" name="safety_score" 
                                           value="<?php echo $driver['safety_score']; ?>" min="0" max="100">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Available" <?php echo $driver['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="On_Trip" <?php echo $driver['status'] == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                        <option value="Off_Duty" <?php echo $driver['status'] == 'Off_Duty' ? 'selected' : ''; ?>>Off Duty</option>
                                        <option value="Suspended" <?php echo $driver['status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Driver
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