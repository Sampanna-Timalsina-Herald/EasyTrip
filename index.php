<?php
// Start session to maintain state
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "bus_system"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize data or load from database
include 'data-handler.php';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_seat':
                updateSeat($conn, $_POST['seat_id'], $_POST['status']);
                break;
            case 'update_schedule':
                updateSchedule($conn, $_POST);
                break;
            case 'update_booking':
                updateBooking($conn, $_POST);
                break;
            case 'add_schedule':
                addSchedule($conn, $_POST);
                break;
            case 'add_booking':
                addBooking($conn, $_POST);
                break;
            case 'delete_schedule':
                deleteSchedule($conn, $_POST['id']);
                break;
            case 'delete_booking':
                deleteBooking($conn, $_POST['id']);
                break;
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get current data
$seatData = getSeats($conn);
$scheduleData = getSchedules($conn);
$bookingsData = getBookings($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bus Operator Dashboard</title>
  <link rel="stylesheet" href="busoperator.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header>
    <h1><i class="fas fa-bus"></i> Bus Operator Dashboard</h1>
    <nav>
      <button class="active" data-section="seats"><i class="fas fa-chair"></i> Seat Layout</button>
      <button data-section="schedule"><i class="fas fa-calendar-alt"></i> Schedule</button>
      <button data-section="bookings"><i class="fas fa-ticket-alt"></i> Bookings</button>
    </nav>
  </header>
  
  <main>
    <section id="seats" class="section-active">
      <h2><i class="fas fa-chair"></i> Bus Seat Layout</h2>
      <div class="bus-container">
        <div class="driver-area">
          <div class="driver-seat"><i class="fas fa-user"></i> Driver</div>
          <div class="door"><i class="fas fa-door-open"></i> Door</div>
        </div>
        <div class="seats-container">
          <?php foreach ($seatData as $seat): ?>
            <div class="seat <?php echo $seat['status']; ?>" 
                 data-id="<?php echo $seat['id']; ?>"
                 onclick="editSeat(<?php echo $seat['id']; ?>)">
              <?php echo $seat['id']; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Seat Edit Modal -->
      <div id="seatEditModal" class="modal">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h3><i class="fas fa-edit"></i> Edit Seat Status</h3>
          <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" name="action" value="update_seat">
            <input type="hidden" id="edit-seat-id" name="seat_id" value="">
            
            <div class="form-group">
              <label for="seat-status"><i class="fas fa-tag"></i> Status:</label>
              <select id="seat-status" name="status">
                <option value="available">Available</option>
                <option value="booked">Booked</option>
                <option value="selected">Selected</option>
              </select>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
          </form>
        </div>
      </div>
      
      <div class="legend">
        <div class="legend-item">
          <div class="seat-demo available"></div>
          <span>Available</span>
        </div>
        <div class="legend-item">
          <div class="seat-demo booked"></div>
          <span>Booked</span>
        </div>
        <div class="legend-item">
          <div class="seat-demo selected"></div>
          <span>Selected</span>
        </div>
      </div>
    </section>

    <section id="schedule">
      <h2><i class="fas fa-calendar-alt"></i> Bus Schedule</h2>
      <button class="add-btn" onclick="showAddScheduleModal()">Add New Schedule</button>
      
      <table class="schedule-table">
        <thead>
          <tr>
            <th>Route</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Available Seats</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="schedule-body">
          <?php foreach ($scheduleData as $schedule): ?>
            <tr>
              <td><?php echo $schedule['route']; ?></td>
              <td><?php echo $schedule['departure']; ?></td>
              <td><?php echo $schedule['arrival']; ?></td>
              <td><?php echo $schedule['available_seats']; ?></td>
              <td><span class="status status-<?php echo $schedule['status']; ?>"><?php echo formatStatus($schedule['status']); ?></span></td>
              <td>
                <button class="edit-btn" onclick="editSchedule(<?php echo $schedule['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline;">
                  <input type="hidden" name="action" value="delete_schedule">
                  <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this schedule?')"><i class="fas fa-trash-alt"></i> Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <!-- Schedule Edit Modal -->
      <div id="scheduleEditModal" class="modal">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h3 id="scheduleModalTitle"><i class="fas fa-edit"></i> Edit Schedule</h3>
          <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" id="schedule-action" name="action" value="update_schedule">
            <input type="hidden" id="edit-schedule-id" name="id" value="">
            
            <div class="form-group">
              <label for="schedule-route"><i class="fas fa-route"></i> Route:</label>
              <input type="text" id="schedule-route" name="route" required>
            </div>
            
            <div class="form-group">
              <label for="schedule-departure"><i class="fas fa-plane-departure"></i> Departure:</label>
              <input type="text" id="schedule-departure" name="departure" required>
            </div>
            
            <div class="form-group">
              <label for="schedule-arrival"><i class="fas fa-plane-arrival"></i> Arrival:</label>
              <input type="text" id="schedule-arrival" name="arrival" required>
            </div>
            
            <div class="form-group">
              <label for="schedule-seats"><i class="fas fa-chair"></i> Available Seats:</label>
              <input type="number" id="schedule-seats" name="available_seats" required>
            </div>
            
            <div class="form-group">
              <label for="schedule-status"><i class="fas fa-info-circle"></i> Status:</label>
              <select id="schedule-status" name="status">
                <option value="on-time">On Time</option>
                <option value="delayed">Delayed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
          </form>
        </div>
      </div>
    </section>

    <section id="bookings">
      <h2><i class="fas fa-ticket-alt"></i> Booking Information</h2>
      <button class="add-btn" onclick="showAddBookingModal()">Add New Booking</button>
      
      <table class="bookings-table">
        <thead>
          <tr>
            <th>Passenger</th>
            <th>Route</th>
            <th>Date</th>
            <th>Seat</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="bookings-body">
          <?php foreach ($bookingsData as $booking): ?>
            <tr>
              <td><?php echo $booking['passenger']; ?></td>
              <td><?php echo $booking['route']; ?></td>
              <td><?php echo formatDate($booking['date']); ?></td>
              <td><?php echo $booking['seat']; ?></td>
              <td><span class="status status-<?php echo $booking['status']; ?>"><?php echo formatStatus($booking['status']); ?></span></td>
              <td>
                <button class="edit-btn" onclick="editBooking(<?php echo $booking['id']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline;">
                  <input type="hidden" name="action" value="delete_booking">
                  <input type="hidden" name="id" value="<?php echo $booking['id']; ?>">
                  <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this booking?')"><i class="fas fa-trash-alt"></i> Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      
      <!-- Booking Edit Modal -->
      <div id="bookingEditModal" class="modal">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h3 id="bookingModalTitle"><i class="fas fa-edit"></i> Edit Booking</h3>
          <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" id="booking-action" name="action" value="update_booking">
            <input type="hidden" id="edit-booking-id" name="id" value="">
            
            <div class="form-group">
              <label for="booking-passenger"><i class="fas fa-user"></i> Passenger:</label>
              <input type="text" id="booking-passenger" name="passenger" required>
            </div>
            
            <div class="form-group">
              <label for="booking-route"><i class="fas fa-route"></i> Route:</label>
              <select id="booking-route" name="route">
                <?php foreach ($scheduleData as $schedule): ?>
                  <option value="<?php echo $schedule['route']; ?>"><?php echo $schedule['route']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label for="booking-date"><i class="fas fa-calendar-day"></i> Date:</label>
              <input type="date" id="booking-date" name="date" required>
            </div>
            
            <div class="form-group">
              <label for="booking-seat"><i class="fas fa-chair"></i> Seat:</label>
              <select id="booking-seat" name="seat">
                <?php foreach ($seatData as $seat): ?>
                  <option value="<?php echo $seat['id']; ?>"><?php echo $seat['id']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="form-group">
              <label for="booking-status"><i class="fas fa-info-circle"></i> Status:</label>
              <select id="booking-status" name="status">
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
              </select>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script src="busoperator.js"></script>
</body>
</html>

<?php
// Helper function to format status text
function formatStatus($status) {
    return ucwords(str_replace('-', ' ', $status));
}

// Helper function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y');
}
?>

