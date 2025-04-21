<?php
session_start();

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';
$user_id = $_SESSION['user_id'];

// Fetch hotels for the logged-in owner
$result = $conn->query("SELECT * FROM hotels WHERE owner_id = $user_id");

// Success and Error message initialization
$success = '';
$error = '';

if (!$result) {
    $error = "Error fetching hotels. Please try again later.";
}

// Handle hotel edit form submission
if (isset($_POST['update_hotel'])) {
    $hotel_id = $_POST['hotel_id'];
    $name = $_POST['name'];
    $location = $_POST['location'];
    $price_per_night = $_POST['price_per_night'];
    $available_rooms = $_POST['available_rooms'];
    $total_rooms = $_POST['total_rooms'];
    $status = $_POST['status'];

    $update_query = "UPDATE hotels SET 
                    name = ?, location = ?, price_per_night = ?, 
                    available_rooms = ?, total_rooms = ?, status = ? 
                    WHERE id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssiiisi", $name, $location, $price_per_night, $available_rooms, $total_rooms, $status, $hotel_id);

    if ($stmt->execute()) {
        $success = "Hotel details updated successfully!";
    } else {
        $error = "Error updating hotel details. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Hotels</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <!-- Include Navbar (sidebar.php) -->
    <?php include 'navbar.php'; ?> <!-- Sidebar is included here -->

    <!-- Main Content -->
    <div class="dashboard-content">
        <h2>My Hotels</h2>

        <?php if (!empty($success)): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="hotel-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($hotel = $result->fetch_assoc()): ?>
                    <div class="hotel-card">
                        <div class="hotel-img">
                            <img src="../assets/images/hotel-placeholder.jpg" alt="Hotel Image">
                        </div>
                        <div class="hotel-details">
                            <h3><?php echo $hotel['name']; ?></h3>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $hotel['location']; ?></p>
                            <p><i class="fas fa-dollar-sign"></i> Price/Night: $<?php echo $hotel['price_per_night']; ?></p>
                            <p><i class="fas fa-bed"></i> <?php echo $hotel['available_rooms']; ?> / <?php echo $hotel['total_rooms']; ?> Rooms</p>
                            <p>Status: <?php echo ($hotel['status'] == 'active') ? 'Active' : 'Inactive'; ?></p>
                            <a href="edit-hotel.php?id=<?php echo $hotel['id']; ?>" class="btn-edit">Edit</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hotels found. Please add a hotel.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

<style>
    /* Hotel List Styles */
    .dashboard-content {
        margin-left: 250px;
        padding: 20px;
    }

    .hotel-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .hotel-card {
        background-color: #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hotel-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .hotel-img img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .hotel-details {
        padding: 20px;
    }

    .hotel-details h3 {
        font-size: 24px;
        margin-bottom: 10px;
    }

    .hotel-details p {
        font-size: 16px;
        margin: 5px 0;
    }

    .hotel-details i {
        margin-right: 10px;
    }

    .btn-edit {
        display: inline-block;
        padding: 10px 20px;
        background-color: #2980b9;
        color: #fff;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }

    .btn-edit:hover {
        background-color: #1c5982;
    }
</style>
