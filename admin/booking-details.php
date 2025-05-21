<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch booking details
$booking = null;
if ($booking_id > 0) {
    $stmt = $conn->prepare("SELECT b.*, u.name as user_name, u.email as user_email, 
                           h.name as hotel_name, h.location as hotel_location, 
                           bs.name as bus_name, bs.departure_location, bs.arrival_location 
                           FROM bookings b 
                           LEFT JOIN users u ON b.user_id = u.id 
                           LEFT JOIN hotels h ON b.hotel_id = h.id 
                           LEFT JOIN buses bs ON b.bus_id = bs.id 
                           WHERE b.id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    }
    $stmt->close();
}

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
        // Update the booking status in our current data
        $booking['status'] = $new_status;
    } else {
        $error_message = "Error updating booking status: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-sidebar">
            <div style="padding: 20px; text-align: center;">
                <h2 style="color: #fff; margin-bottom: 0;">Admin Panel</h2>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="booking.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="hotels.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="buses.php"><i class="fas fa-bus"></i> Buses</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Booking Details</h2>
                <p>
                    <a href="booking.php" style="margin-right: 10px;"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
                    Booking ID: #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>
                </p>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($booking): ?>
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Booking Information</h3>
                        <div>
                            <?php if ($booking['status'] == 'pending'): ?>
                                <span class="badge badge-warning">Pending Verification</span>
                            <?php elseif ($booking['status'] == 'confirmed'): ?>
                                <span class="badge badge-success">Confirmed</span>
                            <?php elseif ($booking['status'] == 'cancelled'): ?>
                                <span class="badge badge-danger">Cancelled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="booking-summary">
                        <div class="summary-item">
                            <span>Booking Date:</span>
                            <span><?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Customer:</span>
                            <span><?php echo $booking['user_name']; ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Email:</span>
                            <span><?php echo $booking['user_email']; ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Total Amount:</span>
                            <span>$<?php echo $booking['total_amount']; ?></span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <?php if (isset($booking['hotel_name'])): ?>
                        <div class="booking-summary">
                            <h4>Hotel Details</h4>
                            <div class="summary-item">
                                <span>Hotel:</span>
                                <span><?php echo $booking['hotel_name']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Location:</span>
                                <span><?php echo $booking['hotel_location']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Check-in:</span>
                                <span><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Check-out:</span>
                                <span><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Guests:</span>
                                <span><?php echo $booking['guests']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($booking['bus_name'])): ?>
                        <div class="booking-summary">
                            <h4>Bus Details</h4>
                            <div class="summary-item">
                                <span>Bus:</span>
                                <span><?php echo $booking['bus_name']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>From:</span>
                                <span><?php echo $booking['departure_location']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>To:</span>
                                <span><?php echo $booking['arrival_location']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Travel Date:</span>
                                <span><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Passengers:</span>
                                <span><?php echo $booking['passengers']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <h4>Payment Proof</h4>
                        <div style="margin-top: 15px; text-align: center;">
                            <?php
                            if (!empty($booking['payment_proof'])) {
                                $payment_proof_path = '../' . ltrim($booking['payment_proof'], '/');
                                if (file_exists($payment_proof_path)) {
                                    echo '<img src="' . $payment_proof_path . '" alt="Payment Proof" style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; display: block; margin: 0 auto;">';
                                } else {
                                    echo '<p style="color: red;">Payment proof image not found.</p>';
                                }
                            } else {
                                echo '<img src="/placeholder.svg?height=300&width=400" alt="Payment Proof Placeholder" style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; display: block; margin: 0 auto;">';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <h4>Update Booking Status</h4>
                        <form action="" method="POST" style="max-width: 400px; margin-top: 15px;">
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending Verification</option>
                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-danger">
                <p>Booking not found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
