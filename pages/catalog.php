<?php
// Catalog Page - Updated with Car Images and Hourly Rate Display
require_once 'includes/header.php';

$conn = getDBConnection();

// Get filter parameters
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$brand = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$price = isset($_GET['price']) ? sanitize($_GET['price']) : '';

// Build query with prepared statements
$params = [];
$types = '';

$query = "SELECT * FROM cars WHERE 1=1";

if ($type && $type != 'all') {
    $query .= " AND type = ?";
    $params[] = $type;
    $types .= 's';
}
if ($brand) {
    $query .= " AND LOWER(brand) = LOWER(?)";
    $params[] = $brand;
    $types .= 's';
}
if ($price) {
    if ($price == '500+') {
        $query .= " AND price_per_day >= ?";
        $params[] = 500;
        $types .= 'i';
    } else {
        list($min, $max) = explode('-', $price);
        $query .= " AND price_per_day >= ? AND price_per_day <= ?";
        $params[] = (int)$min;
        $params[] = (int)$max;
        $types .= 'ii';
    }
}
$query .= " ORDER BY id";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$cars = $stmt->get_result();
?>

<!-- Catalog Page -->
<div class="page active" id="page-catalog">
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Vehicle Catalog</h2>
        </div>
        
        <div class="filters">
            <a href="index.php?page=catalog" class="filter-chip <?php echo !$type ? 'active' : ''; ?>">All Cars</a>
            <a href="index.php?page=catalog&type=sedan" class="filter-chip <?php echo $type == 'sedan' ? 'active' : ''; ?>">Sedan</a>
            <a href="index.php?page=catalog&type=suv" class="filter-chip <?php echo $type == 'suv' ? 'active' : ''; ?>">SUV</a>
            <a href="index.php?page=catalog&type=luxury" class="filter-chip <?php echo $type == 'luxury' ? 'active' : ''; ?>">Luxury</a>
            <a href="index.php?page=catalog&type=electric" class="filter-chip <?php echo $type == 'electric' ? 'active' : ''; ?>">Electric</a>
        </div>

        <div class="search-grid" style="margin-bottom:2rem;background:white;padding:1.5rem;border-radius:1rem;box-shadow:var(--shadow)">
            <div class="form-group">
                <label class="form-label">Brand</label>
                <select class="form-control" id="filterBrand" onchange="applyFilters()">
                    <option value="">All Brands</option>
                    <option value="toyota" <?php echo $brand == 'toyota' ? 'selected' : ''; ?>>Toyota</option>
                    <option value="honda" <?php echo $brand == 'honda' ? 'selected' : ''; ?>>Honda</option>
                    <option value="bmw" <?php echo $brand == 'bmw' ? 'selected' : ''; ?>>BMW</option>
                    <option value="mercedes" <?php echo $brand == 'mercedes' ? 'selected' : ''; ?>>Mercedes</option>
                    <option value="tesla" <?php echo $brand == 'tesla' ? 'selected' : ''; ?>>Tesla</option>
                    <option value="proton" <?php echo $brand == 'proton' ? 'selected' : ''; ?>>Proton</option>
                    <option value="perodua" <?php echo $brand == 'perodua' ? 'selected' : ''; ?>>Perodua</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Price Range (RM)</label>
                <select class="form-control" id="filterPrice" onchange="applyFilters()">
                    <option value="">Any Price</option>
                    <option value="0-150" <?php echo $price == '0-150' ? 'selected' : ''; ?>>RM0 - RM150/day</option>
                    <option value="150-300" <?php echo $price == '150-300' ? 'selected' : ''; ?>>RM150 - RM300/day</option>
                    <option value="300-500" <?php echo $price == '300-500' ? 'selected' : ''; ?>>RM300 - RM500/day</option>
                    <option value="500+" <?php echo $price == '500+' ? 'selected' : ''; ?>>RM500+/day</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Availability</label>
                <input type="date" class="form-control" id="filterDate" min="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="vehicle-grid" id="catalogGrid">
            <?php if ($cars->num_rows > 0): ?>
                <?php while ($car = $cars->fetch_assoc()): ?>
                <a href="index.php?page=book&car_id=<?php echo $car['id']; ?>" class="vehicle-card">
                    <div class="vehicle-image" style="position:relative;overflow:hidden">
                        <?php if (!empty($car['image_exterior'])): ?>
                            <img src="<?php echo $car['image_exterior']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <i class="fas <?php echo getCarIcon($car['type']); ?>"></i>
                        <?php endif; ?>
                        <span class="vehicle-badge <?php echo $car['available'] ? '' : 'unavailable'; ?>">
                            <?php echo $car['available'] ? 'Available' : 'Booked'; ?>
                        </span>
                        <?php if (!empty($car['price_per_hour'])): ?>
                        <span class="vehicle-badge" style="top:auto;bottom:10px;left:10px;background:linear-gradient(135deg, #10b981 0%, #059669 100%);border:none">
                            <i class="fas fa-clock"></i> Hourly Available
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="vehicle-info">
                        <div class="vehicle-header">
                            <div>
                                <div class="vehicle-name"><?php echo $car['brand'] . ' ' . $car['model']; ?></div>
                                <div style="color:var(--secondary);font-size:0.875rem"><?php echo $car['year']; ?> • <?php echo ucfirst($car['type']); ?></div>
                            </div>
                            <div class="vehicle-price">
                                RM<?php echo number_format($car['price_per_day'], 0); ?><span>/day</span>
                                <?php if (!empty($car['price_per_hour'])): ?>
                                <div style="font-size:0.75rem;color:#10b981;font-weight:500">RM<?php echo number_format($car['price_per_hour'], 0); ?>/hr</div>
                                <?php endif; ?>
                            </div>
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
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-car"></i>
                    <h3>No cars found</h3>
                    <p>Try adjusting your filters to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
