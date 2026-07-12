<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get filter parameters
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT * FROM drivers WHERE 1=1";
$params = [];
$types = "";

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (full_name LIKE ? OR license_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$drivers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> Driver Management</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Driver
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
                                    <option value="Available" <?php echo $status == 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="On_Trip" <?php echo $status == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                    <option value="Off_Duty" <?php echo $status == 'Off_Duty' ? 'selected' : ''; ?>>Off Duty</option>
                                    <option value="Suspended" <?php echo $status == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name or license number..." 
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
                
                <!-- Drivers Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>License Number</th>
                                        <th>Category</th>
                                        <th>License Expiry</th>
                                        <th>Safety Score</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($drivers->num_rows > 0): ?>
                                        <?php while ($driver = $drivers->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo $driver['full_name']; ?></strong></td>
                                                <td><?php echo $driver['license_number']; ?></td>
                                                <td><?php echo $driver['license_category']; ?></td>
                                                <td>
                                                    <?php 
                                                    $expiry = strtotime($driver['license_expiry_date']);
                                                    $now = time();
                                                    if ($expiry < $now) {
                                                        echo '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ' . date('Y-m-d', $expiry) . '</span>';
                                                    } else {
                                                        echo date('Y-m-d', $expiry);
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $driver['safety_score'] >= 80 ? 'bg-success' : ($driver['safety_score'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo $driver['safety_score']; ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo getStatusBadge($driver['status']); ?></td>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $driver['driver_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $driver['driver_id']; ?>" 
                                                       class="btn btn-sm btn-danger delete-confirm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No drivers found</p>
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