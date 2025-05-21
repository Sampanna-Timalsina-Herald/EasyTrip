<?php
session_start();

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
  header('Location: ../login.php');
  exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle quick status update
if (isset($_POST['update_status'])) {
  $room_id = $_POST['room_id'];
  $status = $_POST['status'];
  
  $stmt = $conn->prepare("UPDATE hotel_rooms SET status = ? WHERE id = ? AND hotel_id IN (SELECT id FROM hotels WHERE owner_id = ?)");
  $stmt->bind_param("sii", $status, $room_id, $user_id);
  
  if ($stmt->execute()) {
    $success_message = "Room status updated successfully!";
  } else {
    $error_message = "Error updating room status: " . $conn->error;
  }
  $stmt->close();
}

// Handle quick price update
if (isset($_POST['update_price'])) {
  $room_id = $_POST['room_id'];
  $price = $_POST['price'];
  
  if ($price < 0) {
    $error_message = "Price cannot be negative.";
  } else {
    $stmt = $conn->prepare("UPDATE hotel_rooms SET price_per_night = ? WHERE id = ? AND hotel_id IN (SELECT id FROM hotels WHERE owner_id = ?)");
    $stmt->bind_param("dii", $price, $room_id, $user_id);
    
    if ($stmt->execute()) {
      $success_message = "Room price updated successfully!";
    } else {
      $error_message = "Error updating room price: " . $conn->error;
    }
    $stmt->close();
  }
}

// Get hotel filter
$hotel_filter = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

// Get owner's hotels
$hotels = [];
$stmt = $conn->prepare("SELECT id, name FROM hotels WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $hotels[] = $row;
  }
}
$stmt->close();

// Get rooms based on filter
$rooms = [];
$sql = "SELECT r.*, h.name as hotel_name 
        FROM hotel_rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        WHERE h.owner_id = ?";

if ($hotel_filter > 0) {
  $sql .= " AND h.id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $user_id, $hotel_filter);
} else {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
  }
}
$stmt->close();

// For demonstration, create sample data if database is empty
if (empty($hotels)) {
  $hotels = [
    ['id' => 1, 'name' => 'Pokhara Luxury Resort'],
    ['id' => 2, 'name' => 'Lakeside Hotel']
  ];
}

if (empty($rooms)) {
  $rooms = [
    [
      'id' => 1,
      'hotel_id' => 1,
      'hotel_name' => 'Pokhara Luxury Resort',
      'room_number' => '101',
      'room_type' => 'single',
      'price_per_night' => 149.99,
      'status' => 'available'
    ],
    [
      'id' => 2,
      'hotel_id' => 1,
      'hotel_name' => 'Pokhara Luxury Resort',
      'room_number' => '102',
      'room_type' => 'double',
      'price_per_night' => 199.99,
      'status' => 'occupied'
    ],
    [
      'id' => 3,
      'hotel_id' => 1,
      'hotel_name' => 'Pokhara Luxury Resort',
      'room_number' => '103',
      'room_type' => 'suite',
      'price_per_night' => 299.99,
      'status' => 'maintenance'
    ],
    [
      'id' => 4,
      'hotel_id' => 2,
      'hotel_name' => 'Lakeside Hotel',
      'room_number' => '101',
      'room_type' => 'single',
      'price_per_night' => 89.99,
      'status' => 'available'
    ],
    [
      'id' => 5,
      'hotel_id' => 2,
      'hotel_name' => 'Lakeside Hotel',
      'room_number' => '102',
      'room_type' => 'double',
      'price_per_night' => 129.99,
      'status' => 'available'
    ]
  ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Rooms - Hotel Owner Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .room-card {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
      overflow: hidden;
    }
    
    .room-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
    }
    
    .room-number {
      font-weight: 600;
      font-size: 1.2rem;
    }
    
    .room-status {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-available {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-occupied {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .status-maintenance {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .room-content {
      padding: 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .room-details {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .room-detail {
      display: flex;
      justify-content: space-between;
    }
    
    .detail-label {
      font-weight: 500;
      color: #666;
    }
    
    .room-actions {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .quick-actions {
      display: flex;
      gap: 10px;
    }
    
    .quick-actions form {
      flex: 1;
    }
    
    .quick-actions select,
    .quick-actions input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 10px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
    }
    
    .action-buttons a {
      flex: 1;
      text-align: center;
    }
    
    .filter-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .hotel-filter {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .hotel-filter select {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .add-room-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    
    .room-type-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
      text-transform: capitalize;
      background-color: #e9ecef;
    }
    
    .room-price {
      font-size: 1.2rem;
      font-weight: 600;
      color: #3a86ff;
    }
    
    @media (max-width: 768px) {
      .room-content {
        grid-template-columns: 1fr;
      }
      
      .quick-actions {
        flex-direction: column;
      }
      
      .action-buttons {
        flex-direction: column;
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
        <li><a href="hotels.php"><i class="fas fa-hotel"></i> My Hotels</a></li>
        <li><a href="add-hotel.php"><i class="fas fa-plus-circle"></i> Add Hotel</a></li>
        <li><a href="rooms.php" class="active"><i class="fas fa-door-open"></i> Manage Rooms</a></li>
        <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>Manage Rooms</h2>
        <p>Add, edit, and manage rooms for your hotels</p>
      </div>
      
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
          <?php echo $success_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <div class="filter-bar">
        <div class="hotel-filter">
          <label for="hotel-filter">Filter by Hotel:</label>
          <select id="hotel-filter" onchange="window.location.href='rooms.php?hotel_id='+this.value">
            <option value="0">All Hotels</option>
            <?php foreach ($hotels as $hotel): ?>
              <option value="<?php echo $hotel['id']; ?>" <?php echo $hotel_filter == $hotel['id'] ? 'selected' : ''; ?>>
                <?php echo $hotel['name']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <a href="add-room.php<?php echo $hotel_filter ? "?hotel_id=$hotel_filter" : ""; ?>" class="btn add-room-btn">
          <i class="fas fa-plus"></i> Add New Room
        </a>
      </div>
      
      <?php if (empty($rooms)): ?>
        <div class="alert alert-info">
          <p>No rooms found. <a href="add-room.php">Add your first room</a>.</p>
        </div>
      <?php else: ?>
        <div class="rooms-list">
          <?php foreach ($rooms as $room): ?>
            <div class="room-card">
              <div class="room-header">
                <div class="room-number">
                  Room <?php echo $room['room_number']; ?>
                  <span class="room-type-badge"><?php echo ucfirst($room['room_type']); ?></span>
                </div>
                <div class="room-status status-<?php echo $room['status']; ?>">
                  <?php echo ucfirst($room['status']); ?>
                </div>
              </div>
              <div class="room-content">
                <div class="room-details">
                  <div class="room-detail">
                    <span class="detail-label">Hotel:</span>
                    <span><?php echo $room['hotel_name']; ?></span>
                  </div>
                  <div class="room-detail">
                    <span class="detail-label">Room Type:</span>
                    <span><?php echo ucfirst($room['room_type']); ?></span>
                  </div>
                  <div class="room-detail">
                    <span class="detail-label">Price per Night:</span>
                    <span class="room-price">$<?php echo number_format($room['price_per_night'], 2); ?></span>
                  </div>
                  <div class="room-detail">
                    <span class="detail-label">Status:</span>
                    <span><?php echo ucfirst($room['status']); ?></span>
                  </div>
                </div>
                <div class="room-actions">
                  <div class="quick-actions">
                    <form action="" method="POST">
                      <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                      <label for="status-<?php echo $room['id']; ?>">Quick Status Update:</label>
                      <select id="status-<?php echo $room['id']; ?>" name="status">
                        <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo $room['status'] == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $room['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                      </select>
                      <button type="submit" name="update_status" class="btn btn-sm">Update Status</button>
                    </form>
                    <form action="" method="POST">
                      <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                      <label for="price-<?php echo $room['id']; ?>">Quick Price Update:</label>
                      <input type="number" id="price-<?php echo $room['id']; ?>" name="price" value="<?php echo $room['price_per_night']; ?>" step="0.01" min="0">
                      <button type="submit" name="update_price" class="btn btn-sm">Update Price</button>
                    </form>
                  </div>
                  <div class="action-buttons">
                    <a href="edit-room.php?id=<?php echo $room['id']; ?>" class="btn">
                      <i class="fas fa-edit"></i> Edit Room
                    </a>
                    <a href="delete-room.php?id=<?php echo $room['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.')">
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

