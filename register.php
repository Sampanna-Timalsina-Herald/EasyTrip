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
    'confirm_password' => '', 'otp' => ''
];
$formData = [
    'name' => '',
    'email' => isset($_SESSION['register_email']) ? $_SESSION['register_email'] : ''
];

// Handle registration steps
if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 1;  // Step 1: Email entry by default
}

// Generate OTP function
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle back button
    if (isset($_POST['back'])) {
        // Reset session to Step 1 and clear OTP-related data
        $_SESSION['step'] = 1;
        unset($_SESSION['otp']); // Clear OTP data
        unset($_SESSION['otp_time']); // Clear OTP time
        unset($_SESSION['register_email']); // Clear the email session data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['send_otp'])) {
        // Step 1: Email submission
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $errors['email'] = 'Please enter your email';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } else {
            // Check if email exists in the database
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors['email'] = 'Email already registered';
            } else {
                // Generate and send OTP
                $otp = generateOTP();
                $_SESSION['otp'] = $otp;
                $_SESSION['register_email'] = $email;
                $_SESSION['otp_time'] = time();
                
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

                    $mail->setFrom('your_email@gmail.com', 'Travel Community');  // Update with your email
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Registration OTP';
                    $mail->Body = "Your OTP is: <strong>$otp</strong>, it expires in 10 minutes";

                    $mail->send();
                    $_SESSION['step'] = 2; // Move to OTP verification step
                } catch (Exception $e) {
                    $errors['email'] = 'Failed to send OTP. Please try again.';
                }
            }
            $stmt->close();
        }
        $formData['email'] = $email;
    } elseif (isset($_POST['verify_otp'])) {
        // Step 2: OTP verification
        $userOTP = trim($_POST['otp']);
        
        if (empty($userOTP)) {
            $errors['otp'] = 'Please enter OTP';
        } elseif (!isset($_SESSION['otp'])) {
            $errors['otp'] = 'OTP session expired';
            $_SESSION['step'] = 1; // Reset to email input if OTP session expired
        } elseif (time() - $_SESSION['otp_time'] > 600) { // 10 minutes expiration
            $errors['otp'] = 'OTP has expired';
            unset($_SESSION['otp']); // Clear OTP data
            unset($_SESSION['otp_time']); // Clear OTP time
            $_SESSION['step'] = 1; // Reset to email input
        } elseif ($userOTP != $_SESSION['otp']) {
            $errors['otp'] = 'Invalid OTP';
        } else {
            $_SESSION['step'] = 3; // OTP verified, move to registration step
            unset($_SESSION['otp']); // Clear OTP data after successful verification
        }
    } elseif (isset($_POST['register'])) {
        // Step 3: Final registration
        $formData['name'] = trim($_POST['name']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        // Validate name
        if (empty($formData['name'])) {
            $errors['name'] = 'Please enter your name';
        }
        
        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Please enter password';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // If no errors, register user
        if (!array_filter($errors)) {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'traveler')");
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sss", $formData['name'], $_SESSION['register_email'], $password_hash);
            
            if ($stmt->execute()) {
                // Clear session data after successful registration
                session_unset();
                session_destroy();
                header('Location: login.php');
                exit;
            }
            $stmt->close();
        }
 
       }
   }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Community - Register</title>
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
        }

        .registration-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        button {
            width: 100%;
            padding: 1rem;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .primary-btn {
            background: #667eea;
        }

        .primary-btn:hover {
            background: #764ba2;
        }

        .secondary-btn {
            background: #6c757d;
            width: auto;
            padding: 0.75rem 1.5rem;
        }

        .secondary-btn:hover {
            background: #5a6268;
        }

        .step-indicator {
            text-align: center;
            color: #666;
            margin-bottom: 1rem;
        }

        .form-footer {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .login-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1>Join Our Travel Community</h1>
        <div class="step-indicator">Step <?= $_SESSION['step'] ?> of 3</div>

        <!-- Step 1: Email Input -->
        <form class="form-step <?= ($_SESSION['step'] == 1) ? 'active' : '' ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="<?= $formData['email'] ?>" placeholder="Enter your email" required>
                <?php if ($errors['email']): ?>
                    <div class="error"><?= $errors['email'] ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" name="send_otp" class="primary-btn">Send OTP</button>
        </form>

        <!-- Step 2: OTP Verification -->
        <form class="form-step <?= ($_SESSION['step'] == 2) ? 'active' : '' ?>" method="post">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" name="otp" id="otp" placeholder="Enter OTP sent to your email" required>
                <?php if ($errors['otp']): ?>
                    <div class="error"><?= $errors['otp'] ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" name="verify_otp" class="primary-btn">Verify OTP</button>
        </form>

        <!-- Step 3: Registration Form -->
        <form class="form-step <?= ($_SESSION['step'] == 3) ? 'active' : '' ?>" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" value="<?= $formData['name'] ?>" placeholder="Enter your name" required>
                <?php if ($errors['name']): ?>
                    <div class="error"><?= $errors['name'] ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Create a password" required>
                <?php if ($errors['password']): ?>
                    <div class="error"><?= $errors['password'] ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                <?php if ($errors['confirm_password']): ?>
                    <div class="error"><?= $errors['confirm_password'] ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" name="register" class="primary-btn">Register</button>
        </form>

        <div class="text-center">
            <a href="login.php" class="login-link">Already have an account? Login here</a>
        </div>
    </div>
</body>
</html>