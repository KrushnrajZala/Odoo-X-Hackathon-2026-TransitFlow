<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get maintenance records with vehicle details
$query = "SELECT m.*, v.registration_number, v.model 
          FROM maintenance_logs m 
          JOIN vehicles v ON m.vehicle_id = v.vehicle_id 
          ORDER BY m.created_at DESC";
$maintenance = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tools"></i> Maintenance Management</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Maintenance
                    </a>
                </div>
                
                <!-- Maintenance Table -->
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
                                        <th>Cost</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($maintenance->num_rows > 0): ?>
                                        <?php while ($record = $maintenance->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $record['maintenance_id']; ?></td>
                                                <td><?php echo $record['registration_number']; ?></td>
                                                <td><?php echo $record['maintenance_type']; ?></td>
                                                <td><?php echo substr($record['description'], 0, 50) . '...'; ?></td>
                                                <td>$<?php echo number_format($record['cost'], 2); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($record['maintenance_date'])); ?></td>
                                                <td><?php echo getStatusBadge($record['status']); ?></td>
                                                <td>
                                                    <?php if ($record['status'] == 'Active'): ?>
                                                        <a href="close.php?id=<?php echo $record['maintenance_id']; ?>" 
                                                           class="btn btn-sm btn-success status-change" data-action="close">
                                                            <i class="fas fa-check"></i> Close
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
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
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>