<?php
include 'config/database.php';
include 'includes/header.php';

$success_message = '';
$error_message = '';

// Initialize variables
$destination = '';
$details = '';
$email = '';

// If user is logged in, pre-fill email
if (isset($_SESSION['user_id'])) {
  // Get user email from database
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $email = $user['email'];
  }
  $stmt->close();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $destination = trim($_POST['destination']);
  $details = trim($_POST['details']);
  $email = trim($_POST['email']);
  
  // Validate inputs
  if (empty($destination) || empty($details) || empty($email)) {
    $error_message = 'Please fill in all required fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = 'Please enter a valid email address.';
  } else {
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
      // Insert advice request with user_id
      $stmt = $conn->prepare("INSERT INTO advice_requests (user_id, destination, details, user_email, status) VALUES (?, ?, ?, ?, 'pending')");
      $stmt->bind_param("isss", $_SESSION['user_id'], $destination, $details, $email);
    } else {
      // Insert advice request without user_id
      $stmt = $conn->prepare("INSERT INTO advice_requests (destination, details, user_email, status) VALUES (?, ?, ?, 'pending')");
      $stmt->bind_param("sss", $destination, $details, $email);
    }
    
    if ($stmt->execute()) {
      $success_message = 'Your request for travel advice has been submitted successfully! Our travel agents will respond to your email soon.';
      // Clear form fields after successful submission
      $destination = '';
      $details = '';
      if (!isset($_SESSION['user_id'])) {
        $email = ''; // Only clear email if not logged in
      }
    } else {
      $error_message = 'Error submitting your request: ' . $conn->error;
    }
    $stmt->close();
  }
}
?>

<section class="section">
  <div class="container">
    <div class="section-title">
      <h2>Request Travel Advice</h2>
      <p>Get personalized travel recommendations from our expert travel agents</p>
    </div>
    
    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success">
        <?php echo $success_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger">
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>
    
    <div class="advice-request-container">
      <div class="advice-info">
        <h3>Why Ask Our Travel Experts?</h3>
        <div class="advice-benefits">
          <div class="benefit-item">
            <div class="benefit-icon">
              <i class="fas fa-map-marked-alt"></i>
            </div>
            <div class="benefit-content">
              <h4>Local Knowledge</h4>
              <p>Our travel agents have extensive knowledge of destinations throughout Nepal and can provide insider tips.</p>
            </div>
          </div>
          
          <div class="benefit-item">
            <div class="benefit-icon">
              <i class="fas fa-route"></i>
            </div>
            <div class="benefit-content">
              <h4>Personalized Itineraries</h4>
              <p>Get customized travel plans based on your interests, budget, and time constraints.</p>
            </div>
          </div>
          
          <div class="benefit-item">
            <div class="benefit-icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="benefit-content">
              <h4>Budget Optimization</h4>
              <p>Learn how to make the most of your travel budget with expert recommendations.</p>
            </div>
          </div>
          
          <div class="benefit-item">
            <div class="benefit-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <div class="benefit-content">
              <h4>Safety Tips</h4>
              <p>Receive important safety information and travel advisories for your destination.</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="advice-form-container">
        <form action="" method="POST" class="advice-form">
          <div class="form-group">
            <label for="destination">Destination <span class="required">*</span></label>
            <input type="text" id="destination" name="destination" class="form-control" value="<?php echo htmlspecialchars($destination); ?>" placeholder="Where are you planning to travel?" required>
          </div>
          
          <div class="form-group">
            <label for="details">Travel Details <span class="required">*</span></label>
            <textarea id="details" name="details" class="form-control" rows="6" placeholder="Please provide details about your trip (dates, interests, budget, etc.) and any specific questions you have." required><?php echo htmlspecialchars($details); ?></textarea>
            <small>The more details you provide, the better advice our agents can give you.</small>
          </div>
          
          <div class="form-group">
            <label for="email">Your Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="We'll send our advice to this email address" required <?php echo isset($_SESSION['user_id']) ? 'readonly' : ''; ?>>
            <?php if (isset($_SESSION['user_id'])): ?>
              <small>Using your account email. <a href="profile.php">Update your profile</a> to change.</small>
            <?php endif; ?>
          </div>
          
          <div class="form-group">
            <button type="submit" class="btn btn-block">Request Advice</button>
          </div>
          
          <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="login-prompt">
              <p>Already have an account? <a href="login.php">Log in</a> to track your advice requests.</p>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</section>

<style>
  .advice-request-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 30px;
  }
  
  .advice-info {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 25px;
  }
  
  .advice-info h3 {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
  }
  
  .advice-benefits {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  
  .benefit-item {
    display: flex;
    gap: 15px;
  }
  
  .benefit-icon {
    font-size: 2rem;
    color: #3a86ff;
    min-width: 40px;
    display: flex;
    align-items: center;
  }
  
  .benefit-content h4 {
    margin-bottom: 5px;
    font-size: 1.1rem;
  }
  
  .benefit-content p {
    color: #666;
    line-height: 1.5;
  }
  
  .advice-form-container {
    background-color: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  
  .advice-form .form-group {
    margin-bottom: 20px;
  }
  
  .advice-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
  }
  
  .required {
    color: #dc3545;
  }
  
  .advice-form textarea {
    resize: vertical;
  }
  
  .advice-form small {
    display: block;
    margin-top: 5px;
    color: #666;
  }
  
  .login-prompt {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
    color: #666;
  }
  
  .login-prompt a {
    color: #3a86ff;
    font-weight: 500;
  }
  
  @media (max-width: 768px) {
    .advice-request-container {
      grid-template-columns: 1fr;
    }
  }
</style>

<?php include 'includes/footer.php'; ?>

