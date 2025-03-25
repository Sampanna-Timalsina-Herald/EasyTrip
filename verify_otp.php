<?php
include 'connect.php';
session_start();

// Check if email is set, otherwise redirect to registration
if (!isset($_GET['email']) && !isset($_POST['email'])) {
    header("Location: register.php");
    exit();
}

// Get email from GET or POST
$email = isset($_GET['email']) ? $_GET['email'] : $_POST['email'];

if (isset($_POST['verifyOtp'])) {
    $enteredOtp = mysqli_real_escape_string($conn, $_POST['otp']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if OTP matches for the given email
    $checkOtp = "SELECT * FROM users WHERE email='$email' AND otp='$enteredOtp'";
    $result = $conn->query($checkOtp);

    if ($result->num_rows > 0) {
        // ✅ OTP is correct → Remove OTP & set session
        $updateQuery = "UPDATE users SET otp=NULL WHERE email='$email'";
        if ($conn->query($updateQuery) === TRUE) {
            $_SESSION['verified_email'] = $email; // Set session to track verification
            echo "OTP Verified Successfully! Redirecting to login...";
            header("refresh:2;url=login.php"); // Redirect after 2 seconds
            exit();
        } else {
            echo "Error updating OTP status.";
        }
    } else {
        echo "❌ Invalid OTP. Please try again.";
    }
}

// Resend OTP Functionality
if (isset($_POST['resendOtp'])) {
    $newOtp = rand(100000, 999999);

    // Update OTP in the database
    $updateOtpQuery = "UPDATE users SET otp='$newOtp' WHERE email='$email'";
    if ($conn->query($updateOtpQuery) === TRUE) {
        // Send the new OTP to the user's email
        include 'send_otp.php'; // Ensure this file is correctly configured for sending emails
        if (sendOTP($email, $newOtp)) {
            echo "✅ A new OTP has been sent to your email.";
        } else {
            echo "❌ Error sending OTP. Check SMTP settings.";
        }
    } else {
        echo "❌ Error resending OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>
    <form method="post" action="verify_otp.php">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <label>Enter OTP:</label>
        <input type="text" name="otp" required>
        <button type="submit" name="verifyOtp">Verify</button>
    </form>

    <form method="post" action="verify_otp.php">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <button type="submit" name="resendOtp">Resend OTP</button>
    </form>
</body>
</html>
