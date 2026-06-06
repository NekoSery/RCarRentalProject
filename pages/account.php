<?php
// User Account Dashboard Page
require_once 'includes/header.php';

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please sign in to view your account');
    redirect('index.php?page=login');
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fallback if user not found
if (!$user) {
    setFlashMessage('error', 'User data not found. Please log in again.');
    redirect('index.php?page=logout');
}

// Get booking stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalBookings = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as active FROM bookings WHERE user_id = ? AND status IN ('pending', 'active')");
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeBookings = $stmt->get_result()->fetch_assoc()['active'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE user_id = ? AND status != 'cancelled'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalSpent = $stmt->get_result()->fetch_assoc()['total'];

// Get latest booking
$stmt = $conn->prepare("SELECT b.*, c.brand, c.model, c.type FROM bookings b JOIN cars c ON b.car_id = c.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$latestBooking = $stmt->get_result()->fetch_assoc();

$isAdmin = isAdmin();
?>

<!-- Account Dashboard Page -->
<div class="page active" id="page-account">
    <section class="section" style="max-width:900px">
        
        <!-- User Header Card -->
        <div class="account-header">
            <div class="account-avatar-large">
                <span><?php echo getInitials($user['name'] ?? 'User'); ?></span>
            </div>
            <div class="account-info">
                <h2 class="account-name"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h2>
                <p class="account-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <div style="display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;align-items:center">
                    <span class="account-role-badge <?php echo $isAdmin ? 'admin' : 'customer'; ?>">
                        <i class="fas <?php echo $isAdmin ? 'fa-shield-alt' : 'fa-user'; ?>"></i>
                        <?php echo $isAdmin ? 'Administrator' : 'Customer'; ?>
                    </span>
                    <?php if ($user['license_number']): ?>
                    <span class="account-role-badge license">
                        <i class="fas fa-id-card"></i>
                        License: <?php echo htmlspecialchars($user['license_number']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="account-stats">
            <div class="account-stat-card">
                <div class="account-stat-icon blue">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="account-stat-info">
                    <h3><?php echo $totalBookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="account-stat-card">
                <div class="account-stat-icon green">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="account-stat-info">
                    <h3><?php echo $activeBookings; ?></h3>
                    <p>Active Rentals</p>
                </div>
            </div>
            <div class="account-stat-card">
                <div class="account-stat-icon gold">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="account-stat-info">
                    <h3><?php echo formatCurrency($totalSpent); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>

        <!-- Navigation Cards -->
        <h3 class="section-title" style="margin-bottom:1.5rem">Quick Actions</h3>
        <div class="account-nav-grid">
            
            <!-- Profile Card -->
            <a href="index.php?page=profile" class="account-nav-card">
                <div class="account-nav-icon" style="background:linear-gradient(135deg, var(--navy-lighter) 0%, #c7d2fe 100%);color:var(--navy-primary)">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="account-nav-content">
                    <h4>Edit Profile</h4>
                    <p>Update your name, phone, and license information</p>
                </div>
                <div class="account-nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- Bookings Card -->
            <a href="index.php?page=bookings" class="account-nav-card">
                <div class="account-nav-icon" style="background:linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);color:var(--success)">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="account-nav-content">
                    <h4>My Bookings</h4>
                    <p>View and manage all your car rentals</p>
                </div>
                <div class="account-nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <?php if ($isAdmin): ?>
            <!-- Admin Card -->
            <a href="index.php?page=admin" class="account-nav-card">
                <div class="account-nav-icon" style="background:linear-gradient(135deg, var(--gold-pale) 0%, #ffe082 100%);color:var(--gold-dark)">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="account-nav-content">
                    <h4>Admin Panel</h4>
                    <p>Manage fleet, bookings, and users</p>
                </div>
                <div class="account-nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            <?php endif; ?>
            
            <!-- Catalog Card -->
            <a href="index.php?page=catalog" class="account-nav-card">
                <div class="account-nav-icon" style="background:linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);color:#00838f">
                    <i class="fas fa-car"></i>
                </div>
                <div class="account-nav-content">
                    <h4>Browse Cars</h4>
                    <p>Explore our fleet and book a new ride</p>
                </div>
                <div class="account-nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- Logout Card -->
            <a href="index.php?page=logout" class="account-nav-card logout" onclick="return confirm('Are you sure you want to log out?')">
                <div class="account-nav-icon" style="background:linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);color:var(--danger)">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="account-nav-content">
                    <h4>Log Out</h4>
                    <p>Sign out of your account securely</p>
                </div>
                <div class="account-nav-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
        </div>

        <!-- Latest Booking -->
        <?php if ($latestBooking): ?>
        <h3 class="section-title" style="margin:2rem 0 1.5rem">Latest Booking</h3>
        <div class="booking-card" style="margin-bottom:2rem">
            <div class="booking-icon">
                <i class="fas <?php echo getCarIcon($latestBooking['type']); ?>"></i>
            </div>
            <div class="booking-details">
                <h3><?php echo $latestBooking['brand'] . ' ' . $latestBooking['model']; ?></h3>
                <div class="booking-meta">
                    <span><i class="fas fa-calendar"></i> 
                        <?php 
                        if ($latestBooking['rental_type'] == 'hourly' && $latestBooking['hours']) {
                            echo formatDate($latestBooking['pickup_date']) . ' &bull; ' . $latestBooking['hours'] . ' hours';
                        } else {
                            echo formatDate($latestBooking['pickup_date']) . ' - ' . formatDate($latestBooking['return_date']);
                        }
                        ?>
                    </span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo getLocationName($latestBooking['location']); ?></span>
                    <?php echo getStatusBadge($latestBooking['status']); ?>
                </div>
            </div>
            <div class="booking-price">
                <div class="price"><?php echo formatCurrency($latestBooking['total_amount']); ?></div>
                <div style="font-size:0.875rem;color:var(--secondary)">Total</div>
                <a href="index.php?page=bookings" class="btn btn-sm btn-secondary" style="margin-top:0.5rem">View All</a>
            </div>
        </div>
        <?php endif; ?>

    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
