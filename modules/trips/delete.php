<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if trip exists
$checkStmt = $db->prepare("SELECT trip_number, status, vehicle_id, driver_id FROM trips WHERE trip_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$trip = $result->fetch_assoc();

if (!$trip) {
    $_SESSION['error_message'] = 'Trip not found.';
    header("Location: index.php");
    exit();
}

// Check if trip is Completed - Prevent deletion
if ($trip['status'] === 'Completed') {
    $_SESSION['error_message'] = 'Cannot delete completed trip "' . $trip['trip_number'] . '". Completed trips are kept for audit purposes.';
    header("Location: index.php");
    exit();
}

// Check for fuel logs
$fuelCheck = $db->prepare("SELECT COUNT(*) as count FROM fuel_logs WHERE trip_id = ?");
$fuelCheck->bind_param("i", $id);
$fuelCheck->execute();
$fuelResult = $fuelCheck->get_result();
$fuelCount = $fuelResult->fetch_assoc()['count'];

// If there are fuel logs, delete them first (or show error)
if ($fuelCount > 0) {
    // Option 1: Auto-delete fuel logs
    $deleteFuel = $db->prepare("DELETE FROM fuel_logs WHERE trip_id = ?");
    $deleteFuel->bind_param("i", $id);
    $deleteFuel->execute();
    
    // Option 2: Show error (uncomment this if you want to prevent deletion)
    // $_SESSION['error_message'] = 'Cannot delete trip "' . $trip['trip_number'] . '" because it has ' . $fuelCount . ' associated fuel log(s). Delete the fuel logs first.';
    // header("Location: index.php");
    // exit();
}

// If trip is dispatched, restore vehicle and driver status
if ($trip['status'] === 'Dispatched') {
    $db->query("UPDATE vehicles SET status = 'Available' WHERE vehicle_id = {$trip['vehicle_id']}");
    $db->query("UPDATE drivers SET status = 'Available' WHERE driver_id = {$trip['driver_id']}");
}

// Delete trip
$deleteStmt = $db->prepare("DELETE FROM trips WHERE trip_id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    $_SESSION['success_message'] = 'Trip "' . $trip['trip_number'] . '" deleted successfully!';
} else {
    $_SESSION['error_message'] = 'Failed to delete trip. Database error: ' . $db->error;
}

header("Location: index.php");
exit();
?>