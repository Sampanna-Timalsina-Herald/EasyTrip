<?php
include 'config/database.php';
include 'includes/header.php';

// Get buses from database
$sql = "SELECT * FROM buses WHERE status = 'active'";
if (isset($_GET['from']) && !empty($_GET['from'])) {
    $from = $conn->real_escape_string($_GET['from']);
    $sql .= " AND departure_location LIKE '%$from%'";
}
if (isset($_GET['to']) && !empty($_GET['to'])) {
    $to = $conn->real_escape_string($_GET['to']);
    $sql .= " AND arrival_location LIKE '%$to%'";
}

$result = $conn->query($sql);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Available Buses</h2>
            <p>Find the most convenient bus for your journey</p>
        </div>
        
        <div class="search-form" style="margin-bottom: 40px;">
            <form action="buses.php" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="from">From</label>
                        <input type="text" id="from" name="from" class="form-control" placeholder="Departure city">
                    </div>
                    <div class="form-group">
                        <label for="to">To</label>
                        <input type="text" id="to" name="to" class="form-control" placeholder="Arrival city">
                    </div>
                    <div class="form-group">
                        <label for="travel-date">Travel Date</label>
                        <input type="date" id="travel-date" name="travel_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="passengers">Passengers</label>
                        <select id="passengers" name="passengers" class="form-control">
                            <option value="1">1 Passenger</option>
                            <option value="2">2 Passengers</option>
                            <option value="3">3 Passengers</option>
                            <option value="4">4 Passengers</option>
                            <option value="5">5+ Passengers</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Search Buses</button>
            </form>
        </div>
        
        <div class="grid">
            <?php
            // If we have buses in the database, display them
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            ?>
                <div class="card">
                    <div class="card-img">
                        <img src="<?php echo $row['image_url'] ? $row['image_url'] : '/placeholder.svg?height=200&width=300'; ?>" alt="<?php echo $row['name']; ?>">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $row['name']; ?></h3>
                        <p class="card-text">
                            <strong>From:</strong> <?php echo $row['departure_location']; ?><br>
                            <strong>To:</strong> <?php echo $row['arrival_location']; ?><br>
                            <strong>Departure:</strong> <?php echo date('h:i A', strtotime($row['departure_time'])); ?><br>
                            <strong>Arrival:</strong> <?php echo date('h:i A', strtotime($row['arrival_time'])); ?>
                        </p>
                        <div class="card-price">NPR<?php echo $row['price']; ?> per person</div>
                        <a href="bus-details.php?id=<?php echo $row['id']; ?>" class="btn">View Details</a>
                    </div>
                </div>
            <?php
                }
            } else {
                // If no buses found or database is empty
                echo '<div class="alert alert-info">No buses found matching your criteria. Please try a different search.</div>';
                
                // Display sample buses for demonstration
            ?>
                <div class="card">
                    <div class="card-img">
                        <img src="/placeholder.svg?height=200&width=300" alt="Express Bus">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">Express Deluxe</h3>
                        <p class="card-text">
                            <strong>From:</strong> New York<br>
                            <strong>To:</strong> Boston<br>
                            <strong>Departure:</strong> 08:00 AM<br>
                            <strong>Arrival:</strong> 12:30 PM
                        </p>
                        <div class="card-price">$45 per person</div>
                        <a href="bus-details.php?id=1" class="btn">View Details</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-img">
                        <img src="/placeholder.svg?height=200&width=300" alt="Night Bus">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">Night Sleeper</h3>
                        <p class="card-text">
                            <strong>From:</strong> Chicago<br>
                            <strong>To:</strong> Detroit<br>
                            <strong>Departure:</strong> 10:00 PM<br>
                            <strong>Arrival:</strong> 06:00 AM
                        </p>
                        <div class="card-price">$55 per person</div>
                        <a href="bus-details.php?id=2" class="btn">View Details</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-img">
                        <img src="/placeholder.svg?height=200&width=300" alt="Luxury Bus">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">Luxury Coach</h3>
                        <p class="card-text">
                            <strong>From:</strong> Los Angeles<br>
                            <strong>To:</strong> San Francisco<br>
                            <strong>Departure:</strong> 09:30 AM<br>
                            <strong>Arrival:</strong> 04:45 PM
                        </p>
                        <div class="card-price">$65 per person</div>
                        <a href="bus-details.php?id=3" class="btn">View Details</a>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
