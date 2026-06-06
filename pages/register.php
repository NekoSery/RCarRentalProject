<?php
// Register Page
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $license = sanitize($_POST['license']);
    $password = $_POST['password'];
    
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $conn = getDBConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, license_number, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $license);
            
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                
                // Auto login
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                
                setFlashMessage('success', 'Account created successfully!');
                redirect('index.php');
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - RCar Rental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Join us and start renting today</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error show"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name (as per IC)</label>
                    <input type="text" class="form-control" name="name" required placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Malaysian Driving License Number</label>
                    <input type="text" class="form-control" name="license" required placeholder="e.g. 12345678" value="<?php echo isset($_POST['license']) ? htmlspecialchars($_POST['license']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" required placeholder="e.g. 0123456789" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Create a password" minlength="6">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="form-footer">
                Already have an account? <a href="index.php?page=login">Sign in</a>
            </div>
        </div>
    </div>
</body>
</html>
