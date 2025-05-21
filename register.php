<?php
session_start();
require_once 'config/database.php'; // Include database configuration

// Include PHPMailer
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables and errors
$errors = [
    'name' => '', 'email' => '', 'password' => '',
    'confirm_password' => '', 'otp' => '', 'general' => ''
];
$formData = [
    'name' => '',
    'email' => isset($_SESSION['register_email']) ? $_SESSION['register_email'] : ''
];

// Clear session when directly accessing the page (not from form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['keep_session'])) {
    // Reset registration data
    unset($_SESSION['otp']);
    unset($_SESSION['otp_time']);
    unset($_SESSION['register_email']);
    unset($_SESSION['resend_time']);
    unset($_SESSION['email_verified']);
}

// Generate OTP function
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Check if email is already registered
function isEmailRegistered($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

// Validate password strength
function validatePassword($password) {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Check for special character
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

// Process AJAX requests
if (isset($_POST['action'])) {
    $response = ['success' => false, 'message' => '', 'data' => [], 'errors' => []];
    
    // Send OTP action
    if ($_POST['action'] === 'send_otp') {
        $email = trim($_POST['email']);
        
        // Validate email
        if (empty($email)) {
            $response['message'] = 'Please enter your email';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format';
        } else {
            // Check if email exists in the database
            if (isEmailRegistered($conn, $email)) {
                $response['message'] = 'This email is already registered. Please use a different email or login.';
            } else {
                // Check if resend is allowed (60 seconds cooldown)
                if (isset($_SESSION['resend_time']) && (time() - $_SESSION['resend_time'] < 60)) {
                    $timeLeft = 60 - (time() - $_SESSION['resend_time']);
                    $response['message'] = "Please wait $timeLeft seconds before requesting another OTP";
                } else {
                    // Generate and send OTP
                    $otp = generateOTP();
                    $_SESSION['otp'] = $otp;
                    $_SESSION['register_email'] = $email;
                    $_SESSION['otp_time'] = time();
                    $_SESSION['resend_time'] = time(); // Set resend timer
                    
                    // Send OTP via email using PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'sampannactwn@gmail.com';  // Update with your email
                        $mail->Password = 'ijlp mgsu ekst hgwo';  // Update with your password or app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('support@easytrip.com', 'Easy Trip');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Easy Trip Verification Code';

                        // Email template
                        $mail->Body = <<<HTML
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <style>
                                body { 
                                    background: linear-gradient(135deg, #667eea66 0%, #764ba266 100%);
                                    backdrop-filter: blur(10px);
                                    margin: 0;
                                    padding: 40px 20px;
                                    font-family: "Segoe UI", sans-serif;
                                }
                                .email-container {
                                    max-width: 600px;
                                    margin: 0 auto;
                                    background: rgba(255, 255, 255, 0.9);
                                    border-radius: 15px;
                                    padding: 40px;
                                    backdrop-filter: blur(5px);
                                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                                }
                                .header {
                                    text-align: center;
                                    padding-bottom: 30px;
                                    border-bottom: 2px solid #eee;
                                    margin-bottom: 30px;
                                }
                                .logo {
                                    color: #667eea;
                                    font-size: 28px;
                                    font-weight: bold;
                                    letter-spacing: -1px;
                                    text-decoration: none;
                                }
                                .otp-box {
                                    background: #f8f9fa;
                                    border-radius: 10px;
                                    padding: 25px;
                                    text-align: center;
                                    margin: 30px 0;
                                }
                                .otp-code {
                                    font-size: 32px;
                                    color: #667eea;
                                    letter-spacing: 3px;
                                    margin: 15px 0;
                                    font-weight: bold;
                                }
                                .footer {
                                    text-align: center;
                                    color: #666;
                                    font-size: 14px;
                                    margin-top: 30px;
                                    padding-top: 30px;
                                    border-top: 2px solid #eee;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="email-container">
                                <div class="header">
                                    <a href="#" class="logo">Easy Trip</a>
                                </div>
                                
                                <h2 style="color: #333; margin-bottom: 25px;">Your Verification Code</h2>
                                <p style="color: #666; line-height: 1.6;">Welcome to Easy Trip! Use this OTP to complete your registration:</p>
                                
                                <div class="otp-box">
                                    <div class="otp-code">$otp</div>
                                    <p style="color: #888; margin: 0;">Valid for 10 minutes</p>
                                </div>

                                <p style="color: #666; line-height: 1.6;">
                                    Having trouble? Contact our support team at 
                                    <a href="mailto:support@easytrip.com" style="color: #667eea; text-decoration: none;">
                                        support@easytrip.com
                                    </a>
                                </p>

                                <div class="footer">
                                    <p>Safe travels with Easy Trip &copy; {date('Y')}</p>
                                    <p>123 Travel Street, Adventure City, World</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        HTML;

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'Verification code sent successfully!';
                        $response['data'] = [
                            'email' => $email,
                            'resendTime' => time() + 60
                        ];
                    } catch (Exception $e) {
                        $response['message'] = 'Failed to send OTP. Please try again.';
                    }
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Verify OTP action
    if ($_POST['action'] === 'verify_otp') {
        $userOTP = trim($_POST['otp']);
        
        if (empty($userOTP)) {
            $response['message'] = 'Please enter OTP';
        } elseif (!isset($_SESSION['otp'])) {
            $response['message'] = 'OTP session expired';
        } elseif (time() - $_SESSION['otp_time'] > 600) { // 10 minutes expiration
            $response['message'] = 'OTP has expired';
            unset($_SESSION['otp']); // Clear OTP data
            unset($_SESSION['otp_time']); // Clear OTP time
        } elseif ($userOTP != $_SESSION['otp']) {
            $response['message'] = 'Invalid OTP';
        } else {
            // Double check that email is still not registered
            if (isEmailRegistered($conn, $_SESSION['register_email'])) {
                $response['message'] = 'This email has been registered by someone else. Please use a different email.';
            } else {
                $_SESSION['email_verified'] = true;
                $response['success'] = true;
                $response['message'] = 'Email verified successfully!';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Register user action
    if ($_POST['action'] === 'register') {
        $name = trim($_POST['name']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        // Validate inputs
        if (empty($name)) {
            $response['message'] = 'Please enter your name';
        } elseif (empty($password)) {
            $response['message'] = 'Please enter password';
        } elseif ($password !== $confirm_password) {
            $response['message'] = 'Passwords do not match';
        } elseif (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true) {
            $response['message'] = 'Please verify your email first';
        } else {
            // Validate password strength
            $passwordErrors = validatePassword($password);
            
            if (!empty($passwordErrors)) {
                $response['success'] = false;
                $response['message'] = 'Password does not meet requirements';
                $response['errors'] = $passwordErrors;
            } else {
                // Final check to ensure email is not already registered
                if (isEmailRegistered($conn, $_SESSION['register_email'])) {
                    $response['message'] = 'This email has been registered by someone else. Please use a different email.';
                } else {
                    // Register user
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'traveler')");
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("sss", $name, $_SESSION['register_email'], $password_hash);
                    
                    if ($stmt->execute()) {
                        // Store registration success message in session
                        $_SESSION['registration_success'] = true;
                        $_SESSION['registered_email'] = $_SESSION['register_email'];
                        
                        // Clear registration session data
                        unset($_SESSION['otp']);
                        unset($_SESSION['otp_time']);
                        unset($_SESSION['register_email']);
                        unset($_SESSION['resend_time']);
                        unset($_SESSION['email_verified']);
                        
                        $response['success'] = true;
                        $response['message'] = 'Account created successfully!';
                        $response['data'] = [
                            'redirect' => 'login.php'
                        ];
                    } else {
                        $response['message'] = 'Registration failed. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Calculate remaining time for OTP resend
$resendTimeRemaining = 0;
if (isset($_SESSION['resend_time'])) {
    $resendTimeRemaining = max(0, 60 - (time() - $_SESSION['resend_time']));
}

// Check if email is verified
$emailVerified = isset($_SESSION['email_verified']) && $_SESSION['email_verified'] === true;
$registeredEmail = isset($_SESSION['register_email']) ? $_SESSION['register_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Trip - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .registration-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 1.2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            transition: all 0.3s ease;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #eee;
        }

        .section-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }

        .section-number.completed {
            background: #28a745;
        }

        .section-number.disabled {
            background: #ccc;
        }

        .section-title {
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #ddd;
            border-radius: 0.7rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
        }

        .error i {
            margin-right: 5px;
        }

        button {
            width: 100%;
            padding: 1rem;
            color: white;
            border: none;
            border-radius: 0.7rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .primary-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .primary-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4292 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .secondary-btn {
            background: #6c757d;
        }

        .secondary-btn:hover:not(:disabled) {
            background: #5a6268;
        }

        .login-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
            display: inline-block;
            margin-top: 1rem;
        }

        .login-link:hover {
            color: #764ba2;
        }

        .text-center {
            text-align: center;
        }

        .otp-info {
            background: #f0f4ff;
            padding: 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }

        .otp-info p {
            color: #555;
            margin-bottom: 0.5rem;
        }

        .otp-info strong {
            color: #333;
        }

        .timer {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timer i {
            color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .action-buttons button {
            flex: 1;
        }

        .change-email {
            background: none;
            color: #667eea;
            text-decoration: underline;
            padding: 0;
            font-size: 0.9rem;
            width: auto;
            margin-top: 0.5rem;
        }

        .change-email:hover {
            color: #764ba2;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 40px;
            cursor: pointer;
            color: #666;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .success-container {
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        .success-icon {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 1rem;
        }

        .success-message {
            font-size: 1.5rem;
            color: #166534;
            margin-bottom: 1rem;
        }

        .success-details {
            color: #444;
            margin-bottom: 2rem;
        }

        .redirect-message {
            font-size: 0.9rem;
            color: #666;
            margin-top: 1rem;
        }

        .section-disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .section-completed {
            position: relative;
        }

        .section-completed::after {
            content: '✓';
            position: absolute;
            right: 10px;
            top: 10px;
            color: #28a745;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            background: #dcfce7;
            color: #166534;
            padding: 0.3rem 0.7rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .verification-badge i {
            margin-right: 5px;
        }

        .password-requirements {
            background-color: #f8f9fa;
            border-radius: 0.7rem;
            padding: 1rem;
            margin-top: 0.5rem;
            border-left: 4px solid #667eea;
        }

        .password-requirements h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
            color: #666;
            font-size: 0.85rem;
        }

        .requirement i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .requirement.valid {
            color: #22c55e;
        }

        .requirement.invalid {
            color: #ef4444;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        #notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            max-width: 300px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        #notification.success {
            background-color: #10b981;
        }

        #notification.error {
            background-color: #ef4444;
        }

        #notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            top: calc(50% - 10px);
            left: calc(50% - 10px);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1>Join Easy Trip</h1>
        
        <div id="notification"></div>
        
        <form id="registrationForm">
            <!-- Section 1: Email Verification -->
            <div class="form-section" id="emailSection">
                <div class="section-header">
                    <div class="section-number" id="emailSectionNumber">1</div>
                    <div class="section-title">Email Verification</div>
                    <?php if ($emailVerified): ?>
                        <div class="verification-badge"><i class="fas fa-check-circle"></i> Verified</div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?= $registeredEmail ?>" placeholder="Enter your email" required <?= $emailVerified ? 'readonly' : '' ?>>
                    <div class="error" id="emailError"></div>
                </div>
                
                <?php if (!$emailVerified): ?>
                    <button type="button" id="sendOtpBtn" class="primary-btn">
                        <i class="fas fa-paper-plane"></i> Send Verification Code
                    </button>
                <?php endif; ?>
                
                <div id="otpVerificationSection" style="display: <?= isset($_SESSION['otp']) ? 'block' : 'none' ?>;" class="fade-in">
                    <div class="otp-info">
                        <p><i class="fas fa-info-circle"></i> A verification code has been sent to your email.</p>
                        <p>Please check your inbox and spam folder.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="otp">Verification Code</label>
                        <input type="text" name="otp" id="otp" placeholder="Enter 6-digit code" maxlength="6" required>
                        <div class="error" id="otpError"></div>
                        
                        <div class="timer" id="resendTimer" style="display: <?= $resendTimeRemaining > 0 ? 'flex' : 'none' ?>;">
                            <i class="fas fa-clock"></i> Resend available in <span id="timer"><?= $resendTimeRemaining ?></span> seconds
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" id="verifyOtpBtn" class="primary-btn">
                            <i class="fas fa-check-circle"></i> Verify Code
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" id="resendOtpBtn" class="change-email" <?= $resendTimeRemaining > 0 ? 'disabled' : '' ?>>
                            Resend verification code
                        </button>
                        <button type="button" id="changeEmailBtn" class="change-email">
                            Change email address
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 2: Personal Information -->
            <div class="form-section <?= !$emailVerified ? 'section-disabled' : '' ?>" id="personalInfoSection">
                <div class="section-header">
                    <div class="section-number <?= !$emailVerified ? 'disabled' : '' ?>" id="personalInfoSectionNumber">2</div>
                    <div class="section-title">Personal Information</div>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter your name" required>
                    <div class="error" id="nameError"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Create a password" required>
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="error" id="passwordError"></div>
                    
                    <!-- Password requirements section -->
                    <div class="password-requirements">
                        <h4>Password Requirements:</h4>
                        <div class="requirement" id="length-requirement">
                            <i class="fas fa-times-circle"></i> At least 8 characters
                        </div>
                        <div class="requirement" id="uppercase-requirement">
                            <i class="fas fa-times-circle"></i> At least one uppercase letter
                        </div>
                        <div class="requirement" id="special-requirement">
                            <i class="fas fa-times-circle"></i> At least one special character
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="error" id="confirmPasswordError"></div>
                </div>
                
                <button type="button" id="registerBtn" class="primary-btn" disabled>
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
        </form>

        <div class="text-center">
            <a href="login.php" class="login-link">Already have an account? Login here</a>
        </div>
    </div>

    <script>
        // DOM Elements
        const emailInput = document.getElementById('email');
        const otpInput = document.getElementById('otp');
        const nameInput = document.getElementById('name');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        const sendOtpBtn = document.getElementById('sendOtpBtn');
        const verifyOtpBtn = document.getElementById('verifyOtpBtn');
        const resendOtpBtn = document.getElementById('resendOtpBtn');
        const changeEmailBtn = document.getElementById('changeEmailBtn');
        const registerBtn = document.getElementById('registerBtn');
        
        const otpVerificationSection = document.getElementById('otpVerificationSection');
        const resendTimer = document.getElementById('resendTimer');
        const timerElement = document.getElementById('timer');
        const personalInfoSection = document.getElementById('personalInfoSection');
        
        const emailError = document.getElementById('emailError');
        const otpError = document.getElementById('otpError');
        const nameError = document.getElementById('nameError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        
        const notification = document.getElementById('notification');
        
        // Password requirement elements
        const lengthRequirement = document.getElementById('length-requirement');
        const uppercaseRequirement = document.getElementById('uppercase-requirement');
        const specialRequirement = document.getElementById('special-requirement');
        
        // Variables
        let timeLeft = <?= $resendTimeRemaining ?>;
        let timerInterval;
        let emailVerified = <?= $emailVerified ? 'true' : 'false' ?>;
        let passwordValid = false;
        
        // Functions
        function showNotification(message, type) {
            notification.textContent = message;
            notification.className = type;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }
        
        function setButtonLoading(button, isLoading) {
            if (isLoading) {
                button.classList.add('btn-loading');
                button.disabled = true;
            } else {
                button.classList.remove('btn-loading');
                button.disabled = false;
            }
        }
        
        function startResendTimer(seconds) {
            clearInterval(timerInterval);
            timeLeft = seconds;
            
            resendTimer.style.display = 'flex';
            resendOtpBtn.disabled = true;
            
            timerInterval = setInterval(function() {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    resendTimer.style.display = 'none';
                    resendOtpBtn.disabled = false;
                }
            }, 1000);
        }
        
        function resetErrors() {
            emailError.textContent = '';
            otpError.textContent = '';
            nameError.textContent = '';
            passwordError.textContent = '';
            confirmPasswordError.textContent = '';
        }
        
        function enablePersonalInfoSection() {
            personalInfoSection.classList.remove('section-disabled');
            document.getElementById('personalInfoSectionNumber').classList.remove('disabled');
            document.getElementById('emailSectionNumber').classList.add('completed');
            
            // Add verified badge if not already present
            const verificationBadge = document.querySelector('.verification-badge');
            if (!verificationBadge) {
                const badge = document.createElement('div');
                badge.className = 'verification-badge';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Verified';
                document.querySelector('.section-header').appendChild(badge);
            }
            
            // Make email readonly
            emailInput.readOnly = true;
            
            // Hide send OTP button
            if (sendOtpBtn) sendOtpBtn.style.display = 'none';
            
            emailVerified = true;
        }
        
        function validatePasswordRequirements() {
            const password = passwordInput.value;
            let isValid = true;
            
            // Check length requirement
            if (password.length >= 8) {
                lengthRequirement.classList.add('valid');
                lengthRequirement.classList.remove('invalid');
                lengthRequirement.querySelector('i').className = 'fas fa-check-circle';
            } else {
                lengthRequirement.classList.remove('valid');
                lengthRequirement.classList.add('invalid');
                lengthRequirement.querySelector('i').className = 'fas fa-times-circle';
                isValid = false;
            }
            
            // Check uppercase requirement
            if (/[A-Z]/.test(password)) {
                uppercaseRequirement.classList.add('valid');
                uppercaseRequirement.classList.remove('invalid');
                uppercaseRequirement.querySelector('i').className = 'fas fa-check-circle';
            } else {
                uppercaseRequirement.classList.remove('valid');
                uppercaseRequirement.classList.add('invalid');
                uppercaseRequirement.querySelector('i').className = 'fas fa-times-circle';
                isValid = false;
            }
            
            // Check special character requirement
            if (/[^a-zA-Z0-9]/.test(password)) {
                specialRequirement.classList.add('valid');
                specialRequirement.classList.remove('invalid');
                specialRequirement.querySelector('i').className = 'fas fa-check-circle';
            } else {
                specialRequirement.classList.remove('valid');
                specialRequirement.classList.add('invalid');
                specialRequirement.querySelector('i').className = 'fas fa-times-circle';
                isValid = false;
            }
            
            passwordValid = isValid;
            
            // Enable/disable register button based on form validity
            updateRegisterButtonState();
            
            return isValid;
        }
        
        function updateRegisterButtonState() {
            const nameValid = nameInput.value.trim() !== '';
            const passwordsMatch = passwordInput.value === confirmPasswordInput.value;
            
            if (nameValid && passwordValid && passwordsMatch && emailVerified) {
                registerBtn.disabled = false;
            } else {
                registerBtn.disabled = true;
            }
        }
        
        // Event Listeners
        if (sendOtpBtn) {
            sendOtpBtn.addEventListener('click', function() {
                resetErrors();
                
                if (!emailInput.value) {
                    emailError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your email';
                    return;
                }
                
                setButtonLoading(sendOtpBtn, true);
                
                // Send AJAX request to send OTP
                fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'send_otp',
                        'email': emailInput.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(sendOtpBtn, false);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        otpVerificationSection.style.display = 'block';
                        
                        if (data.data.resendTime) {
                            startResendTimer(60);
                        }
                    } else {
                        emailError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    }
                })
                .catch(error => {
                    setButtonLoading(sendOtpBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        if (verifyOtpBtn) {
            verifyOtpBtn.addEventListener('click', function() {
                resetErrors();
                
                if (!otpInput.value) {
                    otpError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter verification code';
                    return;
                }
                
                setButtonLoading(verifyOtpBtn, true);
                
                // Send AJAX request to verify OTP
                fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'verify_otp',
                        'otp': otpInput.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(verifyOtpBtn, false);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        otpVerificationSection.style.display = 'none';
                        enablePersonalInfoSection();
                    } else {
                        otpError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    }
                })
                .catch(error => {
                    setButtonLoading(verifyOtpBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        if (resendOtpBtn) {
            resendOtpBtn.addEventListener('click', function() {
                resetErrors();
                
                setButtonLoading(resendOtpBtn, true);
                
                // Send AJAX request to resend OTP
                fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'send_otp',
                        'email': emailInput.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(resendOtpBtn, false);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        if (data.data.resendTime) {
                            startResendTimer(60);
                        }
                    } else {
                        otpError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    }
                })
                .catch(error => {
                    setButtonLoading(resendOtpBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        if (changeEmailBtn) {
            changeEmailBtn.addEventListener('click', function() {
                resetErrors();
                
                // Enable email input and hide OTP verification section
                emailInput.readOnly = false;
                otpVerificationSection.style.display = 'none';
                
                // Show send OTP button
                if (sendOtpBtn) sendOtpBtn.style.display = 'block';
            });
        }
        
        if (registerBtn) {
            registerBtn.addEventListener('click', function() {
                resetErrors();
                
                // Validate inputs
                let hasError = false;
                
                if (!nameInput.value) {
                    nameError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your name';
                    hasError = true;
                }
                
                if (!validatePasswordRequirements()) {
                    passwordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password does not meet requirements';
                    hasError = true;
                }
                
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                    hasError = true;
                }
                
                if (hasError) return;
                
                setButtonLoading(registerBtn, true);
                
                // Send AJAX request to register user
                fetch('register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'register',
                        'name': nameInput.value,
                        'password': passwordInput.value,
                        'confirm_password': confirmPasswordInput.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(registerBtn, false);
                    
                    if (data.success) {
                        // Show success message
                        document.querySelector('.registration-container').innerHTML = `
                            <div class="success-container">
                                <div class="success-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h2 class="success-message">Account Created Successfully!</h2>
                                <p class="success-details">
                                    Your account has been created successfully.
                                </p>
                                <a href="login.php" class="primary-btn">
                                    <i class="fas fa-sign-in-alt"></i> Login Now
                                </a>
                                <p class="redirect-message">
                                    <i class="fas fa-spinner fa-spin"></i> Redirecting to login page...
                                </p>
                            </div>
                        `;
                        
                        // Redirect to login page after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000);
                    } else {
                        if (data.errors && data.errors.length > 0) {
                            // Display specific password errors
                            passwordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.errors.join('<br><i class="fas fa-exclamation-circle"></i> ');
                        } else {
                            showNotification(data.message, 'error');
                        }
                    }
                })
                .catch(error => {
                    setButtonLoading(registerBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        // Password validation on input
        if (passwordInput) {
            passwordInput.addEventListener('input', validatePasswordRequirements);
        }
        
        // Confirm password validation on input
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                } else {
                    confirmPasswordError.innerHTML = '';
                }
                updateRegisterButtonState();
            });
        }
        
        // Name validation on input
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                if (nameInput.value.trim() === '') {
                    nameError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter your name';
                } else {
                    nameError.innerHTML = '';
                }
                updateRegisterButtonState();
            });
        }
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Initialize timer if needed
        if (timeLeft > 0 && timerElement) {
            startResendTimer(timeLeft);
        }
        
        // Enable personal info section if email is verified
        if (emailVerified) {
            enablePersonalInfoSection();
        }
        
        // Initial password validation check
        if (passwordInput && passwordInput.value) {
            validatePasswordRequirements();
        }
    </script>
</body>
</html>