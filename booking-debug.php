<?php
include 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Error: User not logged in</h1>";
    echo "<p>Please <a href='login.php'>login</a> to continue.</p>";
    exit;
}

// Get booking parameters from URL
echo "<h1>Booking Debug Information</h1>";

echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>GET Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>POST Parameters</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Extract booking parameters
$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;
$bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;
$travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;

echo "<h2>Extracted Parameters</h2>";
echo "<ul>";
echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
echo "<li>Hotel ID: " . $hotel_id . "</li>";
echo "<li>Bus ID: " . $bus_id . "</li>";
echo "<li>Check-in: " . $check_in . "</li>";
echo "<li>Check-out: " . $check_out . "</li>";
echo "<li>Guests: " . $guests . "</li>";
echo "<li>Travel Date: " . $travel_date . "</li>";
echo "<li>Passengers: " . $passengers . "</li>";
echo "</ul>";

// Check if hotel exists
if ($hotel_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $hotel = $result->fetch_assoc();
        echo "<h3>Hotel Found:</h3>";
        echo "<pre>";
        print_r($hotel);
        echo "</pre>";
    } else {
        echo "<h3>Hotel Not Found</h3>";
    }
    $stmt->close();
}

// Check if bus exists
if ($bus_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $bus = $result->fetch_assoc();
        echo "<h3>Bus Found:</h3>";
        echo "<pre>";
        print_r($bus);
        echo "</pre>";
    } else {
        echo "<h3>Bus Not Found</h3>";
    }
    $stmt->close();
}

// Calculate total nights for hotel
$nights = 1;
if (!empty($check_in) && !empty($check_out)) {
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $interval = $check_in_date->diff($check_out_date);
    $nights = $interval->days;
}

echo "<h3>Calculated Values:</h3>";
echo "<ul>";
echo "<li>Nights: " . $nights . "</li>";

// Calculate total costs
$hotel_total = isset($hotel) ? $hotel['price_per_night'] * $nights * $guests : 0;
$bus_total = isset($bus) ? $bus['price'] * $passengers : 0;
$total_cost = $hotel_total + $bus_total;

echo "<li>Hotel Total: $" . $hotel_total . "</li>";
echo "<li>Bus Total: $" . $bus_total . "</li>";
echo "<li>Total Cost: $" . $total_cost . "</li>";
echo "</ul>";

echo "<h2>Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p>Connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p>Database connection successful</p>";
}

echo "<p><a href='index.php'>Return to Homepage</a></p>";
?>
