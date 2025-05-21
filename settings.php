<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config/database.php';
$user_id = $_SESSION['user_id'];

// Initialize variables
$name = '';
$email = '';
$success_message = '';
$error_message = '';
$password_success = '';
$password_error = '';

// Fetch user data
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $email = $user['email'];
}
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    
    // Validate input
    if (empty($new_name)) {
        $error_message = "Name cannot be empty";
    } else {
        // Update user name
        $update_stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_name, $user_id);
        
        if ($update_stmt->execute()) {
            $name = $new_name;
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $password_error = "Password must be at least 6 characters long";
    } else {
        // Verify current password
        $password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $password_stmt->bind_param("i", $user_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $user_data = $password_result->fetch_assoc();
        $password_stmt->close();
        
        if (password_verify($current_password, $user_data['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_password_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_password_stmt->execute()) {
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Error changing password: " . $conn->error;
            }
            $update_password_stmt->close();
        } else {
            $password_error = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Travel Booking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="settings-page">
            <h1>Account Settings</h1>
            
            <div class="settings-container">
                <div class="settings-sidebar">
                    <ul>
                        <li><a href="#profile" class="active"><i class="fas fa-user"></i> Profile Information</a></li>
                        <li><a href="#security"><i class="fas fa-lock"></i> Security</a></li>
                    </ul>
                </div>
                
                <div class="settings-content">
                    <!-- Profile Information Section -->
                    <section id="profile" class="settings-section active">
                        <h2>Profile Information</h2>
                        
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
                        
                        <form action="" method="post" class="settings-form">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                <small class="form-text">Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </section>
                    
                    <!-- Security Section -->
                    <section id="security" class="settings-section">
                        <h2>Change Password</h2>
                        
                        <?php if (!empty($password_success)): ?>
                            <div class="alert alert-success">
                                <?php echo $password_success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($password_error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $password_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="post" class="settings-form">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                                <small class="form-text">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabLinks = document.querySelectorAll('.settings-sidebar a');
            const sections = document.querySelectorAll('.settings-section');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and sections
                    tabLinks.forEach(l => l.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const targetId = this.getAttribute('href').substring(1);
                    document.getElementById(targetId).classList.add('active');
                });
            });
            
            // Check if URL has a hash and activate that tab
            if (window.location.hash) {
                const hash = window.location.hash.substring(1);
                const targetLink = document.querySelector(`.settings-sidebar a[href="#${hash}"]`);
                if (targetLink) {
                    targetLink.click();
                }
            }
        });
    </script>
    
    <style>
        /* Settings Page Styles */
        .settings-page {
            padding: 40px 0;
        }
        
        .settings-page h1 {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .settings-container {
            display: flex;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 40px;
        }
        
        /* Sidebar Styles */
        .settings-sidebar {
            width: 250px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }
        
        .settings-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .settings-sidebar li {
            margin: 0;
        }
        
        .settings-sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #495057;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .settings-sidebar a:hover {
            background: #e9ecef;
            color: #212529;
        }
        
        .settings-sidebar a.active {
            background: #e9ecef;
            color: #3a86ff;
            border-left-color: #3a86ff;
            font-weight: 500;
        }
        
        .settings-sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Content Styles */
        .settings-content {
            flex: 1;
            padding: 30px;
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .settings-section h2 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        /* Form Styles */
        .settings-form {
            max-width: 600px;
        }
        
        .settings-form .form-group {
            margin-bottom: 20px;
        }
        
        .settings-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .settings-form .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .settings-form .form-control:focus {
            border-color: #3a86ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
        }
        
        .settings-form .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        
        .settings-form .form-text {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .settings-form .btn {
            padding: 12px 25px;
            background-color: #3a86ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .settings-form .btn:hover {
            background-color: #2a75e6;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .settings-container {
                flex-direction: column;
            }
            
            .settings-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e9ecef;
            }
            
            .settings-sidebar ul {
                display: flex;
                overflow-x: auto;
            }
            
            .settings-sidebar li {
                flex: 0 0 auto;
            }
            
            .settings-sidebar a {
                padding: 15px;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .settings-sidebar a.active {
                border-left-color: transparent;
                border-bottom-color: #3a86ff;
            }
        }
    </style>
</body>
</html>
