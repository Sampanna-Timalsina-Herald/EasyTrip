<?php
session_start();
require_once 'config/database.php';

// Include PHPMailer
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$email = $role = '';
$otpSent = false;

// Generate OTP function
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Process AJAX requests
if (isset($_POST['action'])) {
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    // Send OTP action
    if ($_POST['action'] === 'send_otp') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = trim($_POST['role']);
        
        // Validate inputs
        if ($email === '' || $password === '' || $role === '') {
            $response['message'] = 'Please enter all fields (Email, Password, and Role).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
        } else {
            // Prepare SQL statement to fetch user
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
            $stmt->bind_param("ss", $email, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verify user exists
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Store user data in session temporarily
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_user_name'] = $user['name'];
                    $_SESSION['temp_user_email'] = $user['email'];
                    $_SESSION['temp_user_role'] = $user['role'];
                    
                    // Generate and store OTP
                    $otp = generateOTP();
                    $_SESSION['login_otp'] = $otp;
                    $_SESSION['login_otp_time'] = time();
                    $_SESSION['login_resend_time'] = time(); // Set resend timer
                    
                    // Send OTP via email
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
                        $mail->Subject = 'Your Easy Trip Login Verification Code';

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
                                
                                <h2 style="color: #333; margin-bottom: 25px;">Login Verification Code</h2>
                                <p style="color: #666; line-height: 1.6;">To complete your login to Easy Trip, please use the following verification code:</p>
                                
                                <div class="otp-box">
                                    <div class="otp-code">$otp</div>
                                    <p style="color: #888; margin: 0;">Valid for 10 minutes</p>
                                </div>

                                <p style="color: #666; line-height: 1.6;">
                                    If you did not attempt to log in, please ignore this email or contact our support team at 
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
                    } catch (Exception $e) {
                        $response['message'] = 'Failed to send verification code. Please try again.';
                    }
                } else {
                    $response['message'] = 'Invalid password. Please try again.';
                }
            } else {
                $response['message'] = 'User not found. Please check your email, role, or register.';
            }
            $stmt->close();
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Verify OTP action
    if ($_POST['action'] === 'verify_otp') {
        $userOTP = trim($_POST['otp']);
        
        if (empty($userOTP)) {
            $response['message'] = 'Please enter verification code';
        } elseif (!isset($_SESSION['login_otp'])) {
            $response['message'] = 'Verification code session expired';
        } elseif (time() - $_SESSION['login_otp_time'] > 600) { // 10 minutes expiration
            $response['message'] = 'Verification code has expired';
            unset($_SESSION['login_otp']);
            unset($_SESSION['login_otp_time']);
        } elseif ($userOTP != $_SESSION['login_otp']) {
            $response['message'] = 'Invalid verification code';
        } else {
            // Complete login process
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['user_name'] = $_SESSION['temp_user_name'];
            $_SESSION['user_email'] = $_SESSION['temp_user_email'];
            $_SESSION['user_role'] = $_SESSION['temp_user_role'];
            
            // Clear temporary and OTP data
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_name']);
            unset($_SESSION['temp_user_email']);
            unset($_SESSION['temp_user_role']);
            unset($_SESSION['login_otp']);
            unset($_SESSION['login_otp_time']);
            
            $response['success'] = true;
            $response['message'] = 'Login successful!';
            
            // Redirect based on user role
            switch ($_SESSION['user_role']) {
                case "admin":
                    $response['data']['redirect'] = 'admin/dashboard.php';
                    break;
                case "hotel_owner":
                    $response['data']['redirect'] = 'hotel-owner/dashboard.php';
                    break;
                case "bus_operator":
                    $response['data']['redirect'] = 'bus-operator/dashboard.php';
                    break;
                case "agent":
                    $response['data']['redirect'] = 'travel-agent/travel-agent-dashboard.php';
                    break;
                case "traveler":
                    $response['data']['redirect'] = 'index.php';
                    break;
                default:
                    $response['data']['redirect'] = 'index.php';
                    break;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Resend OTP action
    if ($_POST['action'] === 'resend_otp') {
        // Check if resend is allowed (60 seconds cooldown)
        if (isset($_SESSION['login_resend_time']) && (time() - $_SESSION['login_resend_time'] < 60)) {
            $timeLeft = 60 - (time() - $_SESSION['login_resend_time']);
            $response['message'] = "Please wait $timeLeft seconds before requesting another OTP";
        } else {
            // Generate and store OTP
            $otp = generateOTP();
            $_SESSION['login_otp'] = $otp;
            $_SESSION['login_otp_time'] = time();
            $_SESSION['login_resend_time'] = time(); // Set resend timer
            
            // Send OTP via email
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
                $mail->addAddress($_SESSION['temp_user_email']);
                $mail->isHTML(true);
                $mail->Subject = 'Your Easy Trip Login Verification Code';

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
                        
                        <h2 style="color: #333; margin-bottom: 25px;">Login Verification Code</h2>
                        <p style="color: #666; line-height: 1.6;">To complete your login to Easy Trip, please use the following verification code:</p>
                        
                        <div class="otp-box">
                            <div class="otp-code">$otp</div>
                            <p style="color: #888; margin: 0;">Valid for 10 minutes</p>
                        </div>

                        <p style="color: #666; line-height: 1.6;">
                            If you did not attempt to log in, please ignore this email or contact our support team at 
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
                $response['message'] = 'Verification code resent successfully!';
                $response['data'] = [
                    'resendTime' => time() + 60
                ];
            } catch (Exception $e) {
                $response['message'] = 'Failed to send verification code. Please try again.';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

}

// Process regular form submission (for non-JS fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Don't trim password
    $role = trim($_POST['role']);

    // Strict validation
    if ($email === '' || $password === '' || $role === '') {
        $error = 'Please enter all fields (Email, Password, and Role).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Prepare SQL statement to fetch user
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verify user exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password exactly (spaces included)
            if (password_verify($password, $user['password'])) {
                // For non-JS fallback, we'll set the OTP and show the OTP form
                $otp = generateOTP();
                $_SESSION['login_otp'] = $otp;
                $_SESSION['login_otp_time'] = time();
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_user_name'] = $user['name'];
                $_SESSION['temp_user_email'] = $user['email'];
                $_SESSION['temp_user_role'] = $user['role'];
                
                // Send OTP via email
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
                    $mail->Subject = 'Your Easy Trip Login Verification Code';

                    // Email template (same as above)
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
                            
                            <h2 style="color: #333; margin-bottom: 25px;">Login Verification Code</h2>
                            <p style="color: #666; line-height: 1.6;">To complete your login to Easy Trip, please use the following verification code:</p>
                            
                            <div class="otp-box">
                                <div class="otp-code">$otp</div>
                                <p style="color: #888; margin: 0;">Valid for 10 minutes</p>
                            </div>

                            <p style="color: #666; line-height: 1.6;">
                                If you did not attempt to log in, please ignore this email or contact our support team at 
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
                    $otpSent = true;
                } catch (Exception $e) {
                    $error = 'Failed to send verification code. Please try again.';
                }
            } else {
                $error = 'Invalid password. Please try again.';
            }
        } else {
            $error = 'User not found. Please check your email, role, or register.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Easy Trip</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-image: url('assets/images/travel-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%);
            z-index: -1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 1.2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            transition: all 0.3s ease;
        }

        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .back-to-home:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
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

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #ddd;
            border-radius: 0.7rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.istockphoto.com%2Fstock-photos%2Fnature-and-landscapes&psig=AOvVaw0aA-OSHLoBcQKSxZm6Gk05&ust=1746212714066000&source=images&cd=vfe&opi=89978449&ved=0CBEQjRxqFwoTCPjMlLf7go0DFQAAAAAdAAAAABAE' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .btn {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btn:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4292 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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

        .register-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .demo-accounts {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .demo-accounts h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #333;
        }

        .demo-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .demo-box p {
            margin-bottom: 8px;
            color: #555;
            font-size: 0.95rem;
        }

        .demo-box strong {
            color: #333;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 40px;
            cursor: pointer;
            color: #666;
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

        .otp-input {
            letter-spacing: 8px;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 600;
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        @media (max-width: 768px) {
            .login-container {
                padding: 2rem;
            }

            .back-to-home {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>

    <div class="login-container">
        <h2>Welcome Back</h2>
        
        <div id="notification"></div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['registration_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registration successful! Please login with your credentials.
            </div>
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>

        <div id="loginForm" style="display: <?php echo $otpSent ? 'none' : 'block'; ?>">
            <form id="loginFormElement">
                <div class="form-group">
                    <label for="role">Select Role</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        <option value="hotel_owner" <?php echo (isset($_POST['role']) && $_POST['role'] == 'hotel_owner') ? 'selected' : ''; ?>>Hotel Owner</option>
                        <option value="bus_operator" <?php echo (isset($_POST['role']) && $_POST['role'] == 'bus_operator') ? 'selected' : ''; ?>>Bus Operator</option>
                        <option value="agent" <?php echo (isset($_POST['role']) && $_POST['role'] == 'agent') ? 'selected' : ''; ?>>Travel Agent</option>
                        <option value="traveler" <?php echo (isset($_POST['role']) && $_POST['role'] == 'traveler') ? 'selected' : ''; ?>>Traveler</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="form-group">
                    <button type="button" id="loginBtn" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Continue
                    </button>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                </div>
            </form>
        </div>

        <div id="otpForm" style="display: <?php echo $otpSent ? 'block' : 'none'; ?>" class="fade-in">
            <div class="otp-info">
                <p><i class="fas fa-info-circle"></i> A verification code has been sent to your email.</p>
                <p>Please check your inbox and spam folder.</p>
            </div>
            
            <form id="otpFormElement">
                <div class="form-group">
                    <label for="otp">Verification Code</label>
                    <input type="text" id="otp" name="otp" class="form-control otp-input" placeholder="Enter 6-digit code" maxlength="6" required>
                    <div class="error" id="otpError"></div>
                
                    <div class="timer" id="resendTimer" style="display: none;">
                        <i class="fas fa-clock"></i> Resend available in <span id="timer">60</span> seconds
                    </div>
                </div>

                <div class="form-group">
                    <button type="button" id="verifyOtpBtn" class="btn">
                        <i class="fas fa-check-circle"></i> Verify & Login
                    </button>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" id="resendOtpBtn" class="btn" style="background: none; color: #667eea; box-shadow: none;">
                        <i class="fas fa-paper-plane"></i> Resend Verification Code
                    </button>
                    <button type="button" id="backToLoginBtn" class="btn" style="background: none; color: #667eea; box-shadow: none;">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </button>
                </div>
            </form>
        </div>

        <!-- Demo login information -->
        <div class="demo-accounts">
            <h3>Demo Accounts</h3>
            <div class="demo-box">
                <p><strong>Admin:</strong> yatrasathi0@gmail.com / password</p>
                <p><strong>Hotel Owner:</strong> sampannactwn@gmail.com / password</p>
                <p><strong>Bus Operator:</strong> bus@example.com / password</p>
                <p><strong>Agent:</strong> np03cs4a230406@heraldcollege.edu.np / password</p>
                <p><strong>Traveler:</strong> sampannat1@gmail.com / password</p>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const loginForm = document.getElementById('loginForm');
        const otpForm = document.getElementById('otpForm');
        const loginBtn = document.getElementById('loginBtn');
        const verifyOtpBtn = document.getElementById('verifyOtpBtn');
        const backToLoginBtn = document.getElementById('backToLoginBtn');
        const notification = document.getElementById('notification');
        
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
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Event Listeners
        if (loginBtn) {
            loginBtn.addEventListener('click', function() {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const role = document.getElementById('role').value;
                
                if (!email || !password || !role) {
                    showNotification('Please fill in all fields', 'error');
                    return;
                }
                
                setButtonLoading(loginBtn, true);
                
                // Send AJAX request to send OTP
                fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'send_otp',
                        'email': email,
                        'password': password,
                        'role': role
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(loginBtn, false);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        loginForm.style.display = 'none';
                        otpForm.style.display = 'block';
                        startResendTimer(60);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    setButtonLoading(loginBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
        
        if (verifyOtpBtn) {
            verifyOtpBtn.addEventListener('click', function() {
                const otp = document.getElementById('otp').value;
                
                if (!otp) {
                    showNotification('Please enter verification code', 'error');
                    return;
                }
                
                setButtonLoading(verifyOtpBtn, true);
                
                // Send AJAX request to verify OTP
                fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'verify_otp',
                        'otp': otp
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setButtonLoading(verifyOtpBtn, false);
                    
                    if (data.success) {
                        showNotification(data.message, 'success');
                        
                        // Redirect to appropriate dashboard
                        if (data.data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.data.redirect;
                            }, 1000);
                        }
                    } else {
                        showNotification(data.message, 'error');
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
                setButtonLoading(resendOtpBtn, true);
            
                // Send AJAX request to resend OTP
                fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'resend_otp'
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
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    setButtonLoading(resendOtpBtn, false);
                    showNotification('An error occurred. Please try again.', 'error');
                });
            });
        }
    
        function startResendTimer(seconds) {
            const resendTimer = document.getElementById('resendTimer');
            const timerElement = document.getElementById('timer');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
        
            let timeLeft = seconds;
        
            resendTimer.style.display = 'flex';
            resendOtpBtn.disabled = true;
        
            const timerInterval = setInterval(function() {
                timeLeft--;
                timerElement.textContent = timeLeft;
            
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    resendTimer.style.display = 'none';
                    resendOtpBtn.disabled = false;
                }
            }, 1000);
        }
        
        if (backToLoginBtn) {
            backToLoginBtn.addEventListener('click', function() {
                otpForm.style.display = 'none';
                loginForm.style.display = 'block';
            });
        }
    </script>
</body>
</html>
