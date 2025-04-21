<?php
session_start();
include '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];

// Fetch admin details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update Profile
        $name = trim($_POST['name']);
        
        if (!empty($name)) {
            $update_stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $update_stmt->bind_param("si", $name, $admin_id);

            if ($update_stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $success = "Profile updated successfully!";
            } else {
                $error = "Error updating profile!";
            }
        } else {
            $error = "Name cannot be empty!";
        }
    }

    if (isset($_POST['change_password'])) {
        // Change Password
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            if ($new_password === $confirm_password) {
                // Check current password
                $password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $password_stmt->bind_param("i", $admin_id);
                $password_stmt->execute();
                $password_result = $password_stmt->get_result();
                $password_data = $password_result->fetch_assoc();

                if (password_verify($current_password, $password_data['password'])) {
                    // Update new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_password_stmt->bind_param("si", $hashed_password, $admin_id);

                    if ($update_password_stmt->execute()) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Error changing password!";
                    }
                } else {
                    $error = "Current password is incorrect!";
                }
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "All fields are required!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Your styles here */
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'navbar.php'; ?> <!-- Include sidebar here -->

        <div class="dashboard-content">
            <h2>Admin Settings</h2>

            <div class="container">
                <?php if (!empty($success)): ?>
                    <p class="success"><?php echo $success; ?></p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>

                <h3>Update Profile</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-save">Save Changes</button>
                </form>

                <hr>

                <h3>Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password:</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password:</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password:</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-danger">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
