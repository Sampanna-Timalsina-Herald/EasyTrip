<?php
session_start();

// Check if user is logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'hotel_owner') {
    header('Location: ../login.php');
    exit;
}

include '../config/database.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $price_per_night = floatval($_POST['price_per_night']);
    $total_rooms = intval($_POST['total_rooms']);
    $available_rooms = intval($_POST['available_rooms']);
    $status = $_POST['status'];

    // Validate form data
    if (empty($name) || empty($location) || $price_per_night <= 0 || $total_rooms <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } elseif ($available_rooms > $total_rooms) {
        $error_message = "Available rooms cannot exceed total rooms.";
    } else {
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error_message = "Only JPG, JPEG, and PNG images are allowed.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error_message = "Image size should not exceed 5MB.";
            } else {
                $upload_dir = '../uploads/hotels/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . $_FILES['image']['name'];
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/hotels/' . $file_name;
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                }
            }
        }
        
        if (empty($error_message)) {
            // Insert hotel into database
            $stmt = $conn->prepare("INSERT INTO hotels (name, location, description, price_per_night, total_rooms, available_rooms, status, image_url, owner_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("sssdiissi", $name, $location, $description, $price_per_night, $total_rooms, $available_rooms, $status, $image_url, $user_id);
            
            if ($stmt->execute()) {
                $hotel_id = $conn->insert_id;
                $success_message = "Hotel added successfully!";
                
                // Redirect to hotel list after successful addition
                $_SESSION['success_message'] = $success_message;
                header('Location: myhotels.php');
                exit;
            } else {
                $error_message = "Error adding hotel: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Set header variables
$header_title = 'Add New Hotel';
$header_subtitle = 'Create a new hotel listing for your property';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Hotel - Hotel Owner Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>
        
        <div class="dashboard-content">
            <?php include 'header.php'; ?>
            
            <div class="form-card">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">Hotel Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="location">Location <span class="required">*</span></label>
                                <input type="text" id="location" name="location" class="form-control" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="price_per_night">Price Per Night ($) <span class="required">*</span></label>
                                <input type="number" id="price_per_night" name="price_per_night" class="form-control" step="0.01" min="0" required value="<?php echo isset($_POST['price_per_night']) ? htmlspecialchars($_POST['price_per_night']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="total_rooms">Total Rooms <span class="required">*</span></label>
                                <input type="number" id="total_rooms" name="total_rooms" class="form-control" min="1" required value="<?php echo isset($_POST['total_rooms']) ? htmlspecialchars($_POST['total_rooms']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="available_rooms">Available Rooms <span class="required">*</span></label>
                                <input type="number" id="available_rooms" name="available_rooms" class="form-control" min="0" required value="<?php echo isset($_POST['available_rooms']) ? htmlspecialchars($_POST['available_rooms']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="image">Hotel Image</label>
                                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg, image/png, image/jpg">
                                <small class="form-text">Upload a main image for your hotel (Max: 5MB, Formats: JPG, JPEG, PNG)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Hotel Amenities</label>
                        <div class="checkbox-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="wifi"> WiFi
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="parking"> Parking
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="pool"> Swimming Pool
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="restaurant"> Restaurant
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="gym"> Gym
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="spa"> Spa
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="amenities[]" value="ac"> Air Conditioning
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Add Hotel</button>
                        <a href="myhotels.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Ensure available rooms don't exceed total rooms
        document.getElementById('total_rooms').addEventListener('change', function() {
            const totalRooms = parseInt(this.value) || 0;
            const availableRooms = document.getElementById('available_rooms');
            
            if (parseInt(availableRooms.value) > totalRooms) {
                availableRooms.value = totalRooms;
            }
            
            availableRooms.setAttribute('max', totalRooms);
        });
        
        // Preview image before upload
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here if needed
                    console.log('Image selected:', e.target.result);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>