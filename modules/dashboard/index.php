<?php
error_reporting(0);
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$page_title = 'Dashboard';
$page_subtitle = 'Overview of your transport operations';

$db = Database::getInstance()->getConnection();

// Get dashboard statistics
$stats = [];

// Active Vehicles
$query = "SELECT COUNT(*) as count FROM vehicles WHERE status IN ('Available', 'On_Trip')";
$result = $db->query($query);
$stats['active_vehicles'] = $result->fetch_assoc()['count'];

// Available Vehicles
$query = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'Available'";
$result = $db->query($query);
$stats['available_vehicles'] = $result->fetch_assoc()['count'];

// Vehicles in Maintenance
$query = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'In_Shop'";
$result = $db->query($query);
$stats['maintenance_vehicles'] = $result->fetch_assoc()['count'];

// Active Trips
$query = "SELECT COUNT(*) as count FROM trips WHERE status = 'Dispatched'";
$result = $db->query($query);
$stats['active_trips'] = $result->fetch_assoc()['count'];

// Pending Trips
$query = "SELECT COUNT(*) as count FROM trips WHERE status = 'Draft'";
$result = $db->query($query);
$stats['pending_trips'] = $result->fetch_assoc()['count'];

// Drivers On Duty
$query = "SELECT COUNT(*) as count FROM drivers WHERE status IN ('Available', 'On_Trip')";
$result = $db->query($query);
$stats['drivers_on_duty'] = $result->fetch_assoc()['count'];

// Fleet Utilization
$query = "SELECT COUNT(*) as total FROM vehicles";
$result = $db->query($query);
$total_vehicles = $result->fetch_assoc()['count'];
$utilization = $total_vehicles > 0 ? round(($stats['active_vehicles'] / $total_vehicles) * 100, 1) : 0;
$stats['fleet_utilization'] = $utilization;

// Recent Trips
$query = "SELECT t.*, v.registration_number, d.full_name as driver_name 
          FROM trips t 
          JOIN vehicles v ON t.vehicle_id = v.vehicle_id 
          JOIN drivers d ON t.driver_id = d.driver_id 
          ORDER BY t.created_at DESC LIMIT 5";
$recent_trips = $db->query($query);

// Chart data
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM trips 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
          GROUP BY DATE(created_at)";
$chart_data = $db->query($query);
$labels = [];
$values = [];
while ($row = $chart_data->fetch_assoc()) {
    $labels[] = date('M d', strtotime($row['date']));
    $values[] = $row['count'];
}
?>
<?php include '../../includes/header.php'; ?>

<!-- Dashboard Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Active Vehicles</span>
                        <h2 class="fw-bold mt-1 mb-0"><?php echo $stats['active_vehicles']; ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-gradient" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-arrow-up text-success"></i> <?php echo $stats['available_vehicles']; ?> available
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Active Trips</span>
                        <h2 class="fw-bold mt-1 mb-0"><?php echo $stats['active_trips']; ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-gradient" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;">
                        <i class="fas fa-route"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-clock text-warning"></i> <?php echo $stats['pending_trips']; ?> pending
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Drivers On Duty</span>
                        <h2 class="fw-bold mt-1 mb-0"><?php echo $stats['drivers_on_duty']; ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-gradient" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-user-check text-success"></i> Available drivers
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 stat-card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Fleet Utilization</span>
                        <h2 class="fw-bold mt-1 mb-0"><?php echo $stats['fleet_utilization']; ?>%</h2>
                    </div>
                    <div class="stat-icon bg-info bg-gradient" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" style="width: <?php echo $stats['fleet_utilization']; ?>%;background:var(--primary-gradient);"></div>
                    </div>
                    <small class="text-muted"><?php echo $stats['active_vehicles']; ?> of <?php echo $total_vehicles; ?> vehicles</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Activity -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-chart-line text-primary"></i> Weekly Trip Activity</h5>
            </div>
            <div class="card-body p-4">
                <canvas id="tripChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-info-circle text-primary"></i> Quick Stats</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Maintenance Vehicles</span>
                    <span class="fw-bold text-danger"><?php echo $stats['maintenance_vehicles']; ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Pending Trips</span>
                    <span class="fw-bold text-warning"><?php echo $stats['pending_trips']; ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Vehicles</span>
                    <span class="fw-bold"><?php echo $total_vehicles; ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Fleet Efficiency</span>
                    <span class="fw-bold text-success"><?php echo $stats['fleet_utilization']; ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Trips -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="fas fa-history text-primary"></i> Recent Trips</h5>
                    <a href="../trips/index.php" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Trip #</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Source</th>
                                <th>Destination</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_trips->num_rows > 0): ?>
                                <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?php echo $trip['trip_number']; ?></span></td>
                                        <td><?php echo $trip['registration_number']; ?></td>
                                        <td><?php echo $trip['driver_name']; ?></td>
                                        <td><?php echo $trip['source_location']; ?></td>
                                        <td><?php echo $trip['destination_location']; ?></td>
                                        <td><?php echo getStatusBadge($trip['status']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-route fa-2x d-block mb-2" style="color:#E5E7EB;"></i>
                                        No trips found
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
// Trip Chart
const ctx = document.getElementById('tripChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 250);
gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Trips Created',
            data: <?php echo json_encode($values); ?>,
            borderColor: '#4F46E5',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#4F46E5',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#4F46E5',
                borderWidth: 2,
                cornerRadius: 10,
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' trips';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#6B7280' },
                grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false }
            },
            x: {
                ticks: { color: '#6B7280' },
                grid: { display: false }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>