<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// Get fleet statistics
$totalVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$activeVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status IN ('Available', 'On_Trip')")->fetch_assoc()['count'];
$utilization = $totalVehicles > 0 ? round(($activeVehicles / $totalVehicles) * 100, 2) : 0;

// Get fuel efficiency
$fuelData = $db->query("
    SELECT 
        SUM(liters) as total_fuel,
        SUM(total_cost) as total_fuel_cost,
        COUNT(DISTINCT trip_id) as trips_with_fuel
    FROM fuel_logs
")->fetch_assoc();

// Get operational costs
$maintenanceCost = $db->query("SELECT SUM(cost) as total FROM maintenance_logs")->fetch_assoc()['total'] ?? 0;
$fuelCost = $fuelData['total_fuel_cost'] ?? 0;
$totalOperationalCost = $maintenanceCost + $fuelCost;

// Get vehicle ROI
$vehicleROI = $db->query("
    SELECT 
        v.registration_number,
        v.acquisition_cost,
        SUM(e.amount) as total_expenses,
        COUNT(t.trip_id) as total_trips
    FROM vehicles v
    LEFT JOIN expenses e ON v.vehicle_id = e.vehicle_id
    LEFT JOIN trips t ON v.vehicle_id = t.vehicle_id AND t.status = 'Completed'
    GROUP BY v.vehicle_id
    ORDER BY v.acquisition_cost DESC
")->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - TransitOps</title>
    <?php include '../../includes/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
                <p class="text-muted">Comprehensive operational insights and performance metrics</p>
            </div>
        </div>
        
        <!-- Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Fleet Utilization</h6>
                        <h2><?php echo $utilization; ?>%</h2>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $utilization; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Fuel Consumed</h6>
                        <h2><?php echo number_format($fuelData['total_fuel'] ?? 0, 2); ?> L</h2>
                        <small class="text-muted">Cost: $<?php echo number_format($fuelCost, 2); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Operational Cost</h6>
                        <h2>$<?php echo number_format($totalOperationalCost, 2); ?></h2>
                        <small class="text-muted">Maintenance: $<?php echo number_format($maintenanceCost, 2); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Active Vehicles</h6>
                        <h2><?php echo $activeVehicles; ?>/<?php echo $totalVehicles; ?></h2>
                        <small class="text-muted">Available for dispatch</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Vehicle ROI Analysis</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="roiChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Cost Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="costChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vehicle ROI Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Vehicle Performance & ROI</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Acquisition Cost</th>
                                        <th>Total Expenses</th>
                                        <th>Total Trips</th>
                                        <th>ROI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($vehicle = $vehicleROI->fetch_assoc()): 
                                        $roi = $vehicle['acquisition_cost'] > 0 ? 
                                               (($vehicle['total_trips'] * 100 - $vehicle['total_expenses']) / $vehicle['acquisition_cost']) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo $vehicle['registration_number']; ?></strong></td>
                                            <td>$<?php echo number_format($vehicle['acquisition_cost'], 2); ?></td>
                                            <td>$<?php echo number_format($vehicle['total_expenses'] ?? 0, 2); ?></td>
                                            <td><?php echo $vehicle['total_trips']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $roi > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo number_format($roi, 2); ?>%
                                                </span>
                                            </td>
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
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    // ROI Chart
    <?php 
    $roiData = [];
    $vehicleROI->data_seek(0);
    while ($vehicle = $vehicleROI->fetch_assoc()) {
        $roi = $vehicle['acquisition_cost'] > 0 ? 
               (($vehicle['total_trips'] * 100 - $vehicle['total_expenses']) / $vehicle['acquisition_cost']) * 100 : 0;
        $roiData[] = ['label' => $vehicle['registration_number'], 'value' => $roi];
    }
    ?>
    const roiCtx = document.getElementById('roiChart').getContext('2d');
    new Chart(roiCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($roiData, 'label')); ?>,
            datasets: [{
                label: 'ROI (%)',
                data: <?php echo json_encode(array_column($roiData, 'value')); ?>,
                backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d'],
                borderColor: '#fff',
                borderWidth: 2
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
                    beginAtZero: true
                }
            }
        }
    });

    // Cost Breakdown Chart
    const costCtx = document.getElementById('costChart').getContext('2d');
    new Chart(costCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fuel', 'Maintenance', 'Tolls', 'Insurance', 'Other'],
            datasets: [{
                data: [
                    <?php 
                    $fuelTotal = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Fuel'")->fetch_assoc()['total'] ?? 0;
                    $maintenanceTotal = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type IN ('Maintenance', 'Repair')")->fetch_assoc()['total'] ?? 0;
                    $tollTotal = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Toll'")->fetch_assoc()['total'] ?? 0;
                    $insuranceTotal = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Insurance'")->fetch_assoc()['total'] ?? 0;
                    $otherTotal = $db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_type = 'Other'")->fetch_assoc()['total'] ?? 0;
                    echo "$fuelTotal, $maintenanceTotal, $tollTotal, $insuranceTotal, $otherTotal";
                    ?>
                ],
                backgroundColor: ['#ffc107', '#dc3545', '#0dcaf0', '#198754', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
</body>
</html>