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

// Get filter parameters
$bus_filter = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Get operator's buses for filter dropdown
$buses = [];
$stmt = $conn->prepare("SELECT id, name FROM buses WHERE operator_id = ? ORDER BY name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
  }
}
$stmt->close();

// Build query for bookings
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, 
         bs.name as bus_name, bs.departure_location, bs.arrival_location, bs.departure_time, bs.arrival_time 
         FROM bookings b 
         LEFT JOIN users u ON b.user_id = u.id 
         LEFT JOIN buses bs ON b.bus_id = bs.id 
         WHERE bs.operator_id = ? AND b.bus_id IS NOT NULL";

$params = [$user_id];
$types = "i";

if ($bus_filter > 0) {
  $sql .= " AND b.bus_id = ?";
  $params[] = $bus_filter;
  $types .= "i";
}

if ($status_filter !== 'all') {
  $sql .= " AND b.status = ?";
  $params[] = $status_filter;
  $types .= "s";
}

if (!empty($date_filter)) {
  $sql .= " AND b.travel_date = ?";
  $params[] = $date_filter;
  $types .= "s";
}

$sql .= " ORDER BY b.travel_date DESC, b.id DESC";

// Get bookings
$bookings = [];
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
  }
}
$stmt->close();

// Process booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  $booking_id = $_POST['booking_id'];
  $new_status = $_POST['status'];
  
  // Verify the booking belongs to one of the operator's buses
  $stmt = $conn->prepare("SELECT b.id FROM bookings b 
                         JOIN buses bs ON b.bus_id = bs.id 
                         WHERE b.id = ? AND bs.operator_id = ?");
  $stmt->bind_param("ii", $booking_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    
    if ($stmt->execute()) {
      $success_message = "Booking status updated successfully!";
      
      // Update the booking in our array to reflect changes without reloading
      foreach ($bookings as &$booking) {
        if ($booking['id'] == $booking_id) {
          $booking['status'] = $new_status;
          break;
        }
      }
    } else {
      $error_message = "Error updating booking status: " . $conn->error;
    }
  } else {
    $error_message = "You don't have permission to update this booking.";
  }
  $stmt->close();
}

// For demonstration, create sample data if database is empty
if (empty($buses)) {
  $buses = [
    ['id' => 1, 'name' => 'Deluxe Express'],
    ['id' => 2, 'name' => 'Tourist Coach'],
    ['id' => 3, 'name' => 'Night Rider']
  ];
}

if (empty($bookings)) {
  $today = date('Y-m-d');
  $tomorrow = date('Y-m-d', strtotime('+1 day'));
  $yesterday = date('Y-m-d', strtotime('-1 day'));
  
  $bookings = [
    [
      'id' => 1,
      'user_id' => 4,
      'user_name' => 'John Doe',
      'user_email' => 'john@example.com',
      'bus_id' => 1,
      'bus_name' => 'Deluxe Express',
      'departure_location' => 'Kathmandu',
      'arrival_location' => 'Pokhara',
      'departure_time' => '07:00:00',
      'arrival_time' => '13:00:00',
      'travel_date' => $tomorrow,
      'passengers' => 2,
      'total_amount' => 50.00,
      'status' => 'confirmed',
      'booking_date' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
      'id' => 2,
      'user_id' => 5,
      'user_name' => 'Jane Smith',
      'user_email' => 'jane@example.com',
      'bus_id' => 2,
      'bus_name' => 'Tourist Coach',
      'departure_location' => 'Kathmandu',
      'arrival_location' => 'Pokhara',
      'departure_time' => '08:30:00',
      'arrival_time' => '14:30:00',
      'travel_date' => $today,
      'passengers' => 1,
      'total_amount' => 35.00,
      'status' => 'pending',
      'booking_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
      'id' => 3,
      'user_id' => 6,
      'user_name' => 'Mike Johnson',
      'user_email' => 'mike@example.com',
      'bus_id' => 3,
      'bus_name' => 'Night Rider',
      'departure_location' => 'Pokhara',
      'arrival_location' => 'Kathmandu',
      'departure_time' => '20:00:00',
      'arrival_time' => '04:00:00',
      'travel_date' => $yesterday,
      'passengers' => 3,
      'total_amount' => 90.00,
      'status' => 'completed',
      'booking_date' => date('Y-m-d H:i:s', strtotime('-5 days'))
    ]
  ];
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
  <title>Bookings - Bus Operator Dashboard</title>
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
    
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 25px;
      background-color: #f8f9fa;
      padding: 15px 20px;
      border-radius: 10px;
    }
    
    .filter-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .filter-group label {
      font-weight: 500;
      color: #444;
      white-space: nowrap;
    }
    
    .filter-group select,
    .filter-group input {
      padding: 8px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    
    .filter-actions {
      margin-left: auto;
    }
    
    .btn {
      padding: 8px 15px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      font-size: 0.95rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
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
    
    .booking-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .booking-table th,
    .booking-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .booking-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #444;
    }
    
    .booking-table tbody tr:hover {
      background-color: #f8f9fa;
    }
    
    .booking-id {
      font-weight: 600;
      color: #3a86ff;
    }
    
    .passenger-info {
      display: flex;
      flex-direction: column;
    }
    
    .passenger-name {
      font-weight: 500;
    }
    
    .passenger-email {
      font-size: 0.9rem;
      color: #666;
    }
    
    .bus-info {
      display: flex;
      flex-direction: column;
    }
    
    .bus-name {
      font-weight: 500;
    }
    
    .bus-route {
      font-size: 0.9rem;
      color: #666;
    }
    
    .travel-info {
      display: flex;
      flex-direction: column;
    }
    
    .travel-date {
      font-weight: 500;
    }
    
    .travel-time {
      font-size: 0.9rem;
      color: #666;
    }
    
    .status-badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-confirmed {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-completed {
      background-color: #d1e7dd;
      color: #146c43;
    }
    
    .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .action-dropdown {
      position: relative;
      display: inline-block;
    }
    
    .dropdown-toggle {
      background-color: transparent;
      border: none;
      cursor: pointer;
      padding: 5px;
      color: #6c757d;
      font-size: 1.2rem;
    }
    
    .dropdown-menu {
      position: absolute;
      right: 0;
      top: 100%;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      min-width: 150px;
      z-index: 10;
      display: none;
    }
    
    .dropdown-menu.show {
      display: block;
    }
    
    .dropdown-item {
      display: block;
      padding: 8px 15px;
      color: #333;
      text-decoration: none;
      transition: background-color 0.2s;
    }
    
    .dropdown-item:hover {
      background-color: #f8f9fa;
    }
    
    .dropdown-divider {
      height: 1px;
      background-color: #eee;
      margin: 5px 0;
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
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 100;
      align-items: center;
      justify-content: center;
    }
    
    .modal.show {
      display: flex;
    }
    
    .modal-content {
      background-color: #fff;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
      padding: 15px 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2c3e50;
      margin: 0;
    }
    
    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #6c757d;
    }
    
    .modal-body {
      padding: 20px;
    }
    
    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
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
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 30px;
    }
    
    .pagination-item {
      margin: 0 5px;
    }
    
    .pagination-link {
      display: block;
      padding: 8px 12px;
      border-radius: 8px;
      background-color: #f8f9fa;
      color: #333;
      text-decoration: none;
      transition: all 0.2s;
    }
    
    .pagination-link:hover,
    .pagination-link.active {
      background-color: #3a86ff;
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
    }
    
    .empty-icon {
      font-size: 3rem;
      color: #adb5bd;
      margin-bottom: 20px;
    }
    
    .empty-text {
      font-size: 1.1rem;
      color: #6c757d;
      margin-bottom: 20px;
    }
    
    @media (max-width: 992px) {
      .filter-bar {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .filter-actions {
        margin-left: 0;
        margin-top: 10px;
        width: 100%;
      }
      
      .filter-actions .btn {
        width: 100%;
      }
      
      .booking-table {
        display: block;
        overflow-x: auto;
      }
    }
    
    @media (max-width: 768px) {
      .filter-group {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
      }
      
      .filter-group select,
      .filter-group input {
        width: 100%;
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
        <li><a href="manage-seats.php"><i class="fas fa-chair"></i> Manage Seats</a></li>
        <li><a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>Bus Bookings</h2>
        <p>View and manage bookings for your buses</p>
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
          <h3 class="card-title">Booking Management</h3>
        </div>
        <div class="card-body">
          <form action="" method="GET" id="filter-form">
            <div class="filter-bar">
              <div class="filter-group">
                <label for="bus_id"><i class="fas fa-bus"></i> Bus:</label>
                <select id="bus_id" name="bus_id" onchange="this.form.submit()">
                  <option value="0">All Buses</option>
                  <?php foreach ($buses as $bus): ?>
                    <option value="<?php echo $bus['id']; ?>" <?php echo $bus_filter == $bus['id'] ? 'selected' : ''; ?>>
                      <?php echo $bus['name']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div class="filter-group">
                <label for="status"><i class="fas fa-tag"></i> Status:</label>
                <select id="status" name="status" onchange="this.form.submit()">
                  <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                  <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                  <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                  <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                  <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
              </div>
              
              <div class="filter-group">
                <label for="date"><i class="fas fa-calendar-alt"></i> Travel Date:</label>
                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
              </div>
              
              <div class="filter-actions">
                <button type="button" class="btn btn-outline" onclick="resetFilters()">
                  <i class="fas fa-redo"></i> Reset Filters
                </button>
              </div>
            </div>
          </form>
          
          <?php if (empty($bookings)): ?>
            <div class="empty-state">
              <div class="empty-icon">
                <i class="fas fa-calendar-times"></i>
              </div>
              <div class="empty-text">No bookings found matching your criteria.</div>
              <button type="button" class="btn btn-outline" onclick="resetFilters()">
                <i class="fas fa-redo"></i> Reset Filters
              </button>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="booking-table">
                <thead>
                  <tr>
                    <th>Booking ID</th>
                    <th>Passenger</th>
                    <th>Bus & Route</th>
                    <th>Travel Date & Time</th>
                    <th>Passengers</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookings as $booking): ?>
                    <tr>
                      <td class="booking-id">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></td>
                      <td>
                        <div class="passenger-info">
                          <span class="passenger-name"><?php echo $booking['user_name']; ?></span>
                          <span class="passenger-email"><?php echo $booking['user_email']; ?></span>
                        </div>
                      </td>
                      <td>
                        <div class="bus-info">
                          <span class="bus-name"><?php echo $booking['bus_name']; ?></span>
                          <span class="bus-route"><?php echo $booking['departure_location']; ?> to <?php echo $booking['arrival_location']; ?></span>
                        </div>
                      </td>
                      <td>
                        <div class="travel-info">
                          <span class="travel-date"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                          <span class="travel-time">
                            <?php echo date('h:i A', strtotime($booking['departure_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($booking['arrival_time'])); ?>
                          </span>
                        </div>
                      </td>
                      <td><?php echo $booking['passengers']; ?></td>
                      <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                      <td>
                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                          <?php echo ucfirst($booking['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="action-dropdown">
                          <button type="button" class="dropdown-toggle" onclick="toggleDropdown(<?php echo $booking['id']; ?>)">
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <div class="dropdown-menu" id="dropdown-<?php echo $booking['id']; ?>">
                            <a href="#" class="dropdown-item" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                              <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="#" class="dropdown-item" onclick="openStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                              <i class="fas fa-edit"></i> Update Status
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item" onclick="printBookingTicket(<?php echo $booking['id']; ?>)">
                              <i class="fas fa-print"></i> Print Ticket
                            </a>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination (for demonstration) -->
            <div class="pagination">
              <div class="pagination-item">
                <a href="#" class="pagination-link active">1</a>
              </div>
              <div class="pagination-item">
                <a href="#" class="pagination-link">2</a>
              </div>
              <div class="pagination-item">
                <a href="#" class="pagination-link">3</a>
              </div>
              <div class="pagination-item">
                <a href="#" class="pagination-link">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Status Update Modal -->
  <div class="modal" id="status-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Update Booking Status</h4>
        <button type="button" class="modal-close" onclick="closeModal('status-modal')">&times;</button>
      </div>
      <form action="" method="POST">
        <div class="modal-body">
          <input type="hidden" name="booking_id" id="status-booking-id">
          <div class="form-group">
            <label for="status">New Status:</label>
            <select name="status" id="status-select" class="form-control">
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('status-modal')">Cancel</button>
          <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Booking Details Modal -->
  <div class="modal" id="details-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Booking Details</h4>
        <button type="button" class="modal-close" onclick="closeModal('details-modal')">&times;</button>
      </div>
      <div class="modal-body" id="booking-details-content">
        <!-- Content will be loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('details-modal')">Close</button>
      </div>
    </div>
  </div>
  
  <script>
    // Toggle dropdown menu
    function toggleDropdown(id) {
      const dropdown = document.getElementById(`dropdown-${id}`);
      
      // Close all other dropdowns first
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu.id !== `dropdown-${id}`) {
          menu.classList.remove('show');
        }
      });
      
      dropdown.classList.toggle('show');
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.matches('.dropdown-toggle') && !event.target.matches('.fas.fa-ellipsis-v')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
          menu.classList.remove('show');
        });
      }
    });
    
    // Open status update modal
    function openStatusModal(bookingId, currentStatus) {
      document.getElementById('status-booking-id').value = bookingId;
      document.getElementById('status-select').value = currentStatus;
      document.getElementById('status-modal').classList.add('show');
    }
    
    // View booking details
    function viewBookingDetails(bookingId) {
      // In a real application, this would fetch booking details via AJAX
      // For demonstration, we'll just show a simple details view
      const detailsContent = document.getElementById('booking-details-content');
      
      // Find the booking in our data
      const bookingRow = document.querySelector(`tr td.booking-id:contains('#${bookingId.toString().padStart(6, '0')}')`).parentNode;
      const passengerInfo = bookingRow.querySelector('.passenger-info').innerHTML;
      const busInfo = bookingRow.querySelector('.bus-info').innerHTML;
      const travelInfo = bookingRow.querySelector('.travel-info').innerHTML;
      const passengers = bookingRow.cells[4].innerText;
      const amount = bookingRow.cells[5].innerText;
      const status = bookingRow.querySelector('.status-badge').outerHTML;
      
      // Build details HTML
      detailsContent.innerHTML = `
        <div style="margin-bottom: 20px;">
          <h5 style="margin-bottom: 10px;">Passenger Information</h5>
          <div>${passengerInfo}</div>
        </div>
        <div style="margin-bottom: 20px;">
          <h5 style="margin-bottom: 10px;">Bus Information</h5>
          <div>${busInfo}</div>
        </div>
        <div style="margin-bottom: 20px;">
          <h5 style="margin-bottom: 10px;">Travel Details</h5>
          <div>${travelInfo}</div>
          <div style="margin-top: 10px;">Number of Passengers: ${passengers}</div>
        </div>
        <div style="margin-bottom: 20px;">
          <h5 style="margin-bottom: 10px;">Payment Information</h5>
          <div>Total Amount: ${amount}</div>
        </div>
        <div>
          <h5 style="margin-bottom: 10px;">Status</h5>
          <div>${status}</div>
        </div>
      `;
      
      // Show modal
      document.getElementById('details-modal').classList.add('show');
    }
    
    // Print booking ticket
    function printBookingTicket(bookingId) {
      // In a real application, this would open a printable ticket view
      alert(`Printing ticket for booking #${bookingId.toString().padStart(6, '0')}`);
    }
    
    // Close modal
    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('show');
    }
    
    // Reset filters
    function resetFilters() {
      document.getElementById('bus_id').value = '0';
      document.getElementById('status').value = 'all';
      document.getElementById('date').value = '';
      document.getElementById('filter-form').submit();
    }
    
    // Add contains selector for jQuery-like functionality
    Element.prototype.matches = Element.prototype.matches || Element.prototype.msMatchesSelector;
    Element.prototype.closest = Element.prototype.closest || function(selector) {
      let el = this;
      while (el) {
        if (el.matches(selector)) {
          return el;
        }
        el = el.parentElement;
      }
      return null;
    };
    
    // Add :contains selector functionality
    HTMLElement.prototype.contains = function(text) {
      return this.textContent.includes(text);
    };
  </script>
  
  <script src="../assets/js/admin.js"></script>
</body>
</html>