<?php
session_start();

// Check if user is logged in and is a bus operator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bus_operator') {
  header('Location: ../login.php');
  exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$bus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bus_id <= 0) {
  $_SESSION['error_message'] = "Invalid bus ID.";
  header('Location: buses.php');
  exit;
}

// Check if bus belongs to the operator
$stmt = $conn->prepare("SELECT id FROM buses WHERE id = ? AND operator_id = ?");
$stmt->bind_param("ii", $bus_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  $_SESSION['error_message'] = "You don't have permission to delete this bus.";
  header('Location: buses.php');
  exit;
}
$stmt->close();

// Check if there are any active bookings for this bus
$stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE bus_id = ? AND status IN ('pending', 'confirmed')");
$stmt->bind_param("i", $bus_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$active_bookings = $row['booking_count'];
$stmt->close();

if ($active_bookings > 0) {
  $_SESSION['error_message'] = "Cannot delete bus with active bookings. Please cancel or complete all bookings first.";
  header('Location: buses.php');
  exit;
}

// Begin transaction
$conn->begin_transaction();

try {
  // Delete bus seats first (foreign key constraint)
  $stmt = $conn->prepare("DELETE FROM bus_seats WHERE bus_id = ?");
  $stmt->bind_param("i", $bus_id);
  $stmt->execute();
  
  // Delete bus amenities
  $stmt = $conn->prepare("DELETE FROM bus_amenities WHERE bus_id = ?");
  $stmt->bind_param("i", $bus_id);
  $stmt->execute();
  
  // Delete bus
  $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
  $stmt->bind_param("i", $bus_id);
  $stmt->execute();
  
  // Commit transaction
  $conn->commit();
  
  $_SESSION['success_message'] = "Bus deleted successfully!";
} catch (Exception $e) {
  // Rollback transaction on error
  $conn->rollback();
  $_SESSION['error_message'] = "Error deleting bus: " . $e->getMessage();
}

// Redirect back to buses page
header('Location: buses.php');
exit;
?>
