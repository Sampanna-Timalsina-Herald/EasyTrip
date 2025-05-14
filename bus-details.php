<?php
include 'config/database.php';
include 'includes/header.php';

// Get bus ID from URL
$bus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch bus details
$bus = null;
if ($bus_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $bus = $result->fetch_assoc();
    }
    $stmt->close();
}

// For demonstration, create sample data if database is empty
if (!$bus && $bus_id > 0) {
    $bus = [
        'id' => $bus_id,
        'name' => 'Express Deluxe',
        'description' => 'Luxury bus service with comfortable seating, WiFi, and refreshments. Enjoy a smooth journey with our professional drivers and modern fleet.',
        'departure_location' => 'New York',
        'arrival_location' => 'Boston',
        'departure_time' => '08:00:00',
        'arrival_time' => '12:30:00',
        'image_url' => '/placeholder.svg?height=400&width=800',
        'price' => 45,
        'total_seats' => 40,
        'available_seats' => 25
    ];
}

// Fetch bus amenities
$amenities = [];
if ($bus_id > 0) {
    $result = $conn->query("SELECT * FROM bus_amenities WHERE bus_id = $bus_id");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $amenities[] = $row['name'];
        }
    }
}

// For demonstration, create sample amenities if none exist
if (empty($amenities)) {
    $amenities = ['WiFi', 'Air Conditioning', 'Reclining Seats', 'Charging Ports', 'Refreshments', 'Restroom', 'Entertainment System'];
}

// Calculate journey duration
$duration = '';
if ($bus) {
    $departure = new DateTime($bus['departure_time']);
    $arrival = new DateTime($bus['arrival_time']);
    
    // Handle overnight journeys
    if ($arrival < $departure) {
        $arrival->modify('+1 day');
    }
    
    $interval = $departure->diff($arrival);
    $hours = $interval->h;
    $minutes = $interval->i;
    
    if ($interval->d > 0) {
        $hours += $interval->d * 24;
    }
    
    $duration = $hours . 'h ' . $minutes . 'm';
}
?>

<section class="section">
    <div class="container">
        <?php if ($bus): ?>
            <div class="bus-details">
                <div class="bus-header">
                    <h1><?php echo $bus['name']; ?></h1>
                    <p class="bus-route"><i class="fas fa-route"></i> <?php echo $bus['departure_location']; ?> to <?php echo $bus['arrival_location']; ?></p>
                </div>
                
                <div class="bus-gallery">
                    <img src="<?php echo $bus['image_url'] ? $bus['image_url'] : '/placeholder.svg?height=400&width=800'; ?>" alt="<?php echo $bus['name']; ?>" class="main-image">
                </div>
                
                <div class="bus-info">
                    <div class="bus-description">
                        <h2>About This Bus Service</h2>
                        <p><?php echo $bus['description']; ?></p>
                        
                        <div class="journey-details">
                            <h3>Journey Details</h3>
                            <div class="journey-info-grid">
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> From</div>
                                    <div class="info-value"><?php echo $bus['departure_location']; ?></div>
                                </div>
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> To</div>
                                    <div class="info-value"><?php echo $bus['arrival_location']; ?></div>
                                </div>
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-clock"></i> Departure</div>
                                    <div class="info-value"><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></div>
                                </div>
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-clock"></i> Arrival</div>
                                    <div class="info-value"><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></div>
                                </div>
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-hourglass-half"></i> Duration</div>
                                    <div class="info-value"><?php echo $duration; ?></div>
                                </div>
                                <div class="journey-info-item">
                                    <div class="info-label"><i class="fas fa-chair"></i> Available Seats</div>
                                    <div class="info-value"><?php echo $bus['available_seats']; ?> / <?php echo $bus['total_seats']; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bus-amenities">
                            <h3>Amenities</h3>
                            <ul class="amenities-list">
                                <?php foreach ($amenities as $amenity): ?>
                                    <li><i class="fas fa-check"></i> <?php echo $amenity; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bus-booking">
                        <div class="booking-card">
                            <h3>Book Your Journey</h3>
                            <div class="price-info">
                                <span class="price">$<?php echo $bus['price']; ?></span>
                                <span class="per-person">per person</span>
                            </div>
                            
                            <form action="booking.php" method="GET">
                                <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="travel-date">Travel Date</label>
                                    <input type="date" id="travel-date" name="travel_date" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="passengers">Passengers</label>
                                    <select id="passengers" name="passengers" class="form-control">
                                        <option value="1">1 Passenger</option>
                                        <option value="2">2 Passengers</option>
                                        <option value="3">3 Passengers</option>
                                        <option value="4">4 Passengers</option>
                                        <option value="5">5+ Passengers</option>
                                    </select>
                                </div>
                                
                                <div class="availability-info">
                                    <p><i class="fas fa-info-circle"></i> <?php echo $bus['available_seats']; ?> seats available for your journey</p>
                                </div>
                                
                                <button type="submit" class="btn btn-block">Book Now</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="bus-reviews">
                    <h2>Passenger Reviews</h2>
                    <!-- Sample reviews for demonstration -->
                    <div class="review">
                        <div class="review-header">
                            <div class="reviewer">Michael T.</div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="review-content">
                            <p>Excellent service! The bus was clean, comfortable, and arrived on time. The staff was very professional and helpful.</p>
                        </div>
                    </div>
                    
                    <div class="review">
                        <div class="review-header">
                            <div class="reviewer">Lisa R.</div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                        </div>
                        <div class="review-content">
                            <p>Very comfortable journey with good amenities. WiFi was a bit slow but overall a great experience. Would recommend!</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Bus not found. Please go back to the <a href="buses.php">buses page</a> and select a valid bus.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Additional styles for bus details page */
.bus-details {
    margin-bottom: 40px;
}

.bus-header {
    margin-bottom: 20px;
}

.bus-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.bus-route {
    color: #666;
    font-size: 1.1rem;
}

.bus-gallery {
    margin-bottom: 30px;
}

.main-image {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.bus-info {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.bus-description h2, .bus-reviews h2 {
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.bus-description p {
    line-height: 1.7;
    margin-bottom: 20px;
}

.journey-details h3, .bus-amenities h3 {
    font-size: 1.4rem;
    margin-bottom: 15px;
}

.journey-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.journey-info-item {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.info-label {
    color: #666;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.info-label i {
    margin-right: 5px;
    color: #3a86ff;
}

.info-value {
    font-weight: 600;
    font-size: 1.1rem;
}

.amenities-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.amenities-list li {
    padding: 5px 0;
}

.amenities-list li i {
    color: #3a86ff;
    margin-right: 10px;
}

.booking-card {
    background-color: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.booking-card h3 {
    font-size: 1.4rem;
    margin-bottom: 15px;
}

.price-info {
    margin-bottom: 20px;
}

.price {
    font-size: 1.8rem;
    font-weight: 700;
    color: #3a86ff;
}

.per-person {
    color: #666;
}

.availability-info {
    margin: 15px 0;
    color: #666;
}

.availability-info i {
    color: #3a86ff;
}

.bus-reviews {
    margin-top: 40px;
}

.review {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.reviewer {
    font-weight: 600;
}

.rating {
    color: #ffc107;
}

@media (max-width: 768px) {
    .bus-info {
        grid-template-columns: 1fr;
    }
    
    .journey-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set min date to today for travel date
    const today = new Date().toISOString().split('T')[0];
    const travelDateInput = document.getElementById('travel-date');
    
    if (travelDateInput) {
        travelDateInput.setAttribute('min', today);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
