<?php
include 'includes/header.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Initialize variables
$success = $error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate form data
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            
            // Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
            $mail->isSMTP();                          // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';     // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                 // Enable SMTP authentication
            $mail->Username   = 'your-email@gmail.com'; // SMTP username (replace with your email)
            $mail->Password   = 'your-app-password';  // SMTP password (use app password for Gmail)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port       = 587;                  // TCP port to connect to; use 587 for TLS
            
            // Recipients
            $mail->setFrom('your-email@gmail.com', 'EasyTrip Contact Form');
            $mail->addAddress('yatrasathi0@gmail.com', 'EasyTrip Team'); // Add a recipient
            $mail->addReplyTo($email, $name);
            
            // Content
            $mail->isHTML(true);                      // Set email format to HTML
            $mail->Subject = "Contact Form: $subject";
            
            // Email body
            $mail->Body = "
            <html>
            <head>
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
                        <p><span class='label'>Name:</span> " . htmlspecialchars($name) . "</p>
                        <p><span class='label'>Email:</span> " . htmlspecialchars($email) . "</p>
                        <p><span class='label'>Subject:</span> " . htmlspecialchars($subject) . "</p>
                        <p><span class='label'>Date:</span> " . date('Y-m-d H:i:s') . "</p>
                    </div>
                    <div class='label'>Message:</div>
                    <div class='message-box'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Plain text alternative for non-HTML mail clients
            $mail->AltBody = "Contact Form Submission\n\nName: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
            
            // Send the email
            $mail->send();
            
            // Log the contact submission
            $log_file = 'logs/contact_submissions.log';
            $log_dir = dirname($log_file);
            
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            
            $log_entry = date('Y-m-d H:i:s') . " | Name: $name | Email: $email | Subject: $subject\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            // Store in database if available
            if (isset($conn) && $conn) {
                // Check if table exists, create if not
                $conn->query("CREATE TABLE IF NOT EXISTS contact_submissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    submission_date DATETIME NOT NULL,
                    processed TINYINT(1) DEFAULT 1
                )");
                
                $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message, submission_date) 
                                       VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $name, $email, $subject, $message);
                $stmt->execute();
            }
            
            $success = "Thank you for your message! We'll get back to you soon.";
            // Clear form data after successful submission
            $name = $email = $subject = $message = '';
            
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            // Log the error
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
    }
}
?>

<div class="contact-page">
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <div class="hero-content animate-fade-in">
                <h1>Contact Us</h1>
                <p>We'd love to hear from you. Get in touch with our team for any inquiries or assistance.</p>
                <div class="hero-buttons">
                    <a href="#contact-form" class="btn btn-primary">Send Message</a>
                    <a href="#faq-section" class="btn btn-outline">View FAQs</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Information Section -->
    <section class="contact-section" id="contact-form">
        <div class="container">
            <div class="section-title">
                <span class="subtitle">Get In Touch</span>
                <h2>Contact Information</h2>
                <div class="title-separator"><span></span></div>
                <p>Have questions or feedback? Reach out to us through any of these channels or use our contact form.</p>
            </div>
            
            <div class="contact-info-wrapper">
                <div class="contact-info-container">
                    <div class="info-cards">
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="details">
                                <h3>Our Location</h3>
                                <p>Kathmandu, Nepal</p>
                                <a href="https://goo.gl/maps/1234" target="_blank" class="info-link">Get Directions <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="details">
                                <h3>Phone Number</h3>
                                <p>+977 9823514674</p>
                                <p>+977 9861408842</p>
                                <a href="tel:+97714123456" class="info-link">Call Now <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="details">
                                <h3>Email Address</h3>
                                <p>yatrasathi0@gmail.com</p>
                                <p>support@easytrip.com</p>
                                <a href="mailto:yatrasathi0@gmail.com" class="info-link">Send Email <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="details">
                                <h3>Working Hours</h3>
                                <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                <p>Saturday: 10:00 AM - 4:00 PM</p>
                                <p>Sunday: Closed</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-connect">
                        <h3>Connect With Us</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-icon" title="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form-container">
                    <div class="form-header">
                        <h2>Send Us a Message</h2>
                        <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                    </div>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="contactForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="contact-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your Name</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Your Email</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="Enter your email address" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <div class="input-with-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" placeholder="What is this regarding?" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <div class="input-with-icon textarea-icon">
                                <i class="fas fa-comment-alt"></i>
                                <textarea id="message" name="message" rows="5" placeholder="How can we help you?" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-footer">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <div class="section-title">
                <span class="subtitle">Our Location</span>
                <h2>Find Us</h2>
                <div class="title-separator"><span></span></div>
            </div>
            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d56516.31625953805!2d85.29111337431642!3d27.70895594443863!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb198a307baabf%3A0xb5137c1bf18db1ea!2sKathmandu%2044600%2C%20Nepal!5e0!3m2!1sen!2sus!4v1620120000000!5m2!1sen!2sus" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section bg-light" id="faq-section">
        <div class="container">
            <div class="section-title">
                <span class="subtitle">Common Questions</span>
                <h2>Frequently Asked Questions</h2>
                <div class="title-separator"><span></span></div>
                <p>Find quick answers to common questions about our services.</p>
            </div>
            
            <div class="faq-container">
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I book a hotel and bus together?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>You can book a hotel and bus together by browsing our travel packages or by selecting individual hotels and buses and adding them to your booking. Our system will help you coordinate the dates and times for a seamless travel experience.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What is your cancellation policy?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Our cancellation policy varies depending on the hotel and bus operator. Generally, cancellations made 48 hours before the scheduled check-in or departure time are eligible for a full refund. Please check the specific terms for each booking.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How can I get travel advice for my trip?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>You can request travel advice through our "Get Advice" page. Our experienced travel agents will provide personalized recommendations based on your preferences and requirements.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I verify my payment was received?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>After making a payment, you will receive a confirmation email with your booking details. You can also check your booking status in the "My Bookings" section of your account.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Can I modify my booking after confirmation?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, you can modify your booking by logging into your account and visiting the "My Bookings" section. Depending on the hotel and bus operator policies, modification fees may apply.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Do you offer group discounts?</h3>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, we offer special discounts for group bookings of 10 or more people. Please contact our customer service team for more information about group rates and packages.</p>
                        </div>
                    </div>
                </div>
                
                <div class="more-questions">
                    <h3>Still have questions?</h3>
                    <p>If you couldn't find the answer to your question, please don't hesitate to contact us directly.</p>
                    <a href="#contact-form" class="btn btn-primary">Contact Us</a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Journey?</h2>
                <p>Discover the best hotels and transportation options for your Nepal adventure.</p>
                <div class="cta-buttons">
                    <a href="hotels.php" class="btn btn-primary">Browse Hotels&Buses</a>
                </div>
            </div>
        </div>
    </section>
</div>

<link rel="stylesheet" href="assets/css/about-contact.css">
<script src="assets/js/contact-form.js"></script>

<?php
include 'includes/footer.php';
?>
