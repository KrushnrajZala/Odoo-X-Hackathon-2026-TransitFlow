<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Allow Fleet Manager and Safety Officer to view
if (!in_array($_SESSION['role'], ['Fleet_Manager', 'Safety_Officer'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$can_edit = isAdmin(); // Only Fleet Manager can add/close maintenance

// Get maintenance records with vehicle details
$query = "SELECT m.*, v.registration_number, v.model 
          FROM maintenance_logs m 
          JOIN vehicles v ON m.vehicle_id = v.vehicle_id 
          ORDER BY m.created_at DESC";
$maintenance = $db->query($query);

$page_title = 'Maintenance';
$page_subtitle = 'Track vehicle maintenance';
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
    .maintenance-cost {
        font-weight: 600;
        color: #1E293B;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="fas fa-tools text-primary"></i> Maintenance Management</h4>
                <small class="text-muted">Track and manage vehicle maintenance</small>
            </div>
            <div>
                <?php if ($can_edit): ?>
                    <a href="create.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus"></i> Add Maintenance
                    </a>
                <?php else: ?>
                    <span class="view-only-badge">
                        <i class="fas fa-eye"></i> View Only
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Maintenance Table -->
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
                                <th>Cost</th>
                                <th>Date</th>
                                <th>Status</th>
                                <?php if ($can_edit): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($maintenance->num_rows > 0): ?>
                                <?php while ($record = $maintenance->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $record['maintenance_id']; ?></td>
                                        <td>
                                            <strong><?php echo $record['registration_number']; ?></strong>
                                            <br><small class="text-muted"><?php echo $record['model']; ?></small>
                                        </td>
                                        <td><?php echo $record['maintenance_type']; ?></td>
                                        <td><?php echo substr($record['description'], 0, 50) . '...'; ?></td>
                                        <td class="maintenance-cost">$<?php echo number_format($record['cost'], 2); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($record['maintenance_date'])); ?></td>
                                        <td><?php echo getStatusBadge($record['status']); ?></td>
                                        <?php if ($can_edit): ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($record['status'] == 'Active'): ?>
                                                        <a href="close.php?id=<?php echo $record['maintenance_id']; ?>" 
                                                           class="btn btn-sm btn-success status-change" 
                                                           data-action="close maintenance">
                                                            <i class="fas fa-check"></i> Close
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $can_edit ? '8' : '7'; ?>" class="text-center py-4">
                                        <i class="fas fa-tools fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No maintenance records found</p>
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