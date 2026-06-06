<?php
// Cancel Booking Page
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please sign in');
    redirect('index.php?page=login');
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Get booking ID
$bookingId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (!$bookingId) {
    setFlashMessage('error', 'Invalid booking');
    redirect('index.php?page=bookings');
}

// Verify booking belongs to user and is pending
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmt->bind_param("si", $bookingId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Booking not found or cannot be cancelled');
    redirect('index.php?page=bookings');
}

// Get car_id before cancelling
$booking = $result->fetch_assoc();
$carId = $booking['car_id'];

// Cancel booking
$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("s", $bookingId);

if ($stmt->execute()) {
    // Restore car availability if no other pending/active bookings exist for this car
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE car_id = ? AND status IN ('pending', 'active')");
    $checkStmt->bind_param("i", $carId);
    $checkStmt->execute();
    $remaining = $checkStmt->get_result()->fetch_assoc()['count'];

    if ($remaining === 0) {
        $restoreStmt = $conn->prepare("UPDATE cars SET available = 1 WHERE id = ?");
        $restoreStmt->bind_param("i", $carId);
        $restoreStmt->execute();
    }

    setFlashMessage('success', 'Booking cancelled successfully');
} else {
    setFlashMessage('error', 'Failed to cancel booking');
}

redirect('index.php?page=bookings');
?>