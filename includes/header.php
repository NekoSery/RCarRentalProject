<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage == 'index') $currentPage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCar Rental - Find Your Perfect Ride</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-car"></i>
                RCar Rental
            </a>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php" class="<?php echo $currentPage == 'home' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="index.php?page=catalog" class="<?php echo $currentPage == 'catalog' ? 'active' : ''; ?>">Cars</a></li>
                <?php if (isLoggedIn()): ?>
                <li><a href="index.php?page=bookings" class="<?php echo $currentPage == 'bookings' ? 'active' : ''; ?>">My Bookings</a></li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li><a href="index.php?page=admin" class="<?php echo $currentPage == 'admin' ? 'active' : ''; ?>">Admin</a></li>
                <?php endif; ?>
            </ul>

            <div class="nav-actions">
                <?php if (!isLoggedIn()): ?>
                <div id="authButtons">
                    <a href="index.php?page=login" class="btn btn-secondary">Sign In</a>
                    <a href="index.php?page=register" class="btn btn-primary">Sign Up</a>
                </div>
                <?php else: ?>
                <a href="index.php?page=account" class="user-avatar-link" title="My Account">
                    <div class="user-avatar">
                        <span><?php echo getInitials($_SESSION['user_name'] ?? 'User'); ?></span>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        
    <?php
    // Display flash messages
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="alert alert-<?php echo $flash['type']; ?> show" style="position: fixed; top: 90px; left: 50%; transform: translateX(-50%); z-index: 2000; min-width: 300px; text-align: center;">
        <?php echo $flash['message']; ?>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) alert.style.display = 'none';
        }, 3000);
    </script>
    <?php endif; ?>
