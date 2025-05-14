<?php
// Include header
include 'includes/header.php';

// Connect to the database
require_once 'config/database.php';

// Get search parameters
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'both';
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 1;

// Validate search parameters
if (empty($from) || empty($to) || empty($date)) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Missing search parameters. Please go back and try again.</div></div>';
    include 'includes/footer.php';
    exit;
}

// Format date for display
$formattedDate = date('F j, Y', strtotime($date));

// Search for hotels
$hotelQuery = "SELECT h.*, 
              (SELECT COUNT(*) FROM rooms r WHERE r.hotel_id = h.id AND r.status = 'available') as available_rooms,
              (SELECT MIN(price) FROM rooms r WHERE r.hotel_id = h.id) as min_price
              FROM hotels h
              WHERE h.location LIKE ? AND h.status = 'active'
              ORDER BY h.rating DESC";
$hotelStmt = $conn->prepare($hotelQuery);
$toParam = "%$to%";
$hotelStmt->bind_param("s", $toParam);
$hotelStmt->execute();
$hotelResult = $hotelStmt->get_result();

// Search for buses
$busQuery = "SELECT b.*, 
            (SELECT COUNT(*) FROM bus_seats bs WHERE bs.bus_id = b.id AND bs.status = 'available') as available_seats,
            bo.name as operator_name
            FROM buses b
            JOIN
