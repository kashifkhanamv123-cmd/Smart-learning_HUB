<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check DB
checkDbConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$user = null;

if (empty($token)) {
    $error = 'Invalid or missing password reset token.';
} else {
    try {
        // Find user by token, check expiration (ensures expiration datetime is in the future)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'The password reset token is invalid or has expired.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password, clear token
            $update = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            if ($update->execute([$newHash, $user['id']])) {
                setFlash('success', 'Your password has been reset successfully! You can now log in.');
                header("Location: login.php");
                exit();
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Smart Learning Hub</title>
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
                <i class="fa-solid fa-lock-open"></i>
            </div>
            <h2>Configure Password</h2>
            <p>Define a new secure password for your account</p>
        </div>
        
        <?php if (!empty($error) && !$user): ?>
            <div class="flash-alert" style="padding: 12px; border: 1px solid #f43f5e; background: rgba(244,63,94,0.1); color: #fda4af; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
                <i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="login.php" class="btn btn-secondary" style="width: 100%;"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        <?php else: ?>
        
            <?php if (!empty($error)): ?>
                <div class="flash-alert" style="padding: 12px; border: 1px solid #f43f5e; background: rgba(244,63,94,0.1); color: #fda4af; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat new password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fa-solid fa-circle-check"></i> Save Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
