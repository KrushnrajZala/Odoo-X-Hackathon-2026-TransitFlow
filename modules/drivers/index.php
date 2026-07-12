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

$page_title = 'Drivers';
$page_subtitle = 'Manage your drivers';
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
    .license-expired {
        color: #DC2626;
        font-weight: 600;
    }
    .license-valid {
        color: #16A34A;
    }
    .license-warning {
        color: #D97706;
        font-weight: 600;
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
    .badge-safety {
        padding: 0.25rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-safety.high { background: #D1FAE5; color: #065F46; }
    .badge-safety.medium { background: #FEF3C7; color: #92400E; }
    .badge-safety.low { background: #FEE2E2; color: #991B1B; }
    
    .badge-status {
        padding: 0.25rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-status.Available { background: #D1FAE5; color: #065F46; }
    .badge-status.On_Trip { background: #DBEAFE; color: #1D4ED8; }
    .badge-status.Off_Duty { background: #FEF3C7; color: #92400E; }
    .badge-status.Suspended { background: #FEE2E2; color: #991B1B; }
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
                <h4 class="fw-bold mb-0"><i class="fas fa-users text-primary"></i> Driver Management</h4>
                <small class="text-muted">View and manage driver profiles</small>
            </div>
            <div>
                <?php if ($can_edit): ?>
                    <a href="create.php" class="btn btn-primary rounded-pill">
                        <i class="fas fa-plus"></i> Add Driver
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
                        <label for="status" class="form-label">
                            <i class="fas fa-filter"></i> Status
                        </label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Available" <?php echo $status == 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="On_Trip" <?php echo $status == 'On_Trip' ? 'selected' : ''; ?>>On Trip</option>
                            <option value="Off_Duty" <?php echo $status == 'Off_Duty' ? 'selected' : ''; ?>>Off Duty</option>
                            <option value="Suspended" <?php echo $status == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="search" class="form-label">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" name="search" id="search" class="form-control" 
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
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>License Number</th>
                                <th>Category</th>
                                <th>License Expiry</th>
                                <th>Safety Score</th>
                                <th>Status</th>
                                <?php if ($can_edit): ?>
                                    <th class="text-center">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($drivers->num_rows > 0): ?>
                                <?php while ($driver = $drivers->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($driver['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                        <td><?php echo htmlspecialchars($driver['license_category']); ?></td>
                                        <td>
                                            <?php 
                                            $expiry = strtotime($driver['license_expiry_date']);
                                            $now = time();
                                            $days_left = ceil(($expiry - $now) / (60 * 60 * 24));
                                            
                                            if ($expiry < $now) {
                                                echo '<span class="license-expired"><i class="fas fa-exclamation-triangle"></i> ' . date('Y-m-d', $expiry) . ' (Expired)</span>';
                                            } elseif ($days_left <= 30) {
                                                echo '<span class="license-warning"><i class="fas fa-clock"></i> ' . date('Y-m-d', $expiry) . ' (' . $days_left . ' days left)</span>';
                                            } else {
                                                echo '<span class="license-valid"><i class="fas fa-check-circle"></i> ' . date('Y-m-d', $expiry) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $score = $driver['safety_score'];
                                            $class = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : 'low');
                                            ?>
                                            <span class="badge-safety <?php echo $class; ?>">
                                                <?php echo number_format($score, 2); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?php echo $driver['status']; ?>">
                                                <?php echo str_replace('_', ' ', $driver['status']); ?>
                                            </span>
                                        </td>
                                        <?php if ($can_edit): ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit.php?id=<?php echo $driver['driver_id']; ?>" 
                                                       class="btn btn-sm btn-edit" 
                                                       title="Edit Driver">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" 
                                                       onclick="deleteDriver(<?php echo $driver['driver_id']; ?>, '<?php echo addslashes($driver['full_name']); ?>', '<?php echo $driver['status']; ?>')"
                                                       class="btn btn-sm btn-delete" 
                                                       title="Delete Driver">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="view.php?id=<?php echo $driver['driver_id']; ?>" 
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

<script>
function deleteDriver(id, name, status) {
    // Check if driver is On Trip
    if (status === 'On_Trip') {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            html: `Driver <strong>${name}</strong> is currently <strong>On Trip</strong>.<br><br>
                   <span style="color: #EF4444;">
                       <i class="fas fa-exclamation-triangle"></i> Please complete or cancel the trip first.
                   </span>`,
            confirmButtonColor: '#4F46E5',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Check if driver has trips
    Swal.fire({
        title: 'Checking...',
        text: 'Please wait while we check for existing trips.',
        allowOutsideClick: false,
        didOpen: function() {
            Swal.showLoading();
            
            $.ajax({
                url: 'check_driver_trips.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    
                    if (response.has_trips) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot Delete',
                            html: `Driver <strong>${name}</strong> has <strong>${response.trip_count}</strong> assigned trip(s).<br><br>
                                   <span style="color: #EF4444;">
                                       <i class="fas fa-exclamation-triangle"></i> Please reassign or delete the trips first.
                                   </span>`,
                            confirmButtonColor: '#4F46E5',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // Proceed with deletion
                        Swal.fire({
                            title: 'Delete Driver',
                            html: `Are you sure you want to delete driver <strong>${name}</strong>?<br><br>
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
                },
                error: function() {
                    Swal.close();
                    // Fallback - proceed with delete confirmation
                    Swal.fire({
                        title: 'Delete Driver',
                        html: `Are you sure you want to delete driver <strong>${name}</strong>?<br><br>
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