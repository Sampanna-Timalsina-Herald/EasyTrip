<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

// Get all bookings
$bookings = [];
$result = $conn->query("SELECT b.*, u.name as user_name, h.name as hotel_name, bs.name as bus_name 
                       FROM bookings b 
                       LEFT JOIN users u ON b.user_id = u.id 
                       LEFT JOIN hotels h ON b.hotel_id = h.id 
                       LEFT JOIN buses bs ON b.bus_id = bs.id 
                       ORDER BY b.booking_date DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Handle actions like confirming payment or deleting booking
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    if ($_GET['action'] == 'confirm') {
        $conn->query("UPDATE bookings SET status = 'confirmed' WHERE id = $booking_id");
    } elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM bookings WHERE id = $booking_id");
    }
    header("Location: bookings.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
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
                <li><a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="hotels.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="buses.php"><i class="fas fa-bus"></i> Buses</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-chart-bar"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Manage Bookings</h2>
                <p>Below is the list of all bookings. You can confirm payment or delete a booking.</p>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3>All Bookings</h3>
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
                                <?php foreach ($bookings as $booking): ?>
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
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <a href="bookings.php?action=confirm&id=<?php echo $booking['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background-color: green; color: white;">Confirm Payment</a>
                                        <?php endif; ?>
                                        <a href="bookings.php?action=delete&id=<?php echo $booking['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background-color: red; color: white;">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>