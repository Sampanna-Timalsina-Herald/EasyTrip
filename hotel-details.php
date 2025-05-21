<?php
include 'config/database.php';
include 'includes/header.php';

// Get hotel ID from URL
$hotel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch hotel details
$hotel = null;
if ($hotel_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $hotel = $result->fetch_assoc();
    }
    $stmt->close();
}

// For demonstration, create sample data if database is empty
if (!$hotel && $hotel_id > 0) {
    $hotel = [
        'id' => $hotel_id,
        'name' => 'Luxury Hotel & Spa',
        'description' => 'Experience luxury at its finest with our premium amenities and services. Our hotel features spacious rooms, a full-service spa, fitness center, and multiple dining options.',
        'location' => 'Beach City',
        'image_url' => '/placeholder.svg?height=400&width=800',
        'price_per_night' => 199,
        'total_rooms' => 50,
        'available_rooms' => 15
    ];
}

// Fetch hotel amenities
$amenities = [];
if ($hotel_id > 0) {
    // Get distinct amenities for all rooms in this hotel
    $stmt = $conn->prepare("
        SELECT DISTINCT ra.amenity_name 
        FROM room_amenities ra
        INNER JOIN hotel_rooms hr ON ra.room_id = hr.id
        WHERE hr.hotel_id = ?
    ");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $amenities[] = $row['amenity_name'];
        }
    }
    $stmt->close();
}

// For demonstration, create sample amenities if none exist
if (empty($amenities)) {
    $amenities = ['Free WiFi', 'Swimming Pool', 'Fitness Center', 'Restaurant', 
                 'Room Service', 'Parking', 'Air Conditioning', 'Spa'];
}

// Fetch available room types
$room_types = [];
if ($hotel_id > 0) {
    $result = $conn->query("SELECT * FROM hotel_rooms WHERE hotel_id = $hotel_id AND status = 'available' GROUP BY room_type");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $room_types[] = $row;
        }
    }
}

// For demonstration, create sample room types if none exist
if (empty($room_types)) {
    $room_types = [
        [
            'room_type' => 'Standard',
            'price_per_night' => $hotel['price_per_night'],
            'description' => 'Comfortable room with all basic amenities.'
        ],
        [
            'room_type' => 'Deluxe',
            'price_per_night' => $hotel['price_per_night'] + 50,
            'description' => 'Spacious room with premium amenities and city view.'
        ],
        [
            'room_type' => 'Suite',
            'price_per_night' => $hotel['price_per_night'] + 100,
            'description' => 'Luxury suite with separate living area and ocean view.'
        ]
    ];
}
?>

<section class="section">
    <div class="container">
        <div class="back-button-container">
            <a href="hotels.php" class="btn"><i class="fas fa-arrow-left"></i> Back to My Hotels</a>
        </div>
        
        <?php if ($hotel): ?>
            <div class="hotel-details">
                <div class="hotel-header">
                    <h1><?php echo $hotel['name']; ?></h1>
                    <p class="hotel-location"><i class="fas fa-map-marker-alt"></i> <?php echo $hotel['location']; ?></p>
                </div>
                
                <div class="hotel-gallery">
                    <div class="hotel-img">
                        <?php 
                        // Process the image URL to use format: uploads/filename.ext (without leading slash)
                        $image_url = '';
                        if (!empty($hotel['image_url'])) {
                            // Extract just the filename from the path
                            $filename = basename($hotel['image_url']);
                            // Create the path in format uploads/filename.ext (no leading slash)
                            $image_url = "uploads/" . $filename;
                        }
                        ?>
                        
                        <?php if (!empty($hotel['image_url'])): ?>
                            <img src="<?php echo $image_url; ?>" alt="<?php echo $hotel['name']; ?>">
                        <?php else: ?>
                            <img src="../assets/images/hotel-placeholder.jpg" alt="Hotel Image">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="hotel-info">
                    <div class="hotel-description">
                        <h2>About This Hotel</h2>
                        <p><?php echo $hotel['description']; ?></p>
                        
                        <div class="hotel-amenities">
                            <h3>Amenities</h3>
                            <ul class="amenities-list">
                                <?php foreach ($amenities as $amenity): ?>
                                    <li><i class="fas fa-check"></i> <?php echo $amenity; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="hotel-booking">
                        <div class="booking-card">
                            <h3>Book Your Stay</h3>
                            <div class="price-info">
                                <span class="price">NPR <?php echo $hotel['price_per_night']; ?></span>
                                <span class="per-night">per night</span>
                            </div>
                            
                            <form action="booking.php" method="GET">
                                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="check-in">Check-in Date</label>
                                    <input type="date" id="check-in" name="check_in" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="check-out">Check-out Date</label>
                                    <input type="date" id="check-out" name="check_out" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="guests">Guests</label>
                                    <select id="guests" name="guests" class="form-control">
                                        <option value="1">1 Guest</option>
                                        <option value="2">2 Guests</option>
                                        <option value="3">3 Guests</option>
                                        <option value="4">4 Guests</option>
                                        <option value="5">5+ Guests</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="room-type">Room Type</label>
                                    <select id="room-type" name="room_type" class="form-control">
                                        <?php foreach ($room_types as $room): ?>
                                            <option value="<?php echo $room['room_type']; ?>">
                                                <?php echo $room['room_type']; ?> - NPR <?php echo $room['price_per_night']; ?>/night
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="availability-info">
                                    <p><i class="fas fa-info-circle"></i> <?php echo $hotel['available_rooms']; ?> rooms available for your dates</p>
                                </div>
                                
                                <button type="submit" class="btn btn-block">Book Now</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="hotel-reviews">
                    <h2>Guest Reviews</h2>
                    <!-- Sample reviews for demonstration -->
                    <div class="review">
                        <div class="review-header">
                            <div class="reviewer">John D.</div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                        <div class="review-content">
                            <p>Great hotel with excellent service. The rooms were clean and comfortable. Would definitely stay here again!</p>
                        </div>
                    </div>
                    
                    <div class="review">
                        <div class="review-header">
                            <div class="reviewer">Sarah M.</div>
                            <div class="rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="review-content">
                            <p>Absolutely loved my stay here! The staff was friendly and the amenities were top-notch. The location is perfect for exploring the city.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Hotel not found. Please go back to the <a href="hotels.php">hotels page</a> and select a valid hotel.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Additional styles for hotel details page */
.back-button-container {
    margin-bottom: 20px;
}

.hotel-details {
    margin-bottom: 40px;
}

.hotel-header {
    margin-bottom: 20px;
}

.hotel-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.hotel-location {
    color: #666;
    font-size: 1.1rem;
}

.hotel-gallery {
    margin-bottom: 30px;
}

.hotel-img img {
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    max-height: 500px;
    object-fit: cover;
}

.hotel-info {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.hotel-description h2, .hotel-reviews h2 {
    font-size: 1.8rem;
    margin-bottom: 20px;
}

.hotel-description p {
    line-height: 1.7;
    margin-bottom: 20px;
}

.hotel-amenities h3 {
    font-size: 1.4rem;
    margin-bottom: 15px;
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

.per-night {
    color: #666;
}

.availability-info {
    margin: 15px 0;
    color: #666;
}

.availability-info i {
    color: #3a86ff;
}

.hotel-reviews {
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
    .hotel-info {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
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