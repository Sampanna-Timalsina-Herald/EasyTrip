<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Change to your database username
$password = ""; // Change to your database password
$dbname = "bus_system"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($dbname);

// Create seats table
$sql = "CREATE TABLE IF NOT EXISTS seats (
    id INT(6) PRIMARY KEY,
    status VARCHAR(30) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'seats' created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create schedules table
$sql = "CREATE TABLE IF NOT EXISTS schedules (
    id INT(6) AUTO_INCREMENT PRIMARY KEY,
    route VARCHAR(100) NOT NULL,
    departure VARCHAR(30) NOT NULL,
    arrival VARCHAR(30) NOT NULL,
    available_seats INT(6) NOT NULL,
    status VARCHAR(30) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'schedules' created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create bookings table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT(6) AUTO_INCREMENT PRIMARY KEY,
    passenger VARCHAR(100) NOT NULL,
    route VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    seat VARCHAR(10) NOT NULL,
    status VARCHAR(30) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'bookings' created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

echo "<p>Database setup completed. <a href='index.php'>Go to Dashboard</a></p>";

$conn->close();
?>

