<?php
// This file returns JSON data about bus seats for AJAX requests

header('Content-Type: application/json');

// Check if bus_id is provided
if (!isset($_GET['bus_id']) || empty($_GET['bus_id'])) {
    echo json_encode(['error' => 'Bus ID is required']);
    exit;
}

$busId = intval($_GET['bus_id']);

// Connect to the database
require_once 'config/database.php';

// Get seat information
$seatQuery = "SELECT * FROM bus_seats WHERE bus_id = ?";
$stmt = $conn->prepare($seatQuery);
$stmt->bind_param("i", $busId);
$stmt->execute();
$seatResult = $stmt->get_result();

$seats = [];
while ($seat = $seatResult->fetch_assoc()) {
    $seats[] = $seat;
}

// Return the seats as JSON
echo json_encode(['seats' => $seats]);
?>
