<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get trip status
$checkStmt = $db->prepare("SELECT status FROM trips WHERE trip_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$trip = $result->fetch_assoc();

if ($trip) {
    // If trip is dispatched, restore vehicle and driver status
    if ($trip['status'] === 'Dispatched') {
        $tripData = $db->query("SELECT vehicle_id, driver_id FROM trips WHERE trip_id = $id")->fetch_assoc();
        $db->query("UPDATE vehicles SET status = 'Available' WHERE vehicle_id = {$tripData['vehicle_id']}");
        $db->query("UPDATE drivers SET status = 'Available' WHERE driver_id = {$tripData['driver_id']}");
    }
    
    // Delete trip
    $deleteStmt = $db->prepare("DELETE FROM trips WHERE trip_id = ?");
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: "Trip deleted successfully.",
                timer: 1500,
                showConfirmButton: false
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
    } else {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to delete trip.",
                confirmButtonColor: "#0d6efd"
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
    }
} else {
    header("Location: index.php");
    exit();
}
?>