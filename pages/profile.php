<?php
// Profile Page
require_once 'includes/header.php';

// Check if logged in
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please sign in to view your profile');
    redirect('index.php?page=login');
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $license = sanitize($_POST['license']);
    $phone = sanitize($_POST['phone']);
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, license_number = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $license, $phone, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully';
    } else {
        $error = 'Failed to update profile';
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fallback if user not found (shouldn't happen, but prevents errors)
if (!$user) {
    setFlashMessage('error', 'User data not found. Please log in again.');
    redirect('index.php?page=logout');
}
?>

<!-- Profile Page -->
<div class="page active" id="page-profile">
    <section class="section" style="max-width:800px">
        <a href="index.php?page=account" class="back-link"><i class="fas fa-arrow-left"></i> Back to Account</a>
        <div class="section-header">
            <h2 class="section-title">My Profile</h2>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success show"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error show"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div style="background:white;padding:2rem;border-radius:1rem;box-shadow:var(--shadow)">
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly style="background:#f1f5f9">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driving License</label>
                        <input type="text" class="form-control" name="license" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+60 12-345 6789">
                    </div>
                </div>
                <div style="margin-top:1.5rem;display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
