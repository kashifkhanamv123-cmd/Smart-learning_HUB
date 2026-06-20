<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/rate_limit.php';
require_once __DIR__ . '/mail.php';

// Check DB
checkDbConnection();
/** @var \PDO $pdo */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = trim($_POST['gender'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } elseif (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($gender) || empty($country)) {
        $error = 'Please fill in all fields.';
    } elseif (!isUsernameAllowed($name)) {
        $error = 'The username cannot contain reserved words like admin or administrator.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? '')) {
        $error = 'Please complete the reCAPTCHA verification.';
    } else {
        try {
            // Check if email already registered
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'This email is already registered.';
            } else {
                // Hash Password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert User (Default role is student)
                $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, gender, country) VALUES (?, ?, ?, 'student', ?, ?)");
                if ($insert->execute([$name, $email, $passwordHash, $gender, $country])) {
                    // Trigger welcome email via PHPMailer
                    sendWelcomeMail($email, $name);

                    setFlash('success', 'Account created successfully! You can now log in.');
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Account creation failed. Please try again.';
                }
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
    <title>Sign Up - Smart Learning Hub</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body class="auth-layout">
    <div class="glass-card auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join Smart Learning Hub and start study tracking</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert" style="padding: 12px; border: 1px solid #f43f5e; background: rgba(244,63,94,0.1); color: #fda4af; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.9rem;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Jane Doe" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@domain.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-control" style="background:#0f172a; cursor: pointer;" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        <option value="Prefer not to say" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="country">Country</label>
                    <select name="country" id="country" class="form-control" style="background:#0f172a; cursor: pointer;" required>
                        <option value="">Select Country</option>
                        <option value="USA" <?php echo (isset($_POST['country']) && $_POST['country'] === 'USA') ? 'selected' : ''; ?>>United States</option>
                        <option value="UK" <?php echo (isset($_POST['country']) && $_POST['country'] === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                        <option value="Canada" <?php echo (isset($_POST['country']) && $_POST['country'] === 'Canada') ? 'selected' : ''; ?>>Canada</option>
                        <option value="Australia" <?php echo (isset($_POST['country']) && $_POST['country'] === 'Australia') ? 'selected' : ''; ?>>Australia</option>
                        <option value="India" <?php echo (isset($_POST['country']) && $_POST['country'] === 'India') ? 'selected' : ''; ?>>India</option>
                        <option value="Other" <?php echo (isset($_POST['country']) && $_POST['country'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                    <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-dark);" onclick="togglePasswordVisibility('password', this)"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
                    <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-dark);" onclick="togglePasswordVisibility('confirm_password', this)"></i>
                </div>
            </div>
            
            <div class="g-recaptcha" data-sitekey="<?php echo getenv('RECAPTCHA_SITE_KEY'); ?>" style="margin-bottom: 15px;"></div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Register
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php" style="font-weight: 600;">Login</a>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, iconElement) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>