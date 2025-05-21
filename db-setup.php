<?php
// Database setup script
// This script will create the necessary database and tables for the travel booking system

// Database configuration
$host = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS travel_booking";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("travel_booking");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin', 'hotel_owner', 'bus_operator') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create hotels table
$sql = "CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100) NOT NULL,
    image_url VARCHAR(255),
    price_per_night DECIMAL(10, 2) NOT NULL,
    total_rooms INT DEFAULT 0,
    available_rooms INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Hotels table created successfully<br>";
} else {
    echo "Error creating hotels table: " . $conn->error . "<br>";
}

// Create hotel_rooms table
$sql = "CREATE TABLE IF NOT EXISTS hotel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    room_type ENUM('single', 'double', 'suite', 'family') DEFAULT 'single',
    price_per_night DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Hotel rooms table created successfully<br>";
} else {
    echo "Error creating hotel rooms table: " . $conn->error . "<br>";
}

// Create hotel_amenities table
$sql = "CREATE TABLE IF NOT EXISTS hotel_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Hotel amenities table created successfully<br>";
} else {
    echo "Error creating hotel amenities table: " . $conn->error . "<br>";
}

// Create buses table
$sql = "CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operator_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    departure_location VARCHAR(100) NOT NULL,
    arrival_location VARCHAR(100) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    image_url VARCHAR(255),
    price DECIMAL(10, 2) NOT NULL,
    total_seats INT DEFAULT 0,
    available_seats INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Buses table created successfully<br>";
} else {
    echo "Error creating buses table: " . $conn->error . "<br>";
}

// Create bus_seats table
$sql = "CREATE TABLE IF NOT EXISTS bus_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    seat_type ENUM('regular', 'premium', 'sleeper') DEFAULT 'regular',
    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Bus seats table created successfully<br>";
} else {
    echo "Error creating bus seats table: " . $conn->error . "<br>";
}

// Create bus_amenities table
$sql = "CREATE TABLE IF NOT EXISTS bus_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Bus amenities table created successfully<br>";
} else {
    echo "Error creating bus amenities table: " . $conn->error . "<br>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT,
    bus_id INT,
    check_in_date DATE,
    check_out_date DATE,
    travel_date DATE,
    guests INT DEFAULT 1,
    passengers INT DEFAULT 1,
    selected_rooms VARCHAR(255),
    selected_seats VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_proof VARCHAR(255),
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE SET NULL,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Bookings table created successfully<br>";
} else {
    echo "Error creating bookings table: " . $conn->error . "<br>";
}

// Insert sample data

// Insert sample users
$password = password_hash('password', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@example.com', '$password', 'admin'),
('Hotel Owner', 'hotel@example.com', '$password', 'hotel_owner'),
('Bus Operator', 'bus@example.com', '$password', 'bus_operator'),
('Regular User', 'user@example.com', '$password', 'user')";

if ($conn->query($sql) === TRUE) {
    echo "Sample users created successfully<br>";
} else {
    echo "Error creating sample users: " . $conn->error . "<br>";
}

// Get user IDs
$admin_id = 1;
$hotel_owner_id = 2;
$bus_operator_id = 3;
$user_id = 4;

// Insert sample hotels
$sql = "INSERT INTO hotels (owner_id, name, description, location, price_per_night, total_rooms, available_rooms) VALUES 
($hotel_owner_id, 'Luxury Hotel & Spa', 'Experience luxury at its finest with our premium amenities and services.', 'New York', 199.99, 50, 35),
($hotel_owner_id, 'Budget Comfort Inn', 'Affordable comfort without compromising on quality and service.', 'Boston', 79.99, 40, 25),
($hotel_owner_id, 'Family Resort & Suites', 'Perfect for family vacations with activities for all ages.', 'Chicago', 149.99, 60, 40)";

if ($conn->query($sql) === TRUE) {
    echo "Sample hotels created successfully<br>";
} else {
    echo "Error creating sample hotels: " . $conn->error . "<br>";
}

// Insert sample hotel amenities
$sql = "INSERT INTO hotel_amenities (hotel_id, name) VALUES 
(1, 'Free WiFi'),
(1, 'Swimming Pool'),
(1, 'Fitness Center'),
(1, 'Spa'),
(1, 'Restaurant'),
(1, 'Room Service'),
(1, 'Parking'),
(1, 'Air Conditioning'),
(2, 'Free WiFi'),
(2, 'Parking'),
(2, 'Air Conditioning'),
(2, 'Breakfast'),
(3, 'Free WiFi'),
(3, 'Swimming Pool'),
(3, 'Kids Club'),
(3, 'Restaurant'),
(3, 'Parking'),
(3, 'Air Conditioning')";

if ($conn->query($sql) === TRUE) {
    echo "Sample hotel amenities created successfully<br>";
} else {
    echo "Error creating sample hotel amenities: " . $conn->error . "<br>";
}

// Insert sample buses
$sql = "INSERT INTO buses (operator_id, name, description, departure_location, arrival_location, departure_time, arrival_time, price, total_seats, available_seats) VALUES 
($bus_operator_id, 'Express Deluxe', 'Luxury bus service with comfortable seating, WiFi, and refreshments.', 'New York', 'Boston', '08:00:00', '12:30:00', 45.00, 40, 25),
($bus_operator_id, 'City Link', 'Affordable and reliable bus service connecting major cities.', 'New York', 'Chicago', '10:15:00', '14:45:00', 35.00, 45, 30),
($bus_operator_id, 'Night Sleeper', 'Overnight bus service with reclining seats for a comfortable journey.', 'Chicago', 'Detroit', '22:00:00', '06:00:00', 55.00, 35, 20)";

if ($conn->query($sql) === TRUE) {
    echo "Sample buses created successfully<br>";
} else {
    echo "Error creating sample buses: " . $conn->error . "<br>";
}

// Insert sample bus amenities
$sql = "INSERT INTO bus_amenities (bus_id, name) VALUES 
(1, 'WiFi'),
(1, 'Air Conditioning'),
(1, 'Reclining Seats'),
(1, 'Charging Ports'),
(1, 'Refreshments'),
(2, 'WiFi'),
(2, 'Air Conditioning'),
(2, 'Charging Ports'),
(3, 'WiFi'),
(3, 'Air Conditioning'),
(3, 'Reclining Seats'),
(3, 'Charging Ports'),
(3, 'Restroom'),
(3, 'Entertainment System')";

if ($conn->query($sql) === TRUE) {
    echo "Sample bus amenities created successfully<br>";
} else {
    echo "Error creating sample bus amenities: " . $conn->error . "<br>";
}

// Insert sample bookings
$sql = "INSERT INTO bookings (user_id, hotel_id, bus_id, check_in_date, check_out_date, travel_date, guests, passengers, total_amount, status) VALUES 
($user_id, 1, NULL, '2023-04-15', '2023-04-18', NULL, 2, 0, 599.97, 'confirmed'),
($user_id, NULL, 1, NULL, NULL, '2023-04-20', 0, 2, 90.00, 'confirmed'),
($user_id, 2, 2, '2023-05-10', '2023-05-12', '2023-05-10', 1, 1, 159.98, 'pending')";

if ($conn->query($sql) === TRUE) {
    echo "Sample bookings created successfully<br>";
} else {
    echo "Error creating sample bookings: " . $conn->error . "<br>";
}

echo "<br>Database setup completed successfully!";
echo "<br><a href='index.php'>Go to Homepage</a>";

$conn->close();
?>
