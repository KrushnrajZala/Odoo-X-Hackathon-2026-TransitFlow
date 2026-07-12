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
$can_edit = isAdmin(); // Only Fleet Manager can add/edit/delete

// Check for success/error messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

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

// Debug - Uncomment to see the query
// echo "Query: " . $query . "<br>";
// echo "Params: " . print_r($params, true) . "<br>";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$vehicles = $stmt->get_result();

// Get distinct vehicle types for filter
$typesResult = $db->query("SELECT DISTINCT vehicle_type FROM vehicles ORDER BY vehicle_type");

$page_title = 'Vehicles';
$page_subtitle = 'Manage your fleet vehicles';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .action-buttons .btn-sm {
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .view-only-badge {
        background: #FEF3C7;
        color: #D97706;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .view-only-badge i {
        font-size: 0.8rem;
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
    .btn-view {
        background: #10B981;
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-view:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
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
    
    /* ===== FILTER LABELS ===== */
    .form-label {
        font-weight: 600;
        color: #1E293B;
        font-size: 0.9rem;
        margin-bottom: 6px;
        display: block;
    }
    .form-label i {
        margin-right: 6px;
        color: #4F46E5;
    }
    .form-control, .form-select {
        border: 2px solid #E2E8F0;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #FFFFFF;
        color: #0F172A;
        width: 100%;
    }
    .form-control:focus, .form-select:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        outline: none;
    }
    .form-control::placeholder {
        color: #94A3B8;
        font-size: 0.9rem;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border: none;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
        color: #fff;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79,70,229,0.4);
        color: #fff;
    }
    .btn-primary i {
        margin-right: 6px;
    }
    .btn-primary.rounded-pill {
        border-radius: 50px;
        padding: 0.6rem 1.8rem;
    }
    
    /* ===== TABLE STYLES ===== */
    .table th {
        font-weight: 600;
        color: #475569;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 2px solid #E2E8F0;
        padding: 0.75rem 1rem;
    }
    .table td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
    }
    .table tbody tr:hover {
        background: #F8FAFC;
    }
    
    /* ===== BADGE STYLES ===== */
    .badge-status {
        padding: 0.25rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-status.Available { background: #D1FAE5; color: #065F46; }
    .badge-status.On_Trip { background: #DBEAFE; color: #1D4ED8; }
    .badge-status.In_Shop { background: #FEF3C7; color: #92400E; }
    .badge-status.Retired { background: #FEE2E2; color: #991B1B; }
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
                <h4 class="fw-bold mb-0"><i class="fas fa-truck text-primary"></i> Vehicle Management</h4>
                <small class="text-muted">Manage your fleet vehicles</small>
            </div>
            <div>
                <?php if ($can_edit): ?>
                    <a href="create.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </a>
                <?php else: ?>
                    <span class="view-only-badge">
                        <i class="fas fa-eye"></i> View Only
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="type" class="form-label">
                            <i class="fas fa-tag"></i> Vehicle Type
                        </label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All Types</option>
                            <?php 
                            if ($typesResult && $typesResult->num_rows > 0):
                                while ($row = $typesResult->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $row['vehicle_type']; ?>" 
                                    <?php echo $type == $row['vehicle_type'] ? 'selected' : ''; ?>>
                                    <?php echo $row['vehicle_type']; ?>
                                </option>
                            <?php endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">
                            <i class="fas fa-filter"></i> Status
                        </label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Available" <?php echo $status == 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="On_Trip" <?php echo $status == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                            <option value="In_Shop" <?php echo $status == 'In_Shop' ? 'selected' : ''; ?>>In Shop</option>
                            <option value="Retired" <?php echo $status == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" name="search" id="search" class="form-control" 
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
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Registration</th>
                                <th>Model</th>
                                <th>Type</th>
                                <th>Max Load (kg)</th>
                                <th>Odometer (km)</th>
                                <th>Status</th>
                                <?php if ($can_edit): ?>
                                    <th class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($vehicles && $vehicles->num_rows > 0): ?>
                                <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($vehicle['registration_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                        <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                        <td><?php echo number_format($vehicle['max_load_capacity'], 2); ?></td>
                                        <td><?php echo number_format($vehicle['odometer_reading'], 0); ?></td>
                                        <td>
                                            <span class="badge-status <?php echo $vehicle['status']; ?>">
                                                <?php echo str_replace('_', ' ', $vehicle['status']); ?>
                                            </span>
                                        </td>
                                        <?php if ($can_edit): ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                                       class="btn btn-sm btn-edit" 
                                                       title="Edit Vehicle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="deleteVehicle(<?php echo $vehicle['vehicle_id']; ?>, '<?php echo addslashes($vehicle['registration_number']); ?>', '<?php echo $vehicle['status']; ?>')"
                                                       class="btn btn-sm btn-delete" 
                                                       title="Delete Vehicle">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $vehicle['vehicle_id']; ?>" 
                                                       class="btn btn-sm btn-view" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $can_edit ? '7' : '6'; ?>" class="text-center py-4">
                                        <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No vehicles found</p>
                                        <?php if ($can_edit): ?>
                                            <a href="create.php" class="btn btn-sm btn-primary mt-2">
                                                <i class="fas fa-plus"></i> Add Vehicle
                                            </a>
                                        <?php endif; ?>
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

<script>
function deleteVehicle(id, registration, status) {
    if (status === 'On_Trip') {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            html: `Vehicle <strong>${registration}</strong> is currently <strong>On Trip</strong>.<br><br>
                   <span style="color: #EF4444;">
                       <i class="fas fa-exclamation-triangle"></i> Please complete or cancel the trip first.
                   </span>`,
            confirmButtonColor: '#4F46E5',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    Swal.fire({
        title: 'Delete Vehicle',
        html: `Are you sure you want to delete vehicle <strong>${registration}</strong>?<br><br>
               <span style="color: #EF4444; font-size: 0.9rem;">
                   <i class="fas fa-exclamation-triangle"></i> This action cannot be undone!
               </span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve) {
                window.location.href = 'delete.php?id=' + id;
                resolve();
            });
        }
    });
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include '../../includes/footer.php'; ?>