<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get user ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = $_GET['id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Fetch available roles dynamically
$roles_result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
$roles_row = $roles_result->fetch_assoc();
preg_match("/^enum\(\'(.*)\'\)$/", $roles_row['Type'], $matches);
$roles = explode("','", $matches[1]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    if (!empty($name) && !empty($email) && in_array($role, $roles)) {
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $name, $email, $role, $user_id);

        if ($update_stmt->execute()) {
            header("Location: users.php?success=User updated successfully");
            exit;
        } else {
            $error = "Error updating user!";
        }
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            width: 50%;
            margin: 40px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-save {
            background: #28a745;
            color: #fff;
        }
        .btn-cancel {
            background: #6c757d;
            color: #fff;
            margin-left: 5px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="dashboard-content">
            <h2>Edit User</h2>
            
            <div class="container">
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Role:</label>
                        <select name="role" class="form-control" required>
                            <?php foreach ($roles as $role_option): ?>
                                <option value="<?php echo $role_option; ?>" <?php echo ($user['role'] == $role_option) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <a href="users.php" class="btn btn-cancel">Cancel</a>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
