<?php
session_start();
ob_start();             // start buffering so no output leaks

// redirect if there's no booking to confirm
if (!isset($_SESSION['booking_success'])) {
    header('Location: index.php');
    exit;
}

include 'config/database.php';
include 'includes/header.php';

// …the rest of your page…


$booking_id = $_SESSION['booking_success'];
unset($_SESSION['booking_success']); // Clear the session variable

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.*, h.name as hotel_name, h.location as hotel_location, 
           bs.name as bus_name, bs.departure_location, bs.arrival_location
    FROM bookings b
    LEFT JOIN hotels h ON b.hotel_id = h.id
    LEFT JOIN buses bs ON b.bus_id = bs.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit;
}

// Calculate nights
$nights = 0;
if ($booking['check_in_date'] && $booking['check_out_date']) {
    $nights = (new DateTime($booking['check_in_date']))->diff(new DateTime($booking['check_out_date']))->days;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-confirmation {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .confirmation-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .booking-details {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .detail-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .detail-title {
            font-weight: bold;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .detail-label {
            color: #6c757d;
        }
        .detail-value {
            font-weight: 500;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .print-button {
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="booking-confirmation">
        <div class="confirmation-header">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Booking Confirmed!</h2>
            <p class="lead">Your booking has been successfully processed.</p>
            <div class="mt-3">
                <span class="status-badge <?= $booking['status'] === 'confirmed' ? 'status-confirmed' : 'status-pending' ?>">
                    <?= ucfirst($booking['status']) ?>
                </span>
            </div>
        </div>
        
        <div class="booking-details">
            <div class="detail-section">
                <h4 class="detail-title">Booking Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">#<?= $booking['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Reference:</span>
                    <span class="detail-value"><?= $booking['order_id'] ?? 'N/A' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Date:</span>
                    <span class="detail-value"><?= date('F j, Y, g:i a', strtotime($booking['booking_date'])) ?></span>
                </div>
                <?php if ($booking['transaction_id']): ?>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value"><?= $booking['transaction_id'] ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($booking['hotel_id']): ?>
            <div class="detail-section">
                <h4 class="detail-title">Hotel Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Hotel:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['hotel_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['hotel_location']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in:</span>
                    <span class="detail-value"><?= date('F j, Y', strtotime($booking['check_in_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out:</span>
                    <span class="detail-value"><?= date('F j, Y', strtotime($booking['check_out_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nights:</span>
                    <span class="detail-value"><?= $nights ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Guests:</span>
                    <span class="detail-value"><?= $booking['guests'] ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($booking['bus_id']): ?>
            <div class="detail-section">
                <h4 class="detail-title">Transportation Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Bus/Transport:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['bus_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Route:</span>
                    <span class="detail-value">
                        <?= htmlspecialchars($booking['departure_location']) ?> → 
                        <?= htmlspecialchars($booking['arrival_location']) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Travel Date:</span>
                    <span class="detail-value"><?= date('F j, Y', strtotime($booking['travel_date'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Passengers:</span>
                    <span class="detail-value"><?= $booking['passengers'] ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-section">
                <h4 class="detail-title">Payment Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">NPR <?= number_format($booking['total_amount'], 2) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value"><?= ucfirst($booking['payment_method']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value">
                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Paid</span>
                        <?php elseif ($booking['status'] === 'pending'): ?>
                            <span class="text-warning"><i class="fas fa-clock me-1"></i>Pending Verification</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i><?= ucfirst($booking['status']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary me-2" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Confirmation
            </button>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i>Return to Home
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
