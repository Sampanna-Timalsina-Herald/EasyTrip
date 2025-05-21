<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
session_start();

// Log the incoming data
error_log('eSewa callback received: ' . json_encode($_GET));

error_log('Session data: ' . json_encode($_SESSION));

include 'config/database.php';

// Verify database connection
if (!$conn) {
    error_log('Database connection failed');
    $_SESSION['payment_error'] = "Database connection failed.";
    //header('Location: index.php');
    exit;
}

// Check if required parameters exist
if (!isset($_GET['oid']) || !isset($_GET['amt']) || !isset($_GET['refId'])) {
    echo $_GET['oid'];
    echo $_GET['amt'];
    echo $_GET['refId'];
    error_log('Missing required parameters from eSewa');
    $_SESSION['payment_error'] = "Invalid payment response received.";
    // header('Location: index.php');
    exit;
}

$order_id = $_GET['oid'];
$amount = $_GET['amt'];
$ref_id = $_GET['refId'];

error_log("Processing payment for order: $order_id, amount: $amount, ref: $ref_id");

// DIRECT DATABASE INSERTION APPROACH
// This approach bypasses session dependency and directly creates/updates the booking

try {
    // First check if we have session data (preferred method)
    if (isset($_SESSION[$order_id])) {
        error_log('Using session data for booking');
        $data = $_SESSION[$order_id];
        
        // Prepare data for insertion
        $user_id = $data['user_id'];
        $hotel_id = $data['hotel_id'] ?: null;
        $bus_id = $data['bus_id'] ?: null;
        $check_in_date = !empty($data['check_in_date']) ? $data['check_in_date'] : null;
        $check_out_date = !empty($data['check_out_date']) ? $data['check_out_date'] : null;
        $travel_date = !empty($data['travel_date']) ? $data['travel_date'] : null;
        $guests = $data['guests'];
        $passengers = $data['passengers'];
        $total_amount = $data['total_amount'];
        $payment_method = 'esewa';
        $status = 'confirmed';
        $booking_date = date('Y-m-d H:i:s');
        $updated_at = $booking_date;
        $selected_rooms = $data['selected_rooms'] ?? '';
        $selected_seats = $data['selected_seats'] ?? '';
        $payment_details = json_encode(['method' => 'esewa', 'ref_id' => $ref_id, 'amount' => $amount]);
    } else {
        // Fallback to using URL parameters if session is lost
        error_log('No session data, using fallback method');
        
        // Try to get user ID from an existing session
        if (!isset($_SESSION['user_id'])) {
            error_log('No user_id in session, payment cannot be processed');
            $_SESSION['payment_error'] = "User session expired. Please login and try again.";
            header('Location: login.php');
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $hotel_id = null;
        $bus_id = null;
        $check_in_date = null;
        $check_out_date = null;
        $travel_date = null;
        $guests = 1;
        $passengers = 1;
        $total_amount = $amount;
        $payment_method = 'esewa';
        $status = 'confirmed';
        $booking_date = date('Y-m-d H:i:s');
        $updated_at = $booking_date;
        $selected_rooms = '';
        $selected_seats = '';
        $payment_details = json_encode(['method' => 'esewa', 'ref_id' => $ref_id, 'amount' => $amount, 'note' => 'Created from callback without session data']);
    }
    
    // Check if a booking with this order_id already exists
    $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE order_id = ? OR transaction_id = ?");
    $check_stmt->bind_param("ss", $order_id, $order_id);
    $check_stmt->execute();
    $existing_result = $check_stmt->get_result();
    
    if ($existing_result->num_rows > 0) {
        // Booking exists, update it
        $existing_booking = $existing_result->fetch_assoc();
        $booking_id = $existing_booking['id'];
        
        error_log("Updating existing booking ID: $booking_id");
        
        $update_stmt = $conn->prepare("
            UPDATE bookings 
            SET status = ?, 
                transaction_id = ?, 
                payment_method = ?,
                updated_at = ?,
                payment_details = ?
            WHERE id = ?
        ");
        
        $update_stmt->bind_param(
            "sssssi",
            $status,
            $ref_id,
            $payment_method,
            $updated_at,
            $payment_details,
            $booking_id
        );
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update booking: " . $update_stmt->error);
        }
        
        error_log("Successfully updated booking ID: $booking_id");
    } else {
        // Create new booking
        error_log("Creating new booking for order: $order_id");
        
        $insert_stmt = $conn->prepare("
            INSERT INTO bookings (
                user_id, hotel_id, bus_id, check_in_date, check_out_date, travel_date,
                guests, passengers, selected_rooms, selected_seats,
                total_amount, payment_method, transaction_id,
                status, booking_date, updated_at, payment_details, order_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_stmt->bind_param(
            "iiisssiissssssssss",
            $user_id,
            $hotel_id,
            $bus_id,
            $check_in_date,
            $check_out_date,
            $travel_date,
            $guests,
            $passengers,
            $selected_rooms,
            $selected_seats,
            $total_amount,
            $payment_method,
            $ref_id,
            $status,
            $booking_date,
            $updated_at,
            $payment_details,
            $order_id
        );
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to create booking: " . $insert_stmt->error);
        }
        
        $booking_id = $insert_stmt->insert_id;
        error_log("Successfully created booking with ID: $booking_id");
    }
    
    // Set the booking success session variable
    $_SESSION['booking_success'] = $booking_id;
    
    // Clear the temp booking data
    if (isset($_SESSION['temp_booking'])) {
        unset($_SESSION['temp_booking']);
    }
    
    // Redirect to the confirmation page
    header('Location: booking-confirmation.php');
    exit;
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    $_SESSION['payment_error'] = "Payment processing error: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>
