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

// Get hotel_id from URL if provided
$selected_hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

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

// For demonstration, create sample data if database is empty
if (empty($hotels)) {
  $hotels = [
    ['id' => 1, 'name' => 'Pokhara Luxury Resort'],
    ['id' => 2, 'name' => 'Lakeside Hotel']
  ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $hotel_id = $_POST['hotel_id'];
  $room_number = trim($_POST['room_number']);
  $room_type = $_POST['room_type'];
  $price_per_night = floatval($_POST['price_per_night']);
  $status = $_POST['status'];
  $description = trim($_POST['description']);
  $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
  
  // Validate inputs
  if (empty($room_number)) {
    $error_message = 'Room number is required.';
  } elseif ($price_per_night <= 0) {
    $error_message = 'Price per night must be greater than zero.';
  } else {
    // Check if hotel belongs to the owner
    $stmt = $conn->prepare("SELECT id FROM hotels WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $hotel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
      $error_message = 'You do not have permission to add rooms to this hotel.';
    } else {
      // Check if room number already exists for this hotel
      $stmt = $conn->prepare("SELECT id FROM hotel_rooms WHERE hotel_id = ? AND room_number = ?");
      $stmt->bind_param("is", $hotel_id, $room_number);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $error_message = 'A room with this number already exists for this hotel.';
      } else {
        // Insert new room
        $stmt = $conn->prepare("INSERT INTO hotel_rooms (hotel_id, room_number, room_type, price_per_night, status, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdss", $hotel_id, $room_number, $room_type, $price_per_night, $status, $description);
        
        if ($stmt->execute()) {
          $room_id = $stmt->insert_id;
          
          // Insert room amenities if any
          if (!empty($amenities)) {
            $amenities_stmt = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_name) VALUES (?, ?)");
            
            foreach ($amenities as $amenity) {
              $amenities_stmt->bind_param("is", $room_id, $amenity);
              $amenities_stmt->execute();
            }
            
            $amenities_stmt->close();
          }
          
          $success_message = 'Room added successfully!';
          
          // Clear form data after successful submission
          $room_number = '';
          $room_type = 'single';
          $price_per_night = '';
          $status = 'available';
          $description = '';
          $amenities = [];
        } else {
          $error_message = 'Error adding room: ' . $conn->error;
        }
      }
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Room - Hotel Owner Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .form-card {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 25px;
      margin-bottom: 30px;
    }
    
    .form-section {
      margin-bottom: 25px;
    }
    
    .form-section h3 {
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
      font-size: 1.2rem;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;
    }
    
    .form-control:focus {
      border-color: #3a86ff;
      outline: none;
    }
    
    textarea.form-control {
      min-height: 100px;
      resize: vertical;
    }
    
    .required {
      color: #dc3545;
    }
    
    .amenities-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 10px;
    }
    
    .amenity-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .amenity-item input[type="checkbox"] {
      width: 16px;
      height: 16px;
    }
    
    .form-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }
    
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
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
        <h2>Add New Room</h2>
        <p>Create a new room for one of your hotels</p>
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
      
      <div class="form-card">
        <form action="" method="POST">
          <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="hotel_id">Select Hotel <span class="required">*</span></label>
                <select id="hotel_id" name="hotel_id" class="form-control" required>
                  <option value="">-- Select Hotel --</option>
                  <?php foreach ($hotels as $hotel): ?>
                    <option value="<?php echo $hotel['id']; ?>" <?php echo $selected_hotel_id == $hotel['id'] ? 'selected' : ''; ?>>
                      <?php echo $hotel['name']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="room_number">Room Number <span class="required">*</span></label>
                <input type="text" id="room_number" name="room_number" class="form-control" value="<?php echo isset($room_number) ? $room_number : ''; ?>" required>
                <small>Must be unique for this hotel (e.g., 101, 102, A1, etc.)</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="room_type">Room Type <span class="required">*</span></label>
                <select id="room_type" name="room_type" class="form-control" required>
                  <option value="single" <?php echo (isset($room_type) && $room_type == 'single') ? 'selected' : ''; ?>>Single</option>
                  <option value="double" <?php echo (isset($room_type) && $room_type == 'double') ? 'selected' : ''; ?>>Double</option>
                  <option value="twin" <?php echo (isset($room_type) && $room_type == 'twin') ? 'selected' : ''; ?>>Twin</option>
                  <option value="suite" <?php echo (isset($room_type) && $room_type == 'suite') ? 'selected' : ''; ?>>Suite</option>
                  <option value="family" <?php echo (isset($room_type) && $room_type == 'family') ? 'selected' : ''; ?>>Family</option>
                  <option value="deluxe" <?php echo (isset($room_type) && $room_type == 'deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                </select>
              </div>
              <div class="form-group">
                <label for="price_per_night">Price per Night ($) <span class="required">*</span></label>
                <input type="number" id="price_per_night" name="price_per_night" class="form-control" value="<?php echo isset($price_per_night) ? $price_per_night : ''; ?>" step="0.01" min="0" required>
              </div>
            </div>
            <div class="form-group">
              <label for="status">Room Status <span class="required">*</span></label>
              <select id="status" name="status" class="form-control" required>
                <option value="available" <?php echo (isset($status) && $status == 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="occupied" <?php echo (isset($status) && $status == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                <option value="maintenance" <?php echo (isset($status) && $status == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
              </select>
            </div>
          </div>
          
          <div class="form-section">
            <h3>Room Details</h3>
            <div class="form-group">
              <label for="description">Room Description</label>
              <textarea id="description" name="description" class="form-control"><?php echo isset($description) ? $description : ''; ?></textarea>
              <small>Describe the room features, view, size, etc.</small>
            </div>
            
            <div class="form-group">
              <label>Room Amenities</label>
              <div class="amenities-list">
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-wifi" name="amenities[]" value="wifi" <?php echo (isset($amenities) && in_array('wifi', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-wifi">WiFi</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-ac" name="amenities[]" value="air_conditioning" <?php echo (isset($amenities) && in_array('air_conditioning', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-ac">Air Conditioning</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-tv" name="amenities[]" value="tv" <?php echo (isset($amenities) && in_array('tv', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-tv">TV</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-fridge" name="amenities[]" value="refrigerator" <?php echo (isset($amenities) && in_array('refrigerator', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-fridge">Refrigerator</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-balcony" name="amenities[]" value="balcony" <?php echo (isset($amenities) && in_array('balcony', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-balcony">Balcony</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-private-bathroom" name="amenities[]" value="private_bathroom" <?php echo (isset($amenities) && in_array('private_bathroom', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-private-bathroom">Private Bathroom</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-hot-water" name="amenities[]" value="hot_water" <?php echo (isset($amenities) && in_array('hot_water', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-hot-water">Hot Water</label>
                </div>
                <div class="amenity-item">
                  <input type="checkbox" id="amenity-mountain-view" name="amenities[]" value="mountain_view" <?php echo (isset($amenities) && in_array('mountain_view', $amenities)) ? 'checked' : ''; ?>>
                  <label for="amenity-mountain-view">Mountain View</label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <a href="rooms.php<?php echo $selected_hotel_id ? "?hotel_id=$selected_hotel_id" : ""; ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn">Add Room</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="../assets/js/admin.js"></script>
</body>
</html>

