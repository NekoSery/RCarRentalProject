<?php
// Admin Dashboard Page - Updated with Image Upload, Hourly Rate Support, and Edit Car Feature
require_once 'includes/header.php';

// Check if admin
if (!isAdmin()) {
    setFlashMessage('error', 'Admin access required');
    redirect('index.php');
}

$conn = getDBConnection();

// Get current view
$view = isset($_GET['view']) ? sanitize($_GET['view']) : 'dashboard';

// Get stats
$totalCars = $conn->query("SELECT COUNT(*) as count FROM cars")->fetch_assoc()['count'];
$availableCars = $conn->query("SELECT COUNT(*) as count FROM cars WHERE available = 1")->fetch_assoc()['count'];
$activeBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'active'")->fetch_assoc()['count'];
$revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE status = 'completed'")->fetch_assoc()['total'];

// Get recent bookings
$recentBookings = $conn->query("SELECT b.*, u.name as user_name, c.brand, c.model 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN cars c ON b.car_id = c.id 
    ORDER BY b.created_at DESC LIMIT 5");

// Get all cars for fleet
$fleet = $conn->query("SELECT * FROM cars ORDER BY id");

// Get all bookings
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$bookingsQuery = "SELECT b.*, u.name as user_name, c.brand, c.model 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN cars c ON b.car_id = c.id";
$bookingParams = [];
$bookingTypes = '';
if ($statusFilter && $statusFilter != 'all') {
    $bookingsQuery .= " WHERE b.status = ?";
    $bookingParams[] = $statusFilter;
    $bookingTypes .= 's';
}
$bookingsQuery .= " ORDER BY b.created_at DESC";

$allBookingsStmt = $conn->prepare($bookingsQuery);
if (!empty($bookingParams)) {
    $allBookingsStmt->bind_param($bookingTypes, ...$bookingParams);
}
$allBookingsStmt->execute();
$allBookings = $allBookingsStmt->get_result();

// Get all users
$users = $conn->query("SELECT * FROM users WHERE role = 'customer' ORDER BY id");

// Get car to edit if edit mode
$editCar = null;
if (isset($_GET['edit_car'])) {
    $editCarId = intval($_GET['edit_car']);
    $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->bind_param("i", $editCarId);
    $stmt->execute();
    $editCar = $stmt->get_result()->fetch_assoc();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new car with image upload
    if (isset($_POST['action']) && $_POST['action'] == 'add_car') {
        $brand = sanitize($_POST['brand']);
        $model = sanitize($_POST['model']);
        $type = sanitize($_POST['type']);
        $price = floatval($_POST['price']);
        $pricePerHour = floatval($_POST['price_per_hour']);
        $year = intval($_POST['year']);
        $seats = intval($_POST['seats']);
        $features = sanitize($_POST['features']);
        
        // Handle image uploads
        $imageExterior = null;
        $imageInterior = null;
        
        // Create uploads directory if not exists
        $uploadDir = 'uploads/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Upload exterior image
        if (isset($_FILES['image_exterior']) && $_FILES['image_exterior']['error'] == 0) {
            $ext = pathinfo($_FILES['image_exterior']['name'], PATHINFO_EXTENSION);
            $filename = 'exterior_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image_exterior']['tmp_name'], $targetPath)) {
                $imageExterior = $targetPath;
            }
        }
        
        // Upload interior image
        if (isset($_FILES['image_interior']) && $_FILES['image_interior']['error'] == 0) {
            $ext = pathinfo($_FILES['image_interior']['name'], PATHINFO_EXTENSION);
            $filename = 'interior_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image_interior']['tmp_name'], $targetPath)) {
                $imageInterior = $targetPath;
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO cars (brand, model, type, price_per_day, price_per_hour, year, seats, features, image_exterior, image_interior, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssdiisss", $brand, $model, $type, $price, $pricePerHour, $year, $seats, $features, $imageExterior, $imageInterior);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Vehicle added successfully');
        } else {
            setFlashMessage('error', 'Failed to add vehicle');
        }
        redirect('index.php?page=admin&view=fleet');
    }
    
    // EDIT CAR - New functionality
    if (isset($_POST['action']) && $_POST['action'] == 'edit_car') {
        $carId = intval($_POST['car_id']);
        $brand = sanitize($_POST['brand']);
        $model = sanitize($_POST['model']);
        $type = sanitize($_POST['type']);
        $price = floatval($_POST['price']);
        $pricePerHour = floatval($_POST['price_per_hour']);
        $year = intval($_POST['year']);
        $seats = intval($_POST['seats']);
        $features = sanitize($_POST['features']);
        $available = isset($_POST['available']) ? 1 : 0;
        
        // Get current car images
        $stmt = $conn->prepare("SELECT image_exterior, image_interior FROM cars WHERE id = ?");
        $stmt->bind_param("i", $carId);
        $stmt->execute();
        $currentCar = $stmt->get_result()->fetch_assoc();
        
        $imageExterior = $currentCar['image_exterior'];
        $imageInterior = $currentCar['image_interior'];
        
        $uploadDir = 'uploads/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Handle new exterior image upload
        if (isset($_FILES['image_exterior']) && $_FILES['image_exterior']['error'] == 0) {
            // Delete old image if exists
            if ($imageExterior && file_exists($imageExterior)) {
                unlink($imageExterior);
            }
            $ext = pathinfo($_FILES['image_exterior']['name'], PATHINFO_EXTENSION);
            $filename = 'exterior_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image_exterior']['tmp_name'], $targetPath)) {
                $imageExterior = $targetPath;
            }
        }
        
        // Handle new interior image upload
        if (isset($_FILES['image_interior']) && $_FILES['image_interior']['error'] == 0) {
            // Delete old image if exists
            if ($imageInterior && file_exists($imageInterior)) {
                unlink($imageInterior);
            }
            $ext = pathinfo($_FILES['image_interior']['name'], PATHINFO_EXTENSION);
            $filename = 'interior_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image_interior']['tmp_name'], $targetPath)) {
                $imageInterior = $targetPath;
            }
        }
        
        // Handle image removal
        if (isset($_POST['remove_exterior']) && $_POST['remove_exterior'] == '1') {
            if ($imageExterior && file_exists($imageExterior)) {
                unlink($imageExterior);
            }
            $imageExterior = null;
        }
        
        if (isset($_POST['remove_interior']) && $_POST['remove_interior'] == '1') {
            if ($imageInterior && file_exists($imageInterior)) {
                unlink($imageInterior);
            }
            $imageInterior = null;
        }
        
        $stmt = $conn->prepare("UPDATE cars SET brand = ?, model = ?, type = ?, price_per_day = ?, price_per_hour = ?, year = ?, seats = ?, features = ?, image_exterior = ?, image_interior = ?, available = ? WHERE id = ?");
        $stmt->bind_param("ssssdiisssii", $brand, $model, $type, $price, $pricePerHour, $year, $seats, $features, $imageExterior, $imageInterior, $available, $carId);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Vehicle updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update vehicle');
        }
        redirect('index.php?page=admin&view=fleet');
    }
    
    // Update booking status
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $bookingId = sanitize($_POST['booking_id']);
        $newStatus = sanitize($_POST['status']);
        
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("ss", $newStatus, $bookingId);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Booking status updated');
        } else {
            setFlashMessage('error', 'Failed to update status');
        }
        redirect('index.php?page=admin&view=bookings');
    }
    
    // Delete car
    if (isset($_POST['action']) && $_POST['action'] == 'delete_car') {
        $carId = intval($_POST['car_id']);
        
        // Get car images to delete
        $stmt = $conn->prepare("SELECT image_exterior, image_interior FROM cars WHERE id = ?");
        $stmt->bind_param("i", $carId);
        $stmt->execute();
        $carImages = $stmt->get_result()->fetch_assoc();
        
        // Delete image files
        if ($carImages['image_exterior'] && file_exists($carImages['image_exterior'])) {
            unlink($carImages['image_exterior']);
        }
        if ($carImages['image_interior'] && file_exists($carImages['image_interior'])) {
            unlink($carImages['image_interior']);
        }
        
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
        $stmt->bind_param("i", $carId);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Vehicle deleted');
        } else {
            setFlashMessage('error', 'Failed to delete vehicle');
        }
        redirect('index.php?page=admin&view=fleet');
    }
    // Delete user
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
        $userId = intval($_POST['user_id']);

        // Prevent deleting admin users or self
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userToDelete = $stmt->get_result()->fetch_assoc();

        if ($userToDelete && $userToDelete['role'] == 'admin') {
            setFlashMessage('error', 'Cannot delete admin users');
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);

            if ($stmt->execute()) {
                setFlashMessage('success', 'User deleted successfully');
            } else {
                setFlashMessage('error', 'Failed to delete user');
            }
        }
        redirect('index.php?page=admin&view=users');
    }
}
?>

<!-- Admin Dashboard -->
<div class="page active" id="page-admin">
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <i class="fas fa-shield-alt"></i>
                    Admin Panel
                </div>
            </div>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="index.php?page=admin&view=dashboard" class="sidebar-link <?php echo $view == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="index.php?page=admin&view=fleet" class="sidebar-link <?php echo $view == 'fleet' ? 'active' : ''; ?>">
                        <i class="fas fa-car"></i> Fleet Management
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="index.php?page=admin&view=bookings" class="sidebar-link <?php echo $view == 'bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> All Bookings
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="index.php?page=admin&view=users" class="sidebar-link <?php echo $view == 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
            </ul>
        </aside>

        <div class="admin-main">
            
            <!-- Dashboard View -->
            <?php if ($view == 'dashboard'): ?>
            <div class="admin-view" id="admin-dashboard">
                <div class="admin-header">
                    <h1 class="admin-title">Dashboard Overview</h1>
                    <a href="index.php?page=admin&view=dashboard" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </a>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $totalCars; ?></h3>
                            <p>Total Vehicles</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $availableCars; ?></h3>
                            <p>Available Now</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $activeBookings; ?></h3>
                            <p>Active Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($revenue); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="admin-section">
                    <div class="section-header-bar">
                        <h3 class="section-title-sm">Recent Bookings</h3>
                        <div style="display:flex;gap:0.5rem">
                            <a href="index.php?page=export-bookings" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel"></i> Export
                            </a>
                            <a href="index.php?page=admin&view=bookings" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo $booking['brand'] . ' ' . $booking['model']; ?></td>
                                    <td><?php echo $booking['rental_type'] == 'hourly' ? 'Hourly' : 'Daily'; ?></td>
                                    <td><?php echo formatDate($booking['pickup_date']) . ($booking['return_date'] != $booking['pickup_date'] ? ' - ' . formatDate($booking['return_date']) : ''); ?></td>
                                    <td><?php echo getStatusBadge($booking['status']); ?></td>
                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fleet Management View -->
            <?php if ($view == 'fleet'): ?>
            <div class="admin-view" id="admin-fleet">
                <div class="admin-header">
                    <h1 class="admin-title">Fleet Management</h1>
                    <button class="btn btn-primary" onclick="openAddCarModal()">
                        <i class="fas fa-plus"></i> Add New Vehicle
                    </button>
                </div>

                <div class="admin-section">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Price/Day</th>
                                    <th>Price/Hour</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($car = $fleet->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:1rem">
                                            <div style="width:60px;height:50px;background:linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);border-radius:0.5rem;display:flex;align-items:center;justify-content:center;color:var(--primary);overflow:hidden">
                                                <?php if (!empty($car['image_exterior'])): ?>
                                                    <img src="<?php echo $car['image_exterior']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>" style="width:100%;height:100%;object-fit:cover;">
                                                <?php else: ?>
                                                    <i class="fas <?php echo getCarIcon($car['type']); ?>"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:700"><?php echo $car['brand'] . ' ' . $car['model']; ?></div>
                                                <div style="font-size:0.875rem;color:var(--secondary)"><?php echo $car['year']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($car['type']); ?></td>
                                    <td>RM<?php echo number_format($car['price_per_day'], 0); ?>/day</td>
                                    <td>RM<?php echo !empty($car['price_per_hour']) ? number_format($car['price_per_hour'], 0) : '-'; ?>/hr</td>
                                    <td><?php echo getStatusBadge($car['available'] ? 'active' : 'cancelled'); ?></td>
                                    <td>
                                        <div style="display:flex;gap:0.5rem">
                                            <!-- EDIT BUTTON - New -->
                                            <a href="index.php?page=admin&view=fleet&edit_car=<?php echo $car['id']; ?>" class="action-btn edit" title="Edit Vehicle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this vehicle?')">
                                                <input type="hidden" name="action" value="delete_car">
                                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                <button type="submit" class="action-btn delete" title="Delete Vehicle"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Bookings View -->
            <?php if ($view == 'bookings'): ?>
            <div class="admin-view" id="admin-bookings">
                <div class="admin-header">
                    <h1 class="admin-title">All Bookings</h1>
                    <div style="display:flex;gap:0.5rem">
                        <a href="index.php?page=export-bookings" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </a>
                        <form method="GET" action="">
                            <input type="hidden" name="page" value="admin">
                            <input type="hidden" name="view" value="bookings">
                            <select class="form-control" name="status" onchange="this.form.submit()" style="width:auto">
                                <option value="all">All Status</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="admin-section">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Pickup</th>
                                    <th>Return</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $allBookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo $booking['brand'] . ' ' . $booking['model']; ?></td>
                                    <td><?php echo $booking['rental_type'] == 'hourly' ? 'Hourly' : 'Daily'; ?></td>
                                    <td><?php echo formatDate($booking['pickup_date']); ?></td>
                                    <td><?php echo formatDate($booking['return_date']); ?></td>
                                    <td><?php echo getStatusBadge($booking['status']); ?></td>
                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display:inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <select class="form-control" name="status" onchange="this.form.submit()" style="width:auto;padding:0.25rem 0.5rem;font-size:0.875rem">
                                                <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="active" <?php echo $booking['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- User Management View -->
            <?php if ($view == 'users'): ?>
            <div class="admin-view" id="admin-users">
                <div class="admin-header">
                    <h1 class="admin-title">User Management</h1>
                </div>

                <div class="admin-section">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>License</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): 
                                    // Get booking count for user
                                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
                                    $stmt->bind_param("i", $user['id']);
                                    $stmt->execute();
                                    $bookingCount = $stmt->get_result()->fetch_assoc()['count'];
                                ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:1rem">
                                            <div style="width:40px;height:40px;background:var(--gradient-primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600">
                                                <?php echo getInitials($user['name']); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:700"><?php echo htmlspecialchars($user['name']); ?></div>
                                                <div style="font-size:0.875rem;color:var(--secondary)">Bookings: <?php echo $bookingCount; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['license_number']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td><?php echo getStatusBadge($user['status']); ?></td>
                                    <td>
                                        <div style="display:flex;gap:0.5rem">
                                            <form method="POST" action="" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this user? All their bookings will also be deleted.')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn delete" title="Delete User"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Car Modal -->
<div class="modal-overlay" id="carModal">
    <div class="modal" style="max-width:700px;max-height:90vh;overflow-y:auto">
        <div class="modal-header">
            <h3 class="modal-title">Add New Vehicle</h3>
            <button class="modal-close" onclick="closeCarModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_car">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <select class="form-control" name="brand" required>
                            <option value="Toyota">Toyota</option>
                            <option value="Honda">Honda</option>
                            <option value="BMW">BMW</option>
                            <option value="Mercedes">Mercedes</option>
                            <option value="Tesla">Tesla</option>
                            <option value="Audi">Audi</option>
                            <option value="Proton">Proton</option>
                            <option value="Perodua">Perodua</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="model" required placeholder="e.g. Camry">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-control" name="type" required>
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="luxury">Luxury</option>
                            <option value="electric">Electric</option>
                            <option value="hatchback">Hatchback</option>
                            <option value="mpv">MPV</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Day (RM)</label>
                        <input type="number" class="form-control" name="price" required min="1" placeholder="150">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Hour (RM)</label>
                        <input type="number" class="form-control" name="price_per_hour" min="0" placeholder="25">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="year" required min="2020" max="2026" value="2024">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seats</label>
                        <input type="number" class="form-control" name="seats" required min="2" max="8" value="5">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Features (comma separated)</label>
                        <input type="text" class="form-control" name="features" placeholder="Bluetooth, GPS, Leather Seats, Sunroof">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exterior Image</label>
                        <input type="file" class="form-control" name="image_exterior" accept="image/*" onchange="previewImage(this, 'previewExterior')">
                        <div id="previewExterior" style="margin-top:0.5rem;width:100%;height:120px;background:#f1f5f9;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;overflow:hidden">
                            <span style="color:var(--secondary);font-size:0.875rem"><i class="fas fa-image"></i> Preview</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interior Image</label>
                        <input type="file" class="form-control" name="image_interior" accept="image/*" onchange="previewImage(this, 'previewInterior')">
                        <div id="previewInterior" style="margin-top:0.5rem;width:100%;height:120px;background:#f1f5f9;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;overflow:hidden">
                            <span style="color:var(--secondary);font-size:0.875rem"><i class="fas fa-image"></i> Preview</span>
                        </div>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem">
                    <button type="button" class="btn btn-secondary" onclick="closeCarModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CAR MODAL - New -->
<?php if ($editCar): ?>
<div class="modal-overlay" id="editCarModal" style="display:flex">
    <div class="modal" style="max-width:700px;max-height:90vh;overflow-y:auto">
        <div class="modal-header">
            <h3 class="modal-title">Edit Vehicle: <?php echo $editCar['brand'] . ' ' . $editCar['model']; ?></h3>
            <a href="index.php?page=admin&view=fleet" class="modal-close">
                <i class="fas fa-times"></i>
            </a>
        </div>
        <div class="modal-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_car">
                <input type="hidden" name="car_id" value="<?php echo $editCar['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <select class="form-control" name="brand" required>
                            <option value="Toyota" <?php echo $editCar['brand'] == 'Toyota' ? 'selected' : ''; ?>>Toyota</option>
                            <option value="Honda" <?php echo $editCar['brand'] == 'Honda' ? 'selected' : ''; ?>>Honda</option>
                            <option value="BMW" <?php echo $editCar['brand'] == 'BMW' ? 'selected' : ''; ?>>BMW</option>
                            <option value="Mercedes" <?php echo $editCar['brand'] == 'Mercedes' ? 'selected' : ''; ?>>Mercedes</option>
                            <option value="Tesla" <?php echo $editCar['brand'] == 'Tesla' ? 'selected' : ''; ?>>Tesla</option>
                            <option value="Audi" <?php echo $editCar['brand'] == 'Audi' ? 'selected' : ''; ?>>Audi</option>
                            <option value="Proton" <?php echo $editCar['brand'] == 'Proton' ? 'selected' : ''; ?>>Proton</option>
                            <option value="Perodua" <?php echo $editCar['brand'] == 'Perodua' ? 'selected' : ''; ?>>Perodua</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="model" required value="<?php echo htmlspecialchars($editCar['model']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select class="form-control" name="type" required>
                            <option value="sedan" <?php echo $editCar['type'] == 'sedan' ? 'selected' : ''; ?>>Sedan</option>
                            <option value="suv" <?php echo $editCar['type'] == 'suv' ? 'selected' : ''; ?>>SUV</option>
                            <option value="luxury" <?php echo $editCar['type'] == 'luxury' ? 'selected' : ''; ?>>Luxury</option>
                            <option value="electric" <?php echo $editCar['type'] == 'electric' ? 'selected' : ''; ?>>Electric</option>
                            <option value="hatchback" <?php echo $editCar['type'] == 'hatchback' ? 'selected' : ''; ?>>Hatchback</option>
                            <option value="mpv" <?php echo $editCar['type'] == 'mpv' ? 'selected' : ''; ?>>MPV</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Day (RM)</label>
                        <input type="number" class="form-control" name="price" required min="1" value="<?php echo $editCar['price_per_day']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price per Hour (RM)</label>
                        <input type="number" class="form-control" name="price_per_hour" min="0" value="<?php echo $editCar['price_per_hour'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <input type="number" class="form-control" name="year" required min="2020" max="2026" value="<?php echo $editCar['year']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Seats</label>
                        <input type="number" class="form-control" name="seats" required min="2" max="8" value="<?php echo $editCar['seats']; ?>">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Features (comma separated)</label>
                        <input type="text" class="form-control" name="features" value="<?php echo htmlspecialchars($editCar['features'] ?? ''); ?>">
                    </div>
                    
                    <!-- Availability Toggle -->
                    <div class="form-group full">
                        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                            <input type="checkbox" name="available" value="1" <?php echo $editCar['available'] ? 'checked' : ''; ?>>
                            <span>Vehicle is available for booking</span>
                        </label>
                    </div>
                    
                    <!-- Exterior Image Section -->
                    <div class="form-group">
                        <label class="form-label">Exterior Image</label>
                        <?php if (!empty($editCar['image_exterior'])): ?>
                        <div style="margin-bottom:0.5rem">
                            <img src="<?php echo $editCar['image_exterior']; ?>" alt="Current Exterior" style="width:100%;height:120px;object-fit:cover;border-radius:0.5rem;">
                            <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.875rem;color:var(--danger);cursor:pointer">
                                <input type="checkbox" name="remove_exterior" value="1">
                                <span><i class="fas fa-trash"></i> Remove current image</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image_exterior" accept="image/*" onchange="previewImage(this, 'previewEditExterior')">
                        <div id="previewEditExterior" style="margin-top:0.5rem;width:100%;height:120px;background:#f1f5f9;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;overflow:hidden">
                            <span style="color:var(--secondary);font-size:0.875rem"><i class="fas fa-image"></i> New Image Preview</span>
                        </div>
                    </div>
                    
                    <!-- Interior Image Section -->
                    <div class="form-group">
                        <label class="form-label">Interior Image</label>
                        <?php if (!empty($editCar['image_interior'])): ?>
                        <div style="margin-bottom:0.5rem">
                            <img src="<?php echo $editCar['image_interior']; ?>" alt="Current Interior" style="width:100%;height:120px;object-fit:cover;border-radius:0.5rem;">
                            <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;font-size:0.875rem;color:var(--danger);cursor:pointer">
                                <input type="checkbox" name="remove_interior" value="1">
                                <span><i class="fas fa-trash"></i> Remove current image</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image_interior" accept="image/*" onchange="previewImage(this, 'previewEditInterior')">
                        <div id="previewEditInterior" style="margin-top:0.5rem;width:100%;height:120px;background:#f1f5f9;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;overflow:hidden">
                            <span style="color:var(--secondary);font-size:0.875rem"><i class="fas fa-image"></i> New Image Preview</span>
                        </div>
                    </div>
                </div>
                
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;margin-top:1.5rem">
                    <a href="index.php?page=admin&view=fleet" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>