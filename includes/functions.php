<?php
// Helper Functions

// Format date for display
function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    return $date->format('j M Y');
}

// Calculate days between two dates
function calculateDays($start, $end) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $interval = $startDate->diff($endDate);
    return max(1, $interval->days);
}

// Format currency
function formatCurrency($amount) {
    return 'RM' . number_format($amount, 2);
}

// Get user initials
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

// Sanitize input
function sanitize($data) {
    $conn = getDBConnection();
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = $conn->real_escape_string($data);
    return $data;
}

// Generate booking ID
function generateBookingId($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
    $row = $result->fetch_assoc();
    $count = $row['count'] + 1;
    return 'BK' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Display status badge
function getStatusBadge($status) {
    $classes = [
        'pending' => 'status-pending',
        'active' => 'status-active',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    $class = $classes[$status] ?? 'status-pending';
    return '<span class="status-badge ' . $class . '">' . ucfirst($status) . '</span>';
}

// Get location name
function getLocationName($location) {
    $locations = [
        'kuala-lumpur' => 'Kuala Lumpur City Centre',
        'kl-sentral' => 'KL Sentral',
        'klia' => 'KLIA Airport',
        'klia2' => 'KLIA2 Airport',
        'petaling-jaya' => 'Petaling Jaya',
        'shah-alam' => 'Shah Alam',
        'subang-jaya' => 'Subang Jaya',
        'johor-bahru' => 'Johor Bahru',
        'penang' => 'Penang',
        'ipoh' => 'Ipoh',
        'kota-kinabalu' => 'Kota Kinabalu',
        'kuching' => 'Kuching',
        'malacca' => 'Malacca'
    ];
    return $locations[$location] ?? $location;
}

// Get car image icon based on type
function getCarIcon($type) {
    $icons = [
        'sedan' => 'fa-car',
        'suv' => 'fa-truck',
        'luxury' => 'fa-car-side',
        'electric' => 'fa-bolt',
        'hatchback' => 'fa-car',
        'mpv' => 'fa-shuttle-van'
    ];
    return $icons[$type] ?? 'fa-car';
}
?>
