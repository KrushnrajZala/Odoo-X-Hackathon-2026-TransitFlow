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

// Check if vehicle is currently on trip
$checkStmt = $db->prepare("SELECT status FROM vehicles WHERE vehicle_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$vehicle = $result->fetch_assoc();

if ($vehicle) {
    if ($vehicle['status'] === 'On_Trip') {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Cannot Delete",
                text: "Vehicle is currently on trip.",
                confirmButtonColor: "#0d6efd"
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
        exit();
    }
    
    // Delete vehicle
    $deleteStmt = $db->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: "Vehicle deleted successfully.",
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
                text: "Failed to delete vehicle.",
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