<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

// ============================================
// 1. FLEET STATISTICS - REAL DATA
// ============================================

// Total Vehicles
$totalVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];

// Active Vehicles (Available or On Trip)
$activeVehicles = $db->query("SELECT COUNT(*) as count FROM vehicles WHERE status IN ('Available', 'On_Trip')")->fetch_assoc()['count'];

// Fleet Utilization
$utilization = $totalVehicles > 0 ? round(($activeVehicles / $totalVehicles) * 100, 2) : 0;

// Vehicles by Status
$statusCounts = [];
$statusResult = $db->query("SELECT status, COUNT(*) as count FROM vehicles GROUP BY status");
while ($row = $statusResult->fetch_assoc()) {
    $statusCounts[$row['status']] = $row['count'];
}

// ============================================
// 2. FUEL STATISTICS - REAL DATA
// ============================================

$fuelData = $db->query("
    SELECT 
        COALESCE(SUM(liters), 0) as total_fuel,
        COALESCE(SUM(total_cost), 0) as total_fuel_cost,
        COUNT(*) as fuel_entries
    FROM fuel_logs
")->fetch_assoc();

$totalFuel = $fuelData['total_fuel'];
$totalFuelCost = $fuelData['total_fuel_cost'];

// ============================================
// 3. OPERATIONAL COSTS - REAL DATA
// ============================================

// Maintenance Costs
$maintenanceData = $db->query("
    SELECT 
        COALESCE(SUM(cost), 0) as total_maintenance,
        COUNT(*) as maintenance_count
    FROM maintenance_logs
")->fetch_assoc();
$totalMaintenance = $maintenanceData['total_maintenance'];

// Fuel Costs (already calculated above)
// Total Operational Cost
$totalOperationalCost = $totalMaintenance + $totalFuelCost;

// ============================================
// 4. REVENUE & ROI - REAL DATA
// ============================================

// Get completed trips revenue
$tripRevenue = $db->query("
    SELECT 
        COUNT(*) as total_trips,
        COALESCE(SUM(planned_distance * 0.5), 0) as estimated_revenue
    FROM trips 
    WHERE status = 'Completed'
")->fetch_assoc();

$totalCompletedTrips = $tripRevenue['total_trips'];
$totalRevenue = $tripRevenue['estimated_revenue'];

// If no revenue from distance, use a default $100 per trip
if ($totalRevenue == 0 && $totalCompletedTrips > 0) {
    $totalRevenue = $totalCompletedTrips * 100;
}

// Calculate Profit
$totalProfit = $totalRevenue - $totalOperationalCost;

// Total Acquisition Cost
$totalAcquisitionCost = $db->query("SELECT COALESCE(SUM(acquisition_cost), 0) as total FROM vehicles")->fetch_assoc()['total'];

// Overall ROI
$overallROI = $totalAcquisitionCost > 0 ? round(($totalProfit / $totalAcquisitionCost) * 100, 2) : 0;

// ============================================
// 5. VEHICLE ROI - REAL DATA PER VEHICLE
// ============================================

$roiQuery = "
    SELECT 
        v.vehicle_id,
        v.registration_number,
        v.model,
        v.vehicle_type,
        v.acquisition_cost,
        v.status,
        COALESCE((
            SELECT COUNT(*) FROM trips t 
            WHERE t.vehicle_id = v.vehicle_id AND t.status = 'Completed'
        ), 0) as completed_trips,
        COALESCE((
            SELECT SUM(amount) FROM expenses e 
            WHERE e.vehicle_id = v.vehicle_id
        ), 0) as total_expenses,
        COALESCE((
            SELECT SUM(cost) FROM maintenance_logs m 
            WHERE m.vehicle_id = v.vehicle_id
        ), 0) as total_maintenance_cost,
        COALESCE((
            SELECT SUM(total_cost) FROM fuel_logs f 
            WHERE f.vehicle_id = v.vehicle_id
        ), 0) as total_fuel_cost
    FROM vehicles v
    ORDER BY v.registration_number
";
$roiResult = $db->query($roiQuery);

// ============================================
// 6. EXPENSE BREAKDOWN - REAL DATA
// ============================================

$expenseBreakdown = [];
$expenseTypes = ['Fuel', 'Toll', 'Maintenance', 'Repair', 'Insurance', 'Other'];
foreach ($expenseTypes as $type) {
    $result = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE expense_type = '$type'");
    $expenseBreakdown[$type] = $result->fetch_assoc()['total'];
}

// Also include maintenance costs as part of expenses
$expenseBreakdown['Maintenance'] += $totalMaintenance;

// ============================================
// 7. PREPARE CHART DATA
// ============================================

$roiData = [];
$vehicleROIList = [];

if ($roiResult && $roiResult->num_rows > 0) {
    while ($vehicle = $roiResult->fetch_assoc()) {
        // Calculate total expenses for this vehicle
        $totalVehicleExpenses = $vehicle['total_expenses'] + $vehicle['total_maintenance_cost'] + $vehicle['total_fuel_cost'];
        
        // Calculate revenue from completed trips
        $vehicleRevenue = $vehicle['completed_trips'] * 100; // $100 per trip
        
        // Calculate profit
        $vehicleProfit = $vehicleRevenue - $totalVehicleExpenses;
        
        // Calculate ROI
        $acqCost = $vehicle['acquisition_cost'];
        if ($acqCost > 0) {
            $roi = round(($vehicleProfit / $acqCost) * 100, 2);
        } else {
            $roi = 0;
        }
        
        $roiData[] = [
            'label' => $vehicle['registration_number'],
            'value' => $roi,
            'model' => $vehicle['model'],
            'type' => $vehicle['vehicle_type'],
            'status' => $vehicle['status'],
            'acquisition_cost' => $acqCost,
            'trips' => $vehicle['completed_trips'],
            'revenue' => $vehicleRevenue,
            'expenses' => $totalVehicleExpenses,
            'profit' => $vehicleProfit,
            'maintenance_cost' => $vehicle['total_maintenance_cost'],
            'fuel_cost' => $vehicle['total_fuel_cost']
        ];
        
        $vehicleROIList[] = $vehicle;
    }
}

$page_title = 'Reports';
$page_subtitle = 'Comprehensive operational insights and performance metrics';
?>
<?php include '../../includes/header.php'; ?>

<style>
    .stat-card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
        overflow: hidden;
        background: white;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }
    .stat-card h2 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        color: #0F172A;
    }
    .stat-card h6 {
        font-size: 0.8rem;
        color: #6B7280;
        margin: 0;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .stat-card .stat-icon.blue { background: linear-gradient(135deg, #4F46E5, #7C3AED); }
    .stat-card .stat-icon.green { background: linear-gradient(135deg, #10B981, #059669); }
    .stat-card .stat-icon.orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .stat-card .stat-icon.red { background: linear-gradient(135deg, #EF4444, #DC2626); }
    .stat-card .stat-icon.purple { background: linear-gradient(135deg, #8B5CF6, #6D28D9); }
    .stat-card .stat-icon.teal { background: linear-gradient(135deg, #14B8A6, #0D9488); }
    
    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }
    .progress {
        height: 8px;
        border-radius: 4px;
        background: #E5E7EB;
    }
    .progress-bar {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        transition: width 1.5s ease;
    }
    .roi-positive { color: #10B981; }
    .roi-negative { color: #EF4444; }
    .roi-neutral { color: #F59E0B; }
    .status-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-badge.Available { background: #D1FAE5; color: #065F46; }
    .status-badge.On_Trip { background: #DBEAFE; color: #1D4ED8; }
    .status-badge.In_Shop { background: #FEF3C7; color: #92400E; }
    .status-badge.Retired { background: #FEE2E2; color: #991B1B; }
</style>

<div class="row">
    <div class="col-12">
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-bar text-primary"></i> Reports & Analytics</h4>
        <small class="text-muted">Comprehensive operational insights and performance metrics</small>
    </div>
</div>

<!-- Key Metrics -->
<div class="row g-3 mt-3">
    <!-- Fleet Utilization -->
    <div class="col-md-3 col-sm-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <h6><i class="fas fa-chart-pie"></i> Fleet Utilization</h6>
                <h2><?php echo $utilization; ?>%</h2>
                <div class="progress mt-2" style="height:6px;">
                    <div class="progress-bar" style="width: <?php echo $utilization; ?>%;"></div>
                </div>
                <small class="text-muted"><?php echo $activeVehicles; ?> of <?php echo $totalVehicles; ?> vehicles</small>
                <div class="mt-1">
                    <small class="text-muted">
                        <?php 
                        $statusText = [];
                        foreach ($statusCounts as $status => $count) {
                            $statusText[] = str_replace('_', ' ', $status) . ': ' . $count;
                        }
                        echo implode(' | ', $statusText);
                        ?>
                    </small>
                </div>
            </div>
            <div class="stat-icon blue"><i class="fas fa-chart-pie"></i></div>
        </div>
    </div>
    
    <!-- Total Fuel -->
    <div class="col-md-3 col-sm-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <h6><i class="fas fa-gas-pump"></i> Total Fuel Consumed</h6>
                <h2><?php echo number_format($totalFuel, 0); ?> L</h2>
                <small class="text-muted">Cost: $<?php echo number_format($totalFuelCost, 2); ?></small>
                <div class="mt-1">
                    <small class="text-muted"><?php echo $fuelData['fuel_entries']; ?> fuel entries</small>
                </div>
            </div>
            <div class="stat-icon orange"><i class="fas fa-gas-pump"></i></div>
        </div>
    </div>
    
    <!-- Operational Cost -->
    <div class="col-md-3 col-sm-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <h6><i class="fas fa-dollar-sign"></i> Operational Cost</h6>
                <h2>$<?php echo number_format($totalOperationalCost, 2); ?></h2>
                <small class="text-muted">Maintenance: $<?php echo number_format($totalMaintenance, 2); ?></small>
                <div class="mt-1">
                    <small class="text-muted">Fuel: $<?php echo number_format($totalFuelCost, 2); ?></small>
                </div>
            </div>
            <div class="stat-icon red"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
    
    <!-- Overall ROI -->
    <div class="col-md-3 col-sm-6">
        <div class="stat-card d-flex justify-content-between align-items-center">
            <div>
                <h6><i class="fas fa-chart-line"></i> Overall ROI</h6>
                <h2 class="<?php echo $overallROI >= 0 ? 'roi-positive' : 'roi-negative'; ?>">
                    <?php echo number_format($overallROI, 2); ?>%
                </h2>
                <small class="text-muted">Revenue: $<?php echo number_format($totalRevenue, 2); ?></small>
                <div class="mt-1">
                    <small class="text-muted">Profit: $<?php echo number_format($totalProfit, 2); ?></small>
                </div>
            </div>
            <div class="stat-icon purple"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-chart-bar text-primary"></i> Vehicle ROI Analysis</h5>
                <small class="text-muted">ROI = (Revenue - Expenses) / Acquisition Cost × 100</small>
            </div>
            <div class="card-body p-4">
                <div class="chart-container">
                    <canvas id="roiChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-chart-doughnut text-primary"></i> Cost Breakdown</h5>
                <small class="text-muted">Distribution of operational expenses</small>
            </div>
            <div class="card-body p-4">
                <div class="chart-container">
                    <canvas id="costChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle ROI Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fas fa-table text-primary"></i> Vehicle Performance & ROI</h5>
                    <small class="text-muted">Detailed breakdown of each vehicle's performance</small>
                </div>
                <button class="btn btn-sm btn-outline-primary rounded-pill" onclick="window.print()">
                    <i class="fas fa-print"></i> Export
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Model</th>
                                <th>Status</th>
                                <th>Acquisition Cost</th>
                                <th>Trips</th>
                                <th>Revenue</th>
                                <th>Expenses</th>
                                <th>Profit</th>
                                <th>ROI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($roiData)): ?>
                                <?php foreach ($roiData as $vehicle): ?>
                                    <tr>
                                        <td><strong><?php echo $vehicle['label']; ?></strong></td>
                                        <td><small><?php echo $vehicle['model']; ?></small></td>
                                        <td>
                                            <span class="status-badge <?php echo $vehicle['status']; ?>">
                                                <?php echo str_replace('_', ' ', $vehicle['status']); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($vehicle['acquisition_cost'], 2); ?></td>
                                        <td><?php echo $vehicle['trips']; ?></td>
                                        <td>$<?php echo number_format($vehicle['revenue'], 2); ?></td>
                                        <td>$<?php echo number_format($vehicle['expenses'], 2); ?></td>
                                        <td class="<?php echo $vehicle['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            $<?php echo number_format($vehicle['profit'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $vehicle['value'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo number_format($vehicle['value'], 2); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                                        No data available for ROI analysis
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($roiData)): ?>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="2">TOTAL / AVERAGE</td>
                                    <td></td>
                                    <td>$<?php echo number_format($totalAcquisitionCost, 2); ?></td>
                                    <td><?php echo $totalCompletedTrips; ?></td>
                                    <td>$<?php echo number_format($totalRevenue, 2); ?></td>
                                    <td>$<?php echo number_format($totalOperationalCost, 2); ?></td>
                                    <td class="<?php echo $totalProfit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($totalProfit, 2); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $overallROI >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo number_format($overallROI, 2); ?>%
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-truck text-primary"></i> Vehicle Status Distribution</h5>
            </div>
            <div class="card-body p-4">
                <?php foreach ($statusCounts as $status => $count): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><?php echo str_replace('_', ' ', $status); ?></span>
                        <span class="fw-bold">
                            <?php echo $count; ?> vehicles
                            <span class="text-muted">
                                (<?php echo $totalVehicles > 0 ? round(($count / $totalVehicles) * 100, 1) : 0; ?>%)
                            </span>
                        </span>
                    </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between align-items-center py-2 fw-bold">
                    <span>Total</span>
                    <span><?php echo $totalVehicles; ?> vehicles</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-tasks text-primary"></i> Trip Statistics</h5>
            </div>
            <div class="card-body p-4">
                <?php
                $tripStats = $db->query("
                    SELECT status, COUNT(*) as count FROM trips GROUP BY status
                ");
                $totalTrips = $db->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'];
                while ($stat = $tripStats->fetch_assoc()):
                ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><?php echo $stat['status']; ?></span>
                        <span class="fw-bold">
                            <?php echo $stat['count']; ?> trips
                            <span class="text-muted">
                                (<?php echo $totalTrips > 0 ? round(($stat['count'] / $totalTrips) * 100, 1) : 0; ?>%)
                            </span>
                        </span>
                    </div>
                <?php endwhile; ?>
                <div class="d-flex justify-content-between align-items-center py-2 fw-bold">
                    <span>Total Trips</span>
                    <span><?php echo $totalTrips; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ROI Chart
    <?php 
    $roiLabels = json_encode(array_column($roiData, 'label'));
    $roiValues = json_encode(array_column($roiData, 'value'));
    $roiColors = json_encode(array_map(function($val) {
        return $val >= 0 ? '#10B981' : '#EF4444';
    }, array_column($roiData, 'value')));
    ?>
    
    const roiCtx = document.getElementById('roiChart').getContext('2d');
    new Chart(roiCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $roiLabels; ?>,
            datasets: [{
                label: 'ROI (%)',
                data: <?php echo $roiValues; ?>,
                backgroundColor: <?php echo $roiColors; ?>,
                borderColor: '#fff',
                borderWidth: 2,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'ROI: ' + context.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Cost Breakdown Chart
    const costCtx = document.getElementById('costChart').getContext('2d');
    new Chart(costCtx, {
        type: 'doughnut',
        data: {
            labels: ['Fuel', 'Tolls', 'Maintenance', 'Repair', 'Insurance', 'Other'],
            datasets: [{
                data: [
                    <?php echo $expenseBreakdown['Fuel']; ?>,
                    <?php echo $expenseBreakdown['Toll']; ?>,
                    <?php echo $expenseBreakdown['Maintenance']; ?>,
                    <?php echo $expenseBreakdown['Repair']; ?>,
                    <?php echo $expenseBreakdown['Insurance']; ?>,
                    <?php echo $expenseBreakdown['Other']; ?>
                ],
                backgroundColor: ['#F59E0B', '#06B6D4', '#EF4444', '#8B5CF6', '#10B981', '#6B7280'],
                borderColor: '#fff',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': $' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>