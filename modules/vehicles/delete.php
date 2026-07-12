<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

// Only Fleet Manager can delete vehicles
if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();

// Get the ID from GET or POST
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0 && isset($_POST['id'])) {
    $id = intval($_POST['id']);
}

// Debug log
error_log("Delete called with ID: " . $id);

// Check if ID is valid
if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid vehicle ID. Please try again.';
    header("Location: index.php");
    exit();
}

// Check if vehicle exists and get details
$checkStmt = $db->prepare("SELECT registration_number, status FROM vehicles WHERE vehicle_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    $_SESSION['error_message'] = 'Vehicle not found.';
    header("Location: index.php");
    exit();
}

// Check if vehicle is currently on trip
if ($vehicle['status'] === 'On_Trip') {
    $_SESSION['error_message'] = 'Cannot delete vehicle "' . $vehicle['registration_number'] . '" because it is currently on trip.';
    header("Location: index.php");
    exit();
}

// Check if vehicle has any associated trips
$tripCheck = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE vehicle_id = ?");
$tripCheck->bind_param("i", $id);
$tripCheck->execute();
$tripResult = $tripCheck->get_result();
$tripCount = $tripResult->fetch_assoc()['count'];

if ($tripCount > 0) {
    $_SESSION['error_message'] = 'Cannot delete vehicle "' . $vehicle['registration_number'] . '" because it has ' . $tripCount . ' associated trip(s).';
    header("Location: index.php");
    exit();
}

// Check if vehicle has maintenance records
$maintenanceCheck = $db->prepare("SELECT COUNT(*) as count FROM maintenance_logs WHERE vehicle_id = ?");
$maintenanceCheck->bind_param("i", $id);
$maintenanceCheck->execute();
$maintenanceResult = $maintenanceCheck->get_result();
$maintenanceCount = $maintenanceResult->fetch_assoc()['count'];

if ($maintenanceCount > 0) {
    $_SESSION['error_message'] = 'Cannot delete vehicle "' . $vehicle['registration_number'] . '" because it has ' . $maintenanceCount . ' associated maintenance record(s).';
    header("Location: index.php");
    exit();
}

// Check if vehicle has expenses
$expenseCheck = $db->prepare("SELECT COUNT(*) as count FROM expenses WHERE vehicle_id = ?");
$expenseCheck->bind_param("i", $id);
$expenseCheck->execute();
$expenseResult = $expenseCheck->get_result();
$expenseCount = $expenseResult->fetch_assoc()['count'];

if ($expenseCount > 0) {
    $_SESSION['error_message'] = 'Cannot delete vehicle "' . $vehicle['registration_number'] . '" because it has ' . $expenseCount . ' associated expense record(s).';
    header("Location: index.php");
    exit();
}

// Check if vehicle has fuel logs
$fuelCheck = $db->prepare("SELECT COUNT(*) as count FROM fuel_logs WHERE vehicle_id = ?");
$fuelCheck->bind_param("i", $id);
$fuelCheck->execute();
$fuelResult = $fuelCheck->get_result();
$fuelCount = $fuelResult->fetch_assoc()['count'];

if ($fuelCount > 0) {
    $_SESSION['error_message'] = 'Cannot delete vehicle "' . $vehicle['registration_number'] . '" because it has ' . $fuelCount . ' associated fuel log(s).';
    header("Location: index.php");
    exit();
}

// If all checks pass, delete the vehicle
$deleteStmt = $db->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    // Check if any rows were affected
    if ($deleteStmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'Vehicle "' . $vehicle['registration_number'] . '" deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'No rows affected. Vehicle may have been already deleted.';
    }
} else {
    // Get the actual error
    $error_msg = $db->error;
    $_SESSION['error_message'] = 'Failed to delete vehicle. Database error: ' . $error_msg;
}

$deleteStmt->close();
header("Location: index.php");
exit();
?>