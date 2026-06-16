<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];

// 1. Fetch Stats
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalDecks = $pdo->query("SELECT COUNT(*) FROM flashcard_decks")->fetchColumn();

// Total user notes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
$stmt->execute([$userId]);
$totalNotes = $stmt->fetchColumn();

// Average user quiz score
$stmt = $pdo->prepare("SELECT AVG(score) FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$userId]);
$avgScore = round($stmt->fetchColumn() ?: 0);

// Total hours studied (sum of session seconds converted to hours)
$stmt = $pdo->prepare("SELECT SUM(duration_seconds) FROM study_sessions WHERE user_id = ?");
$stmt->execute([$userId]);
$totalSeconds = $stmt->fetchColumn() ?: 0;
$totalHours = round($totalSeconds / 3600, 1);

// 2. Fetch Study Sessions for Chart (Past 5 Entries)
$stmt = $pdo->prepare("SELECT * FROM study_sessions WHERE user_id = ? ORDER BY session_date DESC LIMIT 5");
$stmt->execute([$userId]);
$rawSessions = $stmt->fetchAll();
// Reverse to chronological order
$sessions = array_reverse($rawSessions);

// 3. Fetch Recent Activity Logs
$activities = [];

// Fetch quiz attempts
$stmt = $pdo->prepare("SELECT qa.*, q.title FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.user_id = ? ORDER BY qa.attempted_at DESC LIMIT 2");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $activities[] = [
        'title' => 'Completed Quiz: ' . $row['title'],
        'desc' => 'Scored ' . $row['score'] . '% on a ' . $row['total_questions'] . '-question quiz.',
        'time' => date('M d, H:i', strtotime($row['attempted_at'])),
        'icon' => 'fa-square-poll-horizontal',
        'color' => '#6366f1',
        'timestamp' => strtotime($row['attempted_at'])
    ];
}

// Fetch note updates
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC LIMIT 2");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $activities[] = [
        'title' => 'Updated Note: ' . $row['title'],
        'desc' => 'Category: ' . $row['category'] . ' • Words: ' . str_word_count($row['content'] ?? ''),
        'time' => date('M d, H:i', strtotime($row['updated_at'])),
        'icon' => 'fa-note-sticky',
        'color' => '#f59e0b',
        'timestamp' => strtotime($row['updated_at'])
    ];
}

// Sort activities by timestamp descending
usort($activities, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});
?>

<div class="page-header">
    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
    <p>Track your learning progress, manage study guides, and test your knowledge.</p>
</div>

<!-- Statistics Cards Grid -->
<div class="stats-grid">
    <div class="glass-card stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalCourses; ?></h3>
            <p>Courses Available</p>
        </div>
    </div>
    
    <div class="glass-card stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-stopwatch"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalHours; ?>h</h3>
            <p>Hours Studied</p>
        </div>
    </div>
    
    <div class="glass-card stat-card">
        <div class="stat-icon rose"><i class="fa-solid fa-square-poll-horizontal"></i></div>
        <div class="stat-info">
            <h3><?php echo $avgScore; ?>%</h3>
            <p>Average Score</p>
        </div>
    </div>
    
    <div class="glass-card stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-note-sticky"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalNotes; ?></h3>
            <p>Study Notes</p>
        </div>
    </div>
</div>

<!-- Dashboard Split View -->
<div class="dashboard-split">
    <!-- Chart Panel -->
    <div class="glass-card">
        <div class="glass-card-header">
            <h3 class="glass-card-title"><i class="fa-solid fa-chart-line" style="color: var(--primary);"></i> Weekly Study Activity</h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Minutes per day</span>
        </div>
        
        <div class="chart-container">
            <?php if (empty($sessions)): ?>
                <div style="width: 100%; text-align: center; color: var(--text-dark); margin: auto;">
                    <i class="fa-solid fa-chart-bar" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
                    <p>No study logs recorded. Start the Pomodoro timer to track progress!</p>
                </div>
            <?php else: ?>
                <?php 
                $maxMinutes = 10;
                foreach ($sessions as $s) {
                    $minutes = round($s['duration_seconds'] / 60);
                    if ($minutes > $maxMinutes) $maxMinutes = $minutes;
                }
                ?>
                <?php foreach ($sessions as $s): ?>
                    <?php 
                    $minutes = round($s['duration_seconds'] / 60);
                    // Calculate height percentage (min 5% for visual display)
                    $pct = $maxMinutes > 0 ? max(5, round(($minutes / $maxMinutes) * 80)) : 5;
                    $dayLabel = date('D', strtotime($s['session_date']));
                    $formattedDate = date('M d', strtotime($s['session_date']));
                    ?>
                    <div class="chart-bar-wrap">
                        <div class="chart-bar" style="height: <?php echo $pct; ?>%;">
                            <div class="chart-tooltip">
                                <strong><?php echo $minutes; ?> min</strong><br>
                                <span style="font-size:0.7rem; color:var(--text-muted);"><?php echo $formattedDate; ?></span>
                            </div>
                        </div>
                        <span class="chart-label"><?php echo $dayLabel; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lofi Player Widget -->
    <div class="glass-card">
        <div class="glass-card-header">
            <h3 class="glass-card-title"><i class="fa-solid fa-music" style="color: var(--warning);"></i> Study Ambient Sound</h3>
        </div>
        <div class="lofi-widget">
            <div class="lofi-artwork">
                <i class="fa-solid fa-compact-disc fa-spin" style="animation-duration: 6s;"></i>
            </div>
            <div>
                <h4 style="color:#fff; margin-bottom:4px;" id="trackTitle">Lofi Study Beats</h4>
                <p style="font-size:0.8rem; color:var(--text-muted);">Ambient soundtrack mockup</p>
            </div>
            
            <audio id="ambientPlayer" loop></audio>
            
            <div style="display: flex; gap: 10px; margin-top: 5px;">
                <button class="btn btn-secondary btn-sm" id="prevTrackBtn"><i class="fa-solid fa-backward"></i></button>
                <button class="btn btn-primary btn-sm" id="playTrackBtn"><i class="fa-solid fa-play"></i> Play</button>
                <button class="btn btn-secondary btn-sm" id="nextTrackBtn"><i class="fa-solid fa-forward"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity List -->
<div class="glass-card">
    <div class="glass-card-header">
        <h3 class="glass-card-title"><i class="fa-solid fa-clock-rotate-left" style="color: var(--success);"></i> Recent Activity</h3>
    </div>
    
    <div class="activity-list">
        <?php if (empty($activities)): ?>
            <div style="padding: 20px; text-align: center; color: var(--text-dark);">
                <i class="fa-solid fa-shoe-prints" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                No recent activity. Start studying, updating notes, or taking quizzes to log metrics!
            </div>
        <?php else: ?>
            <?php foreach ($activities as $act): ?>
                <div class="activity-item">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo $act['color']; ?>30; color: <?php echo $act['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                            <i class="fa-solid <?php echo $act['icon']; ?>"></i>
                        </div>
                        <div class="activity-details">
                            <h4><?php echo htmlspecialchars($act['title']); ?></h4>
                            <p><?php echo htmlspecialchars($act['desc']); ?></p>
                        </div>
                    </div>
                    <span class="activity-time"><?php echo $act['time']; ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Local Script for Sound Toggle Mockup -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const playBtn = document.getElementById('playTrackBtn');
    const prevBtn = document.getElementById('prevTrackBtn');
    const nextBtn = document.getElementById('nextTrackBtn');
    const trackTitle = document.getElementById('trackTitle');
    const discIcon = document.querySelector('.lofi-artwork i');
    
    const tracks = [
        { name: 'Lofi Study Beats', icon: 'fa-headphones' },
        { name: 'Gentle Summer Rain', icon: 'fa-cloud-showers-water' },
        { name: 'Cosmic White Noise', icon: 'fa-bezier-curve' }
    ];
    let activeIndex = 0;
    let isPlaying = false;
    
    function updatePlayState() {
        if (isPlaying) {
            playBtn.innerHTML = '<i class="fa-solid fa-pause"></i> Pause';
            discIcon.classList.add('fa-spin');
        } else {
            playBtn.innerHTML = '<i class="fa-solid fa-play"></i> Play';
            discIcon.classList.remove('fa-spin');
        }
    }
    
    playBtn.addEventListener('click', () => {
        isPlaying = !isPlaying;
        updatePlayState();
    });
    
    nextBtn.addEventListener('click', () => {
        activeIndex = (activeIndex + 1) % tracks.length;
        trackTitle.textContent = tracks[activeIndex].name;
        isPlaying = true;
        updatePlayState();
    });
    
    prevBtn.addEventListener('click', () => {
        activeIndex = (activeIndex - 1 + tracks.length) % tracks.length;
        trackTitle.textContent = tracks[activeIndex].name;
        isPlaying = true;
        updatePlayState();
    });
    
    // Stop disc spin initially
    discIcon.classList.remove('fa-spin');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
