<?php
// Functions to handle data operations

// Get all seats
function getSeats($conn) {
    $sql = "SELECT * FROM seats ORDER BY id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $seats = [];
        while($row = $result->fetch_assoc()) {
            $seats[] = $row;
        }
        return $seats;
    } else {
        // If no data in database, return default data
        return initializeSeats($conn);
    }
}

// Initialize seats if not in database
function initializeSeats($conn) {
    $defaultSeats = [];
    
    // Create 20 default seats
    for ($i = 1; $i <= 20; $i++) {
        $status = ($i % 3 == 0) ? 'booked' : 'available';
        $defaultSeats[] = [
            'id' => $i,
            'status' => $status
        ];
        
        // Insert into database
        $sql = "INSERT INTO seats (id, status) VALUES ($i, '$status')";
        $conn->query($sql);
    }
    
    return $defaultSeats;
}

// Update seat status
function updateSeat($conn, $seatId, $status) {
    $sql = "UPDATE seats SET status = '$status' WHERE id = $seatId";
    return $conn->query($sql);
}

// Get all schedules
function getSchedules($conn) {
    $sql = "SELECT * FROM schedules ORDER BY departure";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $schedules = [];
        while($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        return $schedules;
    } else {
        // If no data in database, return default data
        return initializeSchedules($conn);
    }
}

// Initialize schedules if not in database
function initializeSchedules($conn) {
    $defaultSchedules = [
        [
            'id' => 1,
            'route' => 'New York - Boston',
            'departure' => '08:00 AM',
            'arrival' => '12:30 PM',
            'available_seats' => 15,
            'status' => 'on-time'
        ],
        [
            'id' => 2,
            'route' => 'Boston - Washington DC',
            'departure' => '10:30 AM',
            'arrival' => '03:45 PM',
            'available_seats' => 8,
            'status' => 'delayed'
        ],
        [
            'id' => 3,
            'route' => 'Chicago - Detroit',
            'departure' => '09:15 AM',
            'arrival' => '01:30 PM',
            'available_seats' => 12,
            'status' => 'on-time'
        ],
        [
            'id' => 4,
            'route' => 'Los Angeles - San Francisco',
            'departure' => '11:00 AM',
            'arrival' => '04:15 PM',
            'available_seats' => 0,
            'status' => 'cancelled'
        ],
        [
            'id' => 5,
            'route' => 'Seattle - Portland',
            'departure' => '02:30 PM',
            'arrival' => '05:45 PM',
            'available_seats' => 20,
            'status' => 'on-time'
        ]
    ];
    
    // Insert into database
    foreach ($defaultSchedules as $schedule) {
        $sql = "INSERT INTO schedules (id, route, departure, arrival, available_seats, status) 
                VALUES ({$schedule['id']}, '{$schedule['route']}', '{$schedule['departure']}', 
                '{$schedule['arrival']}', {$schedule['available_seats']}, '{$schedule['status']}')";
        $conn->query($sql);
    }
    
    return $defaultSchedules;
}

// Update schedule
function updateSchedule($conn, $data) {
    $id = $data['id'];
    $route = $data['route'];
    $departure = $data['departure'];
    $arrival = $data['arrival'];
    $available_seats = $data['available_seats'];
    $status = $data['status'];
    
    $sql = "UPDATE schedules SET 
            route = '$route', 
            departure = '$departure', 
            arrival = '$arrival', 
            available_seats = $available_seats, 
            status = '$status' 
            WHERE id = $id";
            
    return $conn->query($sql);
}

// Add new schedule
function addSchedule($conn, $data) {
    $route = $data['route'];
    $departure = $data['departure'];
    $arrival = $data['arrival'];
    $available_seats = $data['available_seats'];
    $status = $data['status'];
    
    $sql = "INSERT INTO schedules (route, departure, arrival, available_seats, status) 
            VALUES ('$route', '$departure', '$arrival', $available_seats, '$status')";
            
    return $conn->query($sql);
}

// Delete schedule
function deleteSchedule($conn, $id) {
    $sql = "DELETE FROM schedules WHERE id = $id";
    return $conn->query($sql);
}

// Get all bookings
function getBookings($conn) {
    $sql = "SELECT * FROM bookings ORDER BY date";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $bookings = [];
        while($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        return $bookings;
    } else {
        // If no data in database, return default data
        return initializeBookings($conn);
    }
}

// Initialize bookings if not in database
function initializeBookings($conn) {
    $defaultBookings = [
        [
            'id' => 1,
            'passenger' => 'John Smith',
            'route' => 'New York - Boston',
            'date' => '2023-10-15',
            'seat' => '3',
            'status' => 'confirmed'
        ],
        [
            'id' => 2,
            'passenger' => 'Emma Johnson',
            'route' => 'Boston - Washington DC',
            'date' => '2023-10-16',
            'seat' => '5',
            'status' => 'confirmed'
        ],
        [
            'id' => 3,
            'passenger' => 'Michael Brown',
            'route' => 'Chicago - Detroit',
            'date' => '2023-10-17',
            'seat' => '8',
            'status' => 'pending'
        ],
        [
            'id' => 4,
            'passenger' => 'Sophia Wilson',
            'route' => 'Los Angeles - San Francisco',
            'date' => '2023-10-18',
            'seat' => '11',
            'status' => 'confirmed'
        ],
        [
            'id' => 5,
            'passenger' => 'James Davis',
            'route' => 'Seattle - Portland',
            'date' => '2023-10-19',
            'seat' => '14',
            'status' => 'confirmed'
        ],
        [
            'id' => 6,
            'passenger' => 'Olivia Martinez',
            'route' => 'New York - Boston',
            'date' => '2023-10-20',
            'seat' => '17',
            'status' => 'pending'
        ],
        [
            'id' => 7,
            'passenger' => 'William Taylor',
            'route' => 'Chicago - Detroit',
            'date' => '2023-10-21',
            'seat' => '20',
            'status' => 'confirmed'
        ]
    ];
    
    // Insert into database
    foreach ($defaultBookings as $booking) {
        $sql = "INSERT INTO bookings (id, passenger, route, date, seat, status) 
                VALUES ({$booking['id']}, '{$booking['passenger']}', '{$booking['route']}', 
                '{$booking['date']}', '{$booking['seat']}', '{$booking['status']}')";
        $conn->query($sql);
    }
    
    return $defaultBookings;
}

// Update booking
function updateBooking($conn, $data) {
    $id = $data['id'];
    $passenger = $data['passenger'];
    $route = $data['route'];
    $date = $data['date'];
    $seat = $data['seat'];
    $status = $data['status'];
    
    $sql = "UPDATE bookings SET 
            passenger = '$passenger', 
            route = '$route', 
            date = '$date', 
            seat = '$seat', 
            status = '$status' 
            WHERE id = $id";
            
    return $conn->query($sql);
}

// Add new booking
function addBooking($conn, $data) {
    $passenger = $data['passenger'];
    $route = $data['route'];
    $date = $data['date'];
    $seat = $data['seat'];
    $status = $data['status'];
    
    $sql = "INSERT INTO bookings (passenger, route, date, seat, status) 
            VALUES ('$passenger', '$route', '$date', '$seat', '$status')";
            
    return $conn->query($sql);
}

// Delete booking
function deleteBooking($conn, $id) {
    $sql = "DELETE FROM bookings WHERE id = $id";
    return $conn->query($sql);
}
?>

