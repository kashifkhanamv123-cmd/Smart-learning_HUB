<?php
require_once __DIR__ . '/libs/phpmailer/Exception.php';
require_once __DIR__ . '/libs/phpmailer/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer and Brevo SMTP settings from environment.
 * Fallbacks gracefully to logging if SMTP settings are not configured.
 */
function sendMail($to, $subject, $bodyHTML, $altText = '') {
    $smtp_host = getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com';
    $smtp_port = getenv('SMTP_PORT') ?: 587;
    $smtp_user = getenv('SMTP_USER');
    $smtp_pass = getenv('SMTP_PASS');
    $smtp_from = getenv('SMTP_FROM_EMAIL') ?: 'no-reply@smartlearninghub.com';
    $smtp_name = getenv('SMTP_FROM_NAME') ?: 'Smart Learning Hub';
    
    // Check if configuration parameters are still placeholder values
    if (empty($smtp_user) || empty($smtp_pass) || strpos($smtp_user, 'your_brevo') !== false) {
        // Log locally to check details during testing
        $logMessage = "[" . date('Y-m-d H:i:s') . "] SMTP Simulation to <$to> | Subject: $subject\n";
        $logMessage .= "Alt Text: $altText\n";
        $logMessage .= "SMTP credentials missing or set to defaults in .env. Skipping real dispatch.\n";
        $logMessage .= "---------------------------------------------------------\n";
        
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logDir . '/mail_simulation.log', $logMessage, FILE_APPEND);
        return true;
    }
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;
        
        $mail->setFrom($smtp_from, $smtp_name);
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHTML;
        $mail->AltBody = $altText ?: strip_tags($bodyHTML);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer SMTP Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sends a welcome email to a new user
 */
function sendWelcomeMail($toEmail, $userName) {
    $subject = "Welcome to Smart Learning Hub!";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: sans-serif; background-color: #0b0f19; color: #f8fafc; padding: 20px; margin: 0; }
            .card { background-color: #111827; border: 1px solid #1e293b; padding: 40px; border-radius: 16px; max-width: 550px; margin: 20px auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
            h1 { color: #6366f1; font-size: 24px; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 15px; }
            p { color: #94a3b8; line-height: 1.6; font-size: 15px; }
            .btn { display: inline-block; background-color: #6366f1; color: #ffffff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 25px; transition: background-color 0.2s; }
            .btn:hover { background-color: #4f46e5; }
            .footer { margin-top: 30px; font-size: 12px; color: #64748b; border-top: 1px solid #1e293b; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>Welcome to the Hub, " . htmlspecialchars($userName) . "!</h1>
            <p>We are thrilled to welcome you to the <strong>Smart Learning Hub</strong>, your interactive ecosystem designed to accelerate your study habits and build lasting knowledge.</p>
            <p>Here is what you can access right now:</p>
            <ul>
                <li style='color: #94a3b8; margin-bottom: 8px;'>📚 <strong>Interactive Courses</strong>: Walk through modules step-by-step.</li>
                <li style='color: #94a3b8; margin-bottom: 8px;'>🃏 <strong>3D Flashcards</strong>: Master vocabulary and concepts using spaced repetition.</li>
                <li style='color: #94a3b8; margin-bottom: 8px;'>📝 <strong>Live Notes</strong>: Draft study summaries in clean, live-updating markdown.</li>
                <li style='color: #94a3b8; margin-bottom: 8px;'>⏱️ <strong>Pomodoro Timer</strong>: Organize focus cycles with relaxing ambient soundscapes.</li>
            </ul>
            <a href='http://" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "/index.php' class='btn'>Explore Your Dashboard</a>
            <div class='footer'>
                This is an automated welcome email from Smart Learning Hub.
            </div>
        </div>
    </body>
    </html>";
    return sendWelcomeMailMock($toEmail, $userName, $body);
}

// Internal wrapper to ensure compatibility
function sendWelcomeMailMock($toEmail, $userName, $body) {
    return sendMail($toEmail, "Welcome to Smart Learning Hub!", $body, "Welcome to Smart Learning Hub, $userName! Log in to view your course dashboard.");
}

/**
 * Sends a secure login notification alert
 */
function sendLoginAlertMail($toEmail, $userName) {
    $subject = "Security Alert: New Login Detected";
    $time = date('Y-m-d H:i:s T');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'CLI / Unknown';
    
    $body = "
    <html>
    <head>
        <style>
            body { font-family: sans-serif; background-color: #0b0f19; color: #f8fafc; padding: 20px; margin: 0; }
            .card { background-color: #111827; border: 1px solid #1e293b; padding: 40px; border-radius: 16px; max-width: 550px; margin: 20px auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
            h1 { color: #f43f5e; font-size: 22px; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 15px; }
            p { color: #94a3b8; line-height: 1.6; font-size: 15px; }
            .details { background-color: #0b0f19; border: 1px solid #1e293b; padding: 20px; border-radius: 8px; margin: 20px 0; font-family: monospace; font-size: 14px; color: #cbd5e1; }
            .footer { margin-top: 30px; font-size: 12px; color: #64748b; border-top: 1px solid #1e293b; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>New Login Registered</h1>
            <p>Hello " . htmlspecialchars($userName) . ",</p>
            <p>This is a quick security alert that your Smart Learning Hub account was accessed successfully.</p>
            <div class='details'>
                <strong>Time:</strong> $time<br>
                <strong>IP Address:</strong> $ip<br>
                <strong>Device/Agent:</strong> $agent
            </div>
            <p>If this was you, no action is needed. If you did not log in, please reset your password immediately to protect your account.</p>
            <div class='footer'>
                This is a secure system notification from Smart Learning Hub.
            </div>
        </div>
    </body>
    </html>";
    return sendMail($toEmail, $subject, $body, "Security Alert: Successful login for $userName at $time from IP $ip.");
}

/**
 * Sends a password reset link
 */
function sendResetPasswordMail($toEmail, $userName, $resetUrl) {
    $subject = "Reset Your Password - Smart Learning Hub";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: sans-serif; background-color: #0b0f19; color: #f8fafc; padding: 20px; margin: 0; }
            .card { background-color: #111827; border: 1px solid #1e293b; padding: 40px; border-radius: 16px; max-width: 550px; margin: 20px auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
            h1 { color: #eab308; font-size: 22px; margin-bottom: 20px; border-bottom: 1px solid #1e293b; padding-bottom: 15px; }
            p { color: #94a3b8; line-height: 1.6; font-size: 15px; }
            .btn { display: inline-block; background-color: #eab308; color: #000000 !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 15px; margin-bottom: 15px; }
            .footer { margin-top: 30px; font-size: 12px; color: #64748b; border-top: 1px solid #1e293b; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>Password Reset Link</h1>
            <p>Hello " . htmlspecialchars($userName) . ",</p>
            <p>We received a request to reset the password for your Smart Learning Hub account. Click the button below to configure a new password. This link is valid for 1 hour.</p>
            <center>
                <a href='" . htmlspecialchars($resetUrl) . "' class='btn'>Reset Password</a>
            </center>
            <p>If you did not request a password reset, you can safely ignore this email. Your current password will remain unchanged.</p>
            <div class='footer'>
                This is a secure system notification from Smart Learning Hub.
            </div>
        </div>
    </body>
    </html>";
    return sendMail($toEmail, $subject, $body, "Reset your password by visiting: $resetUrl");
}
?>
