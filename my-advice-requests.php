<?php
include 'config/database.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?redirect=my-advice-requests.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's advice requests
$stmt = $conn->prepare("SELECT * FROM advice_requests WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$advice_requests = [];

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $advice_requests[] = $row;
  }
}
$stmt->close();
?>

<section class="section">
  <div class="container">
    <div class="section-title">
      <h2>My Travel Advice Requests</h2>
      <p>View and manage your travel advice requests</p>
    </div>
    
    <div class="action-bar">
      <a href="request-advice.php" class="btn"><i class="fas fa-plus"></i> Request New Advice</a>
    </div>
    
    <?php if (empty($advice_requests)): ?>
      <div class="alert alert-info">
        <p>You haven't made any travel advice requests yet. <a href="request-advice.php">Request advice</a> from our travel experts.</p>
      </div>
    <?php else: ?>
      <div class="advice-requests-list">
        <?php foreach ($advice_requests as $request): ?>
          <div class="advice-request-card">
            <div class="request-header">
              <div class="request-destination">
                <i class="fas fa-map-marker-alt"></i> <?php echo $request['destination']; ?>
              </div>
              <div class="request-status status-<?php echo $request['status']; ?>">
                <?php echo ucfirst($request['status']); ?>
              </div>
            </div>
            <div class="request-content">
              <div class="request-details">
                <h4>My Request:</h4>
                <p><?php echo nl2br($request['details']); ?></p>
              </div>
              
              <?php if ($request['status'] === 'completed' && !empty($request['advice'])): ?>
                <div class="advice-content">
                  <h4>Travel Agent's Advice:</h4>
                  <p><?php echo nl2br($request['advice']); ?></p>
                </div>
              <?php elseif ($request['status'] === 'pending'): ?>
                <div class="pending-message">
                  <i class="fas fa-hourglass-half"></i> Our travel agents are working on your request. You'll receive an email when your advice is ready.
                </div>
              <?php endif; ?>
            </div>
            <div class="request-footer">
              <span class="request-date">Requested on: <?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
              <?php if ($request['status'] === 'pending'): ?>
                <a href="request-advice.php?edit=<?php echo $request['id']; ?>" class="btn btn-sm">Edit Request</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
  .action-bar {
    margin-bottom: 20px;
    display: flex;
    justify-content: flex-end;
  }
  
  .advice-requests-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  
  .advice-request-card {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
  }
  
  .request-destination {
    font-weight: 600;
    font-size: 1.2rem;
  }
  
  .request-destination i {
    color: #3a86ff;
    margin-right: 5px;
  }
  
  .request-status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
  }
  
  .status-pending {
    background-color: #fff3cd;
    color: #856404;
  }
  
  .status-completed {
    background-color: #d4edda;
    color: #155724;
  }
  
  .request-content {
    padding: 20px;
  }
  
  .request-details {
    margin-bottom: 20px;
  }
  
  .request-details h4 {
    margin-bottom: 10px;
    font-size: 1.1rem;
    color: #333;
  }
  
  .request-details p {
    line-height: 1.6;
    color: #555;
  }
  
  .advice-content {
    background-color: #f0f8ff;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 4px solid #3a86ff;
  }
  
  .advice-content h4 {
    margin-bottom: 10px;
    color: #3a86ff;
  }
  
  .pending-message {
    background-color: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    display: flex;
    align-items: center;
    color: #856404;
  }
  
  .pending-message i {
    margin-right: 10px;
    font-size: 1.2rem;
  }
  
  .request-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .request-date {
    color: #666;
    font-size: 0.9rem;
  }
  
  .btn-sm {
    padding: 5px 10px;
    font-size: 0.9rem;
  }
  
  @media (max-width: 768px) {
    .request-footer {
      flex-direction: column;
      gap: 10px;
      align-items: flex-start;
    }
    
    .btn-sm {
      width: 100%;
      text-align: center;
    }
  }
</style>

<?php include 'includes/footer.php'; ?>
