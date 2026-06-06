<?php
// Home Page
require_once 'includes/header.php';

// Get stats
$conn = getDBConnection();
$carCount = $conn->query("SELECT COUNT(*) as count FROM cars WHERE available = 1")->fetch_assoc()['count'];
$customerCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];

// Get featured cars
$featuredCars = $conn->query("SELECT * FROM cars WHERE available = 1 ORDER BY id LIMIT 3");
?>

<!-- Home Page -->
<div class="page active" id="page-home">
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Find Your Perfect Ride</h1>
                <p>Discover premium vehicles for every journey. From city drives to road trips across Malaysia, we've got you covered with competitive prices and exceptional service.</p>
                <a href="index.php?page=catalog" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Browse Cars
                </a>
                
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-value"><?php echo $carCount; ?></div>
                        <div class="stat-label">Vehicles</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo $customerCount + 50; ?>+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="search-section">
        <form class="search-grid" onsubmit="event.preventDefault(); homeSearch();">
            <div class="form-group">
                <label class="form-label">Pick-up Location</label>
                <select class="form-control" id="homeLocation">
                    <option value="">Select location</option>
                    <option value="kuala-lumpur">Kuala Lumpur</option>
                    <option value="petaling-jaya">Petaling Jaya</option>
                    <option value="shah-alam">Shah Alam</option>
                    <option value="subang-jaya">Subang Jaya</option>
                    <option value="johor-bahru">Johor Bahru</option>
                    <option value="penang">Penang</option>
                    <option value="ipoh">Ipoh</option>
                    <option value="kota-kinabalu">Kota Kinabalu</option>
                    <option value="kuching">Kuching</option>
                    <option value="malacca">Malacca</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Pick-up Date</label>
                <input type="date" class="form-control" id="homePickupDate" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Return Date</label>
                <input type="date" class="form-control" id="homeReturnDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Vehicle Type</label>
                <select class="form-control" id="homeType">
                    <option value="">All Types</option>
                    <option value="sedan">Sedan</option>
                    <option value="suv">SUV</option>
                    <option value="luxury">Luxury</option>
                    <option value="electric">Electric</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search Cars
            </button>
        </form>
    </div>

    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Featured Vehicles</h2>
            <a href="index.php?page=catalog" class="btn btn-secondary">View All</a>
        </div>
        <div class="vehicle-grid" id="featuredVehicles">
            <?php while ($car = $featuredCars->fetch_assoc()): ?>
            <a href="index.php?page=book&car_id=<?php echo $car['id']; ?>" class="vehicle-card">
                <div class="vehicle-image">
                    <i class="fas <?php echo getCarIcon($car['type']); ?>"></i>
                    <span class="vehicle-badge">Available</span>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-header">
                        <div>
                            <div class="vehicle-name"><?php echo $car['brand'] . ' ' . $car['model']; ?></div>
                            <div style="color:var(--secondary);font-size:0.875rem"><?php echo $car['year']; ?> • <?php echo ucfirst($car['type']); ?></div>
                        </div>
                        <div class="vehicle-price">RM<?php echo number_format($car['price_per_day'], 0); ?><span>/day</span></div>
                    </div>
                    <div class="vehicle-meta">
                        <span><i class="fas fa-user"></i> <?php echo $car['seats']; ?> seats</span>
                        <span><i class="fas fa-gas-pump"></i> <?php echo $car['type'] === 'electric' ? 'Electric' : 'Petrol'; ?></span>
                        <span><i class="fas fa-cog"></i> Auto</span>
                    </div>
                    <div class="vehicle-features">
                        <?php 
                        $features = explode(',', $car['features']);
                        foreach (array_slice($features, 0, 3) as $feature): 
                        ?>
                        <span class="feature-tag"><?php echo trim($feature); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
