<?php
session_start();

// Check if user is logged in and is an admin or travel agent
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'agent')) {
  header('Location: login.php');
  exit;
}

include '../config/database.php';

// Include PHPMailer classes
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require '../PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Process form submission for providing advice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_advice'])) {
  $request_id = $_POST['request_id'];
  $advice = $_POST['advice'];
  
  // Begin transaction
  $conn->begin_transaction();
  
  try {
    // Update advice request (same as before)
    $stmt = $conn->prepare("UPDATE advice_requests SET advice = ?, status = 'completed' WHERE id = ?");
    $stmt->bind_param("si", $advice, $request_id);
    $stmt->execute();
    
    // Get user email (same as before)
    $stmt = $conn->prepare("SELECT user_email, destination FROM advice_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $user_email = $request['user_email'];
    $destination = $request['destination'];
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
      // Server settings
      $mail->isSMTP();
      $mail->Host       = 'smtp.gmail.com';       // SMTP server
      $mail->SMTPAuth   = true;                  // Enable SMTP authentication
      $mail->Username = 'sampannactwn@gmail.com';  // Update with your email
      $mail->Password = 'ijlp mgsu ekst hgwo';   // SMTP password (app password)
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = 587;                    // TCP port
      
      // Recipients
      $mail->setFrom('noreply@easytrip.com', 'EasyTrip');
      $mail->addAddress($user_email);  // Add a recipient
      
      // Content
      $mail->isHTML(true);
      $mail->Subject = "Travel Advice for " . $destination;
      
      // Email body (same as before)
      $mail->Body = '
      <html>
      <head>
        <title>Your Travel Advice</title>
        <style>
          body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
          .container { max-width: 600px; margin: 0 auto; padding: 20px; }
          .header { background-color: #3a86ff; color: white; padding: 15px; text-align: center; }
          .content { padding: 20px; background-color: #f9f9f9; }
          .advice { background-color: white; padding: 15px; border-left: 4px solid #3a86ff; margin: 20px 0; }
          .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <h2>Your Travel Advice is Ready!</h2>
          </div>
          <div class="content">
            <p>Dear Traveler,</p>
            <p>Thank you for requesting travel advice for <strong>' . $destination . '</strong>. Our travel expert has prepared the following recommendations for you:</p>
            
            <div class="advice">
              ' . nl2br($advice) . '
            </div>
            
            <p>We hope this information helps you plan an amazing trip! If you have any further questions, feel free to reply to this email or submit another request.</p>
            
            <p>Safe travels,<br>The  Team</p>
          </div>
          <div class="footer">
            <p>This email was sent to ' . $user_email . ' because you requested travel advice from EasyTrip.</p>
          </div>
        </div>
      </body>
      </html>
      ';
      
      $mail->send();
      $mail_sent = true;
    } catch (Exception $e) {
      $mail_sent = false;
      error_log('Mailer Error: ' . $mail->ErrorInfo);
    }
    
    // If email fails, still commit the database changes but show a warning
    if ($mail_sent) {
      $success_message = "Advice has been submitted successfully and sent to the user's email!";
    } else {
      $success_message = "Advice has been submitted successfully but there was an issue sending the email. The user can still view their advice on the website.";
    }
    
    // Commit transaction
    $conn->commit();
    
  } catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $error_message = "Error submitting advice: " . $e->getMessage();
  }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch advice requests based on filter
$sql = "SELECT ar.*, u.name as user_name 
        FROM advice_requests ar 
        LEFT JOIN users u ON ar.user_id = u.id";

if ($status_filter !== 'all') {
  $sql .= " WHERE ar.status = '$status_filter'";
}
$sql .= " ORDER BY ar.id DESC";

$result = $conn->query($sql);
$advice_requests = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $advice_requests[] = $row;
  }
}

// For demonstration, create sample data if database is empty
if (empty($advice_requests)) {
  $advice_requests = [
    [
      'id' => 1,
      'user_id' => 4,
      'user_name' => 'John Doe',
      'destination' => 'Pokhara',
      'details' => 'I am planning a 5-day trip to Pokhara with my family (2 adults, 2 children). We are interested in lake activities and hiking. What are the best areas to stay and must-see attractions?',
      'user_email' => 'john@example.com',
      'advice' => '',
      'status' => 'pending'
    ],
    [
      'id' => 2,
      'user_id' => null,
      'user_name' => null,
      'destination' => 'Kathmandu',
      'details' => 'Looking for budget-friendly accommodation and local food recommendations in Kathmandu for a solo traveler. Planning to stay for 3 days.',
      'user_email' => 'sarah@example.com',
      'advice' => 'For budget accommodation in Kathmandu, I recommend staying in the Thamel area. Some good options include Zostel Kathmandu, Elbrus Home, and Karma Boutique Hotel. For local food, be sure to try momos at Momo Star, thukpa at Thakali Kitchen, and newari cuisine at Honacha. Don\'t miss Durbar Square, Swayambhunath (Monkey Temple), and Pashupatinath Temple during your 3-day stay.',
      'status' => 'completed'
    ],
    [
      'id' => 3,
      'user_id' => 4,
      'user_name' => 'Mike Johnson',
      'destination' => 'Chitwan',
      'details' => 'We want to do a safari in Chitwan National Park. What\'s the best time to visit and how many days should we plan for?',
      'user_email' => 'mike@example.com',
      'advice' => '',
      'status' => 'pending'
    ]
  ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Travel Agent Dashboard - Travel Booking System</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --primary-color: #3a86ff;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --gradient-primary: linear-gradient(135deg, #3a86ff 0%, #6f86d6 100%);
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
        --shadow-lg: 0 4px 15px rgba(0,0,0,0.2);
        --border-radius: 12px;
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background-color: #f4f7fc;
        color: var(--dark-color);
    }

    /* Alerts */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: var(--border-radius);
        margin: 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 2px solid transparent;
        opacity: 0;
        animation: slideIn 0.3s ease forwards;
    }

    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .alert-success {
        background: #e8f5e9;
        border-color: #a5d6a7;
        color: #2e7d32;
    }

    .alert-danger {
        background: #ffebee;
        border-color: #ef9a9a;
        color: #c62828;
    }

    .alert i {
        font-size: 1.5rem;
    }

    /* Dashboard Layout */
    .dashboard {
        display: grid;
        grid-template-columns: 240px 1fr;
        min-height: 100vh;
    }

    .dashboard-sidebar {
        background: var(--gradient-primary);
        padding: 1.5rem;
        box-shadow: var(--shadow-lg);
        z-index: 100;
    }

    .dashboard-sidebar h2 {
        color: white;
        font-weight: 600;
        margin-bottom: 2rem;
        font-size: 1.5rem;
    }

    .dashboard-sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .dashboard-sidebar li a {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: rgba(255,255,255,0.8);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: var(--transition);
        text-decoration: none;
    }

    .dashboard-sidebar li a:hover,
    .dashboard-sidebar li a.active {
        background: rgba(255,255,255,0.1);
        color: white;
    }

    /* Main Content */
    .dashboard-content {
        padding: 2rem;
        background-color: white;
        min-height: 100vh;
    }

    .dashboard-header {
        margin-bottom: 2rem;
    }

    .dashboard-header h2 {
        color: var(--dark-color);
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }

    .dashboard-header p {
        color: var(--secondary-color);
    }

    /* Filter Bar */
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: white;
        padding: 1rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
    }

    .filter-options {
        display: flex;
        gap: 0.5rem;
    }

    .filter-link {
        padding: 0.5rem 1.25rem;
        border-radius: 30px;
        background: var(--light-color);
        color: var(--secondary-color);
        text-decoration: none;
        transition: var(--transition);
        border: 2px solid transparent;
        font-weight: 500;
    }

    .filter-link:hover,
    .filter-link.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }

    /* Advice Requests */
    .advice-request {
        background: white;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s ease;
    }

    .advice-request:hover {
        transform: translateY(-2px);
    }

    .request-header {
        padding: 1rem 1.5rem;
        background: var(--light-color);
        border-bottom: 2px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .request-destination {
        font-weight: 600;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .request-status {
        padding: 0.25rem 0.75rem;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    /* Request Content */
    .request-content {
        padding: 1.5rem;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .user-name {
        font-weight: 600;
        color: var(--dark-color);
    }

    .guest-user {
        color: var(--secondary-color);
        font-style: italic;
    }

    .request-details h4 {
        color: var(--dark-color);
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
    }

    .request-details p {
        color: var(--secondary-color);
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .request-email {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--secondary-color);
        margin-bottom: 1.5rem;
    }

    /* Advice Form */
    .advice-form {
        background: var(--light-color);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        margin-top: 1.5rem;
    }

    .advice-form textarea {
        width: 100%;
        min-height: 150px;
        padding: 1rem;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        resize: vertical;
        transition: var(--transition);
    }

    .advice-form textarea:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.25);
    }

    .btn {
        background: var(--gradient-primary);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Advice Content */
    .advice-content {
        background: #f0f8ff;
        padding: 1.5rem;
        border-radius: var(--border-radius);
        border-left: 4px solid var(--primary-color);
        margin-top: 1.5rem;
    }

    .email-sent-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--success-color);
        margin-top: 1rem;
        padding: 0.5rem 1rem;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 30px;
    }
</style>
</head>
<body>
  <div class="dashboard">
    <div class="dashboard-sidebar">
      <div style="padding: 20px; text-align: center;">
        <h2 style="color: #fff; margin-bottom: 0;">Travel Agent</h2>
      </div>
      <ul>
        <li><a href="travel-agent-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="index.php"><i class="fas fa-home"></i> Main Website</a></li>
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <div class="dashboard-header">
        <h2>Travel Agent Dashboard</h2>
        <p>Manage and respond to travel advice requests</p>
      </div>
      
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <?php echo $success_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <div class="filter-bar">
        <div class="filter-options">
          <a href="travel-agent-dashboard.php" class="filter-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Requests</a>
          <a href="travel-agent-dashboard.php?status=pending" class="filter-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
          <a href="travel-agent-dashboard.php?status=completed" class="filter-link <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>
        <div class="request-count">
          <span><?php echo count($advice_requests); ?> requests found</span>
        </div>
      </div>
      
      <?php if (empty($advice_requests)): ?>
        <div class="alert alert-info">
          <p>No advice requests found.</p>
        </div>
      <?php else: ?>
        <?php foreach ($advice_requests as $request): ?>
          <div class="advice-request">
            <div class="request-header">
              <div class="request-destination">
                <i class="fas fa-map-marker-alt"></i> <?php echo $request['destination']; ?>
              </div>
              <div class="request-status status-<?php echo $request['status']; ?>">
                <?php echo ucfirst($request['status']); ?>
              </div>
            </div>
            <div class="request-content">
              <div class="user-info">
                <?php if ($request['user_id']): ?>
                  <div class="user-avatar">
                    <?php echo strtoupper(substr($request['user_name'] ?? 'U', 0, 1)); ?>
                  </div>
                  <div class="user-name"><?php echo $request['user_name']; ?> (Registered User)</div>
                <?php else: ?>
                  <div class="user-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                  <div class="guest-user">Guest User</div>
                <?php endif; ?>
              </div>
              
              <div class="request-details">
                <h4>Request Details:</h4>
                <p><?php echo nl2br($request['details']); ?></p>
              </div>
              
              <div class="request-email">
                <i class="fas fa-envelope"></i> <?php echo $request['user_email']; ?>
              </div>
              
              <?php if ($request['status'] === 'completed' && !empty($request['advice'])): ?>
                <div class="advice-content">
                  <h4>Your Advice:</h4>
                  <p><?php echo nl2br($request['advice']); ?></p>
                  <div class="email-sent-indicator">
                    <i class="fas fa-envelope-open-text"></i> Email sent to user
                  </div>
                </div>
              <?php endif; ?>
              
              <?php if ($request['status'] === 'pending'): ?>
                <form action="" method="POST" class="advice-form">
                  <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                  <h4>Provide Travel Advice:</h4>
                  <p><small>Your response will be emailed to <?php echo $request['user_email']; ?></small></p>
                  <textarea name="advice" placeholder="Enter your expert travel advice here..." required></textarea>
                  <button type="submit" name="submit_advice" class="btn">Submit Advice & Send Email</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <script src="assets/js/admin.js"></script>
</body>
</html>
