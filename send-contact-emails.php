<?php
/**
 * This script can be run manually or via cron to process contact form submissions
 * and send them to the specified email address.
 * 
 * Usage: php send-contact-emails.php
 */

// Include database connection
require_once 'config/database.php';

// Set recipient email
$recipient_email = 'yatrasathi0@gmail.com';

// Check if database connection is available
if (!isset($conn) || !$conn) {
    die("Database connection not available.\n");
}

// Create table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    submission_date DATETIME NOT NULL,
    processed TINYINT(1) DEFAULT 0
)");

// Get unprocessed submissions
$result = $conn->query("SELECT * FROM contact_submissions WHERE processed = 0 ORDER BY submission_date ASC");

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " unprocessed contact submissions.\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "Processing submission ID: " . $row['id'] . "\n";
        
        // Prepare email content
        $to = $recipient_email;
        $subject = "Contact Form: " . $row['subject'];
        
        $message = "
        <html>
        <head>
            <title>Contact Form Submission</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                h2 { color: #0066cc; }
                .info { margin-bottom: 20px; }
                .label { font-weight: bold; }
                .message-box { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0066cc; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>New Contact Form Submission</h2>
                <div class='info'>
                    <p><span class='label'>Name:</span> " . htmlspecialchars($row['name']) . "</p>
                    <p><span class='label'>Email:</span> " . htmlspecialchars($row['email']) . "</p>
                    <p><span class='label'>Subject:</span> " . htmlspecialchars($row['subject']) . "</p>
                    <p><span class='label'>Date:</span> " . $row['submission_date'] . "</p>
                </div>
                <div class='label'>Message:</div>
                <div class='message-box'>
                    " . nl2br(htmlspecialchars($row['message'])) . "
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Set headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $row['name'] . " <" . $row['email'] . ">" . "\r\n";
        $headers .= "Reply-To: " . $row['email'] . "\r\n";
        
        // Try to send email using mail() function
        $mail_sent = false;
        
        try {
            // Attempt to send with PHP mail()
            $mail_sent = mail($to, $subject, $message, $headers);
            
            if ($mail_sent) {
                echo "Email sent successfully to $to\n";
            } else {
                echo "Failed to send email using mail() function.\n";
                
                // Alternative: You could implement SMTP sending here using PHPMailer
                // or another library if mail() fails
            }
        } catch (Exception $e) {
            echo "Error sending email: " . $e->getMessage() . "\n";
        }
        
        // Mark as processed regardless of email success (to prevent endless retries)
        // In a production environment, you might want to only mark as processed if email was sent
        $update = $conn->prepare("UPDATE contact_submissions SET processed = 1 WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();
        
        echo "Marked submission ID: " . $row['id'] . " as processed.\n";
    }
} else {
    echo "No unprocessed contact submissions found.\n";
}

$conn->close();
echo "Done processing contact submissions.\n";
