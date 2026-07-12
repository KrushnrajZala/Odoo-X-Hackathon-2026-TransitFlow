<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Only Fleet Manager and Financial Analyst can view expenses
if (!in_array($_SESSION['role'], ['Fleet_Manager', 'Financial_Analyst'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$can_edit = isAdmin(); // Only Fleet Manager can add expenses

// Check for success/error messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Get expenses with vehicle details
$query = "SELECT e.*, v.registration_number, v.model 
          FROM expenses e 
          JOIN vehicles v ON e.vehicle_id = v.vehicle_id 
          ORDER BY e.created_at DESC";
$expenses = $db->query($query);

// Get summary statistics
$totalFuel = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_type = 'Fuel'")->fetch_assoc()['total'];
$totalMaintenance = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_type IN ('Maintenance', 'Repair')")->fetch_assoc()['total'];
$totalTolls = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_type = 'Toll'")->fetch_assoc()['total'];
$totalExpenses = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses")->fetch_assoc()['total'];

$page_title = 'Expenses';
$page_subtitle = 'Track and manage operational expenses';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .action-buttons .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 8px;
    }
    .view-only-badge {
        background: #FEF3C7;
        color: #D97706;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-edit {
        background: #4F46E5;
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-edit:hover {
        background: #4338CA;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        color: white;
    }
    .btn-delete {
        background: #EF4444;
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-delete:hover {
        background: #DC2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        color: white;
    }
    .alert-success-custom {
        background: #ECFDF5;
        border: 1px solid #10B981;
        color: #065F46;
        border-radius: 12px;
        padding: 1rem 1.2rem;
    }
    .alert-error-custom {
        background: #FEF2F2;
        border: 1px solid #EF4444;
        color: #991B1B;
        border-radius: 12px;
        padding: 1rem 1.2rem;
    }
    .stat-card {
        border: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        background: white;
        padding: 1.2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    }
    .stat-card h3 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        color: #0F172A;
    }
    .stat-card h6 {
        font-size: 0.8rem;
        color: #6B7280;
        margin: 0;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    .stat-card .stat-icon.blue { background: linear-gradient(135deg, #4F46E5, #7C3AED); }
    .stat-card .stat-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
    .stat-card .stat-icon.orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .stat-card .stat-icon.red { background: linear-gradient(135deg, #EF4444, #DC2626); }
</style>

<div class="row">
    <div class="col-12">
        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success-custom alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error-custom alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-coins text-primary"></i> Expense Management</h4>
                <small class="text-muted">Track and monitor operational expenses</small>
            </div>
            <div>
                <?php if ($can_edit): ?>
                    <a href="create.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus"></i> Add Expense
                    </a>
                <?php else: ?>
                    <span class="view-only-badge">
                        <i class="fas fa-eye"></i> View Only
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Total Fuel Cost</h6>
                        <h3>$<?php echo number_format($totalFuel, 2); ?></h3>
                    </div>
                    <div class="stat-icon orange"><i class="fas fa-gas-pump"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Maintenance Cost</h6>
                        <h3>$<?php echo number_format($totalMaintenance, 2); ?></h3>
                    </div>
                    <div class="stat-icon red"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Toll Charges</h6>
                        <h3>$<?php echo number_format($totalTolls, 2); ?></h3>
                    </div>
                    <div class="stat-icon blue"><i class="fas fa-road"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Total Expenses</h6>
                        <h3>$<?php echo number_format($totalExpenses, 2); ?></h3>
                    </div>
                    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                </div>
            </div>
        </div>
        
        <!-- Expenses Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
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
                                        <td>
                                            <strong><?php echo $expense['registration_number']; ?></strong>
                                            <br><small class="text-muted"><?php echo $expense['model']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $expense['expense_type'] == 'Fuel' ? 'bg-warning' : 
                                                     ($expense['expense_type'] == 'Toll' ? 'bg-info' : 
                                                     ($expense['expense_type'] == 'Maintenance' ? 'bg-danger' : 'bg-secondary')); 
                                            ?>">
                                                <?php echo $expense['expense_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo substr($expense['description'], 0, 50) . (strlen($expense['description']) > 50 ? '...' : ''); ?></td>
                                        <td><strong>$<?php echo number_format($expense['amount'], 2); ?></strong></td>
                                        <td><?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-coins fa-3x text-muted mb-3 d-block"></i>
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

<?php include '../../includes/footer.php'; ?>