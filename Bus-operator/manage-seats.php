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

// Get bus_id from URL
$bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;

// Get operator's buses for dropdown
$buses = [];
$stmt = $conn->prepare("SELECT id, name, departure_location, arrival_location FROM buses WHERE operator_id = ? ORDER BY name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
  }
}
$stmt->close();

// If no bus_id is provided but buses exist, use the first one
if ($bus_id === 0 && !empty($buses)) {
  $bus_id = $buses[0]['id'];
}

// Get current bus details
$current_bus = null;
if ($bus_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM buses WHERE id = ? AND operator_id = ?");
  $stmt->bind_param("ii", $bus_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $current_bus = $result->fetch_assoc();
  }
  $stmt->close();
}

// Get seats for the selected bus
$seats = [];
if ($bus_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM bus_seats WHERE bus_id = ? ORDER BY seat_number");
  $stmt->bind_param("i", $bus_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $seats[] = $row;
    }
  }
  $stmt->close();
}

// Process individual seat update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seat'])) {
  $seat_id = $_POST['seat_id'];
  $seat_type = $_POST['seat_type'];
  $status = $_POST['status'];
  
  $stmt = $conn->prepare("UPDATE bus_seats SET seat_type = ?, status = ? WHERE id = ? AND bus_id = ?");
  $stmt->bind_param("ssii", $seat_type, $status, $seat_id, $bus_id);
  
  if ($stmt->execute()) {
    $success_message = "Seat updated successfully!";
    
    // Update the seat in our array to reflect changes without reloading
    foreach ($seats as &$seat) {
      if ($seat['id'] == $seat_id) {
        $seat['seat_type'] = $seat_type;
        $seat['status'] = $status;
        break;
      }
    }
  } else {
    $error_message = "Error updating seat: " . $conn->error;
  }
  $stmt->close();
}

// Process bulk seat update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update'])) {
  $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];
  $bulk_action = $_POST['bulk_action'];
  $bulk_value = $_POST['bulk_value'];
  
  if (empty($selected_seats)) {
    $error_message = "No seats selected for bulk update.";
  } else {
    $seat_ids = implode(',', array_map('intval', $selected_seats));
    
    if ($bulk_action === 'type') {
      $sql = "UPDATE bus_seats SET seat_type = ? WHERE id IN ($seat_ids) AND bus_id = ?";
    } else { // status
      $sql = "UPDATE bus_seats SET status = ? WHERE id IN ($seat_ids) AND bus_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $bulk_value, $bus_id);
    
    if ($stmt->execute()) {
      $success_message = count($selected_seats) . " seats updated successfully!";
      
      // Update the seats in our array to reflect changes without reloading
      foreach ($seats as &$seat) {
        if (in_array($seat['id'], $selected_seats)) {
          if ($bulk_action === 'type') {
            $seat['seat_type'] = $bulk_value;
          } else {
            $seat['status'] = $bulk_value;
          }
        }
      }
    } else {
      $error_message = "Error updating seats: " . $conn->error;
    }
    $stmt->close();
  }
}

// For demonstration, create sample data if database is empty
if (empty($buses)) {
  $buses = [
    ['id' => 1, 'name' => 'Deluxe Express', 'departure_location' => 'Kathmandu', 'arrival_location' => 'Pokhara'],
    ['id' => 2, 'name' => 'Tourist Coach', 'departure_location' => 'Kathmandu', 'arrival_location' => 'Pokhara'],
    ['id' => 3, 'name' => 'Night Rider', 'departure_location' => 'Pokhara', 'arrival_location' => 'Kathmandu']
  ];
  
  if ($bus_id === 0) {
    $bus_id = 1;
  }
}

if (empty($current_bus) && $bus_id > 0) {
  foreach ($buses as $bus) {
    if ($bus['id'] == $bus_id) {
      $current_bus = $bus;
      $current_bus['total_seats'] = 40;
      break;
    }
  }
}

if (empty($seats) && $bus_id > 0) {
  // Create sample seats
  $total_seats = $current_bus['total_seats'] ?? 40;
  for ($i = 1; $i <= $total_seats; $i++) {
    $seat_number = $i <= 9 ? "0$i" : "$i";
    $seat_type = 'regular';
    $status = 'available';
    
    // Make some seats premium based on position
    if ($i <= 5 || ($total_seats - $i) < 5) {
      $seat_type = 'premium';
    }
    
    // Make some seats booked for demonstration
    if ($i % 7 === 0) {
      $status = 'booked';
    }
    
    // Make some seats under maintenance
    if ($i % 19 === 0) {
      $status = 'maintenance';
    }
    
    $seats[] = [
      'id' => $i,
      'bus_id' => $bus_id,
      'seat_number' => $seat_number,
      'seat_type' => $seat_type,
      'status' => $status
    ];
  }
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
  <title>Manage Seats - Bus Operator Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .content-card {
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
      overflow: hidden;
    }
    
    .card-header {
      padding: 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #2c3e50;
      margin: 0;
    }
    
    .card-body {
      padding: 20px;
    }
    
    .bus-selector {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      background-color: #f8f9fa;
      padding: 15px 20px;
      border-radius: 10px;
    }
    
    .bus-selector label {
      font-weight: 500;
      color: #444;
      white-space: nowrap;
    }
    
    .bus-selector select {
      flex: 1;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }
    
    .bus-info {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      background-color: #e9f7fe;
      padding: 15px 20px;
      border-radius: 10px;
      border-left: 4px solid #3a86ff;
    }
    
    .bus-icon {
      font-size: 2rem;
      color: #3a86ff;
    }
    
    .bus-details {
      flex: 1;
    }
    
    .bus-name {
      font-weight: 600;
      font-size: 1.2rem;
      margin-bottom: 5px;
    }
    
    .bus-route {
      color: #666;
    }
    
    .seat-map-container {
      margin-bottom: 30px;
    }
    
    .seat-map {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .seat {
      position: relative;
      padding: 15px 10px;
      border-radius: 8px;
      text-align: center;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: 2px solid transparent;
    }
    
    .seat.selected {
      border-color: #3a86ff;
      box-shadow: 0 0 0 2px rgba(58, 134, 255, 0.3);
    }
    
    .seat-regular {
      background-color: #e9ecef;
      color: #495057;
    }
    
    .seat-premium {
      background-color: #cfe2ff;
      color: #0d6efd;
    }
    
    .seat-sleeper {
      background-color: #d1e7dd;
      color: #146c43;
    }
    
    .seat-available {
      opacity: 1;
    }
    
    .seat-booked {
      opacity: 0.7;
      background-image: repeating-linear-gradient(45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.1) 10px, rgba(0,0,0,0) 10px, rgba(0,0,0,0) 20px);
      cursor: not-allowed;
    }
    
    .seat-maintenance {
      opacity: 0.7;
      background-image: repeating-linear-gradient(-45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.1) 10px, rgba(0,0,0,0) 10px, rgba(0,0,0,0) 20px);
      cursor: not-allowed;
    }
    
    .seat-number {
      font-size: 0.9rem;
    }
    
    .seat-type {
      font-size: 0.8rem;
      margin-top: 5px;
      text-transform: capitalize;
    }
    
    .seat-checkbox {
      position: absolute;
      top: 5px;
      right: 5px;
      width: 16px;
      height: 16px;
    }
    
    .seat-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 4px;
    }
    
    .legend-regular {
      background-color: #e9ecef;
    }
    
    .legend-premium {
      background-color: #cfe2ff;
    }
    
    .legend-sleeper {
      background-color: #d1e7dd;
    }
    
    .legend-booked {
      background-color: #e9ecef;
      opacity: 0.7;
      background-image: repeating-linear-gradient(45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.1) 10px, rgba(0,0,0,0) 10px, rgba(0,0,0,0) 20px);
    }
    
    .legend-maintenance {
      background-color: #e9ecef;
      opacity: 0.7;
      background-image: repeating-linear-gradient(-45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.1) 10px, rgba(0,0,0,0) 10px, rgba(0,0,0,0) 20px);
    }
    
    .seat-actions {
      display: flex;
      gap: 20px;
      margin-top: 30px;
    }
    
    .seat-edit-form {
      flex: 1;
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
    }
    
    .form-title {
      font-weight: 600;
      margin-bottom: 15px;
      color: #2c3e50;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #444;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }
    
    .btn {
      padding: 10px 20px;
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
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
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
    
    .bus-layout {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 30px;
    }
    
    .driver-section {
      width: 100%;
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    
    .driver-seat {
      width: 60px;
      height: 60px;
      background-color: #adb5bd;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }
    
    .steering-wheel {
      font-size: 1.5rem;
    }
    
    .bus-entrance {
      width: 100%;
      text-align: center;
      margin-bottom: 20px;
      color: #6c757d;
      font-weight: 500;
    }
    
    .bus-entrance i {
      margin-right: 5px;
    }
    
    .select-all-container {
      margin-bottom: 15px;
    }
    
    @media (max-width: 992px) {
      .seat-actions {
        flex-direction: column;
      }
      
      .seat-map {
        grid-template-columns: repeat(4, 1fr);
      }
    }
    
    @media (max-width: 768px) {
      .bus-selector {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .bus-selector select {
        width: 100%;
      }
      
      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
      }
      
      .seat-map {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    
    @media (max-width: 576px) {
      .seat-map {
        grid-template-columns: repeat(2, 1fr);
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
        <li><a href="add-bus.php"><i class="fas fa-plus-circle"></i> Add Bus</a></li>
        <li><a href="manage-seats.php" class="active"><i class="fas fa-chair"></i> Manage Seats</a></li>
        <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>Manage Bus Seats</h2>
        <p>Configure and update seat types and availability</p>
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
      
      <div class="content-card">
        <div class="card-header">
          <h3 class="card-title">Seat Configuration</h3>
        </div>
        <div class="card-body">
          <div class="bus-selector">
            <label for="bus-select"><i class="fas fa-bus"></i> Select Bus:</label>
            <select id="bus-select" onchange="window.location.href='manage-seats.php?bus_id='+this.value">
              <?php foreach ($buses as $bus): ?>
                <option value="<?php echo $bus['id']; ?>" <?php echo $bus_id == $bus['id'] ? 'selected' : ''; ?>>
                  <?php echo $bus['name']; ?> (<?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <?php if ($current_bus): ?>
            <div class="bus-info">
              <div class="bus-icon">
                <i class="fas fa-bus"></i>
              </div>
              <div class="bus-details">
                <div class="bus-name"><?php echo $current_bus['name']; ?></div>
                <div class="bus-route"><?php echo $current_bus['departure_location']; ?> to <?php echo $current_bus['arrival_location']; ?></div>
              </div>
            </div>
            
            <div class="seat-legend">
              <div class="legend-item">
                <div class="legend-color legend-regular"></div>
                <span>Regular Seat</span>
              </div>
              <div class="legend-item">
                <div class="legend-color legend-premium"></div>
                <span>Premium Seat</span>
              </div>
              <div class="legend-item">
                <div class="legend-color legend-sleeper"></div>
                <span>Sleeper Seat</span>
              </div>
              <div class="legend-item">
                <div class="legend-color legend-booked"></div>
                <span>Booked</span>
              </div>
              <div class="legend-item">
                <div class="legend-color legend-maintenance"></div>
                <span>Maintenance</span>
              </div>
            </div>
            
            <form action="" method="POST" id="seat-form">
              <div class="select-all-container">
                <label>
                  <input type="checkbox" id="select-all"> Select/Deselect All Seats
                </label>
              </div>
              
              <div class="bus-layout">
                <div class="driver-section">
                  <div class="driver-seat">
                    <i class="fas fa-steering-wheel steering-wheel"></i>
                  </div>
                </div>
                <div class="bus-entrance">
                  <i class="fas fa-door-open"></i> Entrance
                </div>
              </div>
              
              <div class="seat-map-container">
                <div class="seat-map">
                  <?php foreach ($seats as $seat): ?>
                    <div class="seat seat-<?php echo $seat['seat_type']; ?> seat-<?php echo $seat['status']; ?>" 
                         data-id="<?php echo $seat['id']; ?>"
                         data-seat-number="<?php echo $seat['seat_number']; ?>"
                         data-seat-type="<?php echo $seat['seat_type']; ?>"
                         data-status="<?php echo $seat['status']; ?>"
                         onclick="selectSeat(this)">
                      <input type="checkbox" name="selected_seats[]" value="<?php echo $seat['id']; ?>" class="seat-checkbox">
                      <div class="seat-number"><?php echo $seat['seat_number']; ?></div>
                      <div class="seat-type"><?php echo $seat['seat_type']; ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              
              <div class="seat-actions">
                <div class="seat-edit-form">
                  <h4 class="form-title">Edit Individual Seat</h4>
                  <div id="individual-edit-form" style="display: none;">
                    <input type="hidden" name="seat_id" id="edit-seat-id">
                    <div class="form-row">
                      <div class="form-group">
                        <label for="edit-seat-number">Seat Number:</label>
                        <input type="text" id="edit-seat-number" class="form-control" readonly>
                      </div>
                      <div class="form-group">
                        <label for="seat_type">Seat Type:</label>
                        <select name="seat_type" id="edit-seat-type" class="form-control">
                          <option value="regular">Regular</option>
                          <option value="premium">Premium</option>
                          <option value="sleeper">Sleeper</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="status">Status:</label>
                      <select name="status" id="edit-status" class="form-control">
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                      </select>
                    </div>
                    <button type="submit" name="update_seat" class="btn btn-primary">Update Seat</button>
                  </div>
                  <div id="no-seat-selected" style="display: block;">
                    <p>Click on a seat to edit its details.</p>
                  </div>
                </div>
                
                <div class="seat-edit-form">
                  <h4 class="form-title">Bulk Update Selected Seats</h4>
                  <div class="form-group">
                    <label for="bulk_action">Update:</label>
                    <select name="bulk_action" id="bulk_action" class="form-control">
                      <option value="type">Seat Type</option>
                      <option value="status">Status</option>
                    </select>
                  </div>
                  <div class="form-group" id="bulk-type-options">
                    <label for="bulk_value">New Type:</label>
                    <select name="bulk_value" id="bulk_value_type" class="form-control">
                      <option value="regular">Regular</option>
                      <option value="premium">Premium</option>
                      <option value="sleeper">Sleeper</option>
                    </select>
                  </div>
                  <div class="form-group" id="bulk-status-options" style="display: none;">
                    <label for="bulk_value">New Status:</label>
                    <select name="bulk_value" id="bulk_value_status" class="form-control">
                      <option value="available">Available</option>
                      <option value="booked">Booked</option>
                      <option value="maintenance">Maintenance</option>
                    </select>
                  </div>
                  <button type="submit" name="bulk_update" class="btn btn-primary">Update Selected Seats</button>
                </div>
              </div>
            </form>
          <?php else: ?>
            <div class="alert alert-info">
              <p>No buses found. <a href="add-bus.php">Add your first bus</a> to manage seats.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Function to handle seat selection
    function selectSeat(seatElement) {
      // Don't allow selecting booked seats for individual editing
      if (seatElement.classList.contains('seat-booked')) {
        return;
      }
      
      // Get seat data
      const seatId = seatElement.dataset.id;
      const seatNumber = seatElement.dataset.seatNumber;
      const seatType = seatElement.dataset.seatType;
      const status = seatElement.dataset.status;
      
      // Toggle checkbox selection
      const checkbox = seatElement.querySelector('.seat-checkbox');
      checkbox.checked = !checkbox.checked;
      
      // Toggle selected class
      seatElement.classList.toggle('selected', checkbox.checked);
      
      // Update individual edit form
      document.getElementById('edit-seat-id').value = seatId;
      document.getElementById('edit-seat-number').value = seatNumber;
      document.getElementById('edit-seat-type').value = seatType;
      document.getElementById('edit-status').value = status;
      
      // Show individual edit form
      document.getElementById('individual-edit-form').style.display = 'block';
      document.getElementById('no-seat-selected').style.display = 'none';
    }
    
    // Handle select all checkbox
    document.getElementById('select-all').addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.seat-checkbox');
      const seats = document.querySelectorAll('.seat');
      
      checkboxes.forEach((checkbox, index) => {
        checkbox.checked = this.checked;
        seats[index].classList.toggle('selected', this.checked);
      });
    });
    
    // Toggle between bulk update options
    document.getElementById('bulk_action').addEventListener('change', function() {
      const typeOptions = document.getElementById('bulk-type-options');
      const statusOptions = document.getElementById('bulk-status-options');
      
      if (this.value === 'type') {
        typeOptions.style.display = 'block';
        statusOptions.style.display = 'none';
        document.querySelector('input[name="bulk_value"]').name = 'bulk_value';
      } else {
        typeOptions.style.display = 'none';
        statusOptions.style.display = 'block';
        document.querySelector('input[name="bulk_value"]').name = 'bulk_value';
      }
    });
  </script>
  
  <script src="../assets/js/admin.js"></script>
</body>
</html>