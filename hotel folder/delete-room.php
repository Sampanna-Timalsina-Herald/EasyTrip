<?php
session_start();

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
  header('Location: ../login.php');
  exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($room_id <= 0) {
  header('Location: rooms.php');
  exit;
}

// Check if room belongs to one of the owner's hotels
$stmt = $conn->prepare("SELECT r.id, h.id as hotel_id 
                       FROM hotel_rooms r 
                       JOIN hotels h ON r.hotel_id = h.id 
                       WHERE r.id = ? AND h.owner_id = ?");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  $_SESSION['error_message'] = "You don't have permission to delete this room.";
  header('Location: rooms.php');
  exit;
}

$room = $result->fetch_assoc();
$hotel_id = $room['hotel_id'];
$stmt->close();

// Begin transaction
$conn->begin_transaction();

try {
  // Delete room amenities first (foreign key constraint)
  $stmt = $conn->prepare("DELETE FROM room_amenities WHERE room_id = ?");
  $stmt->bind_param("i", $room_id);
  $stmt->execute();
  
  // Delete room
  $stmt = $conn->prepare("DELETE FROM hotel_rooms WHERE id = ?");
  $stmt->bind_param("i", $room_id);
  $stmt->execute();
  
  // Commit transaction
  $conn->commit();
  
  $_SESSION['success_message'] = "Room deleted successfully!";
} catch (Exception $e) {
  // Rollback transaction on error
  $conn->rollback();
  $_SESSION['error_message'] = "Error deleting room: " . $e->getMessage();
}

// Redirect back to rooms page
header("Location: rooms.php?hotel_id=$hotel_id");
exit;
?>

