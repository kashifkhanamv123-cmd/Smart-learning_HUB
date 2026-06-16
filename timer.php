<?php
$pageTitle = 'Study Timer';
require_once __DIR__ . '/auth.php'; // also loads db.php
require_once __DIR__ . '/includes/functions.php';

// Guard: show setup page if DB is unavailable before any queries run
checkDbConnection();
/** @var \PDO $pdo */

$userId = $_SESSION['user_id'];

// Handle AJAX study logging
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_session') {
    $seconds = isset($_POST['seconds']) ? intval($_POST['seconds']) : 0;
    $today = date('Y-m-d');

    header('Content-Type: application/json');
    if ($seconds > 0) {
        try {
            $stmt = $pdo->prepare("SELECT duration_seconds FROM study_sessions WHERE user_id = ? AND session_date = ?");
            $stmt->execute([$userId, $today]);
            $existing = $stmt->fetchColumn();

            if ($existing !== false) {
                $update = $pdo->prepare("UPDATE study_sessions SET duration_seconds = duration_seconds + ? WHERE user_id = ? AND session_date = ?");
                $update->execute([$seconds, $userId, $today]);
            } else {
                $insert = $pdo->prepare("INSERT INTO study_sessions (user_id, session_date, duration_seconds) VALUES (?, ?, ?)");
                $insert->execute([$userId, $today, $seconds]);
            }
            echo json_encode(['status' => 'success', 'message' => 'Study time saved!']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid seconds value.']);
    }
    exit(); // End execution for AJAX requests
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Study Clock</h1>
    <p>Organize your study intervals using the Pomodoro technique with relaxing background audio.</p>
</div>

<div class="timer-widget glass-card">
    <!-- Interval mode selectors -->
    <div class="timer-modes">
        <button class="timer-mode-btn active" id="modeFocus" data-time="25">Focus</button>
        <button class="timer-mode-btn" id="modeShort" data-time="5">Short Break</button>
        <button class="timer-mode-btn" id="modeLong" data-time="15">Long Break</button>
    </div>

    <!-- SVG Circular Countdown Progress -->
    <div class="timer-circle-wrap">
        <svg class="timer-svg" width="260" height="260">
            <circle class="timer-bg-circle" cx="130" cy="130" r="120"></circle>
            <circle class="timer-progress-circle" id="timerProgressRing" cx="130" cy="130" r="120" stroke-dasharray="753.98" stroke-dashoffset="0"></circle>
        </svg>
        <div class="timer-display">
            <div class="timer-numbers" id="timerTimeDisplay">25:00</div>
            <div class="timer-mode" id="timerActiveModeLabel">Focusing</div>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="timer-controls">
        <button class="btn btn-primary" id="timerStartBtn"><i class="fa-solid fa-play"></i> Start</button>
        <button class="btn btn-secondary" id="timerResetBtn"><i class="fa-solid fa-arrows-rotate"></i> Reset</button>
    </div>

    <!-- Ambient Sound selector panel -->
    <div class="sound-controls">
        <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Background Ambience</p>
        <div class="sound-select">
            <button class="sound-btn" data-sound="rain"><i class="fa-solid fa-cloud-showers-heavy"></i> Rain</button>
            <button class="sound-btn" data-sound="waves"><i class="fa-solid fa-water"></i> Ocean</button>
            <button class="sound-btn" data-sound="noise"><i class="fa-solid fa-wind"></i> Wind</button>
        </div>
    </div>
</div>

<!-- Simulated Audio elements -->
<audio id="soundRain" loop src="https://assets.mixkit.co/active_storage/sfx/2433/2433-500.wav"></audio>
<audio id="soundWaves" loop src="https://assets.mixkit.co/active_storage/sfx/1188/1188-500.wav"></audio>
<audio id="soundNoise" loop src="https://assets.mixkit.co/active_storage/sfx/2566/2566-500.wav"></audio>
<audio id="soundAlarm" src="https://assets.mixkit.co/active_storage/sfx/911/911-500.wav"></audio>

<?php require_once __DIR__ . '/includes/footer.php'; ?>