<?php
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

// Get booking by ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "SELECT * FROM bookings WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode($booking);
    } else {
        echo json_encode(['error' => 'Booking not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}

$conn->close();
?>

