<?php
// This file handles the AJAX form submission
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate form data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['message'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Please enter a valid email address.";
    } else {
        // Store the submission data
        $submission_data = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Log the contact submission to a file
        $log_file = 'logs/contact_submissions.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = date('Y-m-d H:i:s') . " | Name: $name | Email: $email | Subject: $subject | Message: " . substr($message, 0, 100) . "...\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Store in database if connection is available
        if (isset($conn) && $conn) {
            try {
                // Check if table exists, create if not
                $conn->query("CREATE TABLE IF NOT EXISTS contact_submissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    submission_date DATETIME NOT NULL,
                    processed TINYINT(1) DEFAULT 0
                )");
                
                $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message, submission_date) 
                                       VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $name, $email, $subject, $message);
                $stmt->execute();
                
                $response['success'] = true;
                $response['message'] = "Thank you for your message! We'll get back to you soon.";
            } catch (Exception $e) {
                // If database storage fails, still consider it a success if we logged to file
                $response['success'] = true;
                $response['message'] = "Thank you for your message! We'll get back to you soon.";
                
                // Log the error
                error_log("Database error in contact form: " . $e->getMessage());
            }
        } else {
            // No database connection, but we logged to file
            $response['success'] = true;
            $response['message'] = "Thank you for your message! We'll get back to you soon.";
        }
        
        // Send notification email using PHPMailer or another method if available
        // This would be implemented in a separate file
    }
}

// Return JSON response
echo json_encode($response);
