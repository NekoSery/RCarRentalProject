<?php
// Bookings Page - Updated with Rental Type and Car Images
require_once 'includes/header.php';

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please sign in to view your bookings');
    redirect('index.php?page=login');
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get current tab
$tab = isset($_GET['tab']) ? sanitize($_GET['tab']) : 'upcoming';

// Get user's bookings with car images using prepared statement
$query = "SELECT b.*, c.brand, c.model, c.year, c.type, c.image, c.image_exterior, c.image_interior 
          FROM bookings b 
          JOIN cars c ON b.car_id = c.id 
          WHERE b.user_id = ?";

$types = 'i';
$params = [$userId];

if ($tab == 'upcoming') {
    $query .= " AND b.status IN ('pending', 'active')";
} elseif ($tab == 'past') {
    $query .= " AND b.status = 'completed'";
} elseif ($tab == 'cancelled') {
    $query .= " AND b.status = 'cancelled'";
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<!-- Bookings Page -->
<div class="page active" id="page-bookings">
    <section class="section">
        <a href="index.php?page=account" class="back-link"><i class="fas fa-arrow-left"></i> Back to Account</a>
        <div class="section-header">
            <h2 class="section-title">My Bookings</h2>
        </div>
        
        <div class="tabs">
            <a href="index.php?page=bookings&tab=upcoming" class="tab <?php echo $tab == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
            <a href="index.php?page=bookings&tab=past" class="tab <?php echo $tab == 'past' ? 'active' : ''; ?>">Past Rentals</a>
            <a href="index.php?page=bookings&tab=cancelled" class="tab <?php echo $tab == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <div id="bookingsList">
            <?php if ($bookings->num_rows > 0): ?>
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="booking-card">
                    <div class="booking-icon" style="overflow:hidden;padding:0;width:80px;height:80px;border-radius:0.75rem">
                        <?php if (!empty($booking['image_exterior'])): ?>
                            <img src="<?php echo $booking['image_exterior']; ?>" alt="<?php echo $booking['brand'] . ' ' . $booking['model']; ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <i class="fas <?php echo getCarIcon($booking['type']); ?>" style="font-size:1.5rem"></i>
                        <?php endif; ?>
                    </div>
                    <div class="booking-details">
                        <h3><?php echo $booking['brand'] . ' ' . $booking['model']; ?></h3>
                        <div class="booking-meta">
                            <span>
                                <i class="fas <?php echo $booking['rental_type'] == 'hourly' ? 'fa-clock' : 'fa-calendar'; ?>"></i> 
                                <?php 
                                if ($booking['rental_type'] == 'hourly' && $booking['hours']) {
                                    echo formatDate($booking['pickup_date']) . ' • ' . $booking['hours'] . ' hours';
                                } else {
                                    echo formatDate($booking['pickup_date']) . ' - ' . formatDate($booking['return_date']);
                                }
                                ?>
                            </span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo getLocationName($booking['location']); ?></span>
                            <?php if ($booking['rental_type'] == 'hourly'): ?>
                            <span style="background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:white;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.75rem;font-weight:500">
                                <i class="fas fa-clock"></i> Hourly
                            </span>
                            <?php endif; ?>
                            <?php echo getStatusBadge($booking['status']); ?>
                        </div>
                    </div>
                    <div class="booking-price">
                        <div class="price"><?php echo formatCurrency($booking['total_amount']); ?></div>
                        <div style="font-size:0.875rem;color:var(--secondary)">Total</div>
                        <?php if ($booking['status'] == 'pending'): ?>
                        <a href="index.php?page=cancel-booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" style="margin-top:0.5rem" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No bookings yet</h3>
                    <p>Start exploring our fleet and make your first reservation!</p>
                    <a href="index.php?page=catalog" class="btn btn-primary" style="margin-top:1rem">Browse Cars</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
