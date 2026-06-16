<?php
$pageTitle = 'Admin Stats';
require_once dirname(__DIR__) . '/auth.php'; // also loads db.php
require_once dirname(__DIR__) . '/includes/functions.php';

// Guard: show setup page if DB is unavailable
checkDbConnection();
/** @var \PDO $pdo */

// Enforce administrative privileges
require_admin();

// Fetch admin overview stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalLessons = $pdo->query("SELECT COUNT(*) FROM lessons")->fetchColumn();
$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$totalAttempts = $pdo->query("SELECT COUNT(*) FROM quiz_attempts")->fetchColumn();

$totalSecs = $pdo->query("SELECT SUM(duration_seconds) FROM study_sessions")->fetchColumn() ?: 0;
$totalHours = round($totalSecs / 3600, 1);

// Fetch recent users list
$recentUsers = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <h1>Admin Console</h1>
    <p>Manage courses, quizzes, student profiles, and platform statistics.</p>
</div>

<!-- Admin stats cards grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="glass-card stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-book"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Courses</p>
        </div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-icon rose"><i class="fa-solid fa-circle-question"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalQuizzes; ?></h3>
            <p>Quizzes</p>
        </div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-stopwatch"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalHours; ?>h</h3>
            <p>Study Logged</p>
        </div>
    </div>
</div>

<div class="dashboard-split" style="grid-template-columns: 2fr 1.2fr;">
    <!-- Manage links dashboard -->
    <div class="glass-card">
        <div class="glass-card-header">
            <h3 class="glass-card-title"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--primary);"></i> Management Actions</h3>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <a href="users.php" class="glass-card" style="margin-bottom:0; text-align:center; padding:30px; display:flex; flex-direction:column; align-items:center; gap:12px;">
                <i class="fa-solid fa-users-gear" style="font-size:2.2rem; color:var(--primary);"></i>
                <h4 style="color:#fff;">Student Roles</h4>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0;">Promote users and delete profiles</p>
            </a>

            <a href="courses.php" class="glass-card" style="margin-bottom:0; text-align:center; padding:30px; display:flex; flex-direction:column; align-items:center; gap:12px;">
                <i class="fa-solid fa-folder-open" style="font-size:2.2rem; color:var(--success);"></i>
                <h4 style="color:#fff;">Course Builder</h4>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0;">Create lessons and coding tutorials</p>
            </a>

            <a href="quizzes.php" class="glass-card" style="margin-bottom:0; text-align:center; padding:30px; display:flex; flex-direction:column; align-items:center; gap:12px; grid-column:span 2;">
                <i class="fa-solid fa-square-poll-vertical" style="font-size:2.2rem; color:var(--warning);"></i>
                <h4 style="color:#fff;">Quiz Manager</h4>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0;">Create multiple choice questions and review explanations</p>
            </a>
        </div>
    </div>

    <!-- Recent Users Feed -->
    <div class="glass-card">
        <div class="glass-card-header">
            <h3 class="glass-card-title"><i class="fa-solid fa-user-plus" style="color:var(--success);"></i> Recent Signups</h3>
        </div>
        <div class="activity-list">
            <?php foreach ($recentUsers as $user): ?>
                <div class="activity-item">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="avatar" style="width:30px; height:30px; font-size:0.8rem;">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="activity-details" style="text-align:left;">
                            <h4 style="font-size:0.85rem; color:#fff;"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p style="font-size:0.75rem; color:var(--text-dark);"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <span class="user-role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-student'; ?>" style="font-size:0.65rem; padding:2px 6px;">
                        <?php echo $user['role']; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>