<?php
include 'config/database.php';
include 'includes/header.php';

// Get search parameters
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;
$travel_type = isset($_GET['travel_type']) ? $_GET['travel_type'] : 'both';
$travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : $check_in; // Use check-in date as travel date if not specified

// Validate dates
$today = date('Y-m-d');
if (empty($check_in) || strtotime($check_in) < strtotime($today)) {
  $check_in = $today;
}
if (empty($check_out) || strtotime($check_out) <= strtotime($check_in)) {
  $check_out_date = new DateTime($check_in);
  $check_out_date->modify('+1 day');
  $check_out = $check_out_date->format('Y-m-d');
}
if (empty($travel_date) || strtotime($travel_date) < strtotime($today)) {
  $travel_date = $check_in;
}

// Calculate nights
$nights = 1;
if (!empty($check_in) && !empty($check_out)) {
  $check_in_date = new DateTime($check_in);
  $check_out_date = new DateTime($check_out);
  $interval = $check_in_date->diff($check_out_date);
  $nights = $interval->days;
}

// Search hotels
$hotels = [];
if ($travel_type == 'both' || $travel_type == 'hotel') {
  $sql = "SELECT * FROM hotels WHERE status = 'active'";
  if (!empty($destination)) {
      $destination_param = "%$destination%";
      $sql .= " AND (location LIKE ? OR name LIKE ?)";
  }
  
  $stmt = $conn->prepare($sql);
  if (!empty($destination)) {
      $stmt->bind_param("ss", $destination_param, $destination_param);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $hotels[] = $row;
      }
  }
  $stmt->close();
}

// Search buses - Only show buses related to the destination
$buses = [];
if ($travel_type == 'both' || $travel_type == 'bus') {
  $sql = "SELECT * FROM buses WHERE status = 'active'";
  if (!empty($destination)) {
      $destination_param = "%$destination%";
      // Only show buses that are related to the destination (either departing from or arriving at)
      $sql .= " AND (departure_location LIKE ? OR arrival_location LIKE ?)";
  }
  
  $stmt = $conn->prepare($sql);
  if (!empty($destination)) {
      $stmt->bind_param("ss", $destination_param, $destination_param);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $buses[] = $row;
      }
  }
  $stmt->close();
}

// For demonstration, create sample data if database is empty
if (empty($hotels) && ($travel_type == 'both' || $travel_type == 'hotel')) {
  $hotels = [
      [
          'id' => 1,
          'name' => 'Pokhara Luxury Resort',
          'description' => 'Experience luxury with stunning lake views and premium amenities.',
          'location' => 'Pokhara',
          'image_url' => '/placeholder.svg?height=200&width=300',
          'price_per_night' => 149.99,
          'total_rooms' => 50,
          'available_rooms' => 35
      ],
      [
          'id' => 2,
          'name' => 'Lakeside Hotel',
          'description' => 'Comfortable accommodation with beautiful views of Phewa Lake.',
          'location' => 'Pokhara',
          'image_url' => '/placeholder.svg?height=200&width=300',
          'price_per_night' => 89.99,
          'total_rooms' => 40,
          'available_rooms' => 25
      ],
      [
          'id' => 3,
          'name' => 'Mountain View Inn',
          'description' => 'Cozy rooms with spectacular views of the Annapurna range.',
          'location' => 'Pokhara',
          'image_url' => '/placeholder.svg?height=200&width=300',
          'price_per_night' => 69.99,
          'total_rooms' => 30,
          'available_rooms' => 20
      ]
  ];
}

// Only show buses related to the destination
if (empty($buses) && ($travel_type == 'both' || $travel_type == 'bus')) {
  // For Pokhara, only show buses related to Pokhara
  if (stripos($destination, 'pokhara') !== false) {
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
            'available_seats' => 25
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
            'available_seats' => 20
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
            'available_seats' => 22
        ]
    ];
  } else {
    // For other destinations, show generic buses
    $buses = [
        [
            'id' => 4,
            'name' => 'Mountain Express',
            'description' => 'Comfortable journey through scenic mountain routes.',
            'departure_location' => 'Kathmandu',
            'arrival_location' => $destination,
            'departure_time' => '09:00:00',
            'arrival_time' => '14:00:00',
            'image_url' => '/placeholder.svg?height=200&width=300',
            'price' => 20.00,
            'total_seats' => 42,
            'available_seats' => 30
        ],
        [
            'id' => 5,
            'name' => 'Luxury Sleeper',
            'description' => 'Premium overnight service with full sleeper seats.',
            'departure_location' => $destination,
            'arrival_location' => 'Kathmandu',
            'departure_time' => '21:00:00',
            'arrival_time' => '05:00:00',
            'image_url' => '/placeholder.svg?height=200&width=300',
            'price' => 40.00,
            'total_seats' => 30,
            'available_seats' => 18
        ]
    ];
  }
}

// Create combo packages by pairing hotels and buses
$combos = [];
if (!empty($hotels) && !empty($buses) && ($travel_type == 'both')) {
  foreach ($hotels as $hotel) {
    foreach ($buses as $bus) {
      // Only create combos where the bus is related to the hotel location
      if (stripos($bus['departure_location'], $hotel['location']) !== false || 
          stripos($bus['arrival_location'], $hotel['location']) !== false) {
        $combos[] = [
          'hotel' => $hotel,
          'bus' => $bus
        ];
      }
    }
  }
}
?>

<section class="section">
  <div class="container">
      <div class="section-title">
          <h2>Search Results</h2>
          <p>Showing results for <?php echo htmlspecialchars($destination); ?></p>
      </div>
      
      <div class="search-summary">
          <div class="search-details">
              <?php if ($travel_type == 'both' || $travel_type == 'hotel'): ?>
                  <div class="search-detail">
                      <i class="fas fa-calendar-alt"></i>
                      <span><?php echo date('M d, Y', strtotime($check_in)); ?> - <?php echo date('M d, Y', strtotime($check_out)); ?> (<?php echo $nights; ?> nights)</span>
                  </div>
              <?php endif; ?>
              
              <?php if ($travel_type == 'both' || $travel_type == 'bus'): ?>
                  <div class="search-detail">
                      <i class="fas fa-bus"></i>
                      <span>Travel Date: <?php echo date('M d, Y', strtotime($travel_date)); ?></span>
                  </div>
              <?php endif; ?>
              
              <div class="search-detail">
                  <i class="fas fa-user"></i>
                  <span><?php echo $guests; ?> <?php echo $guests > 1 ? 'Guests' : 'Guest'; ?></span>
              </div>
          </div>
          
          <div class="search-actions">
              <button class="btn" onclick="toggleSearchForm()">Modify Search</button>
          </div>
      </div>
      
      <div class="search-form" id="search-form" style="display: none; margin-bottom: 40px;">
          <form action="search-results.php" method="GET">
              <div class="form-row">
                  <div class="form-group">
                      <label for="destination">Destination</label>
                      <input type="text" id="destination" name="destination" class="form-control" value="<?php echo htmlspecialchars($destination); ?>" placeholder="Where are you going?" required>
                  </div>
                  <div class="form-group">
                      <label for="check-in">Check-in Date</label>
                      <input type="date" id="check-in" name="check_in" class="form-control" value="<?php echo $check_in; ?>" required>
                  </div>
                  <div class="form-group">
                      <label for="check-out">Check-out Date</label>
                      <input type="date" id="check-out" name="check_out" class="form-control" value="<?php echo $check_out; ?>" required>
                  </div>
              </div>
              <div class="form-row">
                  <div class="form-group">
                      <label for="guests">Guests</label>
                      <select id="guests" name="guests" class="form-control">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                              <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>>
                                  <?php echo $i; ?> <?php echo $i > 1 ? 'Guests' : 'Guest'; ?>
                              </option>
                          <?php endfor; ?>
                          <option value="6" <?php echo $guests >= 6 ? 'selected' : ''; ?>>6+ Guests</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="travel-type">Travel Type</label>
                      <select id="travel-type" name="travel_type" class="form-control">
                          <option value="both" <?php echo $travel_type == 'both' ? 'selected' : ''; ?>>Hotel & Bus</option>
                          <option value="hotel" <?php echo $travel_type == 'hotel' ? 'selected' : ''; ?>>Hotel Only</option>
                          <option value="bus" <?php echo $travel_type == 'bus' ? 'selected' : ''; ?>>Bus Only</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label>&nbsp;</label>
                      <button type="submit" class="btn btn-block">Update Search</button>
                  </div>
              </div>
          </form>
      </div>
      
      <!-- Show Combo Deals First (if available) -->
      <?php if (!empty($combos) && $travel_type == 'both'): ?>
          <div class="result-section">
              <h3>Recommended Combo Packages</h3>
              <p>Book hotel and bus together for a seamless travel experience and save!</p>
              
              <div class="grid">
                  <?php foreach ($combos as $index => $combo): ?>
                      <?php 
                      $hotel = $combo['hotel'];
                      $bus = $combo['bus'];
                      $hotel_total = $hotel['price_per_night'] * $nights * $guests;
                      $bus_total = $bus['price'] * $guests;
                      $total_price = $hotel_total + $bus_total;
                      $original_price = ($hotel['price_per_night'] * $nights * 1.05) + ($bus['price'] * $guests * 1.05);
                      $savings = $original_price - $total_price;
                      ?>
                      <div class="card combo-card">
                          <div class="combo-header">
                              <span class="combo-badge">Combo Deal</span>
                              <h3 class="combo-title"><?php echo $hotel['name']; ?> + <?php echo $bus['name']; ?></h3>
                          </div>
                          <div class="combo-content">
                              <div class="combo-item">
                                  <div class="combo-icon">
                                      <i class="fas fa-hotel"></i>
                                  </div>
                                  <div class="combo-details">
                                      <h4><?php echo $hotel['name']; ?></h4>
                                      <p><i class="fas fa-map-marker-alt"></i> <?php echo $hotel['location']; ?></p>
                                      <p><i class="fas fa-calendar-alt"></i> <?php echo date('M d', strtotime($check_in)); ?> - <?php echo date('M d, Y', strtotime($check_out)); ?> (<?php echo $nights; ?> nights)</p>
                                      <p class="combo-price">$<?php echo $hotel['price_per_night']; ?> per night</p>
                                  </div>
                              </div>
                              <div class="combo-item">
                                  <div class="combo-icon">
                                      <i class="fas fa-bus"></i>
                                  </div>
                                  <div class="combo-details">
                                      <h4><?php echo $bus['name']; ?></h4>
                                      <p><i class="fas fa-route"></i> <?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?></p>
                                      <p><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($travel_date)); ?></p>
                                      <p class="combo-price">$<?php echo $bus['price']; ?> per person</p>
                                  </div>
                              </div>
                          </div>
                          <div class="combo-footer">
                              <div class="combo-pricing">
                                  <div class="combo-original-price">Original: $<?php echo number_format($original_price, 2); ?></div>
                                  <div class="combo-total-price">Combo Price: $<?php echo number_format($total_price, 2); ?></div>
                                  <div class="combo-savings">You save: $<?php echo number_format($savings, 2); ?></div>
                              </div>
                              <a href="booking.php?hotel_id=<?php echo $hotel['id']; ?>&bus_id=<?php echo $bus['id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&travel_date=<?php echo $travel_date; ?>&guests=<?php echo $guests; ?>&passengers=<?php echo $guests; ?>" class="btn btn-block btn-primary">Book This Combo</a>
                          </div>
                      </div>
                  <?php endforeach; ?>
              </div>
          </div>
      <?php endif; ?>
      
      <!-- Show individual hotels and buses only if specifically requested or no combos available -->
      <?php if (($travel_type == 'hotel' || empty($combos)) && !empty($hotels)): ?>
          <div class="result-section">
              <h3>Available Hotels</h3>
              
              <?php if (empty($hotels)): ?>
                  <div class="alert alert-info">
                      <p>No hotels found matching your criteria. Please try a different search.</p>
                  </div>
              <?php else: ?>
                  <div class="grid">
                      <?php foreach ($hotels as $hotel): ?>
                          <div class="card">
                              <div class="card-img">
                                  <img src="<?php echo $hotel['image_url'] ? $hotel['image_url'] : '/placeholder.svg?height=200&width=300'; ?>" alt="<?php echo $hotel['name']; ?>">
                              </div>
                              <div class="card-body">
                                  <h3 class="card-title"><?php echo $hotel['name']; ?></h3>
                                  <p class="card-text"><?php echo $hotel['description']; ?></p>
                                  <div class="card-location"><i class="fas fa-map-marker-alt"></i> <?php echo $hotel['location']; ?></div>
                                  <div class="card-price">$<?php echo $hotel['price_per_night']; ?> per night</div>
                                  <div class="card-total">$<?php echo number_format($hotel['price_per_night'] * $nights, 2); ?> total for <?php echo $nights; ?> nights</div>
                                  <a href="hotel-details.php?id=<?php echo $hotel['id']; ?>" class="btn">View Details</a>
                                  <a href="booking.php?hotel_id=<?php echo $hotel['id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&guests=<?php echo $guests; ?>" class="btn btn-primary">Book Now</a>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
          </div>
      <?php endif; ?>
      
      <?php if (($travel_type == 'bus' || empty($combos)) && !empty($buses)): ?>
          <div class="result-section">
              <h3>Available Buses</h3>
              
              <?php if (empty($buses)): ?>
                  <div class="alert alert-info">
                      <p>No buses found matching your criteria. Please try a different search.</p>
                  </div>
              <?php else: ?>
                  <div class="grid">
                      <?php foreach ($buses as $bus): ?>
                          <div class="card">
                              <div class="card-img">
                                  <img src="<?php echo $bus['image_url'] ? $bus['image_url'] : '/placeholder.svg?height=200&width=300'; ?>" alt="<?php echo $bus['name']; ?>">
                              </div>
                              <div class="card-body">
                                  <h3 class="card-title"><?php echo $bus['name']; ?></h3>
                                  <p class="card-text"><?php echo $bus['description']; ?></p>
                                  <div class="card-route">
                                      <i class="fas fa-route"></i> <?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?>
                                  </div>
                                  <div class="card-time">
                                      <span><i class="fas fa-clock"></i> Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?></span>
                                      <span><i class="fas fa-clock"></i> Arrival: <?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></span>
                                  </div>
                                  <div class="card-price">$<?php echo $bus['price']; ?> per person</div>
                                  <div class="card-total">$<?php echo number_format($bus['price'] * $guests, 2); ?> total for <?php echo $guests; ?> <?php echo $guests > 1 ? 'passengers' : 'passenger'; ?></div>
                                  <a href="bus-details.php?id=<?php echo $bus['id']; ?>" class="btn">View Details</a>
                                  <a href="booking.php?bus_id=<?php echo $bus['id']; ?>&travel_date=<?php echo $travel_date; ?>&passengers=<?php echo $guests; ?>" class="btn btn-primary">Book Now</a>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
          </div>
      <?php endif; ?>
  </div>
</section>

<style>
/* Additional styles for search results page */
.search-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f8f9fa;
  padding: 15px 20px;
  border-radius: 8px;
  margin-bottom: 30px;
}

.search-details {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.search-detail {
  display: flex;
  align-items: center;
}

.search-detail i {
  color: #3a86ff;
  margin-right: 8px;
}

.result-section {
  margin-bottom: 40px;
}

.result-section h3 {
  font-size: 1.6rem;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.card-location, .card-route {
  color: #666;
  margin-bottom: 10px;
}

.card-location i, .card-route i {
  color: #3a86ff;
  margin-right: 5px;
}

.card-time {
  display: flex;
  flex-direction: column;
  gap: 5px;
  color: #666;
  margin-bottom: 10px;
}

.card-time i {
  color: #3a86ff;
  margin-right: 5px;
}

.card-total {
  font-weight: 600;
  margin-bottom: 15px;
  color: #28a745;
}

.btn-primary {
  background-color: #28a745;
  margin-left: 10px;
}

.btn-primary:hover {
  background-color: #218838;
}

/* Combo deal styles */
.combo-card {
  border: 2px solid #3a86ff;
  margin-bottom: 20px;
}

.combo-header {
  background-color: #3a86ff;
  color: white;
  padding: 15px;
  position: relative;
}

.combo-badge {
  position: absolute;
  top: -10px;
  right: 10px;
  background-color: #ff3a86;
  color: white;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.combo-title {
  margin: 0;
  font-size: 1.3rem;
}

.combo-content {
  padding: 20px;
}

.combo-item {
  display: flex;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.combo-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.combo-icon {
  font-size: 2rem;
  color: #3a86ff;
  margin-right: 15px;
  display: flex;
  align-items: center;
}

.combo-details h4 {
  margin: 0 0 5px 0;
  font-size: 1.1rem;
}

.combo-details p {
  margin: 0 0 5px 0;
  color: #666;
}

.combo-price {
  font-weight: 600;
  color: #3a86ff;
}

.combo-footer {
  background-color: #f8f9fa;
  padding: 15px;
  border-top: 1px solid #eee;
}

.combo-pricing {
  margin-bottom: 15px;
}

.combo-original-price {
  text-decoration: line-through;
  color: #666;
  font-size: 0.9rem;
}

.combo-total-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #3a86ff;
}

.combo-savings {
  color: #28a745;
  font-weight: 600;
}

@media (max-width: 768px) {
  .search-summary {
      flex-direction: column;
      align-items: flex-start;
  }
  
  .search-actions {
      margin-top: 15px;
      width: 100%;
  }
  
  .search-actions .btn {
      width: 100%;
  }
  
  .combo-item {
      flex-direction: column;
  }
  
  .combo-icon {
      margin-right: 0;
      margin-bottom: 10px;
  }
}
</style>

<script>
function toggleSearchForm() {
  const searchForm = document.getElementById('search-form');
  if (searchForm.style.display === 'none') {
      searchForm.style.display = 'block';
  } else {
      searchForm.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Set min date to today for check-in
  const today = new Date().toISOString().split('T')[0];
  const checkInInput = document.getElementById('check-in');
  const checkOutInput = document.getElementById('check-out');
  
  if (checkInInput) {
      checkInInput.setAttribute('min', today);
      
      // Update check-out min date when check-in changes
      checkInInput.addEventListener('change', function() {
          const checkInDate = new Date(this.value);
          const nextDay = new Date(checkInDate);
          nextDay.setDate(checkInDate.getDate() + 1);
          
          const nextDayStr = nextDay.toISOString().split('T')[0];
          checkOutInput.setAttribute('min', nextDayStr);
          
          // If check-out date is before check-in date, reset it
          if (checkOutInput.value && new Date(checkOutInput.value) <= checkInDate) {
              checkOutInput.value = nextDayStr;
          }
      });
  }
});
</script>

<?php include 'includes/footer.php'; ?>

