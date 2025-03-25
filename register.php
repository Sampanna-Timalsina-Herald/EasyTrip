<?php
include 'send_otp.php';
include 'connect.php';
session_start();

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// User Registration with OTP
if (isset($_POST['signUp'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['fName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']); // Consider password_hash() for better security
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : 'User'; // ✅ Default role if not provided
    $otp = rand(100000, 999999); // Generate a 6-digit OTP

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        echo "Email Address Already Exists!";
    } else {
        // Insert user data with OTP and role
        $insertQuery = "INSERT INTO users (firstName, lastName, email, password, role, otp)
                        VALUES ('$firstName', '$lastName', '$email', '$password', '$role', '$otp')";
        
        if ($conn->query($insertQuery) === TRUE) {
            // Send OTP to user's email
            if (sendOTP($email, $otp)) {
                header("Location: verify_otp.php?email=$email");
                exit();
            } else {
                echo "Error sending OTP. Please try again.";
            }
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// User Login (Requires OTP Verification)
if (isset($_POST['signIn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ✅ Only block if OTP is NOT NULL
        if (!is_null($row['otp']) && $row['otp'] !== '') {
            echo "❌ Please verify your OTP before logging in. <a href='verify_otp.php?email=$email'>Verify Here</a>";
            exit();
        }

        // Set session variables
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role'] ?? 'User';

        // Redirect based on role
        if ($row['role'] == 'Admin') {
            header("Location: admin_dashboard.php");
        } elseif ($row['role'] == 'Bus Operator') {
            header("Location: index.php");
        } elseif ($row['role'] == 'Hotel Operator') {
            header("Location: hotelperator.php");
        } elseif ($row['role'] == 'Travel Agent') {
            header("Location: travelagent.php");
        } 
         else {
            header("Location: homepage.php");
        }
        exit();
    } else {
        echo "❌ Incorrect Email or Password";
    }
}

?>
