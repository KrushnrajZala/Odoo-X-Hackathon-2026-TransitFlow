<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get expenses with vehicle details
$query = "SELECT e.*, v.registration_number, v.model 
          FROM expenses e 
          JOIN vehicles v ON e.vehicle_id = v.vehicle_id 
          ORDER BY e.created_at DESC";
$expenses = $db->query($query);

// Get summary statistics
$totalFuel = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Fuel'")->fetch_assoc()['total'] ?? 0;
$totalMaintenance = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type IN ('Maintenance', 'Repair')")->fetch_assoc()['total'] ?? 0;
$totalTolls = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Toll'")->fetch_assoc()['total'] ?? 0;
$totalExpenses = $db->query("SELECT SUM(amount) as total FROM expenses")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-dollar-sign"></i> Expense Management</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Expense
                    </a>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Total Fuel Cost</h6>
                                <h3>$<?php echo number_format($totalFuel, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Maintenance Cost</h6>
                                <h3>$<?php echo number_format($totalMaintenance, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Toll Charges</h6>
                                <h3>$<?php echo number_format($totalTolls, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h6 class="text-muted">Total Expenses</h6>
                                <h3>$<?php echo number_format($totalExpenses, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expenses Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Vehicle</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($expenses->num_rows > 0): ?>
                                        <?php while ($expense = $expenses->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $expense['expense_id']; ?></td>
                                                <td><?php echo $expense['registration_number']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $expense['expense_type'] == 'Fuel' ? 'warning' : 
                                                             ($expense['expense_type'] == 'Toll' ? 'info' : 
                                                             ($expense['expense_type'] == 'Maintenance' ? 'danger' : 'secondary')); 
                                                    ?>">
                                                        <?php echo $expense['expense_type']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo substr($expense['description'], 0, 50) . '...'; ?></td>
                                                <td><strong>$<?php echo number_format($expense['amount'], 2); ?></strong></td>
                                                <td><?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="fas fa-dollar-sign fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No expenses found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>