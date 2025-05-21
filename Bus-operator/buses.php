<?php
session_start();

// Check if user is logged in and is a bus operator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'bus_operator') {
  header('Location: ../login.php');
  exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle quick status update
if (isset($_POST['update_status'])) {
  $bus_id = $_POST['bus_id'];
  $status = $_POST['status'];
  
  $stmt = $conn->prepare("UPDATE buses SET status = ? WHERE id = ? AND operator_id = ?");
  $stmt->bind_param("sii", $status, $bus_id, $user_id);
  
  if ($stmt->execute()) {
    $success_message = "Bus status updated successfully!";
  } else {
    $error_message = "Error updating bus status: " . $conn->error;
  }
  $stmt->close();
}

// Handle quick price update
if (isset($_POST['update_price'])) {
  $bus_id = $_POST['bus_id'];
  $price = $_POST['price'];
  
  if ($price < 0) {
    $error_message = "Price cannot be negative.";
  } else {
    $stmt = $conn->prepare("UPDATE buses SET price = ? WHERE id = ? AND operator_id = ?");
    $stmt->bind_param("dii", $price, $bus_id, $user_id);
    
    if ($stmt->execute()) {
      $success_message = "Bus price updated successfully!";
    } else {
      $error_message = "Error updating bus price: " . $conn->error;
    }
    $stmt->close();
  }
}

// Handle quick available seats update
if (isset($_POST['update_seats'])) {
  $bus_id = $_POST['bus_id'];
  $available_seats = $_POST['available_seats'];
  $total_seats = $_POST['total_seats'];
  
  if ($available_seats < 0) {
    $error_message = "Available seats cannot be negative.";
  } elseif ($available_seats > $total_seats) {
    $error_message = "Available seats cannot exceed total seats.";
  } else {
    $stmt = $conn->prepare("UPDATE buses SET available_seats = ? WHERE id = ? AND operator_id = ?");
    $stmt->bind_param("iii", $available_seats, $bus_id, $user_id);
    
    if ($stmt->execute()) {
      $success_message = "Available seats updated successfully!";
    } else {
      $error_message = "Error updating available seats: " . $conn->error;
    }
    $stmt->close();
  }
}

// Get route filter
$route_filter = isset($_GET['route']) ? $_GET['route'] : '';

// Get operator's buses
$buses = [];
$sql = "SELECT * FROM buses WHERE operator_id = ?";

if (!empty($route_filter)) {
  $sql .= " AND (departure_location LIKE ? OR arrival_location LIKE ?)";
  $route_param = "%$route_filter%";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iss", $user_id, $route_param, $route_param);
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
  }
}
$stmt->close();

// Get unique routes for filter dropdown
$routes = [];
$stmt = $conn->prepare("SELECT DISTINCT departure_location, arrival_location FROM buses WHERE operator_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $routes[] = $row['departure_location'] . ' to ' . $row['arrival_location'];
  }
}
$stmt->close();

// For demonstration, create sample data if database is empty
if (empty($buses)) {
  $buses = [
    [
      'id' => 1,
      'name' => 'Deluxe Express',
      'description' => 'Comfortable bus service with AC and reclining seats.',
      'departure_location' => 'Kathmandu',
      'arrival_location' => 'Pokhara',
      'departure_time' => '07:00:00',
      'arrival_time' => '13:00:00',
      'image_url' => '/placeholder.svg?height=200&width=300',
      'price' => 25.00,
      'total_seats' => 40,
      'available_seats' => 25,
      'status' => 'active'
    ],
    [
      'id' => 2,
      'name' => 'Tourist Coach',
      'description' => 'Premium bus service with extra legroom and refreshments.',
      'departure_location' => 'Kathmandu',
      'arrival_location' => 'Pokhara',
      'departure_time' => '08:30:00',
      'arrival_time' => '14:30:00',
      'image_url' => '/placeholder.svg?height=200&width=300',
      'price' => 35.00,
      'total_seats' => 35,
      'available_seats' => 20,
      'status' => 'active'
    ],
    [
      'id' => 3,
      'name' => 'Night Rider',
      'description' => 'Overnight bus service with semi-sleeper seats.',
      'departure_location' => 'Pokhara',
      'arrival_location' => 'Kathmandu',
      'departure_time' => '20:00:00',
      'arrival_time' => '04:00:00',
      'image_url' => '/placeholder.svg?height=200&width=300',
      'price' => 30.00,
      'total_seats' => 38,
      'available_seats' => 22,
      'status' => 'active'
    ]
  ];
  
  $routes = ['Kathmandu to Pokhara', 'Pokhara to Kathmandu'];
}

// Check for success or error messages in session
if (isset($_SESSION['success_message'])) {
  $success_message = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
  $error_message = $_SESSION['error_message'];
  unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Buses - Bus Operator Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .bus-card {
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
      overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .bus-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }
    
    .bus-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
    }
    
    .bus-name {
      font-weight: 600;
      font-size: 1.3rem;
      color: #2c3e50;
    }
    
    .bus-status {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .status-active {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-inactive {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .status-maintenance {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .bus-content {
      padding: 20px;
    }
    
    .bus-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 20px;
    }
    
    .bus-route {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      background-color: #f0f7ff;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 10px;
    }
    
    .route-icon {
      font-size: 1.5rem;
      color: #3a86ff;
      margin-right: 15px;
    }
    
    .route-details {
      flex: 1;
    }
    
    .route-locations {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 5px;
    }
    
    .route-times {
      display: flex;
      color: #666;
    }
    
    .route-times span {
      margin-right: 15px;
    }
    
    .route-times i {
      margin-right: 5px;
      color: #3a86ff;
    }
    
    .bus-details {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .bus-detail {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 8px;
      border-bottom: 1px dashed #eee;
    }
    
    .detail-label {
      font-weight: 500;
      color: #666;
      display: flex;
      align-items: center;
    }
    
    .detail-label i {
      margin-right: 8px;
      color: #3a86ff;
    }
    
    .detail-value {
      font-weight: 600;
    }
    
    .bus-price {
      font-size: 1.3rem;
      color: #3a86ff;
    }
    
    .bus-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-top: 20px;
    }
    
    .quick-actions {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .quick-form {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
    }
    
    .quick-form label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #555;
    }
    
    .quick-form select,
    .quick-form input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      margin-bottom: 10px;
      font-size: 0.95rem;
    }
    
    .quick-form select:focus,
    .quick-form input:focus {
      border-color: #3a86ff;
      outline: none;
      box-shadow: 0 0 0 2px rgba(58, 134, 255, 0.1);
    }
    
    .action-buttons {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    
    .action-buttons a {
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .btn-primary {
      background-color: #3a86ff;
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #2a75e6;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
    }
    
    .btn-danger {
      background-color: #dc3545;
      color: white;
    }
    
    .btn-danger:hover {
      background-color: #c82333;
    }
    
    .btn-sm {
      padding: 8px 12px;
      font-size: 0.9rem;
    }
    
    .filter-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      background-color: #fff;
      padding: 15px 20px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .route-filter {
      display: flex;
      gap: 15px;
      align-items: center;
    }
    
    .route-filter select {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      min-width: 200px;
    }
    
    .add-bus-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background-color: #3a86ff;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      transition: background-color 0.2s;
    }
    
    .add-bus-btn:hover {
      background-color: #2a75e6;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
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
    
    @media (max-width: 992px) {
      .bus-info {
        grid-template-columns: 1fr;
      }
      
      .bus-actions {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 768px) {
      .filter-bar {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
      }
      
      .route-filter {
        flex-direction: column;
        align-items: stretch;
      }
      
      .action-buttons {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <div class="dashboard-sidebar">
      <div style="padding: 20px; text-align: center;">
        <h2 style="color: #fff; margin-bottom: 0;">Bus Dashboard</h2>
      </div>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="buses.php" class="active"><i class="fas fa-bus"></i> My Buses</a></li>
        <li><a href="add-bus.php"><i class="fas fa-plus-circle"></i> Add Bus</a></li>
        <li><a href="manage-seats.php"><i class="fas fa-chair"></i> Manage Seats</a></li>
        <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>My Buses</h2>
        <p>View and manage your bus fleet</p>
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
      
      <div class="filter-bar">
        <div class="route-filter">
          <label for="route-filter"><i class="fas fa-filter"></i> Filter by Route:</label>
          <select id="route-filter" onchange="window.location.href='buses.php?route='+this.value">
            <option value="">All Routes</option>
            <?php foreach ($routes as $route): ?>
              <?php 
                $selected = ($route_filter && (strpos($route, $route_filter) !== false)) ? 'selected' : '';
              ?>
              <option value="<?php echo htmlspecialchars($route); ?>" <?php echo $selected; ?>>
                <?php echo $route; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <a href="add-bus.php" class="add-bus-btn">
          <i class="fas fa-plus"></i> Add New Bus
        </a>
      </div>
      
      <?php if (empty($buses)): ?>
        <div class="alert alert-info">
          <p>No buses found. <a href="add-bus.php">Add your first bus</a>.</p>
        </div>
      <?php else: ?>
        <div class="buses-list">
          <?php foreach ($buses as $bus): ?>
            <div class="bus-card">
              <div class="bus-header">
                <div class="bus-name">
                  <?php echo $bus['name']; ?>
                </div>
                <div class="bus-status status-<?php echo $bus['status']; ?>">
                  <?php echo ucfirst($bus['status']); ?>
                </div>
              </div>
              <div class="bus-content">
                <div class="bus-info">
                  <div class="bus-route">
                    <div class="route-icon">
                      <i class="fas fa-route"></i>
                    </div>
                    <div class="route-details">
                      <div class="route-locations">
                        <?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?>
                      </div>
                      <div class="route-times">
                        <span><i class="fas fa-clock"></i> Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?></span>
                        <span><i class="fas fa-clock"></i> Arrival: <?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="bus-details">
                    <div class="bus-detail">
                      <span class="detail-label"><i class="fas fa-tag"></i> Price per Seat:</span>
                      <span class="detail-value bus-price">$<?php echo number_format($bus['price'], 2); ?></span>
                    </div>
                    <div class="bus-detail">
                      <span class="detail-label"><i class="fas fa-chair"></i> Total Seats:</span>
                      <span class="detail-value"><?php echo $bus['total_seats']; ?></span>
                    </div>
                    <div class="bus-detail">
                      <span class="detail-label"><i class="fas fa-check-circle"></i> Available Seats:</span>
                      <span class="detail-value"><?php echo $bus['available_seats']; ?></span>
                    </div>
                    <div class="bus-detail">
                      <span class="detail-label"><i class="fas fa-info-circle"></i> Status:</span>
                      <span class="detail-value"><?php echo ucfirst($bus['status']); ?></span>
                    </div>
                  </div>
                  
                  <div class="bus-description">
                    <p><?php echo $bus['description']; ?></p>
                  </div>
                </div>
                
                <div class="bus-actions">
                  <div class="quick-actions">
                    <form action="" method="POST" class="quick-form">
                      <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                      <label for="status-<?php echo $bus['id']; ?>"><i class="fas fa-toggle-on"></i> Quick Status Update:</label>
                      <select id="status-<?php echo $bus['id']; ?>" name="status">
                        <option value="active" <?php echo $bus['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $bus['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="maintenance" <?php echo $bus['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                      </select>
                      <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update Status</button>
                    </form>
                    
                    <form action="" method="POST" class="quick-form">
                      <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                      <label for="price-<?php echo $bus['id']; ?>"><i class="fas fa-dollar-sign"></i> Quick Price Update:</label>
                      <input type="number" id="price-<?php echo $bus['id']; ?>" name="price" value="<?php echo $bus['price']; ?>" step="0.01" min="0">
                      <button type="submit" name="update_price" class="btn btn-primary btn-sm">Update Price</button>
                    </form>
                    
                    <form action="" method="POST" class="quick-form">
                      <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                      <input type="hidden" name="total_seats" value="<?php echo $bus['total_seats']; ?>">
                      <label for="seats-<?php echo $bus['id']; ?>"><i class="fas fa-chair"></i> Update Available Seats:</label>
                      <input type="number" id="seats-<?php echo $bus['id']; ?>" name="available_seats" value="<?php echo $bus['available_seats']; ?>" min="0" max="<?php echo $bus['total_seats']; ?>">
                      <button type="submit" name="update_seats" class="btn btn-primary btn-sm">Update Seats</button>
                    </form>
                  </div>
                  
                  <div class="action-buttons">
                    <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-secondary">
                      <i class="fas fa-edit"></i> Edit Bus
                    </a>
                    <a href="manage-seats.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-primary">
                      <i class="fas fa-chair"></i> Manage Seats
                    </a>
                    <a href="view-bookings.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-secondary">
                      <i class="fas fa-calendar-check"></i> View Bookings
                    </a>
                    <a href="delete-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this bus? This action cannot be undone.')">
                      <i class="fas fa-trash"></i> Delete
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <script src="../assets/js/admin.js"></script>
</body>
</html>
