<?php
session_start();

// Check if user is logged in and is an admin or travel agent
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'travel_agent')) {
  header('Location: login.php');
  exit;
}

include 'config/database.php';

// Process form submission for adding/editing templates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add_template'])) {
    $title = $_POST['template_title'];
    $content = $_POST['template_content'];
    $agent_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO response_templates (agent_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $agent_id, $title, $content);
    
    if ($stmt->execute()) {
      $success_message = "Template added successfully!";
    } else {
      $error_message = "Error adding template: " . $conn->error;
    }
  } elseif (isset($_POST['edit_template'])) {
    $template_id = $_POST['template_id'];
    $title = $_POST['template_title'];
    $content = $_POST['template_content'];
    $agent_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE response_templates SET title = ?, content = ? WHERE id = ? AND agent_id = ?");
    $stmt->bind_param("ssii", $title, $content, $template_id, $agent_id);
    
    if ($stmt->execute()) {
      $success_message = "Template updated successfully!";
    } else {
      $error_message = "Error updating template: " . $conn->error;
    }
  } elseif (isset($_POST['delete_template'])) {
    $template_id = $_POST['template_id'];
    $agent_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("DELETE FROM response_templates WHERE id = ? AND agent_id = ?");
    $stmt->bind_param("ii", $template_id, $agent_id);
    
    if ($stmt->execute()) {
      $success_message = "Template deleted successfully!";
    } else {
      $error_message = "Error deleting template: " . $conn->error;
    }
  }
}

// Fetch templates
$agent_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM response_templates WHERE agent_id = ? ORDER BY title ASC");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();

$templates = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
  }
}

// Sample templates if none exist
if (empty($templates)) {
  $templates = [
    [
      'id' => 1,
      'title' => 'General Travel Advice',
      'content' => "Dear Traveler,\n\nThank you for your interest in visiting [DESTINATION]. Based on your request, here are my recommendations:\n\n1. Accommodation:\n   - [Suggestion 1]\n   - [Suggestion 2]\n   - [Suggestion 3]\n\n2. Activities:\n   - [Activity 1]\n   - [Activity 2]\n   - [Activity 3]\n\n3. Transportation:\n   - [Transportation tip]\n\n4. Food & Dining:\n   - [Restaurant/Food recommendation]\n\nBest time to visit: [Season/Month]\nRecommended duration: [X days]\n\nAdditional tips:\n- [Tip 1]\n- [Tip 2]\n\nI hope this helps with your travel planning! Feel free to reach out if you have any other questions.\n\nSafe travels,\n[Your Name]\nTravel Agent, TravelEase",
      'created_at' => '2023-05-15 10:30:00'
    ],
    [
      'id' => 2,
      'title' => 'Kathmandu City Guide',
      'content' => "Dear Traveler,\n\nThank you for your interest in visiting Kathmandu! Here's my comprehensive guide to help you plan your trip:\n\n1. Accommodation:\n   - Budget: Zostel Kathmandu, Elbrus Home\n   - Mid-range: Kathmandu Eco Hotel, Hotel Moonlight\n   - Luxury: Dwarika's Hotel, Hyatt Regency\n\n2. Must-Visit Places:\n   - Durbar Square (UNESCO World Heritage Site)\n   - Swayambhunath (Monkey Temple)\n   - Pashupatinath Temple\n   - Boudhanath Stupa\n   - Thamel Shopping District\n\n3. Food Recommendations:\n   - Momos at Momo Star in Thamel\n   - Newari cuisine at Honacha near Durbar Square\n   - Thakali Kitchen for authentic Nepali thali\n   - OR2K for vegetarian options\n\n4. Transportation:\n   - Use ride-sharing apps like Pathao or inDrive\n   - Local taxis (always negotiate price before riding)\n   - Consider hiring a private driver for day trips\n\nBest time to visit: October-November or March-April\nRecommended duration: 3-4 days\n\nAdditional tips:\n- Carry small denominations of Nepali Rupees for small purchases\n- Bargain at local markets, but respectfully\n- Dress modestly when visiting religious sites\n\nI hope you have a wonderful time exploring Kathmandu!\n\nSafe travels,\n[Your Name]\nTravel Agent, TravelEase",
      'created_at' => '2023-05-20 14:45:00'
    ],
    [
      'id' => 3,
      'title' => 'Pokhara Adventure Guide',
      'content' => "Dear Traveler,\n\nExcited to help you plan your Pokhara adventure! Here's my comprehensive guide:\n\n1. Accommodation:\n   - Lakeside (North): Quieter area, great for relaxation\n   - Lakeside (Central): Perfect for dining and shopping\n   - Lakeside (South): Budget-friendly options\n\n2. Must-Do Activities:\n   - Paragliding from Sarangkot (one of the world's best spots!)\n   - Boating on Phewa Lake\n   - Hiking to World Peace Pagoda\n   - Visiting Davis Falls and Gupteshwor Cave\n   - Early morning trip to Sarangkot for Himalayan views\n\n3. Adventure Options:\n   - Ultralight aircraft flights\n   - Zip-lining (one of the world's longest)\n   - Mountain biking\n   - Stand-up paddleboarding on the lake\n\n4. Dining Recommendations:\n   - Busy Bee Cafe for live music and good food\n   - Moondance Restaurant for lakeside dining\n   - Thakali Kitchen for authentic Nepali cuisine\n   - OR2K for vegetarian options\n\nBest time to visit: October-November or March-April\nRecommended duration: 3-5 days\n\nAdditional tips:\n- Book adventure activities through reputable companies\n- Bargain for souvenirs but be respectful\n- Carry cash as not all places accept cards\n\nI hope you have an amazing time in Pokhara!\n\nSafe travels,\n[Your Name]\nTravel Agent, TravelEase",
      'created_at' => '2023-06-05 09:15:00'
    ]
  ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Response Templates - Travel Agent Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .templates-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .template-card {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
    }
    
    .template-card:hover {
      transform: translateY(-5px);
    }
    
    .template-header {
      padding: 15px 20px;
      background-color: #f8f9fa;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .template-title {
      font-weight: 600;
      font-size: 1.1rem;
      color: #333;
    }
    
    .template-actions {
      display: flex;
      gap: 10px;
    }
    
    .template-action {
      background: none;
      border: none;
      cursor: pointer;
      color: #666;
      transition: color 0.3s;
    }
    
    .template-action:hover {
      color: #3a86ff;
    }
    
    .template-action.delete:hover {
      color: #dc3545;
    }
    
    .template-content {
      padding: 20px;
      max-height: 200px;
      overflow-y: auto;
      white-space: pre-line;
      color: #555;
      font-size: 0.9rem;
      line-height: 1.6;
    }
    
    .template-footer {
      padding: 10px 20px;
      background-color: #f8f9fa;
      border-top: 1px solid #eee;
      font-size: 0.8rem;
      color: #666;
      display: flex;
      justify-content: space-between;
    }
    
    .add-template-card {
      background-color: #f8f9fa;
      border: 2px dashed #ccc;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .add-template-card:hover {
      border-color: #3a86ff;
      background-color: #f0f7ff;
      transform: translateY(-5px);
    }
    
    .add-template-card i {
      font-size: 2rem;
      color: #3a86ff;
      margin-bottom: 15px;
    }
    
    .add-template-card h3 {
      margin: 0;
      color: #333;
    }
    
    .add-template-card p {
      color: #666;
      margin-top: 10px;
    }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      overflow-y: auto;
    }
    
    .modal-content {
      background-color: #fff;
      margin: 50px auto;
      max-width: 700px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      position: relative;
    }
    
    .modal-header {
      padding: 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
    
    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }
    
    .modal-body {
      padding: 20px;
    }
    
    .modal-footer {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      text-align: right;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: inherit;
      font-size: inherit;
    }
    
    textarea.form-control {
      min-height: 300px;
      resize: vertical;
    }
    
    .form-control:focus {
      border-color: #3a86ff;
      outline: none;
    }
    
    .btn-group {
      display: flex;
      gap: 10px;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      background-color: #f8f9fa;
      border-radius: 10px;
      margin-top: 20px;
    }
    
    .empty-state i {
      font-size: 3rem;
      color: #adb5bd;
      margin-bottom: 20px;
    }
    
    .empty-state h3 {
      margin-bottom: 10px;
      color: #495057;
    }
    
    .empty-state p {
      color: #6c757d;
      max-width: 500px;
      margin: 0 auto 20px;
    }
    
    @media (max-width: 768px) {
      .templates-container {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        margin: 20px;
        width: auto;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <div class="dashboard-sidebar">
      <div style="padding: 20px; text-align: center;">
        <h2 style="color: #fff; margin-bottom: 5px;">Travel Agent</h2>
        <p style="color: rgba(255,255,255,0.7); margin: 0;">Dashboard</p>
      </div>
      <ul>
        <li><a href="travel-agent-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="travel-agent-profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
        <li><a href="travel-agent-completed.php"><i class="fas fa-check-circle"></i> Completed Requests</a></li>
        <li><a href="travel-agent-templates.php" class="active"><i class="fas fa-file-alt"></i> Response Templates</a></li>
        <li><a href="travel-agent-resources.php"><i class="fas fa-book"></i> Travel Resources</a></li>
        <li><a href="index.php"><i class="fas fa-home"></i> Main Website</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </div>
    <div class="dashboard-content">
      <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i> Menu
      </button>
      
      <div class="dashboard-header">
        <div class="dashboard-title">
          <h2>Response Templates</h2>
          <p>Create and manage your response templates to save time</p>
        </div>
        <div class="dashboard-actions">
          <button id="add-template-btn" class="btn"><i class="fas fa-plus"></i> New Template</button>
        </div>
      </div>
      
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <div class="templates-container">
        <div class="add-template-card" id="add-template-card">
          <i class="fas fa-plus-circle"></i>
          <h3>Create New Template</h3>
          <p>Save time by creating reusable response templates</p>
        </div>
        
        <?php foreach ($templates as $template): ?>
          <div class="template-card">
            <div class="template-header">
              <div class="template-title"><?php echo htmlspecialchars($template['title']); ?></div>
              <div class="template-actions">
                <button class="template-action edit-template" data-id="<?php echo $template['id']; ?>" data-title="<?php echo htmlspecialchars($template['title']); ?>" data-content="<?php echo htmlspecialchars($template['content']); ?>">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="template-action delete template-delete" data-id="<?php echo $template['id']; ?>" data-title="<?php echo htmlspecialchars($template['title']); ?>">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
            <div class="template-content"><?php echo nl2br(htmlspecialchars($template['content'])); ?></div>
            <div class="template-footer">
              <span>Created: <?php echo date('M d, Y', strtotime($template['created_at'])); ?></span>
              <button class="template-action use-template" data-content="<?php echo htmlspecialchars($template['content']); ?>">
                <i class="fas fa-copy"></i> Use Template
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  
  <!-- Add/Edit Template Modal -->
  <div id="template-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Create New Template</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="template-form" method="POST">
        <div class="modal-body">
          <input type="hidden" id="template_id" name="template_id" value="">
          <div class="form-group">
            <label for="template_title">Template Title</label>
            <input type="text" id="template_title" name="template_title" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="template_content">Template Content</label>
            <textarea id="template_content" name="template_content" class="form-control" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div class="btn-group">
            <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
            <button type="submit" class="btn" id="template-submit-btn" name="add_template">Create Template</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div id="delete-modal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h3 class="modal-title">Delete Template</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="delete-form" method="POST">
        <div class="modal-body">
          <input type="hidden" id="delete_template_id" name="template_id" value="">
          <p>Are you sure you want to delete the template "<span id="delete-template-name"></span>"?</p>
          <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <div class="btn-group">
            <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
            <button type="submit" class="btn btn-danger" name="delete_template">Delete Template</button>
