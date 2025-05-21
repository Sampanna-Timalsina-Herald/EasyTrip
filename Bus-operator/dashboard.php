<?php
session_start();

// Check if user is logged in and is a bus operator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bus_operator') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get bus operator's buses
$buses = [];
$result = $conn->query("SELECT * FROM buses WHERE operator_id = $user_id");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buses[] = $row;
    }
}


// Get recent bookings for operator's buses
$recent_bookings = [];
$bus_ids = array_column($buses, 'id');
$bus_ids_str = implode(',', $bus_ids);

if (!empty($bus_ids)) {
    $result = $conn->query("SELECT b.*, u.name as user_name, bs.name as bus_name 
                           FROM bookings b 
                           LEFT JOIN users u ON b.user_id = u.id 
                           LEFT JOIN buses bs ON b.bus_id = bs.id 
                           WHERE b.bus_id IN ($bus_ids_str) 
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
    <title>Bus Operator Dashboard - Travel Booking System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-sidebar">
            <div style="padding: 20px; text-align: center;">
                <h2 style="color: #fff; margin-bottom: 0;">Bus Dashboard</h2>
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="buses.php"><i class="fas fa-bus"></i> My Buses</a></li>
                <li><a href="add-bus.php"><i class="fas fa-plus-circle"></i> Add Bus</a></li>
                <li><a href="seats.php"><i class="fas fa-chair"></i> Manage Seats</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Bus Operator Dashboard</h2>
                <p>Welcome, <?php echo $_SESSION['user_name']; ?>! Manage your buses and bookings here.</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo count($buses); ?></h3>
                    <p>Total Buses</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo array_sum(array_column($buses, 'total_seats')); ?></h3>
                    <p>Total Seats</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo array_sum(array_column($buses, 'available_seats')); ?></h3>
                    <p>Available Seats</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($recent_bookings); ?></h3>
                    <p>Recent Bookings</p>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-body">
                    <h3>Your Buses</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bus Name</th>
                                    <th>Route</th>
                                    <th>Departure</th>
                                    <th>Arrival</th>
                                    <th>Price</th>
                                    <th>Total Seats</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses as $bus): ?>
                                <tr>
                                    <td><?php echo $bus['name']; ?></td>
                                    <td><?php echo $bus['departure_location'] . ' to ' . $bus['arrival_location']; ?></td>
                                    <td><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></td>
                                    <td>$<?php echo $bus['price']; ?></td>
                                    <td><?php echo $bus['total_seats']; ?></td>
                                    <td><?php echo $bus['available_seats']; ?></td>
                                    <td>
                                        <?php if ($bus['status'] == 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 15px;">
                        <a href="add-bus.php" class="btn">Add New Bus</a>
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
                                    <th>Passenger</th>
                                    <th>Bus</th>
                                    <th>Travel Date</th>
                                    <th>Passengers</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo $booking['user_name']; ?></td>
                                    <td><?php echo $booking['bus_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td>
                                    <td><?php echo $booking['passengers']; ?></td>
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
