<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gbtravel_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$destination = "";
$checkin = "";
$guesthouses_results = [];
$bus_tickets_results = [];

// Initialize variables
$destination = "";
$guesthouses_results = [];

// Guesthouse search logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guesthouse_search'])) {
    // Get form data for guesthouses
    $destination = $_POST['destination'];

    // Fetch all guesthouses for the selected destination
    $guesthouses_sql = "SELECT * FROM guesthouses WHERE location = ?";
    $stmt = $conn->prepare($guesthouses_sql);
    $stmt->bind_param("s", $destination);
    $stmt->execute();
    $guesthouses_result = $stmt->get_result();
    $stmt->close();

    if ($guesthouses_result->num_rows > 0) {
        while ($row = $guesthouses_result->fetch_assoc()) {
            $guesthouses_results[] = $row;
        }
    }
}

// Bus ticket search logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bus_ticket_search'])) {
    // Get form data for bus tickets
    $destination = $_POST['bus_destination'];
    $checkin = $_POST['bus_checkin'];

    // Fetch bus tickets based on destination and check-in (departure) date
    $bus_tickets_sql = "SELECT * FROM bus_tickets WHERE destination = ? AND departure_date = ?";
    $stmt = $conn->prepare($bus_tickets_sql);
    $stmt->bind_param("ss", $destination, $checkin);
    $stmt->execute();
    $bus_tickets_result = $stmt->get_result();
    $stmt->close();

    if ($bus_tickets_result->num_rows > 0) {
        while ($row = $bus_tickets_result->fetch_assoc()) {
            $bus_tickets_results[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Search</title>
    <link rel="stylesheet" href="srb.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <div class="srb">
        <div class="container">
            <h1 class="text-center my-3">Search for Guesthouses and Bus Tickets</h1>

            <!-- Guesthouse Search Form -->
            <form id="guesthouseSearchForm" method="POST" action="">
                <h2>Guesthouse Search</h2>
                <label for="destination" class="d-none">Destination:</label>
                <input type="text" id="destination" placeholder="Enter Destination:" name="destination"
                    value="<?php echo htmlspecialchars($destination); ?>" required>

                <button type="submit" name="guesthouse_search">Search Guesthouses</button>
            </form>

            <!-- Display Guesthouse Results -->
            <div class="row">
                <div id="guesthouseResults">
                    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guesthouse_search'])): ?>
                        <h3 class="search_result">Guesthouses in <?php echo htmlspecialchars($destination); ?></h3>
                        <?php if (!empty($guesthouses_results)): ?>
                            <?php foreach ($guesthouses_results as $guesthouse): ?>
                                <div class="col-3">
                                    <div class="guesthouse">
                                        <div class="gimg">
                                            <img src="<?php echo htmlspecialchars($guesthouse['image_url']); ?>"
                                                alt="Guesthouse Image">
                                        </div>
                                        <div class="gh_title d-inline-block">
                                            <?php echo htmlspecialchars($guesthouse['name']); ?>,
                                        </div>
                                        <div class="gh_location d-inline-block">
                                            <?php echo htmlspecialchars($guesthouse['location']); ?>
                                        </div>
                                        <div class="gh_price">
                                            Price: $<?php echo htmlspecialchars($guesthouse['price']); ?> /person
                                        </div>
                                        <div class="gh_rating">
                                            Rating: <?php echo htmlspecialchars($guesthouse['rating']); ?>
                                        </div>
                                        <div class="book_cta">
                                            <a href="#">Book Now <i class="fa-regular fa-rectangle-list"></i></a>
                                        </div>
                                        <p class="fw-bold">Note: Call to check for availability.</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No guesthouses found for the selected destination.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bus Ticket Search Form -->
            <form id="busTicketSearchForm" method="POST" action="">
                <h2>Bus Ticket Search</h2>
                <label for="bus_destination" class="d-none">Destination:</label>
                <input type="text" id="bus_destination" placeholder="Enter Travel Destination:" name="bus_destination"
                    value="<?php echo htmlspecialchars($destination); ?>" required>

                <label for="bus_checkin" class="d-none">Departure Date:</label>
                <input type="date" id="bus_checkin" placeholder="Enter Departure Date:" name="bus_checkin"
                    value="<?php echo htmlspecialchars($checkin); ?>" required>

                <button type="submit" name="bus_ticket_search">Search Bus Tickets</button>
            </form>

            <!-- Display Bus Ticket Results -->
            <div class="row">
                <div id="busTicketResults">
                    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bus_ticket_search'])): ?>
                        <h3 class="search_result">Bus Tickets for <?php echo htmlspecialchars($destination);?></h3>
                        <?php if (!empty($bus_tickets_results)): ?>
                            <?php foreach ($bus_tickets_results as $ticket): ?>
                                <div class="col-3">
                                    <div class="ticket">
                                        <div class="ticket_operator text-center d-block">
                                            <h4><?php echo htmlspecialchars($ticket['operator']); ?></h4>
                                        </div>
                                        <div class="ticket_departure">
                                            Departure:
                                            <?php echo htmlspecialchars($ticket['departure_date']) . ' at ' . htmlspecialchars($ticket['departure_time']); ?>
                                        </div>
                                        <div class="ticket_arrival">
                                            Arrival:
                                            <?php echo htmlspecialchars($ticket['arrival_date']) . ' at ' . htmlspecialchars($ticket['arrival_time']); ?>
                                        </div>
                                        <div class="ticket_price">
                                            Price: $<?php echo htmlspecialchars($ticket['price']); ?>
                                        </div>
                                        <div class="ticket_seats">
                                            No. of Seats: <?php echo htmlspecialchars($ticket['available_seats']); ?>
                                        </div>
                                        <div class="book_cta">
                                            <a href="#">Book Now <i class="fa-regular fa-rectangle-list"></i></a>
                                        </div>
                                        <p class="fw-bold">Note: Call to check for seats remaining.</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No bus tickets found for the selected destination and departure date.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
        <script src="srb.js"></script>
</body>

</html>