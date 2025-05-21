<?php
session_start();

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get hotel owner's hotels
$hotels = [];
$result = $conn->query("SELECT * FROM hotels WHERE owner_id = $user_id");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

// Get recent bookings for owner's hotels
$recent_bookings = [];
$hotel_ids = array_column($hotels, 'id');
$hotel_ids_str = implode(',', $hotel_ids);

if (!empty($hotel_ids)) {
    $result = $conn->query("SELECT b.*, u.name as user_name, h.name as hotel_name 
                           FROM bookings b 
                           LEFT JOIN users u ON b.user_id = u.id 
                           LEFT JOIN hotels h ON b.hotel_id = h.id 
                           WHERE b.hotel_id IN ($hotel_ids_str) 
                           ORDER BY b.booking_date DESC LIMIT 10");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recent_bookings[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Owner Dashboard - Travel Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'navbar.php'; ?>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Hotel Owner Dashboard</h2>
                <p>Welcome, <?php echo $_SESSION['user_name']; ?>! Manage your hotels and bookings here.</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo count($hotels); ?></h3>
                    <p>Total Hotels</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo array_sum(array_column($hotels, 'total_rooms')); ?></h3>
                    <p>Total Rooms</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo array_sum(array_column($hotels, 'available_rooms')); ?></h3>
                    <p>Available Rooms</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($recent_bookings); ?></h3>
                    <p>Recent Bookings</p>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-body">
                    <h3>Your Hotels</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hotel Name</th>
                                    <th>Location</th>
                                    <th>Price/Night</th>
                                    <th>Total Rooms</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hotels as $hotel): ?>
                                <tr>
                                    <td><?php echo $hotel['name']; ?></td>
                                    <td><?php echo $hotel['location']; ?></td>
                                    <td>$<?php echo $hotel['price_per_night']; ?></td>
                                    <td><?php echo $hotel['total_rooms']; ?></td>
                                    <td><?php echo $hotel['available_rooms']; ?></td>
                                    <td>
                                        <?php if ($hotel['status'] == 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit-hotel.php?id=<?php echo $hotel['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 15px;">
                        <a href="add-hotel.php" class="btn">Add New Hotel</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3>Recent Bookings</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Hotel</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo $booking['user_name']; ?></td>
                                    <td><?php echo $booking['hotel_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>$<?php echo $booking['total_amount']; ?></td>
                                    <td>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($booking['status'] == 'confirmed'): ?>
                                            <span class="badge badge-success">Confirmed</span>
                                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 15px;">
                        <a href="bookings.php" class="btn">View All Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
