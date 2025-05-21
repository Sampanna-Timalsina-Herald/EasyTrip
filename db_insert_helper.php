<?php
// This is a helper script to insert sample data into your database
// Run this script once to populate your database with sample data

include 'config/database.php';

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Population Script</h2>";

// Function to execute SQL queries
function executeQuery($conn, $sql, $description) {
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ $description successfully!</p>";
        return true;
    } else {
        echo "<p>❌ Error $description: " . $conn->error . "</p>";
        return false;
    }
}

// Insert sample hotels
$hotels = [
    [2, 'Pokhara Luxury Resort', 'Experience luxury with stunning lake views and premium amenities.', 'Pokhara', '/placeholder.svg?height=200&width=300', 149.99, 50, 35, 'active'],
    [2, 'Lakeside Hotel', 'Comfortable accommodation with beautiful views of Phewa Lake.', 'Pokhara', '/placeholder.svg?height=200&width=300', 89.99, 40, 25, 'active'],
    [2, 'Mountain View Inn', 'Cozy rooms with spectacular views of the Annapurna range.', 'Pokhara', '/placeholder.svg?height=200&width=300', 69.99, 30, 20, 'active'],
    [2, 'Kathmandu Grand Hotel', 'Luxury hotel in the heart of Kathmandu with excellent amenities.', 'Kathmandu', '/placeholder.svg?height=200&width=300', 129.99, 60, 40, 'active'],
    [2, 'Thamel Boutique Hotel', 'Charming boutique hotel in the popular Thamel district.', 'Kathmandu', '/placeholder.svg?height=200&width=300', 79.99, 25, 15, 'active'],
    [2, 'Chitwan Safari Lodge', 'Experience wildlife up close at our comfortable safari lodge.', 'Chitwan', '/placeholder.svg?height=200&width=300', 99.99, 20, 12, 'active']
];

echo "<h3>Inserting Hotels</h3>";
$hotel_success = 0;

foreach ($hotels as $hotel) {
    $stmt = $conn->prepare("INSERT INTO hotels (owner_id, name, description, location, image_url, price_per_night, total_rooms, available_rooms, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdiss", $hotel[0], $hotel[1], $hotel[2], $hotel[3], $hotel[4], $hotel[5], $hotel[6], $hotel[7], $hotel[8]);
    
    if ($stmt->execute()) {
        echo "<p>✅ Added hotel: {$hotel[1]}</p>";
        $hotel_success++;
    } else {
        echo "<p>❌ Error adding hotel {$hotel[1]}: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<p>Added $hotel_success out of " . count($hotels) . " hotels</p>";

// Insert sample buses
$buses = [
    [3, 'Deluxe Express', 'Comfortable bus service with AC and reclining seats.', 'Kathmandu', 'Pokhara', '07:00:00', '13:00:00', '/placeholder.svg?height=200&width=300', 25.00, 40, 25, 'active'],
    [3, 'Tourist Coach', 'Premium bus service with extra legroom and refreshments.', 'Kathmandu', 'Pokhara', '08:30:00', '14:30:00', '/placeholder.svg?height=200&width=300', 35.00, 35, 20, 'active'],
    [3, 'Night Rider', 'Overnight bus service with semi-sleeper seats.', 'Pokhara', 'Kathmandu', '20:00:00', '04:00:00', '/placeholder.svg?height=200&width=300', 30.00, 38, 22, 'active'],
    [3, 'Mountain Express', 'Comfortable journey through scenic mountain routes.', 'Kathmandu', 'Chitwan', '09:00:00', '14:00:00', '/placeholder.svg?height=200&width=300', 20.00, 42, 30, 'active'],
    [3, 'Luxury Sleeper', 'Premium overnight service with full sleeper seats.', 'Kathmandu', 'Pokhara', '21:00:00', '05:00:00', '/placeholder.svg?height=200&width=300', 40.00, 30, 18, 'active'],
    [3, 'Chitwan Safari Bus', 'Direct service to Chitwan National Park.', 'Pokhara', 'Chitwan', '08:00:00', '13:30:00', '/placeholder.svg?height=200&width=300', 22.00, 36, 24, 'active']
];

echo "<h3>Inserting Buses</h3>";
$bus_success = 0;

foreach ($buses as $bus) {
    $stmt = $conn->prepare("INSERT INTO buses (operator_id, name, description, departure_location, arrival_location, departure_time, arrival_time, image_url, price, total_seats, available_seats, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssdiis", $bus[0], $bus[1], $bus[2], $bus[3], $bus[4], $bus[5], $bus[6], $bus[7], $bus[8], $bus[9], $bus[10], $bus[11]);
    
    if ($stmt->execute()) {
        echo "<p>✅ Added bus: {$bus[1]}</p>";
        $bus_success++;
    } else {
        echo "<p>❌ Error adding bus {$bus[1]}: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<p>Added $bus_success out of " . count($buses) . " buses</p>";

// Insert hotel amenities
echo "<h3>Inserting Hotel Amenities</h3>";

$hotel_amenities = [
    [1, 'Free WiFi'], [1, 'Swimming Pool'], [1, 'Spa'], [1, 'Restaurant'], [1, 'Room Service'], 
    [1, 'Fitness Center'], [1, 'Air Conditioning'], [1, 'Mountain View'],
    [2, 'Free WiFi'], [2, 'Restaurant'], [2, 'Lake View'], [2, 'Air Conditioning'], [2, 'Room Service'],
    [3, 'Free WiFi'], [3, 'Mountain View'], [3, 'Restaurant'], [3, 'Air Conditioning'],
    [4, 'Free WiFi'], [4, 'Swimming Pool'], [4, 'Spa'], [4, 'Restaurant'], [4, 'Room Service'],
    [4, 'Fitness Center'], [4, 'Air Conditioning'],
    [5, 'Free WiFi'], [5, 'Restaurant'], [5, 'Air Conditioning'], [5, 'Room Service'],
    [6, 'Free WiFi'], [6, 'Swimming Pool'], [6, 'Restaurant'], [6, 'Safari Tours'], [6, 'Air Conditioning']
];

$amenity_success = 0;
foreach ($hotel_amenities as $amenity) {
    $stmt = $conn->prepare("INSERT INTO hotel_amenities (hotel_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $amenity[0], $amenity[1]);
    
    if ($stmt->execute()) {
        $amenity_success++;
    } else {
        echo "<p>❌ Error adding amenity {$amenity[1]} to hotel {$amenity[0]}: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<p>Added $amenity_success hotel amenities</p>";

// Insert bus amenities
echo "<h3>Inserting Bus Amenities</h3>";

$bus_amenities = [
    [1, 'WiFi'], [1, 'Air Conditioning'], [1, 'Reclining Seats'], [1, 'Charging Ports'],
    [2, 'WiFi'], [2, 'Air Conditioning'], [2, 'Reclining Seats'], [2, 'Charging Ports'], 
    [2, 'Refreshments'], [2, 'Entertainment System'],
    [3, 'WiFi'], [3, 'Air Conditioning'], [3, 'Semi-Sleeper Seats'], [3, 'Charging Ports'], [3, 'Blankets'],
    [4, 'WiFi'], [4, 'Air Conditioning'], [4, 'Reclining Seats'], [4, 'Charging Ports'],
    [5, 'WiFi'], [5, 'Air Conditioning'], [5, 'Full Sleeper Seats'], [5, 'Charging Ports'], 
    [5, 'Blankets'], [5, 'Refreshments'], [5, 'Entertainment System'],
    [6, 'WiFi'], [6, 'Air Conditioning'], [6, 'Reclining Seats'], [6, 'Charging Ports']
];

$bus_amenity_success = 0;
foreach ($bus_amenities as $amenity) {
    $stmt = $conn->prepare("INSERT INTO bus_amenities (bus_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $amenity[0], $amenity[1]);
    
    if ($stmt->execute()) {
        $bus_amenity_success++;
    } else {
        echo "<p>❌ Error adding amenity {$amenity[1]} to bus {$amenity[0]}: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<p>Added $bus_amenity_success bus amenities</p>";

// Insert sample bookings
echo "<h3>Inserting Sample Bookings</h3>";

$bookings = [
    [4, 1, NULL, '2025-04-15', '2025-04-18', NULL, 2, 0, 449.97, 'confirmed', '2025-03-20 10:15:30'],
    [4, NULL, 1, NULL, NULL, '2025-04-20', 0, 2, 50.00, 'confirmed', '2025-03-21 14:22:45'],
    [4, 2, 2, '2025-05-10', '2025-05-12', '2025-05-10', 1, 1, 214.98, 'pending', '2025-03-22 09:30:15']
];

$booking_success = 0;
foreach ($bookings as $booking) {
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, hotel_id, bus_id, check_in_date, check_out_date, travel_date, guests, passengers, total_amount, status, booking_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssiidss", $booking[0], $booking[1], $booking[2], $booking[3], $booking[4], $booking[5], $booking[6], $booking[7], $booking[8], $booking[9], $booking[10]);
    
    if ($stmt->execute()) {
        $booking_success++;
    } else {
        echo "<p>❌ Error adding booking: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<p>Added $booking_success sample bookings</p>";

echo "<h3>Summary</h3>";
echo "<p>Successfully added:</p>";
echo "<ul>";
echo "<li>$hotel_success hotels</li>";
echo "<li>$bus_success buses</li>";
echo "<li>$amenity_success hotel amenities</li>";
echo "<li>$bus_amenity_success bus amenities</li>";
echo "<li>$booking_success bookings</li>";
echo "</ul>";

echo "<p><a href='index.php'>Return to Homepage</a></p>";

$conn->close();
?>
