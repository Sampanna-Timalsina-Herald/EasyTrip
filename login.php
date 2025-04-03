<?php
session_start();
require_once 'config/database.php';

$error = '';
$email = $role = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Validate inputs
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please enter all fields (Email, Password, and Role).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Prepare SQL statement to fetch user
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        // Verify user exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on user role
                switch ($user['role']) {
                    case "admin":
                        header('Location: admin/dashboard.php');
                        exit;
                    case "hotel_owner":
                        header('Location: hotel-owner/dashboard.php');
                        exit;
                    case "bus_operator":
                        header('Location: bus-operator/dashboard.php');
                        exit;
                    case "agent":
                        header('Location: travel-agent/travel-agent-dashboard.php');
                        exit;
                    case "traveler":
                        header('Location: index.php');
                        exit;
                    default:
                        header('Location: index.php');
                        exit;
                }
            } else {
                $error = 'Invalid password. Please try again.';
            }
        } else {
            $error = 'User not found. Please check your email, role, or register.';
        }

        $stmt->close();
    }
}
?>


<?php include 'includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="form-card">
            <h2>Login to Your Account</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="role">Select Role</label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        <option value="hotel_owner" <?php echo (isset($_POST['role']) && $_POST['role'] == 'hotel_owner') ? 'selected' : ''; ?>>Hotel Owner</option>
                        <option value="bus_operator" <?php echo (isset($_POST['role']) && $_POST['role'] == 'bus_operator') ? 'selected' : ''; ?>>Bus Operator</option>
                        <option value="agent" <?php echo (isset($_POST['role']) && $_POST['role'] == 'agent') ? 'selected' : ''; ?>>Travel Agent</option>
                        <option value="traveler" <?php echo (isset($_POST['role']) && $_POST['role'] == 'traveler') ? 'selected' : ''; ?>>Traveler</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-block">Login</button>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>

            <!-- Demo login information -->
            <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                <h3 style="font-size: 1.2rem; margin-bottom: 10px;">Demo Accounts</h3>
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
                    <p><strong>Admin:</strong> admin@example.com / password</p>
                    <p><strong>Hotel Owner:</strong> hotel@example.com / password</p>
                    <p><strong>Bus Operator:</strong> bus@example.com / password</p>
                    <p><strong>Agent:</strong> agent@example.com / password</p>
                    <p><strong>Traveler:</strong> user@example.com / password</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>