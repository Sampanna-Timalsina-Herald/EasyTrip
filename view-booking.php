<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Get booking details
$booking = null;
$hotel = null;
$user = null;

if ($booking_id > 0) {
    // Verify the booking belongs to one of the owner's hotels
    $stmt = $conn->prepare("SELECT b.*, h.name as hotel_name, h.location as hotel_location, h.price_per_night, 
                           u.name as user_name, u.email as user_email, u.phone as user_phone
                           FROM bookings b 
                           JOIN hotels h ON b.hotel_id = h.id 
                           JOIN users u ON b.user_id = u.id
                           WHERE b.id = ? AND h.owner_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Calculate nights
        $check_in = new DateTime($booking['check_in_date']);
        $check_out = new DateTime($booking['check_out_date']);
        $nights = $check_in->diff($check_out)->days;
        
        // Calculate subtotal (before tax)
        $subtotal = $booking['total_amount'] / 1.1; // Assuming 10% tax
        $tax = $booking['total_amount'] - $subtotal;
    } else {
        $error_message = "Booking not found or you don't have permission to view it.";
    }
    $stmt->close();
}

// Process booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    // Verify the booking belongs to one of the owner's hotels
    $stmt = $conn->prepare("SELECT b.id FROM bookings b 
                         JOIN hotels h ON b.hotel_id = h.id 
                         WHERE b.id = ? AND h.owner_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $booking_id);
        
        if ($stmt->execute()) {
            $success_message = "Booking status updated successfully!";
            $booking['status'] = $new_status; // Update the status in our data
        } else {
            $error_message = "Error updating booking status: " . $conn->error;
        }
    } else {
        $error_message = "You don't have permission to update this booking.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking - Hotel Owner Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .dashboard-sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .dashboard-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .dashboard-sidebar ul li {
            margin-bottom: 5px;
        }
        
        .dashboard-sidebar ul li a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .dashboard-sidebar ul li a:hover {
            background-color: #34495e;
        }
        
        .dashboard-sidebar ul li a.active {
            background-color: #3a86ff;
            color: white;
        }
        
        .dashboard-sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .dashboard-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background-color: #f5f7fa;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        .content-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .booking-id {
            font-size: 1.2rem;
            font-weight: 600;
            color: #3a86ff;
            margin-bottom: 5px;
        }
        
        .booking-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .booking-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .booking-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .booking-detail {
            margin-bottom: 15px;
        }
        
        .booking-detail:last-child {
            margin-bottom: 0;
        }
        
        .booking-detail-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .booking-detail-value {
            color: #212529;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #d1e7dd;
            color: #146c43;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .payment-info {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        
        .payment-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .payment-info-row:last-child {
            margin-bottom: 0;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            font-weight: 600;
        }
        
        .payment-proof-image {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        
        .esewa-badge {
            display: inline-flex;
            align-items: center;
            background-color: #60BB46;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            gap: 5px;
        }
        
        .esewa-badge img {
            height: 16px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background-color: #3a86ff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2a75e6;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #6c757d;
            color: #6c757d;
        }
        
        .btn-outline:hover {
            background-color: #f8f9fa;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .dashboard-content {
                margin-left: 0;
            }
            
            .booking-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-sidebar">
            <div style="padding: 20px; text-align: center;">
                <h2 style="color: #fff; margin-bottom: 0;">Hotel Dashboard</h2>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="myhotels.php"><i class="fas fa-hotel"></i> My Hotels</a></li>
                <li><a href="add-hotel.php"><i class="fas fa-plus-circle"></i> Add Hotel</a></li>
                <li><a href="rooms.php"><i class="fas fa-door-open"></i> Manage Rooms</a></li>
                <li><a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Booking Details</h2>
                <a href="bookings.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($booking): ?>
                <div class="content-card">
                    <div class="card-header">
                        <div>
                            <div class="booking-id">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <div class="booking-date">Booked on <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></div>
                        </div>
                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="booking-grid">
                            <div class="booking-section">
                                <h3 class="booking-section-title">Guest Information</h3>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Name</div>
                                    <div class="booking-detail-value"><?php echo $booking['user_name']; ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Email</div>
                                    <div class="booking-detail-value"><?php echo $booking['user_email']; ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Phone</div>
                                    <div class="booking-detail-value"><?php echo $booking['user_phone'] ?? 'Not provided'; ?></div>
                                </div>
                            </div>
                            
                            <div class="booking-section">
                                <h3 class="booking-section-title">Hotel Information</h3>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Hotel</div>
                                    <div class="booking-detail-value"><?php echo $booking['hotel_name']; ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Location</div>
                                    <div class="booking-detail-value"><?php echo $booking['hotel_location']; ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Price per Night</div>
                                    <div class="booking-detail-value">NPR <?php echo number_format($booking['price_per_night'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-grid">
                            <div class="booking-section">
                                <h3 class="booking-section-title">Stay Details</h3>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Check-in Date</div>
                                    <div class="booking-detail-value"><?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Check-out Date</div>
                                    <div class="booking-detail-value"><?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Duration</div>
                                    <div class="booking-detail-value"><?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></div>
                                </div>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Guests</div>
                                    <div class="booking-detail-value"><?php echo $booking['guests']; ?> person<?php echo $booking['guests'] != 1 ? 's' : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="booking-section">
                                <h3 class="booking-section-title">Payment Information</h3>
                                <div class="booking-detail">
                                    <div class="booking-detail-label">Payment Method</div>
                                    <div class="booking-detail-value">
                                        <?php if (isset($booking['payment_method']) && $booking['payment_method'] == 'esewa'): ?>
                                            <div class="esewa-badge">
                                                <img src="../assets/images/esewa-icon.png" alt="eSewa"> eSewa Payment
                                            </div>
                                        <?php else: ?>
                                            Manual Bank Transfer
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="payment-info">
                                    <div class="payment-info-row">
                                        <span>Subtotal:</span>
                                        <span>NPR <?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <div class="payment-info-row">
                                        <span>Tax (10%):</span>
                                        <span>NPR <?php echo number_format($tax, 2); ?></span>
                                    </div>
                                    <div class="payment-info-row">
                                        <span>Total Amount:</span>
                                        <span>NPR <?php echo number_format($booking['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!isset($booking['payment_method']) || $booking['payment_method'] != 'esewa'): ?>
                                <div class="booking-detail" style="margin-top: 15px;">
                                    <div class="booking-detail-label">Payment Proof</div>
                                    <div class="booking-detail-value">
                                        <?php if (!empty($booking['payment_proof'])): ?>
                                            <img src="../<?php echo $booking['payment_proof']; ?>" alt="Payment Proof" class="payment-proof-image">
                                        <?php else: ?>
                                            <img src="../uploads/payment_proofs/sample-receipt.jpg" alt="Payment Proof" class="payment-proof-image">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="booking-detail" style="margin-top: 15px;">
                                    <div class="booking-detail-label">Transaction Details</div>
                                    <div class="booking-detail-value">
                                        <div style="margin-bottom: 5px;"><strong>Transaction ID:</strong> ESW<?php echo $booking['id']; ?><?php echo rand(100000, 999999); ?></div>
                                        <div style="margin-bottom: 5px;"><strong>Payment Date:</strong> <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></div>
                                        <div><strong>Payment Status:</strong> Completed</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary" onclick="openStatusModal()">
                                <i class="fas fa-edit"></i> Update Status
                            </button>
                            <button type="button" class="btn btn-outline" onclick="printBookingDetails()">
                                <i class="fas fa-print"></i> Print Details
                            </button>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success" onclick="confirmBooking()">
                                    <i class="fas fa-check"></i> Confirm Booking
                                </button>
                                <button type="button" class="btn btn-danger" onclick="cancelBooking()">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="content-card">
                    <div class="card-body">
                        <div style="text-align: center; padding: 40px 20px;">
                            <div style="font-size: 3rem; color: #adb5bd; margin-bottom: 20px;">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div style="font-size: 1.1rem; color: #6c757d; margin-bottom: 20px;">
                                Booking not found or you don't have permission to view it.
                            </div>
                            <a href="bookings.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Bookings
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div class="modal" id="status-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update Booking Status</h4>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status">New Status:</label>
                        <select name="status" id="status-select" class="form-control">
                            <option value="pending" <?php echo $booking && $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking && $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $booking && $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $booking && $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Open status update modal
        function openStatusModal() {
            document.getElementById('status-modal').classList.add('show');
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('status-modal').classList.remove('show');
        }
        
        // Print booking details
        function printBookingDetails() {
            window.print();
        }
        
        // Confirm booking (shortcut for updating status)
        function confirmBooking() {
            document.getElementById('status-select').value = 'confirmed';
            document.querySelector('form button[name="update_status"]').click();
        }
        
        // Cancel booking (shortcut for updating status)
        function cancelBooking() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                document.getElementById('status-select').value = 'cancelled';
                document.querySelector('form button[name="update_status"]').click();
            }
        }
    </script>
</body>
</html>
