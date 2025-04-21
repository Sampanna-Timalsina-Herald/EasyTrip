<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

$success_message = '';  // Initialize a variable for success message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $total_rooms = $_POST['total_rooms'];
    $owner_id = $_SESSION['user_id'];
    $status = 'active';
    
    // Handle Image Upload
    $image_url = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_url = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $image_url);
    }

    // Prepare SQL query using prepared statements
    $sql = "INSERT INTO hotels (owner_id, name, description, location, image_url, price_per_night, total_rooms, available_rooms, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters
        $stmt->bind_param("issssiiis", $owner_id, $name, $description, $location, $image_url, $price, $total_rooms, $total_rooms, $status);

        // Execute the query
        if ($stmt->execute()) {
            // Success message to be displayed after form submission
            $success_message = "Hotel added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the prepared statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Hotel - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add some basic styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .dashboard {
            display: flex;
        }
        .dashboard-content {
            width: 100%;
            padding: 20px;
        }
        .dashboard-header {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        h2 {
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            border-radius: 5px;
        }
        button:hover {
            background: #218838;
        }
        .success-message {
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'navbar.php'; ?>
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h2>Add a New Hotel</h2>
                <p>Fill in the details to add a new hotel to your list.</p>
            </div>
            <div class="container">
                <?php
                if ($success_message != '') {
                    echo '<p class="success-message">' . $success_message . '</p>';
                }
                ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Hotel Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Location:</label>
                        <input type="text" name="location" required>
                    </div>
                    <div class="form-group">
                        <label>Price Per Night:</label>
                        <input type="number" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Total Rooms:</label>
                        <input type="number" name="total_rooms" required>
                    </div>
                    <div class="form-group">
                        <label>Hotel Image:</label>
                        <input type="file" name="image" required>
                    </div>
                    <button type="submit">Add Hotel</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
