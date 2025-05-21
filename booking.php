<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
session_start();

// Start output buffering after session_start
ob_start();

include 'config/database.php';
include 'includes/header.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['booking_redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?redirect=booking');
    exit;
}

// Sanitize and validate inputs with NULL handling
$hotel_id = isset($_GET['hotel_id']) && $_GET['hotel_id'] !== '' ? intval($_GET['hotel_id']) : null;
$bus_id = isset($_GET['bus_id']) && $_GET['bus_id'] !== '' ? intval($_GET['bus_id']) : null;
$check_in = htmlspecialchars($_GET['check_in'] ?? '');
$check_out = htmlspecialchars($_GET['check_out'] ?? '');
$guests = intval($_GET['guests'] ?? 1);
$travel_date = htmlspecialchars($_GET['travel_date'] ?? '');
$passengers = intval($_GET['passengers'] ?? 1);
$payment_method = htmlspecialchars($_POST['payment_method'] ?? 'manual');

// Fetch services only if IDs exist
$hotel = [];
if ($hotel_id) {
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $hotel_id);
    $stmt->execute();
    $hotel = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

$bus = [];
if ($bus_id) {
    $stmt = $conn->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $bus = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
}

// Calculate pricing
$nights = $check_in && $check_out
    ? (new DateTime($check_in))->diff(new DateTime($check_out))->days
    : 0;

$hotel_total = ($hotel['price_per_night'] ?? 0) * $nights * $guests;
$bus_total = ($bus['price'] ?? 0) * $passengers;
$total_cost = $hotel_total + $bus_total;

// Calculate tax (assuming 10% tax rate)
$tax_amount = $total_cost * 0.10;
$total_with_tax = $total_cost + $tax_amount;

// Generate a unique transaction ID
$transaction_uuid = uniqid('TXN_');

// eSewa configuration
$esewa_product_code = "EPAYTEST"; // Replace with your actual product code in production
$esewa_secret_key = "8gBm/:&EnhH.1/q"; // Replace with your actual secret key in production
$esewa_success_url = "http://localhost/hotel-bus-booking/esewa-success.php";
$esewa_failure_url = "http://localhost/hotel-bus-booking/esewa-failure.php";

// Generate eSewa signature
$signature_data = "total_amount={$total_with_tax},transaction_uuid={$transaction_uuid},product_code={$esewa_product_code}";
$esewa_signature = base64_encode(hash_hmac('sha256', $signature_data, $esewa_secret_key, true));

// Process booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_proof = '';

    // Only process file upload for manual payment
    if ($payment_method === 'manual') {
        // File upload handling
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/payment_proofs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $filename = sprintf("payment_%d_%s.%s", $_SESSION['user_id'], bin2hex(random_bytes(4)), $ext);

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $filename)) {
                $payment_proof = $upload_dir . $filename;
            } else {
                $error = "Failed to upload payment proof. Please try again.";
            }
        } elseif ($payment_method === 'manual') {
            $error = "Please upload payment proof.";
        }
    } elseif ($payment_method === 'esewa') {
        // For eSewa, we'll redirect to their payment gateway
        // Store booking details in session for completion after payment
        $_SESSION[$transaction_uuid] = [
            'user_id' => $_SESSION['user_id'],
            'hotel_id' => $hotel_id,
            'bus_id' => $bus_id,
            'check_in_date' => $check_in,
            'check_out_date' => $check_out,
            'travel_date' => $travel_date,
            'guests' => $guests,
            'passengers' => $passengers,
            'total_amount' => $total_with_tax,
            'transaction_id' => $transaction_uuid,
            'payment_method' => 'esewa',
            'order_id' => $transaction_uuid,
            'payment_proof' => '',
            'selected_rooms' => '',
            'selected_seats' => '',
            'payment_details' => json_encode(['method' => 'esewa', 'status' => 'pending'])
        ];


        // Log the session data for debugging
        error_log('Setting temp_booking session data: ' . json_encode($_SESSION['temp_booking']));

        // IMPORTANT: Create a pending booking record BEFORE redirecting to eSewa
        // This ensures we have a record even if the session is lost
        try {
            $status = 'pending';
            $booking_date = date('Y-m-d H:i:s');
            $payment_details = json_encode(['method' => 'esewa', 'status' => 'pending']);

            $stmt = $conn->prepare("INSERT INTO bookings (
                user_id, 
                hotel_id, 
                bus_id, 
                check_in_date, 
                check_out_date, 
                travel_date, 
                guests, 
                passengers, 
                total_amount, 
                payment_method,
                transaction_id,
                order_id,
                status, 
                booking_date,
                payment_details
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "iiisssiidsssss",  // Type string
                $_SESSION['user_id'],
                $hotel_id,
                $bus_id,
                $check_in ?: null,
                $check_out ?: null,
                $travel_date ?: null,
                $guests,
                $passengers,
                $total_with_tax,
                $payment_method,
                $transaction_uuid,
                $transaction_uuid,
                $status,
                $booking_date,
                $payment_details
            );

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }

            $pending_booking_id = $stmt->insert_id;
            error_log("Created pending booking ID: $pending_booking_id before eSewa redirect");

            // Store the pending booking ID in session
            $_SESSION['pending_booking_id'] = $pending_booking_id;
        } catch (Exception $e) {
            error_log("Failed to create pending booking: " . $e->getMessage());
            // Continue to eSewa even if this fails - we'll handle it in the callback
        }

        // The form will submit to eSewa
        // We don't need to do anything here as the form action points to eSewa
        // The actual booking will be updated in esewa-success.php
    }

    // Only proceed with database insertion for manual payment or if there's no error
    if ($payment_method === 'manual' && !isset($error)) {
        try {
            $stmt = $conn->prepare("INSERT INTO bookings (
                user_id, 
                hotel_id, 
                bus_id, 
                check_in_date, 
                check_out_date, 
                travel_date, 
                guests, 
                passengers, 
                total_amount, 
                payment_proof,
                payment_method,
                transaction_id,
                order_id,
                status, 
                booking_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $status = 'pending';
            $booking_date = date('Y-m-d H:i:s');

            $booking_values = [
                $_SESSION['user_id'],
                $hotel_id,
                $bus_id,
                $check_in ?: null,
                $check_out ?: null,
                $travel_date ?: null,
                $guests,
                $passengers,
                $total_with_tax,
                $payment_proof,
                $payment_method,
                $transaction_uuid,
                $transaction_uuid, // Use same value for order_id
                $status,
                $booking_date
            ];

            $stmt->bind_param(
                "iiisssiidssssss",  // Type string
                ...$booking_values
            );

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $stmt->error);
            }

            $_SESSION['booking_success'] = $stmt->insert_id;
            header('Location: booking-confirmation.php');
            exit;
        } catch (Exception $e) {
            $error = "Booking failed: " . $e->getMessage();
            error_log("Manual booking failed: " . $e->getMessage());
        }
    }
}

// CSS for payment methods
$custom_css = <<<CSS
.payment-methods {
    margin-bottom: 2rem;
}

.payment-method {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.payment-method:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.payment-method.active {
    border-color: #667eea;
    background-color: rgba(102, 126, 234, 0.05);
}

.payment-method-radio {
    position: absolute;
    opacity: 0;
}

.payment-method-label {
    display: flex;
    align-items: center;
    margin: 0;
    cursor: pointer;
    width: 100%;
}

.payment-icon {
    width: 60px;
    height: 60px;
    margin-right: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #f8f9fa;
}

.payment-icon img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.payment-details {
    flex: 1;
}

.payment-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.payment-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

.payment-content {
    display: none;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
}

.payment-content {
    display: block;
}

.qr-code {
    text-align: center;
    margin: 1.5rem 0;
}

.qr-code img {
    max-width: 200px;
    border: 1px solid #e0e0e0;
    padding: 0.5rem;
    border-radius: 10px;
}

.esewa-btn {
    background: linear-gradient(135deg, #60BB46 0%, #008A43 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.esewa-btn:hover {
    background: linear-gradient(135deg, #4FA83C 0%, #007A3B 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.esewa-btn img {
    height: 24px;
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.payment-summary {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.payment-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.payment-summary-total {
    font-weight: bold;
    border-top: 1px solid #dee2e6;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .payment-method-label {
        flex-direction: column;
        text-align: center;
    }
    
    .payment-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}
CSS;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Include your existing head content here -->
    <style>
        <?= $custom_css ?>
    </style>
</head>

<body>
    <!-- HTML remains mostly the same, just ensure proper NULL checks in display -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Complete Booking</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['payment_error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['payment_error'] ?></div>
                    <?php unset($_SESSION['payment_error']); ?>
                <?php endif; ?>
            </div>

            <div class="row">
                <!-- Hotel Section -->
                <?php if (!empty($hotel)): ?>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5><?= htmlspecialchars($hotel['name']) ?></h5>
                                <p class="text-muted"><?= htmlspecialchars($hotel['location']) ?></p>
                                <div class="row">
                                    <div class="col-6">Check-in:</div>
                                    <div class="col-6"><?= date('M j, Y', strtotime($check_in)) ?></div>
                                    <div class="col-6">Check-out:</div>
                                    <div class="col-6"><?= date('M j, Y', strtotime($check_out)) ?></div>
                                    <div class="col-6">Guests:</div>
                                    <div class="col-6"><?= $guests ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bus Section -->
                <?php if (!empty($bus)): ?>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5><?= htmlspecialchars($bus['name']) ?></h5>
                                <p class="text-muted">
                                    <?= htmlspecialchars($bus['departure_location']) ?> →
                                    <?= htmlspecialchars($bus['arrival_location']) ?>
                                </p>
                                <div class="row">
                                    <div class="col-6">Travel Date:</div>
                                    <div class="col-6"><?= date('M j, Y', strtotime($travel_date)) ?></div>
                                    <div class="col-6">Passengers:</div>
                                    <div class="col-6"><?= $passengers ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-4">Payment Details</h5>

                    <!-- Payment Summary -->
                    <div class="payment-summary">
                        <div class="payment-summary-row">
                            <span>Subtotal:</span>
                            <span>NPR <?= number_format($total_cost, 2) ?></span>
                        </div>
                        <div class="payment-summary-row">
                            <span>Tax (10%):</span>
                            <span>NPR <?= number_format($tax_amount, 2) ?></span>
                        </div>
                        <div class="payment-summary-row payment-summary-total">
                            <span>Total Amount:</span>
                            <span>NPR <?= number_format($total_with_tax, 2) ?></span>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="payment-methods">
                        <h6 class="mb-3">Select Payment Method</h6>

                        <!-- Manual Payment Option -->
                        <div class="payment-method <?= ($payment_method === 'manual') ? 'active' : '' ?>" id="manual-payment">
                            <input type="radio" name="payment_method" id="method-manual" class="payment-method-radio" value="manual" <?= ($payment_method === 'manual') ? 'checked' : '' ?>>
                            <label for="method-manual" class="payment-method-label">
                                <div class="payment-icon">
                                    <img src="assets\Images\Screenshot 2025-05-10 142715.png" alt="Bank Transfer">
                                </div>
                                <div class="payment-details">
                                    <div class="payment-title">Manual Bank Transfer</div>
                                    <div class="payment-description">Transfer the amount to our bank account and upload proof of payment</div>
                                </div>
                            </label>

                            <div class="payment-content">
                                <div class="alert alert-info">
                                    <p><strong>Bank Details:</strong></p>
                                    <p>Bank Name: Nepal Bank Ltd.</p>
                                    <p>Account Name: Easy Trip Pvt. Ltd.</p>
                                    <p>Account Number: 0123456789012</p>
                                    <p>Branch: Kathmandu</p>
                                </div>

                                <div class="qr-code">
                                    <img src="assets\Images\Screenshot 2025-05-10 142715.png" alt="Payment QR Code">
                                    <p class="mt-2 text-muted">Scan to pay via mobile banking</p>
                                </div>

                                <form method="POST" enctype="multipart/form-data" id="manual-payment-form">
                                    <input type="hidden" name="payment_method" value="manual">

                                    <div class="mb-3">
                                        <label class="form-label">Upload Payment Proof</label>
                                        <input type="file" name="payment_proof" class="form-control" required
                                            accept="image/*,.pdf">
                                        <small class="form-text text-muted">
                                            Upload screenshot or scan of payment confirmation (JPEG, PNG, PDF)
                                        </small>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        Confirm Booking
                                    </button>
                                </form>
                            </div>
                        </div>
                        <!-- eSewa Payment Option -->
                        <div class="payment-method <?= ($payment_method === 'esewa') ? 'active' : '' ?>" id="esewa-payment">
                            <input type="radio" name="payment_method" id="method-esewa" class="payment-method-radio" value="esewa" <?= ($payment_method === 'esewa') ? 'checked' : '' ?>>
                            <label for="method-esewa" class="payment-method-label">
                                <div class="payment-content">
                                    <!-- Using the exact form structure provided by the user -->
                                    <form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST" id="esewa-payment-form">
                                        <!-- Hidden fields for eSewa payment -->
                                        <input type="hidden" id="amount" name="amount" value="<?= $total_cost ?>">
                                        <input type="hidden" id="tax_amount" name="tax_amount" value="<?= $tax_amount ?>">
                                        <input type="hidden" id="total_amount" name="total_amount" value="<?= $total_with_tax ?>">
                                        <input type="hidden" id="transaction_uuid" name="transaction_uuid" value="<?= $transaction_uuid ?>">
                                        <input type="hidden" id="product_code" name="product_code" value="<?= $esewa_product_code ?>">
                                        <input type="hidden" id="product_service_charge" name="product_service_charge" value="0">
                                        <input type="hidden" id="product_delivery_charge" name="product_delivery_charge" value="0">
                                        <input type="hidden" id="success_url" name="success_url" value="<?= $esewa_success_url ?>?oid=<?= $transaction_uuid ?>&amt=<?= $total_with_tax ?>&refId=<?= $transaction_uuid ?>">
                                        <input type="hidden" id="failure_url" name="failure_url" value="<?= $esewa_failure_url ?>">
                                        <input type="hidden" id="signed_field_names" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                                        <input type="hidden" id="signature" name="signature" value="<?= $esewa_signature ?>">

                                        <button type="submit" class="esewa-btn w-100">
                                            <img src="assets\Images\esewa-icon.png" alt="eSewa Icon">
                                            Pay with eSewa
                                        </button>
                                    </form>
                                </div>
                                <!-- <div class="payment-details">
                                    <div class="payment-title"><a href=>Pay with eSewa</a></div>
                                    <div class="payment-description">Quick and secure payment via eSewa digital wallet</div>
                                </div> -->
                            </label>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            // const paymentMethods = document.querySelectorAll('.payment-method');
            // const paymentRadios = document.querySelectorAll('.payment-method-radio');

            // // Initialize active state based on checked radio
            // paymentRadios.forEach(radio => {
            //     if (radio.checked) {
            //         document.getElementById(radio.value + '-payment').classList.add('active');
            //     }
            // });

            // Add click event to payment method containers
            // paymentMethods.forEach(method => {
            //     method.addEventListener('click', function() {
            //         // Remove active class from all methods
            //         paymentMethods.forEach(m => m.classList.remove('active'));

            //         // Add active class to clicked method
            //         this.classList.add('active');

            //         // Check the radio button
            //         const radio = this.querySelector('.payment-method-radio');
            //         radio.checked = true;

            //         // Update hidden input in both forms
            //         document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            //             input.value = radio.value;
            //         });
            //     });
            // });

            // Form validation for manual payment
            const manualForm = document.getElementById('manual-payment-form');
            if (manualForm) {
                manualForm.addEventListener('submit', function(e) {
                    const fileInput = this.querySelector('input[name="payment_proof"]');
                    if (fileInput.value === '') {
                        e.preventDefault();
                        alert('Please upload payment proof');
                    }
                });
            }
        });
    </script>

    <?php
    include 'includes/footer.php';
    // Flush the output buffer at the end
    ob_end_flush();
    ?>