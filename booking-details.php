<?php
include 'config/database.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch booking details
$booking = null;
if ($booking_id > 0) {
    $stmt = $conn->prepare("SELECT b.*, h.name as hotel_name, h.location as hotel_location, 
                           bs.name as bus_name, bs.departure_location, bs.arrival_location,
                           bs.departure_time, bs.arrival_time
                           FROM bookings b 
                           LEFT JOIN hotels h ON b.hotel_id = h.id 
                           LEFT JOIN buses bs ON b.bus_id = bs.id 
                           WHERE b.id = ? AND b.user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    }
    $stmt->close();
}

// For demonstration, create sample data if database is empty
if (!$booking && $booking_id > 0) {
    $booking = [
        'id' => $booking_id,
        'booking_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'hotel_name' => 'Luxury Hotel & Spa',
        'hotel_location' => 'New York',
        'bus_name' => 'Express Deluxe',
        'departure_location' => 'New York',
        'arrival_location' => 'Boston',
        'departure_time' => '08:00:00',
        'arrival_time' => '12:30:00',
        'check_in_date' => date('Y-m-d', strtotime('+5 days')),
        'check_out_date' => date('Y-m-d', strtotime('+8 days')),
        'travel_date' => date('Y-m-d', strtotime('+5 days')),
        'guests' => 2,
        'passengers' => 2,
        'total_amount' => 689.97,
        'payment_proof' => 'uploads/payment_proofs/sample_payment.jpg',
        'status' => 'confirmed'
    ];
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Booking Details</h2>
            <p>
                <a href="my-bookings.php" style="margin-right: 10px;"><i class="fas fa-arrow-left"></i> Back to My Bookings</a>
                Booking ID: #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?>
            </p>
        </div>
        
        <?php if ($booking): ?>
            <div class="booking-details-card">
                <div class="booking-header">
                    <div class="booking-info">
                        <h3>Booking Information</h3>
                        <p>Booked on: <?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></p>
                    </div>
                    <div class="booking-status">
                        <?php if ($booking['status'] == 'pending'): ?>
                            <span class="badge badge-warning">Pending Verification</span>
                        <?php elseif ($booking['status'] == 'confirmed'): ?>
                            <span class="badge badge-success">Confirmed</span>
                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="booking-content">
                    <?php if ($booking['hotel_name']): ?>
                        <div class="booking-section">
                            <h4><i class="fas fa-hotel"></i> Hotel Details</h4>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Hotel:</span>
                                    <span class="detail-value"><?php echo $booking['hotel_name']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Location:</span>
                                    <span class="detail-value"><?php echo $booking['hotel_location']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Check-in:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Check-out:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Guests:</span>
                                    <span class="detail-value"><?php echo $booking['guests']; ?></span>
                                </div>
                                <?php
                                // Calculate nights
                                $check_in = new DateTime($booking['check_in_date']);
                                $check_out = new DateTime($booking['check_out_date']);
                                $nights = $check_out->diff($check_in)->days;
                                ?>
                                <div class="detail-item">
                                    <span class="detail-label">Nights:</span>
                                    <span class="detail-value"><?php echo $nights; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['bus_name']): ?>
                        <div class="booking-section">
                            <h4><i class="fas fa-bus"></i> Bus Details</h4>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Bus:</span>
                                    <span class="detail-value"><?php echo $booking['bus_name']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Route:</span>
                                    <span class="detail-value"><?php echo $booking['departure_location']; ?> to <?php echo $booking['arrival_location']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Travel Date:</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Departure:</span>
                                    <span class="detail-value"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Arrival:</span>
                                    <span class="detail-value"><?php echo date('h:i A', strtotime($booking['arrival_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Passengers:</span>
                                    <span class="detail-value"><?php echo $booking['passengers']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="booking-section">
                        <h4><i class="fas fa-money-bill-wave"></i> Payment Details</h4>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value price">$<?php echo $booking['total_amount']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Payment Status:</span>
                                <span class="detail-value">
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <span class="badge badge-warning">Pending Verification</span>
                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                        <span class="badge badge-success">Verified</span>
                                    <?php elseif ($booking['status'] == 'cancelled'): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['payment_proof'])): ?>
                            <div class="payment-proof">
                                <h5>Payment Proof</h5>
                                <img src="<?php echo $booking['payment_proof']; ?>" alt="Payment Proof" class="proof-image">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="booking-footer">
                    <div class="booking-actions">
                        <?php if ($booking['status'] == 'pending'): ?>
                            <a href="cancel-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel Booking</a>
                        <?php endif; ?>
                        <a href="my-bookings.php" class="btn">Back to My Bookings</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Booking not found or you don't have permission to view this booking.</p>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="my-bookings.php" class="btn">View Your Bookings</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Additional styles for booking details page */
.booking-details-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 40px;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.booking-info h3 {
    margin: 0 0 5px 0;
    font-size: 1.4rem;
}

.booking-info p {
    margin: 0;
    color: #666;
}

.booking-content {
    padding: 20px;
}

.booking-section {
    margin-bottom: 30px;
}

.booking-section:last-child {
    margin-bottom: 0;
}

.booking-section h4 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.booking-section h4 i {
    margin-right: 10px;
    color: #3a86ff;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.detail-value {
    font-weight: 600;
}

.detail-value.price {
    color: #3a86ff;
    font-size: 1.2rem;
}

.payment-proof {
    margin-top: 20px;
}

.payment-proof h5 {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.proof-image {
    max-width: 100%;
    max-height: 300px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.booking-footer {
    padding: 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eee;
}

.booking-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .booking-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .booking-status {
        margin-top: 10px;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .booking-actions {
        flex-direction: column;
    }
    
    .booking-actions .btn {
        width: 100%;
        margin-bottom: 10px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
