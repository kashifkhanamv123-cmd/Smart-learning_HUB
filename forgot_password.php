<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/mail.php';

// Check DB
checkDbConnection();
/** @var \PDO $pdo */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token in DB
                $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $update->execute([$token, $expires, $user['id']]);

                // Construct reset URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $resetUrl = "$protocol://$host$dir/reset_password.php?token=$token";

                // Send recovery email via PHPMailer
                sendResetPasswordMail($email, $user['name'], $resetUrl);
            }

            // Render the same message for safety
            $message = 'If the email is registered on our system, a password reset link has been sent. Please check your inbox.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Smart Learning Hub</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
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
                <i class="fa-solid fa-key"></i>
            </div>
            <h2>Reset Password</h2>
            <p>Enter your email to receive a password reset link</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="flash-alert" style="padding: 12px; border: 1px solid <?php echo $message_type === 'success' ? '#10b981' : '#f43f5e'; ?>; background: <?php echo $message_type === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(244,63,94,0.1)'; ?>; color: <?php echo $message_type === 'success' ? '#34d399' : '#fda4af'; ?>; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                <i class="fa-solid fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            Remember your password? <a href="login.php" style="font-weight: 600;">Login</a>
        </div>

        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid var(--border-color); text-align: left;">
            <p style="font-size: 0.75rem; color: var(--text-dark); line-height: 1.5;">
                <strong>Local Testing Tip:</strong><br>
                If SMTP is simulated (not set in <code>.env</code>), check the simulation file at:<br>
                <code>logs/mail_simulation.log</code> for the generated URL.
            </p>
        </div>
    </div>
</body>

</html>