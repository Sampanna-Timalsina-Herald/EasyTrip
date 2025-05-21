<?php
require_once 'config/database.php';
require_once 'includes/seat-selection.php';

// Check if bus_id is provided
if (!isset($_GET['bus_id']) || empty($_GET['bus_id'])) {
    echo '<div class="alert alert-danger">Bus ID is required</div>';
    exit;
}

$busId = intval($_GET['bus_id']);

// Display the seat selection
displaySeatSelection($busId);
?>
