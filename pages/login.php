<?php
// Login Page
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session only if not already started (safe for direct access or router inclusion)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('index.php');
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - RCar Rental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error show"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="form-footer">
                Don't have an account? <a href="index.php?page=register">Sign up</a>
            </div>
            
            <div style="margin-top:1rem;text-align:center">
                <small style="color:var(--secondary)">Demo: user@example.com / password</small><br>
                <small style="color:var(--secondary)">Admin: admin@rcar.com / admin</small>
            </div>
        </div>
    </div>
</body>
</html>
