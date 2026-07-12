<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

if (!isAdmin()) {
    header("Location: ../dashboard/index.php");
    exit();
}

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if driver exists
$checkStmt = $db->prepare("SELECT full_name, status FROM drivers WHERE driver_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$driver = $result->fetch_assoc();

if (!$driver) {
    $_SESSION['error_message'] = 'Driver not found.';
    header("Location: index.php");
    exit();
}

// ============================================
// CHECK FOR EXISTING TRIPS
// ============================================
$tripCheck = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE driver_id = ?");
$tripCheck->bind_param("i", $id);
$tripCheck->execute();
$tripResult = $tripCheck->get_result();
$tripCount = $tripResult->fetch_assoc()['count'];

if ($tripCount > 0) {
    $_SESSION['error_message'] = 'Cannot delete driver "' . $driver['full_name'] . '" because they have ' . $tripCount . ' assigned trip(s). Please reassign or delete the trips first.';
    header("Location: index.php");
    exit();
}

// Check if driver is currently on trip
if ($driver['status'] === 'On_Trip') {
    $_SESSION['error_message'] = 'Cannot delete driver "' . $driver['full_name'] . '" because they are currently on trip.';
    header("Location: index.php");
    exit();
}

// Delete driver
$deleteStmt = $db->prepare("DELETE FROM drivers WHERE driver_id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    $_SESSION['success_message'] = 'Driver "' . $driver['full_name'] . '" deleted successfully!';
} else {
    $_SESSION['error_message'] = 'Failed to delete driver. Database error: ' . $db->error;
}

header("Location: index.php");
exit();
?>