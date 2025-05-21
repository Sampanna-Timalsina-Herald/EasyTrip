<?php
include 'config/database.php';
session_start();

// Check if we have the order ID
if (!isset($_GET['oid'])) {
    $_SESSION['payment_error'] = "Invalid payment response received.";
    header('Location: index.php');
    exit;
}

$order_id = $_GET['oid'];

// Check if the order already exists in the database
$stmt = $conn->prepare("SELECT id FROM bookings WHERE order_id = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing booking
    $stmt = $conn->prepare("UPDATE bookings SET status = 'payment_failed' WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
}

// Clear the temporary booking data
unset($_SESSION['temp_booking']);

// Set error message and redirect
$_SESSION['payment_error'] = "Your payment was not successful. Please try again or choose a different payment method.";
header('Location: index.php');
exit;
?>