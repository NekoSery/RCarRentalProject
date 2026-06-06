<?php
// Export Bookings to Excel (CSV format)
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin
if (!isAdmin()) {
    setFlashMessage('error', 'Admin access required');
    redirect('index.php');
}

$conn = getDBConnection();

// Fetch all bookings with full details
$query = "SELECT 
    b.id AS booking_id,
    b.created_at AS booking_date,
    u.name AS customer_name,
    u.email AS customer_email,
    u.phone AS customer_phone,
    u.license_number AS license,
    c.brand AS car_brand,
    c.model AS car_model,
    c.type AS car_type,
    c.year AS car_year,
    b.rental_type,
    b.hours,
    b.pickup_date,
    b.return_date,
    b.location,
    b.status,
    b.total_amount,
    b.insurance,
    b.gps,
    b.child_seat,
    b.touch_n_go
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN cars c ON b.car_id = c.id
ORDER BY b.created_at DESC";

$result = $conn->query($query);

// Set headers for CSV download
$filename = 'RCar_Bookings_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write header row
fputcsv($output, [
    'Booking ID',
    'Booking Date',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'License Number',
    'Car Brand',
    'Car Model',
    'Car Type',
    'Car Year',
    'Rental Type',
    'Hours',
    'Pickup Date',
    'Return Date',
    'Location',
    'Status',
    'Total Amount (RM)',
    'Insurance',
    'GPS',
    'Child Seat',
    "Touch 'n Go"
]);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['booking_id'],
        $row['booking_date'],
        $row['customer_name'],
        $row['customer_email'],
        $row['customer_phone'],
        $row['license'],
        $row['car_brand'],
        $row['car_model'],
        $row['car_type'],
        $row['car_year'],
        ucfirst($row['rental_type']),
        $row['hours'] ?? '-',
        $row['pickup_date'],
        $row['return_date'],
        getLocationName($row['location']),
        ucfirst($row['status']),
        number_format($row['total_amount'], 2),
        $row['insurance'] ? 'Yes' : 'No',
        $row['gps'] ? 'Yes' : 'No',
        $row['child_seat'] ? 'Yes' : 'No',
        $row['touch_n_go'] ? 'Yes' : 'No'
    ]);
}

fclose($output);
exit();
?>