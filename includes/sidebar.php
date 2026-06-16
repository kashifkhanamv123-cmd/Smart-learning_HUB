<?php
$currentFile = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));

// Check if tab matches navigation list
function isActive($file, $dir = '') {
    global $currentFile, $currentDir;
    if ($dir !== '') {
        return ($currentFile === $file && $currentDir === $dir) ? 'active' : '';
    }
    return ($currentFile === $file && $currentDir !== 'admin') ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebarContainer">
    <div class="sidebar-brand">
        <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <h2>SmartLearn</h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo getAssetPath('dashboard.php'); ?>" class="nav-item <?php echo isActive('dashboard.php'); ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo getAssetPath('courses.php'); ?>" class="nav-item <?php echo isActive('courses.php'); ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Courses</span>
        </a>
        <a href="<?php echo getAssetPath('flashcards.php'); ?>" class="nav-item <?php echo isActive('flashcards.php'); ?>">
            <i class="fa-solid fa-clone"></i>
            <span>Flashcards</span>
        </a>
        <a href="<?php echo getAssetPath('quiz.php'); ?>" class="nav-item <?php echo isActive('quiz.php'); ?>">
            <i class="fa-solid fa-square-poll-horizontal"></i>
            <span>Quizzes</span>
        </a>
        <a href="<?php echo getAssetPath('chat.php'); ?>" class="nav-item <?php echo isActive('chat.php'); ?>">
            <i class="fa-solid fa-robot"></i>
            <span>AI Assistant</span>
        </a>
        <a href="<?php echo getAssetPath('timer.php'); ?>" class="nav-item <?php echo isActive('timer.php'); ?>">
            <i class="fa-solid fa-stopwatch"></i>
            <span>Study Timer</span>
        </a>
        <a href="<?php echo getAssetPath('notes.php'); ?>" class="nav-item <?php echo isActive('notes.php'); ?>">
            <i class="fa-solid fa-note-sticky"></i>
            <span>Notes</span>
        </a>
        
        <?php if ($currentUserRole === 'admin'): ?>
            <div class="nav-divider">Admin Console</div>
            <a href="<?php echo getAssetPath('admin/index.php'); ?>" class="nav-item <?php echo isActive('index.php', 'admin'); ?>">
                <i class="fa-solid fa-user-gear"></i>
                <span>Admin Stats</span>
            </a>
            <a href="<?php echo getAssetPath('admin/users.php'); ?>" class="nav-item <?php echo isActive('users.php', 'admin'); ?>">
                <i class="fa-solid fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="<?php echo getAssetPath('admin/courses.php'); ?>" class="nav-item <?php echo isActive('courses.php', 'admin'); ?>">
                <i class="fa-solid fa-folder-plus"></i>
                <span>Manage Courses</span>
            </a>
            <a href="<?php echo getAssetPath('admin/quizzes.php'); ?>" class="nav-item <?php echo isActive('quizzes.php', 'admin'); ?>">
                <i class="fa-solid fa-circle-question"></i>
                <span>Manage Quizzes</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo getAssetPath('settings.php'); ?>" class="nav-item <?php echo isActive('settings.php'); ?>" style="margin-bottom: 5px;">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
        <a href="<?php echo getAssetPath('logout.php'); ?>" class="nav-item logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
