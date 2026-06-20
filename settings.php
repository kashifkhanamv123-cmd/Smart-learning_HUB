<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/auth.php'; // also loads db.php
require_once __DIR__ . '/includes/functions.php';

// Guard: DB check
checkDbConnection();
/** @var \PDO $pdo */

// Must be logged in
require_login();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT name, email, gender, country FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        $error = 'Invalid CSRF token. Please try again.';
    } elseif ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $country = trim($_POST['country'] ?? '');
        
        $is_restricted = !isUsernameAllowed($name);
        
        // Admin restriction check bypass if user is actually an admin
        if ($_SESSION['user_role'] === 'admin') {
            $is_restricted = false;
        }
        
        if (empty($name) || empty($email)) {
            $error = 'Name and Email are required.';
        } elseif ($is_restricted) {
            $error = 'The username cannot contain reserved words like admin or administrator.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email already registered to someone else
            $stmtEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmtEmail->execute([$email, $userId]);
            if ($stmtEmail->fetchColumn() > 0) {
                $error = 'This email is already in use by another account.';
            } else {
                $update = $pdo->prepare("UPDATE users SET name = ?, email = ?, gender = ?, country = ? WHERE id = ?");
                if ($update->execute([$name, $email, $gender, $country, $userId])) {
                    $success = 'Profile updated successfully.';
                    $_SESSION['user_name'] = $name; // Update session
                    // Refresh data
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['gender'] = $gender;
                    $user['country'] = $country;
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            // Verify current password
            $stmtPass = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmtPass->execute([$userId]);
            $hash = $stmtPass->fetchColumn();
            
            if (password_verify($current_password, $hash)) {
                $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($updatePass->execute([$newHash, $userId])) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to update password.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Settings</h1>
    <p>Manage your account settings and preferences.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="grid-layout" style="grid-template-columns: 1fr 1fr;">
    
    <!-- Profile Form -->
    <div class="card" style="margin-bottom: 20px;">
        <h2 style="margin-bottom: 15px; font-size: 1.25rem;">Profile Information</h2>
        <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-control" style="background:#0f172a; cursor: pointer;">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo ($user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        <option value="Prefer not to say" <?php echo ($user['gender'] === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="country">Country</label>
                    <select name="country" id="country" class="form-control" style="background:#0f172a; cursor: pointer;">
                        <option value="">Select Country</option>
                        <option value="USA" <?php echo ($user['country'] === 'USA') ? 'selected' : ''; ?>>United States</option>
                        <option value="UK" <?php echo ($user['country'] === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                        <option value="Canada" <?php echo ($user['country'] === 'Canada') ? 'selected' : ''; ?>>Canada</option>
                        <option value="Australia" <?php echo ($user['country'] === 'Australia') ? 'selected' : ''; ?>>Australia</option>
                        <option value="India" <?php echo ($user['country'] === 'India') ? 'selected' : ''; ?>>India</option>
                        <option value="Other" <?php echo ($user['country'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <i class="fa-solid fa-save"></i> Save Profile
            </button>
        </form>
    </div>
    
    <!-- Password Form -->
    <div class="card" style="margin-bottom: 20px;">
        <h2 style="margin-bottom: 15px; font-size: 1.25rem;">Change Password</h2>
        <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update_password">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div style="position: relative;">
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                    <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-dark);" onclick="togglePasswordVisibility('current_password', this)"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div style="position: relative;">
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="At least 6 characters" required>
                    <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-dark);" onclick="togglePasswordVisibility('new_password', this)"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    <i class="fa-solid fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-dark);" onclick="togglePasswordVisibility('confirm_password', this)"></i>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <i class="fa-solid fa-lock"></i> Update Password
            </button>
        </form>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
