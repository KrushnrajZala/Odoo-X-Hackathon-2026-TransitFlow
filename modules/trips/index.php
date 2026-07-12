<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Allow Fleet Manager and Driver to view trips
if (!in_array($_SESSION['role'], ['Fleet_Manager', 'Driver'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$can_edit = in_array($_SESSION['role'], ['Fleet_Manager', 'Driver']);

// Check for success/error messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

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

$page_title = 'Trips';
$page_subtitle = 'Manage your trips';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .action-buttons .btn-sm {
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        border-radius: 8px;
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
    
    /* Filter Labels */
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
    .status-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-badge.Draft { background: #E2E8F0; color: #475569; }
    .status-badge.Dispatched { background: #DBEAFE; color: #1D4ED8; }
    .status-badge.Completed { background: #D1FAE5; color: #065F46; }
    .status-badge.Cancelled { background: #FEE2E2; color: #991B1B; }
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
                <h4 class="fw-bold mb-0"><i class="fas fa-route text-primary"></i> Trip Management</h4>
                <small class="text-muted">View and manage your trips</small>
            </div>
            <div>
                <?php if ($can_edit): ?>
                    <a href="create.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus"></i> Create Trip
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">
                            <i class="fas fa-filter"></i> Status
                        </label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Draft" <?php echo $status == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Dispatched" <?php echo $status == 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="Completed" <?php echo $status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="search" class="form-label">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" name="search" id="search" class="form-control" 
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
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Trip #</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Source</th>
                                <th>Destination</th>
                                <th>Cargo (kg)</th>
                                <th>Status</th>
                                <?php if ($can_edit): ?>
                                    <th class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($trips->num_rows > 0): ?>
                                <?php while ($trip = $trips->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?php echo $trip['trip_number']; ?></span></td>
                                        <td><?php echo $trip['registration_number']; ?></td>
                                        <td><?php echo $trip['driver_name']; ?></td>
                                        <td><?php echo $trip['source_location']; ?></td>
                                        <td><?php echo $trip['destination_location']; ?></td>
                                        <td><?php echo number_format($trip['cargo_weight'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $trip['status']; ?>">
                                                <?php echo $trip['status']; ?>
                                            </span>
                                        </td>
                                        <?php if ($can_edit): ?>
                                            <td>
                                                <div class="action-buttons justify-content-center">
                                                    <a href="edit.php?id=<?php echo $trip['trip_id']; ?>" 
                                                       class="btn btn-sm btn-edit" 
                                                       title="Edit Trip">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="deleteTrip(<?php echo $trip['trip_id']; ?>, '<?php echo addslashes($trip['trip_number']); ?>', '<?php echo $trip['status']; ?>')"
                                                       class="btn btn-sm btn-delete" 
                                                       title="Delete Trip">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $trip['trip_id']; ?>" 
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
                                    <td colspan="<?php echo $can_edit ? '8' : '7'; ?>" class="text-center py-4">
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

<script>
function deleteTrip(id, tripNumber, status) {
    // Check if trip is Completed - Cannot delete completed trips
    if (status === 'Completed') {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            html: `Trip <strong>${tripNumber}</strong> is <strong>Completed</strong>.<br><br>
                   <span style="color: #EF4444;">
                       <i class="fas fa-exclamation-triangle"></i> Completed trips cannot be deleted for audit purposes.
                   </span>`,
            confirmButtonColor: '#4F46E5',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Check if trip is Dispatched - Warning before delete
    if (status === 'Dispatched') {
        Swal.fire({
            title: 'Trip is Dispatched',
            html: `Trip <strong>${tripNumber}</strong> is currently <strong>Dispatched</strong>.<br><br>
                   <span style="color: #D97706;">
                       <i class="fas fa-exclamation-triangle"></i> Deleting will restore the vehicle and driver to Available.
                   </span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete.php?id=' + id;
            }
        });
        return;
    }
    
    // For Draft or Cancelled trips - normal delete with confirmation
    Swal.fire({
        title: 'Delete Trip',
        html: `Are you sure you want to delete trip <strong>${tripNumber}</strong>?<br><br>
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
        allowOutsideClick: false,
        preConfirm: function() {
            // Navigate to delete page
            window.location.href = 'delete.php?id=' + id;
            return new Promise(function(resolve) {
                setTimeout(resolve, 500);
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