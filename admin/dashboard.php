<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

// Get statistics
$total_bookings = 0;
$pending_bookings = 0;
$total_users = 0;
$total_revenue = 0;

// Get total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_bookings = $row['count'];
}

// Get pending bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $pending_bookings = $row['count'];
}

// Get total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_users = $row['count'];
}

// Get total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE status = 'confirmed'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_revenue = $row['total'] ? $row['total'] : 0;
}

// Get recent bookings
$recent_bookings = [];
$result = $conn->query("SELECT b.*, u.name as user_name, h.name as hotel_name, bs.name as bus_name 
                       FROM bookings b 
                       LEFT JOIN users u ON b.user_id = u.id 
                       LEFT JOIN hotels h ON b.hotel_id = h.id 
                       LEFT JOIN buses bs ON b.bus_id = bs.id 
                       ORDER BY b.booking_date DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Travel Booking System</title>
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="booking.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="hotels.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="buses.php"><i class="fas fa-bus"></i> Buses</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-chart-bar"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Dashboard</h2>
                <p>Welcome to the admin dashboard. Here's an overview of your system.</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $pending_bookings; ?></h3>
                    <p>Pending Verifications</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Registered Users</p>
                </div>
                <div class="stat-card">
                    <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-body">
                    <h3>Recent Bookings</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Hotel</th>
                                    <th>Bus</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo $booking['user_name']; ?></td>
                                    <td><?php echo $booking['hotel_name'] ? $booking['hotel_name'] : 'N/A'; ?></td>
                                    <td><?php echo $booking['bus_name'] ? $booking['bus_name'] : 'N/A'; ?></td>
                                    <td>$<?php echo $booking['total_amount']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($booking['status'] == 'confirmed'): ?>
                                            <span class="badge badge-success">Confirmed</span>
                                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 15px;">
                        <a href="booking.php" class="btn">View All Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

