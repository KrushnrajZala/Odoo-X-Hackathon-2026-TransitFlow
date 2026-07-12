<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get filter parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT * FROM vehicles WHERE 1=1";
$params = [];
$types = "";

if ($type) {
    $query .= " AND vehicle_type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($search) {
    $query .= " AND (registration_number LIKE ? OR model LIKE ?)";
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
$vehicles = $stmt->get_result();

// Get distinct vehicle types for filter
$typesResult = $db->query("SELECT DISTINCT vehicle_type FROM vehicles ORDER BY vehicle_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-truck"></i> Vehicle Management</h2>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Vehicle Type</label>
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <?php while ($row = $typesResult->fetch_assoc()): ?>
                                        <option value="<?php echo $row['vehicle_type']; ?>" 
                                            <?php echo $type == $row['vehicle_type'] ? 'selected' : ''; ?>>
                                            <?php echo $row['vehicle_type']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Available" <?php echo $status == 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="On_Trip" <?php echo $status == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                                    <option value="In_Shop" <?php echo $status == 'In_Shop' ? 'selected' : ''; ?>>In Shop</option>
                                    <option value="Retired" <?php echo $status == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by registration or model..." 
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
                
                <!-- Vehicles Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Registration</th>
                                        <th>Model</th>
                                        <th>Type</th>
                                        <th>Max Load (kg)</th>
                                        <th>Odometer (km)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($vehicles->num_rows > 0): ?>
                                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo $vehicle['registration_number']; ?></strong></td>
                                                <td><?php echo $vehicle['model']; ?></td>
                                                <td><?php echo $vehicle['vehicle_type']; ?></td>
                                                <td><?php echo number_format($vehicle['max_load_capacity'], 2); ?></td>
                                                <td><?php echo number_format($vehicle['odometer_reading'], 0); ?></td>
                                                <td><?php echo getStatusBadge($vehicle['status']); ?></td>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                                       class="btn btn-sm btn-danger delete-confirm">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                                                <p class="text-muted">No vehicles found</p>
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