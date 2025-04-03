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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $description = trim($_POST['description']);
  $departure_location = trim($_POST['departure_location']);
  $arrival_location = trim($_POST['arrival_location']);
  $departure_time = $_POST['departure_time'];
  $arrival_time = $_POST['arrival_time'];
  $price = floatval($_POST['price']);
  $total_seats = intval($_POST['total_seats']);
  $bus_type = $_POST['bus_type'];
  $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
  
  // Validate inputs
  if (empty($name) || empty($departure_location) || empty($arrival_location)) {
    $error_message = 'Please fill in all required fields.';
  } elseif ($price <= 0) {
    $error_message = 'Price must be greater than zero.';
  } elseif ($total_seats <= 0) {
    $error_message = 'Total seats must be greater than zero.';
  } else {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
      // Insert new bus
      $stmt = $conn->prepare("INSERT INTO buses (operator_id, name, description, departure_location, arrival_location, 
                             departure_time, arrival_time, price, total_seats, available_seats, bus_type, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
      
      // Set available seats equal to total seats initially
      $available_seats = $total_seats;
      
      $stmt->bind_param("issssssdiss", $user_id, $name, $description, $departure_location, $arrival_location, 
                       $departure_time, $arrival_time, $price, $total_seats, $available_seats, $bus_type);
      
      if ($stmt->execute()) {
        $bus_id = $stmt->insert_id;
        
        // Insert bus amenities
        if (!empty($amenities)) {
          $amenities_stmt = $conn->prepare("INSERT INTO bus_amenities (bus_id, name) VALUES (?, ?)");
          
          foreach ($amenities as $amenity) {
            $amenities_stmt->bind_param("is", $bus_id, $amenity);
            $amenities_stmt->execute();
          }
          
          $amenities_stmt->close();
        }
        
        // Create default seats
        for ($i = 1; $i <= $total_seats; $i++) {
          $seat_number = $i <= 9 ? "0$i" : "$i";
          $seat_type = 'regular';
          $status = 'available';
          
          // Make some seats premium based on position
          if ($i <= 5 || ($total_seats - $i) < 5) {
            $seat_type = 'premium';
          }
          
          // For sleeper buses, make some seats sleeper type
          if ($bus_type === 'sleeper' && $i % 3 === 0) {
            $seat_type = 'sleeper';
          }
          
          $seat_stmt = $conn->prepare("INSERT INTO bus_seats (bus_id, seat_number, seat_type, status) VALUES (?, ?, ?, ?)");
          $seat_stmt->bind_param("isss", $bus_id, $seat_number, $seat_type, $status);
          $seat_stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = 'Bus added successfully!';
        
        // Clear form data after successful submission
        $name = '';
        $description = '';
        $departure_location = '';
        $arrival_location = '';
        $departure_time = '';
        $arrival_time = '';
        $price = '';
        $total_seats = '';
        $bus_type = '';
        $amenities = [];
      } else {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = 'Error adding bus: ' . $conn->error;
      }
      
      $stmt->close();
    } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollback();
      $error_message = 'Error adding bus: ' . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Bus - Bus Operator Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .form-card {
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      padding: 30px;
      margin-bottom: 30px;
    }
    
    .form-section {
      margin-bottom: 30px;
    }
    
    .form-section h3 {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
      font-size: 1.3rem;
      color: #2c3e50;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 10px;
      font-weight: 500;
      color: #444;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .form-control:focus {
      border-color: #3a86ff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
    }
    
    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }
    
    .required {
      color: #dc3545;
      margin-left: 3px;
    }
    
    .amenities-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 10px;
    }
    
    .amenity-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px;
      border-radius: 8px;
      background-color: #f8f9fa;
      transition: background-color 0.2s;
    }
    
    .amenity-item:hover {
      background-color: #e9ecef;
    }
    
    .amenity-item input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #3a86ff;
    }
    
    .amenity-item label {
      margin-bottom: 0;
      cursor: pointer;
    }
    
    .form-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 40px;
    }
    
    .btn {
      padding: 12px 25px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      font-size: 1rem;
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
    
    .form-help {
      font-size: 0.9rem;
      color: #6c757d;
      margin-top: 5px;
    }
    
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
      }
      
      .form-actions {
        flex-direction: column-reverse;
        gap: 15px;
      }
      
      .btn {
        width: 100%;
        text-align: center;
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
        <li><a href="buses.php"><i class="fas fa-bus"></i> My Buses</a></li>
        <li><a href="add-bus.php" class="active"><i class="fas fa-plus-circle"></i> Add Bus</a></li>
        <li><a href="manage-seats.php"><i class="fas fa-chair"></i> Manage Seats</a></li>
        <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>Add New Bus</h2>
        <p>Create a new bus for your fleet</p>
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
      
      <div class="form-card">
        <form action="" method="POST">
          <div class="form-section">
            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="name">Bus Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                <div class="form-help">Enter a unique name for your bus (e.g., Deluxe Express, Tourist Coach)</div>
              </div>
              <div class="form-group">
                <label for="bus_type">Bus Type <span class="required">*</span></label>
                <select id="bus_type" name="bus_type" class="form-control" required>
                  <option value="regular" <?php echo (isset($bus_type) && $bus_type == 'regular') ? 'selected' : ''; ?>>Regular</option>
                  <option value="deluxe" <?php echo (isset($bus_type) && $bus_type == 'deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                  <option value="ac" <?php echo (isset($bus_type) && $bus_type == 'ac') ? 'selected' : ''; ?>>AC</option>
                  <option value="sleeper" <?php echo (isset($bus_type) && $bus_type == 'sleeper') ? 'selected' : ''; ?>>Sleeper</option>
                  <option value="tourist" <?php echo (isset($bus_type) && $bus_type == 'tourist') ? 'selected' : ''; ?>>Tourist</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="description">Bus Description</label>
              <textarea id="description" name="description" class="form-control"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
              <div class="form-help">Describe the bus features, comfort level, and any special amenities</div>
            </div>
          </div>
          
          <div class="form-section">
            <h3><i class="fas fa-route"></i> Route Information</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="departure_location">Departure Location <span class="required">*</span></label>
                <input type="text" id="departure_location" name="departure_location" class="form-control" value="<?php echo isset($departure_location) ? htmlspecialchars($departure_location) : ''; ?>" required>
              </div>
              <div class="form-group">
                <label for="arrival_location">Arrival Location <span class="required">*</span></label>
                <input type="text" id="arrival_location" name="arrival_location" class="form-control" value="<?php echo isset($arrival_location) ? htmlspecialchars($arrival_location) : ''; ?>" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="departure_time">Departure Time <span class="required">*</span></label>
                <input type="time" id="departure_time" name="departure_time" class="form-control" value="<?php echo isset($departure_time) ? $departure_time : ''; ?>" required>
              </div>
              <div class="form-group">
                <label for="arrival_time">Arrival Time <span class="required">*</span></label>
                <input type="time" id="arrival_time" name="arrival_time" class="form-control" value="<?php echo isset($arrival_time) ? $arrival_time : ''; ?>" required>
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <h3><i class="fas fa-chair"></i> Seating & Pricing</h3>
            <div class="form-row">
              <div class="form-group">
                <label for="total_seats">Total Seats <span class="required">*</span></label>
                <input type="number" id="total_seats" name="total_seats" class="form-control" value="<?php echo isset($total_seats) ? $total_seats : ''; ?>" min="1" required>
                <div class="form-help">Default seat numbers will be created automatically</div>
              </div>
              <div class="form-group">
                <label for="price">Price per Seat ($) <span class="required">*</span></label>
                <input type="number" id="price" name="price" class="form-control" value="<?php echo isset($price) ? $price : ''; ?>" step="0.01" min="0" required>
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <h3><i class="fas fa-list-ul"></i> Bus Amenities</h3>
            <p class="form-help">Select the amenities available on this bus</p>
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
                <input type="checkbox" id="amenity-charging" name="amenities[]" value="charging_ports" <?php echo (isset($amenities) && in_array('charging_ports', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-charging">Charging Ports</label>
              </div>
              <div class="amenity-item">
                <input type="checkbox" id="amenity-tv" name="amenities[]" value="tv" <?php echo (isset($amenities) && in_array('tv', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-tv">TV/Entertainment</label>
              </div>
              <div class="amenity-item">
                <input type="checkbox" id="amenity-refreshments" name="amenities[]" value="refreshments" <?php echo (isset($amenities) && in_array('refreshments', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-refreshments">Refreshments</label>
              </div>
              <div class="amenity-item">
                <input type="checkbox" id="amenity-reclining" name="amenities[]" value="reclining_seats" <?php echo (isset($amenities) && in_array('reclining_seats', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-reclining">Reclining Seats</label>
              </div>
              <div class="amenity-item">
                <input type="checkbox" id="amenity-restroom" name="amenities[]" value="restroom" <?php echo (isset($amenities) && in_array('restroom', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-restroom">Restroom</label>
              </div>
              <div class="amenity-item">
                <input type="checkbox" id="amenity-blankets" name="amenities[]" value="blankets" <?php echo (isset($amenities) && in_array('blankets', $amenities)) ? 'checked' : ''; ?>>
                <label for="amenity-blankets">Blankets</label>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <a href="buses.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Bus</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="../assets/js/admin.js"></script>
</body>
</html>