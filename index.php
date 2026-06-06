<?php
// Main Router - RCar Rental
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Get page parameter
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'home';

// Route to appropriate page
switch ($page) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'catalog':
        include 'pages/catalog.php';
        break;
    case 'bookings':
        include 'pages/bookings.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    case 'admin':
        include 'pages/admin.php';
        break;
    case 'account':
        include 'pages/account.php';
        break;
    case 'logout':
        include 'pages/logout.php';
        break;
    case 'book':
        include 'pages/book.php';
        break;
    case 'cancel-booking':
        include 'pages/cancel-booking.php';
        break;
    case 'export-bookings':
        include 'pages/export-bookings.php';
        break;
    default:
        include 'pages/home.php';
        break;
}
?>
