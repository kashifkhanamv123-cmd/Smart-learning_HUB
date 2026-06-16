<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/auth.php';

// Check database connection and render setup screen if connection is down
checkDbConnection();

// Auto seed the database if it's new
seedDatabase($pdo);

// Assert login to access protected pages
require_login();

$pageTitle = isset($pageTitle) ? $pageTitle : 'Smart Learning Hub';
$currentUser = $_SESSION['user_name'];
$currentUserRole = $_SESSION['user_role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Smart Learning Hub</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Premium Style Sheet -->
    <link rel="stylesheet" href="<?php echo getAssetPath('css/style.css'); ?>">
    <?php if (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin'): ?>
        <link rel="stylesheet" href="<?php echo getAssetPath('css/admin.css'); ?>">
    <?php endif; ?>
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar navigation -->
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        
        <!-- Main Content Wrapper -->
        <div class="main-container">
            <!-- Header Bar -->
            <header class="top-header">
                <div class="mobile-menu-toggle" id="mobileSidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div class="header-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search courses, cards, notes..." id="globalSearchInput">
                </div>
                <div class="user-profile">
                    <span class="user-role-badge <?php echo $currentUserRole === 'admin' ? 'role-admin' : 'role-student'; ?>">
                        <?php echo ucfirst($currentUserRole); ?>
                    </span>
                    <div class="avatar">
                        <?php echo strtoupper(substr($currentUser, 0, 1)); ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars($currentUser); ?></span>
                    <div class="profile-dropdown">
                        <a href="<?php echo getAssetPath('logout.php'); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </header>
            
            <main class="page-content">
                <!-- Flash messages -->
                <?php displayFlash(); ?>
