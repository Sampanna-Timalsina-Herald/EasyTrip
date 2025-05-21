<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

// Fetch all users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $conn->query("DELETE FROM users WHERE id = $delete_id");
    header("Location: users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background: #f4f4f4;
            font-weight: bold;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 5px;
        }
        .btn-edit {
            background: #007bff;
            color: #fff;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-sidebar">
            <div style="padding: 20px; text-align: center;">
                <h2 style="color: #fff; margin-bottom: 0;">Admin Panel</h2>
            </div>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="hotels.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="buses.php"><i class="fas fa-bus"></i> Buses</a></li>
                <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Manage Users</h2>
                <p>View and manage all registered users.</p>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3>All Users</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo $user['name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
