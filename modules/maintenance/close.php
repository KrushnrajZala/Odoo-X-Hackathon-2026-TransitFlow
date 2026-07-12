<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session_check.php';

$db = Database::getInstance()->getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get maintenance record
$stmt = $db->prepare("SELECT * FROM maintenance_logs WHERE maintenance_id = ? AND status = 'Active'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$maintenance = $result->fetch_assoc();

if ($maintenance) {
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Update maintenance status to Closed
        $updateStmt = $db->prepare("UPDATE maintenance_logs SET status = 'Closed', closed_date = NOW() WHERE maintenance_id = ?");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();
        
        // Update vehicle status back to Available
        $updateVehicle = $db->prepare("UPDATE vehicles SET status = 'Available' WHERE vehicle_id = ?");
        $updateVehicle->bind_param("i", $maintenance['vehicle_id']);
        $updateVehicle->execute();
        
        $db->commit();
        
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Maintenance Closed!",
                text: "Vehicle is now available again.",
                timer: 2000,
                showConfirmButton: false
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
    } catch (Exception $e) {
        $db->rollback();
        echo '<script>
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Failed to close maintenance.",
                confirmButtonColor: "#0d6efd"
            }).then(function() {
                window.location.href = "index.php";
            });
        </script>';
    }
} else {
    echo '<script>
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Maintenance record not found or already closed.",
            confirmButtonColor: "#0d6efd"
        }).then(function() {
            window.location.href = "index.php";
        });
    </script>';
}
?>