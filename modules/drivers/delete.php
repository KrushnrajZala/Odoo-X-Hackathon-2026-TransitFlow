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

// Check if driver is currently on trip
$checkStmt = $db->prepare("SELECT status FROM drivers WHERE driver_id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$driver = $result->fetch_assoc();

if ($driver) {
    if ($driver['status'] === 'On_Trip') {
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Cannot Delete",
                text: "Driver is currently on trip.",
                confirmButtonColor: "#0d6efd"
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
        exit();
    }
    
    // Delete driver
    $deleteStmt = $db->prepare("DELETE FROM drivers WHERE driver_id = ?");
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: "Driver deleted successfully.",
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
                text: "Failed to delete driver.",
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