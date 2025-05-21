<?php
include 'config/database.php';
include 'includes/header.php';

// Get destination filter if provided
$destination_filter = isset($_GET['location']) ? trim($_GET['location']) : 
                     (isset($_GET['destination']) ? trim($_GET['destination']) : '');

// Get all active hotels
$hotel_sql = "SELECT * FROM hotels WHERE status = 'active'";
if (!empty($destination_filter)) {
    $destination_param = "%$destination_filter%";
    $hotel_sql .= " AND (location LIKE ? OR name LIKE ?)";
}

$hotel_stmt = $conn->prepare($hotel_sql);
if (!empty($destination_filter)) {
    $hotel_stmt->bind_param("ss", $destination_param, $destination_param);
}
$hotel_stmt->execute();
$hotels_result = $hotel_stmt->get_result();
$hotels = [];
while ($row = $hotels_result->fetch_assoc()) {
    $hotels[] = $row;
}
$hotel_stmt->close();

// Get all active buses
$bus_sql = "SELECT * FROM buses WHERE status = 'active'";
$bus_stmt = $conn->prepare($bus_sql);
$bus_stmt->execute();
$buses_result = $bus_stmt->get_result();
$buses = [];
while ($row = $buses_result->fetch_assoc()) {
    $buses[] = $row;
}
$bus_stmt->close();

// Get all destinations for filter dropdown
$destinations_sql = "SELECT DISTINCT location FROM hotels WHERE status = 'active' ORDER BY location";
$destinations_result = $conn->query($destinations_sql);
$destinations = [];
while ($row = $destinations_result->fetch_assoc()) {
    $destinations[] = $row['location'];
}

// Create combo packages by pairing hotels and buses
$combos = [];
if (!empty($hotels) && !empty($buses)) {
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

// Set default values for dates and guests
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$default_check_in = $today;
$default_check_out = $tomorrow;
$default_guests = 2;
$default_nights = 1;

// If no combos found, create sample data for demonstration
if (empty($combos)) {
    $sample_hotels = [
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
    
    $sample_buses = [
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
    
    // Create sample combos
    foreach ($sample_hotels as $hotel) {
        foreach ($sample_buses as $bus) {
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

// Group combos by destination for better organization
$combos_by_destination = [];
if (!empty($destination_filter)) {
    // If a specific destination is requested, only include that destination
    foreach ($combos as $combo) {
        $destination = $combo['hotel']['location'];
        if (stripos($destination, $destination_filter) !== false) {
            if (!isset($combos_by_destination[$destination])) {
                $combos_by_destination[$destination] = [];
            }
            $combos_by_destination[$destination][] = $combo;
        }
    }
} else {
    // Otherwise include all destinations
    foreach ($combos as $combo) {
        $destination = $combo['hotel']['location'];
        if (!isset($combos_by_destination[$destination])) {
            $combos_by_destination[$destination] = [];
        }
        $combos_by_destination[$destination][] = $combo;
    }
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <?php if (!empty($destination_filter)): ?>
                <h2>Travel Packages in <?php echo ucfirst($destination_filter); ?></h2>
                <p>Find the perfect hotel and transportation combo for your journey to <?php echo ucfirst($destination_filter); ?></p>
            <?php else: ?>
                <h2>Travel Packages</h2>
                <p>Find the perfect hotel and transportation combo for your journey</p>
            <?php endif; ?>
        </div>
        
        <!-- Simple filter options -->
        <div class="filter-options">
            <form action="hotels.php" method="GET" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="destination">Filter by Destination:</label>
                        <select id="destination" name="destination" class="form-control" onchange="this.form.submit()">
                            <option value="">All Destinations</option>
                            <?php foreach ($destinations as $destination): ?>
                                <option value="<?php echo htmlspecialchars($destination); ?>" 
                                    <?php echo (strtolower($destination_filter) == strtolower($destination) || 
                                                stripos(strtolower($destination), strtolower($destination_filter)) !== false) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($destination); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Sort by:</label>
                        <select id="sort" name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="name_asc">Hotel Name: A-Z</option>
                            <option value="name_desc">Hotel Name: Z-A</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (empty($combos_by_destination)): ?>
            <div class="alert alert-info">
                <p>No travel packages found. Please try a different filter or check back later.</p>
            </div>
        <?php else: ?>
            <?php foreach ($combos_by_destination as $destination => $destination_combos): ?>
                <div class="destination-section">
                    <h3 class="destination-title"><?php echo htmlspecialchars($destination); ?></h3>
                    
                    <div class="grid">
                        <?php foreach ($destination_combos as $combo): ?>
                            <?php 
                            $hotel = $combo['hotel'];
                            $bus = $combo['bus'];
                            $hotel_total = $hotel['price_per_night'] * $default_nights * $default_guests;
                            $bus_total = $bus['price'] * $default_guests;
                            $total_price = $hotel_total + $bus_total;
                            $original_price = ($hotel['price_per_night'] * $default_nights * 1.05) + ($bus['price'] * $default_guests * 1.05);
                            $savings = $original_price - $total_price;
                            ?>
                            <div class="card combo-card">
                                <div class="combo-header">
                                    <span class="combo-badge">Combo Deal</span>
                                    <h3 class="combo-title"><?php echo htmlspecialchars($hotel['name']); ?> + <?php echo htmlspecialchars($bus['name']); ?></h3>
                                </div>
                                <div class="combo-content">
                                    <div class="combo-item">
                                        <div class="combo-icon">
                                            <i class="fas fa-hotel"></i>
                                        </div>
                                        <div class="combo-details">
                                            <h4><?php echo htmlspecialchars($hotel['name']); ?></h4>
                                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
                                            <p class="combo-description"><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</p>
                                            <p class="combo-price">NPR <?php echo number_format($hotel['price_per_night'], 2); ?> per night</p>
                                            <a href="hotel-details.php?id=<?php echo $hotel['id']; ?>" class="btn btn-sm">Hotel Details</a>
                                        </div>
                                    </div>
                                    <div class="combo-item">
                                        <div class="combo-icon">
                                            <i class="fas fa-bus"></i>
                                        </div>
                                        <div class="combo-details">
                                            <h4><?php echo htmlspecialchars($bus['name']); ?></h4>
                                            <p><i class="fas fa-route"></i> <?php echo htmlspecialchars($bus['departure_location']); ?> to <?php echo htmlspecialchars($bus['arrival_location']); ?></p>
                                            <p><i class="fas fa-clock"></i> Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?></p>
                                            <p class="combo-price">NPR <?php echo number_format($bus['price'], 2); ?> per person</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="combo-booking">
                                    <form action="booking.php" method="GET" class="booking-form">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                        <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                        
                                        <div class="booking-dates">
                                            <div class="form-group">
                                                <label for="check_in_<?php echo $hotel['id'] . '_' . $bus['id']; ?>">Check-in</label>
                                                <input type="date" id="check_in_<?php echo $hotel['id'] . '_' . $bus['id']; ?>" name="check_in" class="form-control" value="<?php echo $default_check_in; ?>" min="<?php echo $today; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="check_out_<?php echo $hotel['id'] . '_' . $bus['id']; ?>">Check-out</label>
                                                <input type="date" id="check_out_<?php echo $hotel['id'] . '_' . $bus['id']; ?>" name="check_out" class="form-control" value="<?php echo $default_check_out; ?>" min="<?php echo $tomorrow; ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="guests_<?php echo $hotel['id'] . '_' . $bus['id']; ?>">Guests</label>
                                                <select id="guests_<?php echo $hotel['id'] . '_' . $bus['id']; ?>" name="guests" class="form-control">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $default_guests == $i ? 'selected' : ''; ?>>
                                                            <?php echo $i; ?> <?php echo $i > 1 ? 'Guests' : 'Guest'; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <input type="hidden" name="travel_date" value="<?php echo $default_check_in; ?>">
                                            <input type="hidden" name="passengers" value="<?php echo $default_guests; ?>">
                                        </div>
                                        
                                        <div class="combo-footer">
                                            <div class="combo-pricing">
                                                <div class="combo-original-price">Original: NPR <?php echo number_format($original_price, 2); ?></div>
                                                <div class="combo-total-price">Combo Price: NPR <?php echo number_format($total_price, 2); ?></div>
                                                <div class="combo-savings">You save: NPR <?php echo number_format($savings, 2); ?></div>
                                            </div>
                                            <button type="submit" class="btn btn-block btn-primary">Book This Package</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<style>
/* Additional styles for combo packages */
.filter-options {
    margin-bottom: 30px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.destination-section {
    margin-bottom: 40px;
}

.destination-title {
    font-size: 1.8rem;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #3a86ff;
}

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

.combo-description {
    margin: 10px 0;
    font-style: italic;
}

.combo-price {
    font-weight: 600;
    color: #3a86ff;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
    margin-top: 5px;
}

.combo-booking {
    background-color: #f8f9fa;
    padding: 15px;
    border-top: 1px solid #eee;
}

.booking-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.booking-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.booking-dates .form-group {
    flex: 1;
    min-width: 120px;
}

.combo-footer {
    margin-top: 15px;
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

.btn-primary {
    background-color: #28a745;
}

.btn-primary:hover {
    background-color: #218838;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .combo-item {
        flex-direction: column;
    }
    
    .combo-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .booking-dates {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set min date for all check-in inputs
    const today = new Date().toISOString().split('T')[0];
    const checkInInputs = document.querySelectorAll('input[name="check_in"]');
    const checkOutInputs = document.querySelectorAll('input[name="check_out"]');
    
    checkInInputs.forEach(function(input, index) {
        input.setAttribute('min', today);
        
        // Update check-out min date when check-in changes
        input.addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const nextDay = new Date(checkInDate);
            nextDay.setDate(checkInDate.getDate() + 1);
            
            const nextDayStr = nextDay.toISOString().split('T')[0];
            checkOutInputs[index].setAttribute('min', nextDayStr);
            
            // If check-out date is before check-in date, reset it
            if (checkOutInputs[index].value && new Date(checkOutInputs[index].value) <= checkInDate) {
                checkOutInputs[index].value = nextDayStr;
            }
            
            // Also update the travel_date hidden input to match check-in
            const form = this.closest('form');
            const travelDateInput = form.querySelector('input[name="travel_date"]');
            if (travelDateInput) {
                travelDateInput.value = this.value;
            }
        });
    });
    
    // Update passengers count when guests change
    const guestsSelects = document.querySelectorAll('select[name="guests"]');
    guestsSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const passengersInput = form.querySelector('input[name="passengers"]');
            if (passengersInput) {
                passengersInput.value = this.value;
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
