<?php
include 'config/database.php';
include 'includes/header.php';

// Default values for date ranges and guests
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$next_week = date('Y-m-d', strtotime('+7 days'));
$check_in = $today;
$check_out = $tomorrow;
$travel_date = $today;
$guests = 2;
$nights = 1;

// Fetch all active hotels
$hotels = [];
$sql = "SELECT * FROM hotels WHERE status = 'active'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}

// Fetch all active buses
$buses = [];
$sql = "SELECT * FROM buses WHERE status = 'active'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $buses[] = $row;
    }
}

// For demonstration, create sample data if database is empty
if (empty($hotels)) {
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
        ],
        [
            'id' => 4,
            'name' => 'Kathmandu Grand Hotel',
            'description' => 'Luxury hotel in the heart of Kathmandu with easy access to major attractions.',
            'location' => 'Kathmandu',
            'image_url' => '/placeholder.svg?height=200&width=300',
            'price_per_night' => 129.99,
            'total_rooms' => 60,
            'available_rooms' => 40
        ],
        [
            'id' => 5,
            'name' => 'Thamel Boutique Inn',
            'description' => 'Charming boutique hotel in the popular Thamel district.',
            'location' => 'Kathmandu',
            'image_url' => '/placeholder.svg?height=200&width=300',
            'price_per_night' => 79.99,
            'total_rooms' => 25,
            'available_rooms' => 15
        ]
    ];
}

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
        ],
        [
            'id' => 4,
            'name' => 'Mountain Express',
            'description' => 'Comfortable journey through scenic mountain routes.',
            'departure_location' => 'Kathmandu',
            'arrival_location' => 'Chitwan',
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
            'departure_location' => 'Chitwan',
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

// Create combo packages by pairing hotels and buses
$combos = [];
$locations = ['Kathmandu', 'Pokhara', 'Chitwan']; // Common locations

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

// If no combos were created, create some default ones
if (empty($combos)) {
    // Create some default combos between major destinations
    foreach ($hotels as $hotel) {
        foreach ($buses as $bus) {
            // Create at least one combo for each hotel
            $combos[] = [
                'hotel' => $hotel,
                'bus' => $bus
            ];
            break; // Just one bus per hotel for defaults
        }
    }
}

// Group combos by destination for better organization
$combos_by_destination = [];
foreach ($combos as $combo) {
    $destination = $combo['hotel']['location'];
    if (!isset($combos_by_destination[$destination])) {
        $combos_by_destination[$destination] = [];
    }
    $combos_by_destination[$destination][] = $combo;
}
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Travel Packages</h2>
            <p>Exclusive hotel and bus combo deals for a seamless travel experience</p>
        </div>
        
        <div class="package-filters">
            <div class="filter-group">
                <label for="destination-filter">Filter by Destination:</label>
                <select id="destination-filter" class="form-control">
                    <option value="all">All Destinations</option>
                    <?php foreach (array_keys($combos_by_destination) as $destination): ?>
                        <option value="<?php echo strtolower($destination); ?>"><?php echo $destination; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-by">Sort by:</label>
                <select id="sort-by" class="form-control">
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="name">Hotel Name</option>
                </select>
            </div>
        </div>
        
        <?php foreach ($combos_by_destination as $destination => $destination_combos): ?>
            <div class="destination-section" data-destination="<?php echo strtolower($destination); ?>">
                <h3 class="destination-title"><?php echo $destination; ?> Packages</h3>
                
                <div class="grid">
                    <?php foreach ($destination_combos as $index => $combo): ?>
                        <?php 
                        $hotel = $combo['hotel'];
                        $bus = $combo['bus'];
                        $hotel_total = $hotel['price_per_night'] * $nights * $guests;
                        $bus_total = $bus['price'] * $guests;
                        $total_price = $hotel_total + $bus_total;
                        $original_price = ($hotel['price_per_night'] * $nights * 1.05) + ($bus['price'] * $guests * 1.05);
                        $savings = $original_price - $total_price;
                        ?>
                        <div class="card combo-card" data-price="<?php echo $total_price; ?>" data-name="<?php echo strtolower($hotel['name']); ?>">
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
                                        <p class="combo-price">$<?php echo $hotel['price_per_night']; ?> per night</p>
                                        <a href="hotel-details.php?id=<?php echo $hotel['id']; ?>" class="view-details">View Hotel Details</a>
                                    </div>
                                </div>
                                <div class="combo-item">
                                    <div class="combo-icon">
                                        <i class="fas fa-bus"></i>
                                    </div>
                                    <div class="combo-details">
                                        <h4><?php echo $bus['name']; ?></h4>
                                        <p><i class="fas fa-route"></i> <?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?></p>
                                        <p class="combo-price">$<?php echo $bus['price']; ?> per person</p>
                                        <a href="bus-details.php?id=<?php echo $bus['id']; ?>" class="view-details">View Bus Details</a>
                                    </div>
                                </div>
                            </div>
                            <div class="combo-footer">
                                <div class="combo-pricing">
                                    <div class="combo-original-price">Original: $<?php echo number_format($original_price, 2); ?></div>
                                    <div class="combo-total-price">Combo Price: $<?php echo number_format($total_price, 2); ?></div>
                                    <div class="combo-savings">You save: $<?php echo number_format($savings, 2); ?></div>
                                </div>
                                <div class="date-selection">
                                    <div class="form-group">
                                        <label for="check-in-<?php echo $index; ?>">Check-in Date:</label>
                                        <input type="date" id="check-in-<?php echo $index; ?>" class="form-control check-in-date" min="<?php echo $today; ?>" value="<?php echo $check_in; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="check-out-<?php echo $index; ?>">Check-out Date:</label>
                                        <input type="date" id="check-out-<?php echo $index; ?>" class="form-control check-out-date" min="<?php echo $tomorrow; ?>" value="<?php echo $check_out; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="guests-<?php echo $index; ?>">Guests:</label>
                                        <select id="guests-<?php echo $index; ?>" class="form-control guests-select">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == $guests ? 'selected' : ''; ?>><?php echo $i; ?> <?php echo $i > 1 ? 'Guests' : 'Guest'; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <a href="javascript:void(0);" class="btn btn-block btn-primary book-combo-btn" 
                                   data-hotel-id="<?php echo $hotel['id']; ?>" 
                                   data-bus-id="<?php echo $bus['id']; ?>" 
                                   data-index="<?php echo $index; ?>">Book This Package</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($combos)): ?>
            <div class="alert alert-info">
                <p>No travel packages available at the moment. Please check back later or contact our customer service.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Styles for packages page */
.package-filters {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.filter-group {
    display: flex;
    align-items: center;
}

.filter-group label {
    margin-right: 10px;
    font-weight: 600;
}

.destination-title {
    font-size: 1.8rem;
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #3a86ff;
}

.combo-card {
    border: 2px solid #3a86ff;
    margin-bottom: 30px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.combo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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

.view-details {
    display: inline-block;
    margin-top: 5px;
    color: #3a86ff;
    text-decoration: none;
    font-size: 0.9rem;
}

.view-details:hover {
    text-decoration: underline;
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

.date-selection {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.btn-primary {
    background-color: #28a745;
    color: white;
}

.btn-primary:hover {
    background-color: #218838;
}

@media (max-width: 768px) {
    .package-filters {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .filter-group:last-child {
        margin-bottom: 0;
    }
    
    .combo-item {
        flex-direction: column;
    }
    
    .combo-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .date-selection {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set min date to today for all check-in dates
    const today = new Date().toISOString().split('T')[0];
    const checkInInputs = document.querySelectorAll('.check-in-date');
    const checkOutInputs = document.querySelectorAll('.check-out-date');
    
    checkInInputs.forEach((input, index) => {
        input.setAttribute('min', today);
        
        // Update check-out min date when check-in changes
        input.addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const nextDay = new Date(checkInDate);
            nextDay.setDate(checkInDate.getDate() + 1);
            
            const nextDayStr = nextDay.toISOString().split('T')[0];
            const correspondingCheckOut = checkOutInputs[index];
            correspondingCheckOut.setAttribute('min', nextDayStr);
            
            // If check-out date is before check-in date, reset it
            if (correspondingCheckOut.value && new Date(correspondingCheckOut.value) <= checkInDate) {
                correspondingCheckOut.value = nextDayStr;
            }
        });
    });
    
    // Handle destination filtering
    const destinationFilter = document.getElementById('destination-filter');
    destinationFilter.addEventListener('change', function() {
        const selectedDestination = this.value;
        const destinationSections = document.querySelectorAll('.destination-section');
        
        destinationSections.forEach(section => {
            if (selectedDestination === 'all' || section.dataset.destination === selectedDestination) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });
    
    // Handle sorting
    const sortBySelect = document.getElementById('sort-by');
    sortBySelect.addEventListener('change', function() {
        const sortValue = this.value;
        const destinationSections = document.querySelectorAll('.destination-section');
        
        destinationSections.forEach(section => {
            const cards = Array.from(section.querySelectorAll('.combo-card'));
            
            cards.sort((a, b) => {
                if (sortValue === 'price-low') {
                    return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                } else if (sortValue === 'price-high') {
                    return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                } else if (sortValue === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                }
                return 0;
            });
            
            const grid = section.querySelector('.grid');
            cards.forEach(card => grid.appendChild(card));
        });
    });
    
    // Handle booking button clicks
    const bookButtons = document.querySelectorAll('.book-combo-btn');
    bookButtons.forEach(button => {
        button.addEventListener('click', function() {
            const hotelId = this.dataset.hotelId;
            const busId = this.dataset.busId;
            const index = this.dataset.index;
            
            const checkInDate = document.getElementById(`check-in-${index}`).value;
            const checkOutDate = document.getElementById(`check-out-${index}`).value;
            const guestsCount = document.getElementById(`guests-${index}`).value;
            
            <?php if(isset($_SESSION['user_id'])): ?>
                // User is logged in, proceed to booking
                window.location.href = `booking.php?hotel_id=${hotelId}&bus_id=${busId}&check_in=${checkInDate}&check_out=${checkOutDate}&travel_date=${checkInDate}&guests=${guestsCount}&passengers=${guestsCount}`;
            <?php else: ?>
                // User is not logged in, redirect to login
                alert('Please log in to book this package.');
                window.location.href = `login.php?redirect=packages`;
            <?php endif; ?>
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
