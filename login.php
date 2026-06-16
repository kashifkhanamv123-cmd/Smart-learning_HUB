<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/mail.php';

// Check DB Connection
checkDbConnection();

// Run seeder to ensure default accounts work
seedDatabase($pdo);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect already logged-in users
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Trigger PHPMailer Alert
                sendLoginAlertMail($user['email'], $user['name']);
                
                // Redirect
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Learning Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-layout">
    <div class="glass-card auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Access your dashboard and start learning</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="flash-alert" style="padding: 12px; border: 1px solid #f43f5e; background: rgba(244,63,94,0.1); color: #fda4af; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php displayFlash(); ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label for="password" style="margin-bottom: 0;">Password</label>
                    <a href="forgot_password.php" style="font-size: 0.8rem; font-weight: 500;">Forgot Password?</a>
                </div>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>
        
        <div class="auth-footer">
            Don't have an account? <a href="signup.php" style="font-weight: 600;">Sign Up</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid var(--border-color); text-align: left;">
            <p style="font-size: 0.75rem; color: var(--text-dark); line-height: 1.5;">
                <strong>Demo Accounts:</strong><br>
                • Student: <code>student@hub.com</code> / <code>student123</code><br>
                • Admin: <code>admin@hub.com</code> / <code>admin123</code>
            </p>
        </div>
    </div>
</body>
</html>
