<?php
// Book Car Page - Updated with Hourly Rental Option
require_once 'includes/header.php';

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please sign in to book a vehicle');
    redirect('index.php?page=login');
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get car ID
$carId = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;

if (!$carId) {
    setFlashMessage('error', 'Please select a vehicle');
    redirect('index.php?page=catalog');
}

// Get car details
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND available = 1");
$stmt->bind_param("i", $carId);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    setFlashMessage('error', 'Vehicle not found or unavailable');
    redirect('index.php?page=catalog');
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rentalType = sanitize($_POST['rental_type']);
    $pickup = sanitize($_POST['pickup']);
    $return = isset($_POST['return']) ? sanitize($_POST['return']) : $pickup;
    $pickupTime = isset($_POST['pickup_time']) ? sanitize($_POST['pickup_time']) : null;
    $returnTime = isset($_POST['return_time']) ? sanitize($_POST['return_time']) : null;
    $hours = isset($_POST['hours']) ? intval($_POST['hours']) : null;
    $location = sanitize($_POST['location']);
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $gps = isset($_POST['gps']) ? 1 : 0;
    $childSeat = isset($_POST['child_seat']) ? 1 : 0;
    $touchNGo = isset($_POST['touch_n_go']) ? 1 : 0;
    $totalAmount = floatval($_POST['total_amount']);
    
    // Generate booking ID
    $bookingId = generateBookingId($conn);
    
    // Insert booking with rental type
    $stmt = $conn->prepare("INSERT INTO bookings (id, user_id, car_id, rental_type, hours, pickup_date, return_date, pickup_time, return_time, location, status, total_amount, insurance, gps, child_seat, touch_n_go) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisisssssdiiii", $bookingId, $userId, $carId, $rentalType, $hours, $pickup, $return, $pickupTime, $returnTime, $location, $totalAmount, $insurance, $gps, $childSeat, $touchNGo);
    
    if ($stmt->execute()) {
        // Mark car as unavailable since it now has a pending/active booking
        $unavailStmt = $conn->prepare("UPDATE cars SET available = 0 WHERE id = ?");
        $unavailStmt->bind_param("i", $carId);
        $unavailStmt->execute();

        setFlashMessage('success', 'Booking confirmed successfully!');
        redirect('index.php?page=bookings');
    } else {
        $error = 'Failed to create booking. Please try again.';
    }
}

// Get default dates
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$dayAfter = date('Y-m-d', strtotime('+2 days'));
$defaultTime = '09:00';

$pickup = isset($_GET['pickup']) ? sanitize($_GET['pickup']) : $tomorrow;
$return = isset($_GET['return']) ? sanitize($_GET['return']) : $dayAfter;
$location = isset($_GET['location']) ? sanitize($_GET['location']) : 'kuala-lumpur';
?>

<!-- Book Car Page -->
<div class="page active" id="page-book">
    <section class="section" style="max-width:1000px">
        <div class="section-header">
            <h2 class="section-title">Book Your Vehicle</h2>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error show"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="background:white;padding:2rem;border-radius:1rem;box-shadow:var(--shadow)">
            <form id="bookingForm" method="POST" action="">
                <input type="hidden" id="pricePerDay" value="<?php echo $car['price_per_day']; ?>">
                <input type="hidden" id="pricePerHour" value="<?php echo $car['price_per_hour'] ?? 0; ?>">
                <input type="hidden" name="total_amount" id="totalAmount" value="<?php echo $car['price_per_day'] * 3; ?>">
                
                <!-- Car Details with Images -->
                <div class="car-detail-header" style="flex-direction:column;gap:1.5rem">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <?php if (!empty($car['image_exterior'])): ?>
                        <div style="border-radius:1rem;overflow:hidden;height:200px">
                            <img src="<?php echo $car['image_exterior']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?> Exterior" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($car['image_interior'])): ?>
                        <div style="border-radius:1rem;overflow:hidden;height:200px">
                            <img src="<?php echo $car['image_interior']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?> Interior" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <?php endif; ?>
                        <?php if (empty($car['image_exterior']) && empty($car['image_interior'])): ?>
                        <div class="car-detail-image" style="grid-column:1/-1;height:200px">
                            <i class="fas <?php echo getCarIcon($car['type']); ?>"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="car-detail-info" style="width:100%">
                        <h2><?php echo $car['brand'] . ' ' . $car['model']; ?></h2>
                        <p style="color:var(--secondary)"><?php echo $car['year']; ?> • <?php echo ucfirst($car['type']); ?> • <?php echo $car['seats']; ?> seats</p>
                        <div style="display:flex;gap:1rem;margin-top:0.5rem;flex-wrap:wrap">
                            <span style="background:linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);color:white;padding:0.5rem 1rem;border-radius:2rem;font-weight:600">
                                RM<?php echo number_format($car['price_per_day'], 0); ?>/day
                            </span>
                            <?php if (!empty($car['price_per_hour'])): ?>
                            <span style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:white;padding:0.5rem 1rem;border-radius:2rem;font-weight:600">
                                <i class="fas fa-clock"></i> RM<?php echo number_format($car['price_per_hour'], 0); ?>/hour
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="car-specs">
                            <?php 
                            $features = explode(',', $car['features']);
                            foreach ($features as $feature): 
                            ?>
                            <div class="spec-item"><i class="fas fa-check"></i> <?php echo trim($feature); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Rental Type Selection -->
                <h3 style="margin-bottom:1rem">Select Rental Type</h3>
                <div class="form-group" style="margin-bottom:1.5rem">
                    <div style="display:flex;gap:1rem;flex-wrap:wrap">
                        <label class="rental-type-option <?php echo empty($car['price_per_hour']) ? 'disabled' : ''; ?>" style="flex:1;min-width:200px;cursor:pointer;<?php echo empty($car['price_per_hour']) ? 'opacity:0.5;pointer-events:none' : ''; ?>">
                            <input type="radio" name="rental_type" value="hourly" id="rentalHourly" <?php echo empty($car['price_per_hour']) ? 'disabled' : ''; ?> onchange="toggleRentalType()">
                            <div style="border:2px solid #e2e8f0;border-radius:1rem;padding:1.5rem;text-align:center;transition:all 0.3s" class="rental-type-box">
                                <i class="fas fa-clock" style="font-size:2rem;color:#10b981;margin-bottom:0.5rem"></i>
                                <div style="font-weight:600">Hourly Rental</div>
                                <div style="color:var(--secondary);font-size:0.875rem">RM<?php echo !empty($car['price_per_hour']) ? number_format($car['price_per_hour'], 0) : '-'; ?>/hour</div>
                                <div style="color:var(--secondary);font-size:0.75rem;margin-top:0.5rem">Best for short trips</div>
                            </div>
                        </label>
                        <label class="rental-type-option" style="flex:1;min-width:200px;cursor:pointer">
                            <input type="radio" name="rental_type" value="daily" id="rentalDaily" checked onchange="toggleRentalType()">
                            <div style="border:2px solid #e2e8f0;border-radius:1rem;padding:1.5rem;text-align:center;transition:all 0.3s" class="rental-type-box active">
                                <i class="fas fa-calendar-alt" style="font-size:2rem;color:#4f46e5;margin-bottom:0.5rem"></i>
                                <div style="font-weight:600">Daily Rental</div>
                                <div style="color:var(--secondary);font-size:0.875rem">RM<?php echo number_format($car['price_per_day'], 0); ?>/day</div>
                                <div style="color:var(--secondary);font-size:0.75rem;margin-top:0.5rem">Best for longer trips</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Hourly Rental Options -->
                <div id="hourlyOptions" style="display:none">
                    <h3 style="margin-bottom:1rem">Select Date & Hours</h3>
                    <div class="date-picker">
                        <div class="form-group">
                            <label class="form-label">Rental Date</label>
                            <input type="date" class="form-control" name="pickup" id="bookingPickup" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" onchange="updatePrice()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Number of Hours</label>
                            <select class="form-control" name="hours" id="bookingHours" onchange="updatePrice()">
                                <?php for ($i = 1; $i <= 24; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 4 ? 'selected' : ''; ?>><?php echo $i; ?> hour<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="date-picker" style="margin-top:1rem">
                        <div class="form-group">
                            <label class="form-label">Pick-up Time</label>
                            <input type="time" class="form-control" name="pickup_time" id="pickupTime" value="09:00" onchange="updateReturnTime()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Return Time</label>
                            <input type="time" class="form-control" name="return_time" id="returnTime" value="13:00" readonly>
                        </div>
                    </div>
                    <!-- Hidden return_date for hourly (same as pickup) -->
                    <input type="hidden" name="return" id="hourlyReturnDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <!-- Daily Rental Options -->
                <div id="dailyOptions">
                    <h3 style="margin-bottom:1rem">Select Rental Dates</h3>
                    <div class="date-picker">
                        <div class="form-group">
                            <label class="form-label">Pick-up Date</label>
                            <input type="date" class="form-control" name="pickup" id="bookingPickupDaily" value="<?php echo $pickup; ?>" min="<?php echo date('Y-m-d'); ?>" onchange="updatePrice()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Return Date</label>
                            <input type="date" class="form-control" name="return" id="bookingReturn" value="<?php echo $return; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="updatePrice()">
                        </div>
                    </div>
                </div>
                
                <!-- Location -->
                <div class="form-group" style="margin-bottom:1.5rem;margin-top:1.5rem">
                    <label class="form-label">Pick-up Location</label>
                    <select class="form-control" name="location" id="bookingLocation">
                        <option value="kuala-lumpur" <?php echo $location == 'kuala-lumpur' ? 'selected' : ''; ?>>Kuala Lumpur City Centre</option>
                        <option value="kl-sentral" <?php echo $location == 'kl-sentral' ? 'selected' : ''; ?>>KL Sentral</option>
                        <option value="klia" <?php echo $location == 'klia' ? 'selected' : ''; ?>>KLIA Airport</option>
                        <option value="klia2" <?php echo $location == 'klia2' ? 'selected' : ''; ?>>KLIA2 Airport</option>
                        <option value="petaling-jaya" <?php echo $location == 'petaling-jaya' ? 'selected' : ''; ?>>Petaling Jaya</option>
                        <option value="shah-alam" <?php echo $location == 'shah-alam' ? 'selected' : ''; ?>>Shah Alam</option>
                        <option value="subang-jaya" <?php echo $location == 'subang-jaya' ? 'selected' : ''; ?>>Subang Jaya</option>
                        <option value="johor-bahru" <?php echo $location == 'johor-bahru' ? 'selected' : ''; ?>>Johor Bahru</option>
                        <option value="penang" <?php echo $location == 'penang' ? 'selected' : ''; ?>>Penang</option>
                        <option value="ipoh" <?php echo $location == 'ipoh' ? 'selected' : ''; ?>>Ipoh</option>
                    </select>
                </div>
                
                <!-- Additional Options -->
                <div class="form-group" style="margin-bottom:1.5rem">
                    <label class="form-label">Additional Options</label>
                    <div style="display:flex;flex-direction:column;gap:0.75rem">
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" name="insurance" id="optInsurance" checked onchange="updatePrice()">
                            <span>Full Insurance Coverage (+RM50/day)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" name="gps" id="optGPS" onchange="updatePrice()">
                            <span>GPS Navigation (+RM20/day)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" name="child_seat" id="optChildSeat" onchange="updatePrice()">
                            <span>Child Safety Seat (+RM30/day)</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" name="touch_n_go" id="optTouchNGo" onchange="updatePrice()">
                            <span>Touch 'n Go Card with RM50 credit (+RM60)</span>
                        </label>
                    </div>
                </div>
                
                <!-- Price Breakdown -->
                <div class="price-breakdown">
                    <div class="price-row">
                        <span id="baseRateLabel">Base Rate</span> (<span id="priceDays">3</span> <span id="durationUnit">days</span>@ RM<span id="rateDisplay"><?php echo number_format($car['price_per_day'], 0); ?></span>/<span id="ratePeriod">day</span>)
                        <span id="priceBase"><?php echo formatCurrency($car['price_per_day'] * 3); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Additional Options</span>
                        <span id="priceOptions">RM150.00</span>
                    </div>
                    <div class="price-row">
                        <span>SST (10%)</span>
                        <span id="priceTax"><?php echo formatCurrency(($car['price_per_day'] * 3 + 150) * 0.10); ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Total Amount</span>
                        <span id="priceTotal"><?php echo formatCurrency(($car['price_per_day'] * 3 + 150) * 1.10); ?></span>
                    </div>
                </div>
                
                <!-- Driver Info -->
                <div style="background:#f8fafc;border-radius:0.75rem;padding:1.5rem;margin-top:1.5rem">
                    <h4 style="margin-bottom:1rem"><i class="fas fa-user"></i> Driver Information</h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div>
                            <div style="color:var(--secondary);font-size:0.875rem">Name</div>
                            <div style="font-weight:600"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
                        </div>
                        <div>
                            <div style="color:var(--secondary);font-size:0.875rem">License Number</div>
                            <div style="font-weight:600"><?php echo htmlspecialchars($user['license_number'] ?? ''); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div style="display:flex;justify-content:space-between;margin-top:1.5rem">
                    <a href="index.php?page=catalog" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Catalog</a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<style>
.rental-type-option input[type="radio"] {
    display: none;
}
.rental-type-option input[type="radio"]:checked + .rental-type-box {
    border-color: #4f46e5 !important;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
}
.rental-type-option input[type="radio"]:checked + .rental-type-box.active {
    border-color: #4f46e5 !important;
}
</style>

<script>
// Initialize price calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
});

function toggleRentalType() {
    const isHourly = document.getElementById('rentalHourly').checked;
    const hourlyOptions = document.getElementById('hourlyOptions');
    const dailyOptions = document.getElementById('dailyOptions');
    
    // Update visual selection
    document.querySelectorAll('.rental-type-box').forEach(box => {
        box.classList.remove('active');
        box.style.borderColor = '#e2e8f0';
    });
    
    if (isHourly) {
        hourlyOptions.style.display = 'block';
        dailyOptions.style.display = 'none';
        // Disable daily inputs so they don't submit
        document.querySelectorAll('#dailyOptions input, #dailyOptions select').forEach(el => el.disabled = true);
        document.querySelectorAll('#hourlyOptions input, #hourlyOptions select').forEach(el => el.disabled = false);
        
        document.querySelector('#rentalHourly + .rental-type-box').classList.add('active');
        document.querySelector('#rentalHourly + .rental-type-box').style.borderColor = '#10b981';
    } else {
        hourlyOptions.style.display = 'none';
        dailyOptions.style.display = 'block';
        // Disable hourly inputs so they don't submit
        document.querySelectorAll('#hourlyOptions input, #hourlyOptions select').forEach(el => el.disabled = true);
        document.querySelectorAll('#dailyOptions input, #dailyOptions select').forEach(el => el.disabled = false);
        
        document.querySelector('#rentalDaily + .rental-type-box').classList.add('active');
        document.querySelector('#rentalDaily + .rental-type-box').style.borderColor = '#4f46e5';
    }
    
    updatePrice();
}

function updateReturnTime() {
    const pickupTime = document.getElementById('pickupTime').value;
    const hours = parseInt(document.getElementById('bookingHours').value);
    
    if (pickupTime) {
        const [h, m] = pickupTime.split(':');
        const pickupDate = new Date();
        pickupDate.setHours(parseInt(h), parseInt(m));
        pickupDate.setHours(pickupDate.getHours() + hours);
        
        const returnHours = String(pickupDate.getHours()).padStart(2, '0');
        const returnMinutes = String(pickupDate.getMinutes()).padStart(2, '0');
        document.getElementById('returnTime').value = returnHours + ':' + returnMinutes;
    }
}

function updatePrice() {
    const isHourly = document.getElementById('rentalHourly').checked;
    const pricePerDay = parseFloat(document.getElementById('pricePerDay').value) || 0;
    const pricePerHour = parseFloat(document.getElementById('pricePerHour').value) || 0;
    
    let baseRate = 0;
    let duration = 0;
    let durationLabel = '';
    let unitLabel = '';
    let rateValue = 0;
    let periodLabel = '';
    
    if (isHourly && pricePerHour > 0) {
        // Hourly calculation
        duration = parseInt(document.getElementById('bookingHours').value) || 1;
        baseRate = pricePerHour * duration;
        durationLabel = duration;
        unitLabel = duration > 1 ? 'hours' : 'hour';
        rateValue = pricePerHour;
        periodLabel = 'hour';
        
        // Update return time
        updateReturnTime();
    } else {
        // Daily calculation
        const pickup = document.getElementById('bookingPickupDaily').value;
        const returnDate = document.getElementById('bookingReturn').value;
        
        if (pickup && returnDate) {
            const start = new Date(pickup + 'T00:00:00');
            const end = new Date(returnDate + 'T00:00:00');
            duration = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
        } else {
            duration = 1;
        }
        
        baseRate = pricePerDay * duration;
        durationLabel = duration;
        unitLabel = duration > 1 ? 'days' : 'day';
        rateValue = pricePerDay;
        periodLabel = 'day';
    }
    
    // Calculate additional options
    const insurance = document.getElementById('optInsurance').checked ? 50 * (isHourly ? 1 : duration) : 0;
    const gps = document.getElementById('optGPS').checked ? 20 * (isHourly ? 1 : duration) : 0;
    const childSeat = document.getElementById('optChildSeat').checked ? 30 * (isHourly ? 1 : duration) : 0;
    const touchNGo = document.getElementById('optTouchNGo').checked ? 60 : 0;
    
    const optionsTotal = insurance + gps + childSeat + touchNGo;
    const subtotal = baseRate + optionsTotal;
    const tax = subtotal * 0.10;
    const total = subtotal + tax;
    
    // Update display elements safely
    const elBaseRateLabel = document.getElementById('baseRateLabel');
    const elPriceDays = document.getElementById('priceDays');
    const elDurationUnit = document.getElementById('durationUnit');
    const elRateDisplay = document.getElementById('rateDisplay');
    const elRatePeriod = document.getElementById('ratePeriod');
    const elPriceBase = document.getElementById('priceBase');
    const elPriceOptions = document.getElementById('priceOptions');
    const elPriceTax = document.getElementById('priceTax');
    const elPriceTotal = document.getElementById('priceTotal');
    const elTotalAmount = document.getElementById('totalAmount');
    
    if (elBaseRateLabel) elBaseRateLabel.textContent = 'Base Rate';
    if (elPriceDays) elPriceDays.textContent = durationLabel;
    if (elDurationUnit) elDurationUnit.textContent = unitLabel;
    if (elRateDisplay) elRateDisplay.textContent = rateValue;
    if (elRatePeriod) elRatePeriod.textContent = periodLabel;
    if (elPriceBase) elPriceBase.textContent = 'RM' + baseRate.toFixed(2);
    if (elPriceOptions) elPriceOptions.textContent = 'RM' + optionsTotal.toFixed(2);
    if (elPriceTax) elPriceTax.textContent = 'RM' + tax.toFixed(2);
    if (elPriceTotal) elPriceTotal.textContent = 'RM' + total.toFixed(2);
    if (elTotalAmount) elTotalAmount.value = total.toFixed(2);
}
</script>

<?php require_once 'includes/footer.php'; ?>