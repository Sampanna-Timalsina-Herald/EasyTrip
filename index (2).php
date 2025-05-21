<?php include 'includes/header.php'; ?>

<section class="hero">
    <div class="container">
        <h1>Book Hotels & Buses in One Place</h1>
        <p>Find the best hotels and bus tickets for your journey. Book together and save!</p>
    </div>
</section>

<section class="search-section">
    <div class="container">
        <div class="search-form">
            <h2>Plan Your Trip</h2>
            <form action="search-results.php" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="destination">Destination</label>
                        <input type="text" id="destination" name="destination" class="form-control" placeholder="Where are you going?" required>
                    </div>
                    <div class="form-group">
                        <label for="check-in">Check-in Date</label>
                        <input type="date" id="check-in" name="check_in" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="check-out">Check-out Date</label>
                        <input type="date" id="check-out" name="check_out" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guests">Guests</label>
                        <select id="guests" name="guests" class="form-control">
                            <option value="1">1 Guest</option>
                            <option value="2">2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="5">5+ Guests</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="travel-type">Travel Type</label>
                        <select id="travel-type" name="travel_type" class="form-control">
                            <option value="both">Hotel & Bus</option>
                            <option value="hotel">Hotel Only</option>
                            <option value="bus">Bus Only</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-block">Search</button>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Popular Destinations</h2>
            <p>Explore our most booked destinations and find your next adventure</p>
        </div>
        <div class="grid">
            <div class="card">
                <div class="card-img">
                    <img src="/placeholder.svg?height=200&width=300" alt="Mountain Resort">
                </div>
                <div class="card-body">
                    <h3 class="card-title">Mountain Resort</h3>
                    <p class="card-text">Experience the beauty of nature with our mountain resort packages.</p>
                    <div class="card-price">From $99/night</div>
                    <a href="hotels.php?location=mountains" class="btn">View Details</a>
                </div>
            </div>
            <div class="card">
                <div class="card-img">
                    <img src="/placeholder.svg?height=200&width=300" alt="Beach Paradise">
                </div>
                <div class="card-body">
                    <h3 class="card-title">Beach Paradise</h3>
                    <p class="card-text">Relax and unwind at our beautiful beach resorts with ocean views.</p>
                    <div class="card-price">From $129/night</div>
                    <a href="hotels.php?location=beach" class="btn">View Details</a>
                </div>
            </div>
            <div class="card">
                <div class="card-img">
                    <img src="/placeholder.svg?height=200&width=300" alt="City Escape">
                </div>
                <div class="card-body">
                    <h3 class="card-title">City Escape</h3>
                    <p class="card-text">Explore the vibrant city life with our centrally located hotels.</p>
                    <div class="card-price">From $89/night</div>
                    <a href="hotels.php?location=city" class="btn">View Details</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background-color: #f0f4f8;">
    <div class="container">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Book your hotel and bus in three simple steps</p>
        </div>
        <div class="grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: #3a86ff; margin-bottom: 20px;">
                    <i class="fas fa-search"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Search</h3>
                    <p class="card-text">Find hotels and buses that match your travel dates and preferences.</p>
                </div>
            </div>
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: #3a86ff; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Select</h3>
                    <p class="card-text">Choose the perfect hotel and bus combination for your journey.</p>
                </div>
            </div>
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: #3a86ff; margin-bottom: 20px;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Pay</h3>
                    <p class="card-text">Make a single payment for both hotel and bus bookings.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

