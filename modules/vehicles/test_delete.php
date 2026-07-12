<?php
// test_delete.php - Debug file to test delete functionality
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "<h1>Delete Debug</h1>";
echo "<hr>";

echo "<h3>Vehicle ID: " . $id . "</h3>";
echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>GET parameters: " . print_r($_GET, true) . "</p>";

if ($id > 0) {
    // Check vehicle exists
    $checkStmt = $db->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $vehicle = $result->fetch_assoc();
    
    if ($vehicle) {
        echo "<h4 style='color:green;'>✅ Vehicle Found:</h4>";
        echo "<pre>";
        print_r($vehicle);
        echo "</pre>";
        
        // Check related records
        echo "<h4>Related Records:</h4>";
        
        // Check trips
        $tripCheck = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE vehicle_id = ?");
        $tripCheck->bind_param("i", $id);
        $tripCheck->execute();
        $tripResult = $tripCheck->get_result();
        $tripCount = $tripResult->fetch_assoc()['count'];
        echo "Trips: " . $tripCount . "<br>";
        
        // Check maintenance
        $maintenanceCheck = $db->prepare("SELECT COUNT(*) as count FROM maintenance_logs WHERE vehicle_id = ?");
        $maintenanceCheck->bind_param("i", $id);
        $maintenanceCheck->execute();
        $maintenanceResult = $maintenanceCheck->get_result();
        $maintenanceCount = $maintenanceResult->fetch_assoc()['count'];
        echo "Maintenance Records: " . $maintenanceCount . "<br>";
        
        // Check expenses
        $expenseCheck = $db->prepare("SELECT COUNT(*) as count FROM expenses WHERE vehicle_id = ?");
        $expenseCheck->bind_param("i", $id);
        $expenseCheck->execute();
        $expenseResult = $expenseCheck->get_result();
        $expenseCount = $expenseResult->fetch_assoc()['count'];
        echo "Expenses: " . $expenseCount . "<br>";
        
        // Check fuel logs
        $fuelCheck = $db->prepare("SELECT COUNT(*) as count FROM fuel_logs WHERE vehicle_id = ?");
        $fuelCheck->bind_param("i", $id);
        $fuelCheck->execute();
        $fuelResult = $fuelCheck->get_result();
        $fuelCount = $fuelResult->fetch_assoc()['count'];
        echo "Fuel Logs: " . $fuelCount . "<br>";
        
        echo "<hr>";
        echo "<a href='delete.php?id=" . $id . "' class='btn btn-danger'>Try Delete</a> ";
        echo "<a href='index.php' class='btn btn-secondary'>Back to Vehicles</a>";
        
    } else {
        echo "<h4 style='color:red;'>❌ Vehicle not found!</h4>";
        echo "<a href='index.php'>Back to Vehicles</a>";
    }
} else {
    echo "<h4 style='color:red;'>❌ Invalid ID! No ID parameter received.</h4>";
    echo "<h4>Available Vehicles:</h4>";
    
    $allVehicles = $db->query("SELECT vehicle_id, registration_number FROM vehicles");
    if ($allVehicles->num_rows > 0) {
        echo "<ul>";
        while ($v = $allVehicles->fetch_assoc()) {
            echo "<li>ID: " . $v['vehicle_id'] . " - " . $v['registration_number'] . 
                 " <a href='test_delete.php?id=" . $v['vehicle_id'] . "'>Test Delete</a></li>";
        }
        echo "</ul>";
    } else {
        echo "No vehicles found in database.";
    }
}
?>