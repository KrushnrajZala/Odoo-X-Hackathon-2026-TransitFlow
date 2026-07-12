<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get filter parameters
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT t.*, v.registration_number, d.full_name as driver_name 
          FROM trips t 
          JOIN vehicles v ON t.vehicle_id = v.vehicle_id 
          JOIN drivers d ON t.driver_id = d.driver_id 
          WHERE 1=1";
$params = [];
$types = "";

if ($status) {
    $query .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (t.trip_number LIKE ? OR t.source_location LIKE ? OR t.destination_location LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$trips = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-route"></i> Trip Management</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Trip
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Draft" <?php echo $status == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Dispatched" <?php echo $status == 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                    <option value="Completed" <?php echo $status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by trip number, source, or destination..." 
                                       value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Trips Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Trip #</th>
                                        <th>Vehicle</th>
                                        <th>Driver</th>
                                        <th>Source</th>
                                        <th>Destination</th>
                                        <th>Cargo (kg)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($trips->num_rows > 0): ?>
                                        <?php while ($trip = $trips->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo $trip['trip_number']; ?></strong></td>
                                                <td><?php echo $trip['registration_number']; ?></td>
                                                <td><?php echo $trip['driver_name']; ?></td>
                                                <td><?php echo $trip['source_location']; ?></td>
                                                <td><?php echo $trip['destination_location']; ?></td>
                                                <td><?php echo number_format($trip['cargo_weight'], 2); ?></td>
                                                <td><?php echo getStatusBadge($trip['status']); ?></td>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $trip['trip_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $trip['trip_id']; ?>" 
                                                       class="btn btn-sm btn-danger delete-confirm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-route fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No trips found</p>
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