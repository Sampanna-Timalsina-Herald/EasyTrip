<?php
include 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Check if booking exists and belongs to the user
if ($booking_id > 0) {
    $stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Check if booking is in pending status
        if ($booking['status'] == 'pending') {
            // Update booking status to cancelled
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            
            if ($stmt->execute()) {
                $_SESSION['cancel_success'] = true;
            } else {
                $_SESSION['cancel_error'] = "Error cancelling booking: " . $conn->error;
            }
        } else {
            $_SESSION['cancel_error'] = "Only pending bookings can be cancelled.";
        }
    } else {
        $_SESSION['cancel_error'] = "Booking not found or you don't have permission to cancel it.";
    }
    
    $stmt->close();
} else {
    $_SESSION['cancel_error'] = "Invalid booking ID.";
}

// Redirect back to my bookings page
header('Location: my-bookings.php');
exit;
?>
