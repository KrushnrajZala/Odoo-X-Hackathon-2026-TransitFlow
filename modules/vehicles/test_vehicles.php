<?php
// test_vehicles.php - Debug file to check vehicles
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();

echo "<h1>Vehicle Database Debug</h1>";
echo "<hr>";

// Check total vehicles
$total = $db->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
echo "<h3>Total Vehicles: " . $total . "</h3>";

if ($total > 0) {
    echo "<h4>All Vehicles:</h4>";
    $result = $db->query("SELECT * FROM vehicles ORDER BY created_at DESC");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Registration</th>
            <th>Model</th>
            <th>Type</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Created</th>
          </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['vehicle_id'] . "</td>";
        echo "<td>" . $row['registration_number'] . "</td>";
        echo "<td>" . $row['model'] . "</td>";
        echo "<td>" . $row['vehicle_type'] . "</td>";
        echo "<td>" . $row['max_load_capacity'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No vehicles found in database!</p>";
}

echo "<hr>";
echo "<a href='index.php'>Back to Vehicles</a>";
?>