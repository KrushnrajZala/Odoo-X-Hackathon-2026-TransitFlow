<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$error = '';

// Get vehicles
$vehicles = $db->query("SELECT * FROM vehicles WHERE status != 'Retired' ORDER BY registration_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = intval($_POST['vehicle_id']);
    $expense_type = sanitize($_POST['expense_type']);
    $description = sanitize($_POST['description']);
    $amount = floatval($_POST['amount']);
    $expense_date = sanitize($_POST['expense_date']);
    
    $stmt = $db->prepare("INSERT INTO expenses (vehicle_id, expense_type, description, amount, expense_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $vehicle_id, $expense_type, $description, $amount, $expense_date);
    
    if ($stmt->execute()) {
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Expense Added!",
                text: "Expense recorded successfully.",
                timer: 2000,
                showConfirmButton: false
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
    } else {
        $error = 'Failed to add expense. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-plus-circle"></i> Add Expense</h4>
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
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="expense_type" class="form-label">Expense Type *</label>
                                    <select class="form-select" id="expense_type" name="expense_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Fuel">Fuel</option>
                                        <option value="Toll">Toll</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Repair">Repair</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" 
                                              name="description" rows="2" 
                                              placeholder="Describe the expense"></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">Amount ($) *</label>
                                    <input type="number" step="0.01" class="form-control" 
                                           id="amount" name="amount" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="expense_date" class="form-label">Expense Date *</label>
                                    <input type="date" class="form-control" id="expense_date" 
                                           name="expense_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Expense
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