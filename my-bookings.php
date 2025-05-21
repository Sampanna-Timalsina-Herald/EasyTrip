<?php
include 'config/database.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings
$bookings = [];
$sql = "SELECT b.*, h.name as hotel_name, h.location as hotel_location, 
       bs.name as bus_name, bs.departure_location, bs.arrival_location 
       FROM bookings b 
       LEFT JOIN hotels h ON b.hotel_id = h.id 
       LEFT JOIN buses bs ON b.bus_id = bs.id 
       WHERE b.user_id = ? 
       ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
$stmt->close();

// For demonstration, create sample data if database is empty
if (empty($bookings)) {
    $bookings = [
        [
            'id' => 1,
            'booking_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'hotel_name' => 'Luxury Hotel & Spa',
            'hotel_location' => 'New York',
            'bus_name' => null,
            'check_in_date' => date('Y-m-d', strtotime('+5 days')),
            'check_out_date' => date('Y-m-d', strtotime('+8 days')),
            'travel_date' => null,
            'guests' => 2,
            'passengers' => 0,
            'total_amount' => 599.97,
            'status' => 'confirmed'
        ],
        [
            'id' => 2,
            'booking_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'hotel_name' => null,
            'bus_name' => 'Express Deluxe',
            'departure_location' => 'New York',
            'arrival_location' => 'Boston',
            'check_in_date' => null,
            'check_out_date' => null,
            'travel_date' => date('Y-m-d', strtotime('+3 days')),
            'guests' => 0,
            'passengers' => 2,
            'total_amount' => 90.00,
            'status' => 'confirmed'
        ],
        [
            'id' => 3,
            'booking_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'hotel_name' => 'Budget Comfort Inn',
            'hotel_location' => 'Boston',
            'bus_name' => 'City Link',
            'departure_location' => 'New York',
            'arrival_location' => 'Chicago',
            'check_in_date' => date('Y-m-d', strtotime('+10 days')),
            'check_out_date' => date('Y-m-d', strtotime('+12 days')),
            'travel_date' => date('Y-m-d', strtotime('+10 days')),
            'guests' => 1,
            'passengers' => 1,
            'total_amount' => 159.98,
            'status' => 'pending'
        ]
    ];
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>My Bookings</h2>
            <p>View and manage your bookings</p>
        </div>
        
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                <p>You don't have any bookings yet. <a href="index.php">Start booking now</a>.</p>
            </div>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-id">
                                <h3>Booking #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                <span class="booking-date"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
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
                        
                        <div class="booking-details">
                            <?php if ($booking['hotel_name']): ?>
                                <div class="booking-service">
                                    <div class="service-icon">
                                        <i class="fas fa-hotel"></i>
                                    </div>
                                    <div class="service-details">
                                        <h4><?php echo $booking['hotel_name']; ?></h4>
                                        <p><?php echo $booking['hotel_location']; ?></p>
                                        <div class="service-dates">
                                            <span><i class="fas fa-calendar-alt"></i> Check-in: <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                                            <span><i class="fas fa-calendar-alt"></i> Check-out: <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                                        </div>
                                        <div class="service-guests">
                                            <span><i class="fas fa-user"></i> <?php echo $booking['guests']; ?> <?php echo $booking['guests'] > 1 ? 'Guests' : 'Guest'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['bus_name']): ?>
                                <div class="booking-service">
                                    <div class="service-icon">
                                        <i class="fas fa-bus"></i>
                                    </div>
                                    <div class="service-details">
                                        <h4><?php echo $booking['bus_name']; ?></h4>
                                        <p><?php echo $booking['departure_location']; ?> to <?php echo $booking['arrival_location']; ?></p>
                                        <div class="service-dates">
                                            <span><i class="fas fa-calendar-alt"></i> Travel Date: <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                                        </div>
                                        <div class="service-guests">
                                            <span><i class="fas fa-user"></i> <?php echo $booking['passengers']; ?> <?php echo $booking['passengers'] > 1 ? 'Passengers' : 'Passenger'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="booking-footer">
                            <div class="booking-price">
                                <span class="price-label">Total:</span>
                                <span class="price-amount">$<?php echo $booking['total_amount']; ?></span>
                            </div>
                            <div class="booking-actions">
                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn">View Details</a>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="cancel-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Additional styles for bookings page */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.booking-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.booking-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.booking-id h3 {
    margin: 0;
    font-size: 1.2rem;
}

.booking-date {
    font-size: 0.9rem;
    color: #666;
}

.booking-details {
    padding: 20px;
}

.booking-service {
    display: flex;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.booking-service:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.service-icon {
    font-size: 2rem;
    color: #3a86ff;
    margin-right: 15px;
    display: flex;
    align-items: center;
}

.service-details h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.service-details p {
    margin: 0 0 10px 0;
    color: #666;
}

.service-dates, .service-guests {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
}

.service-dates i, .service-guests i {
    margin-right: 5px;
    color: #3a86ff;
}

.booking-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eee;
}

.price-label {
    font-weight: 600;
    margin-right: 5px;
}

.price-amount {
    font-size: 1.2rem;
    font-weight: 700;
    color: #3a86ff;
}

.booking-actions {
    display: flex;
    gap: 10px;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid #3a86ff;
    color: #3a86ff;
}

.btn-outline:hover {
    background-color: #3a86ff;
    color: #fff;
}

@media (max-width: 768px) {
    .booking-header, .booking-footer {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .booking-status, .booking-actions {
        margin-top: 10px;
    }
    
    .booking-service {
        flex-direction: column;
    }
    
    .service-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
