<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/includes/rate_limit.php';
require_once __DIR__ . '/db.php';

/**
 * Asserts user is logged in. Redirects to login.php if unauthorized.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        // Resolve path to login.php dynamically depending on current page folder depth
        $currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $loginUrl = ($currentDir === 'admin') ? '../login.php' : 'login.php';
        header("Location: $loginUrl");
        exit();
    }
}

/**
 * Asserts user is an admin. Redirects to student dashboard if unauthorized.
 */
function require_admin() {
    // Assert general login first
    require_login();
    
    if ($_SESSION['user_role'] !== 'admin') {
        // Resolve path to dashboard.php dynamically depending on current page folder depth
        $currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $dashboardUrl = ($currentDir === 'admin') ? '../dashboard.php' : 'dashboard.php';
        
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Access Denied: Administrative privileges are required.'
        ];
        
        header("Location: $dashboardUrl");
        exit();
    }
}
?>
