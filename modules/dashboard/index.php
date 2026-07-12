<?php
error_reporting(0);
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

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

// Chart data - Weekly trips
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TransitOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-truck-fast"></i> TransitOps
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-vehicle"></i> Vehicles
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../vehicles/index.php">All Vehicles</a></li>
                            <li><a class="dropdown-item" href="../vehicles/create.php">Add Vehicle</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Drivers
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../drivers/index.php">All Drivers</a></li>
                            <li><a class="dropdown-item" href="../drivers/create.php">Add Driver</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../trips/index.php">
                            <i class="fas fa-route"></i> Trips
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../maintenance/index.php">
                            <i class="fas fa-tools"></i> Maintenance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../expenses/index.php">
                            <i class="fas fa-dollar-sign"></i> Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../reports/index.php">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Welcome Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h4 class="mb-0">
                            <i class="fas fa-wave-square"></i> 
                            Welcome back, <?php echo $_SESSION['full_name']; ?>!
                        </h4>
                        <small>Role: <?php echo str_replace('_', ' ', $_SESSION['role']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Active Vehicles</h6>
                                <h2 class="mb-0"><?php echo $stats['active_vehicles']; ?></h2>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Available Vehicles</h6>
                                <h2 class="mb-0"><?php echo $stats['available_vehicles']; ?></h2>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Active Trips</h6>
                                <h2 class="mb-0"><?php echo $stats['active_trips']; ?></h2>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-route"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">Drivers On Duty</h6>
                                <h2 class="mb-0"><?php echo $stats['drivers_on_duty']; ?></h2>
                            </div>
                            <div class="stat-icon bg-info">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Recent Activity -->
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Weekly Trip Activity</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="tripChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Fleet Utilization</span>
                                <span class="fw-bold"><?php echo $stats['fleet_utilization']; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $stats['fleet_utilization']; ?>%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span>Maintenance Vehicles</span>
                            <span class="float-end fw-bold text-danger"><?php echo $stats['maintenance_vehicles']; ?></span>
                        </div>
                        <div class="mb-2">
                            <span>Pending Trips</span>
                            <span class="float-end fw-bold text-warning"><?php echo $stats['pending_trips']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Trips -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Trips</h5>
                    </div>
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
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($trip = $recent_trips->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $trip['trip_number']; ?></td>
                                            <td><?php echo $trip['registration_number']; ?></td>
                                            <td><?php echo $trip['driver_name']; ?></td>
                                            <td><?php echo $trip['source_location']; ?></td>
                                            <td><?php echo $trip['destination_location']; ?></td>
                                            <td><?php echo getStatusBadge($trip['status']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container-fluid text-center">
            <span class="text-muted">
                &copy; <?php echo date('Y'); ?> TransitOps - Smart Transport Operations Platform
            </span>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize chart
        const ctx = document.getElementById('tripChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Trips Created',
                    data: <?php echo json_encode($values); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>