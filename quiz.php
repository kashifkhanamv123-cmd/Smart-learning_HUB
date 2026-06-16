<?php
$pageTitle = 'Quizzes';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];
$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

// --------------------------------------------------------------------------
// View State 1: Process Quiz Submission (POST)
// --------------------------------------------------------------------------
$scoreData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    $submitQuizId = intval($_POST['quiz_id']);
    $userAnswers = isset($_POST['answers']) ? $_POST['answers'] : []; // [question_id => 'A'/'B'/'C'/'D']
    
    try {
        // Fetch quiz details
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$submitQuizId]);
        $quiz = $stmt->fetch();
        
        if ($quiz) {
            // Fetch all questions for this quiz
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
            $stmt->execute([$submitQuizId]);
            $questions = $stmt->fetchAll();
            
            $totalQuestions = count($questions);
            $correctCount = 0;
            $resultsDetail = []; // For the review sheet
            
            foreach ($questions as $q) {
                $qId = $q['id'];
                $selected = isset($userAnswers[$qId]) ? $userAnswers[$qId] : '';
                $isCorrect = ($selected === $q['correct_option']);
                
                if ($isCorrect) {
                    $correctCount++;
                }
                
                $resultsDetail[] = [
                    'question_text' => $q['question_text'],
                    'option_a' => $q['option_a'],
                    'option_b' => $q['option_b'],
                    'option_c' => $q['option_c'],
                    'option_d' => $q['option_d'],
                    'correct' => $q['correct_option'],
                    'selected' => $selected,
                    'is_correct' => $isCorrect,
                    'explanation' => $q['explanation']
                ];
            }
            
            $percentageScore = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;
            
            // Log attempt to database
            $insert = $pdo->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)");
            $insert->execute([$userId, $submitQuizId, $percentageScore, $totalQuestions]);
            
            $scoreData = [
                'quiz_title' => $quiz['title'],
                'score' => $percentageScore,
                'correct' => $correctCount,
                'total' => $totalQuestions,
                'details' => $resultsDetail
            ];
            
            setFlash('success', 'Quiz completed! Your score has been logged.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database error verifying quiz: ' . $e->getMessage());
    }
}

// --------------------------------------------------------------------------
// View State 2: Display Quiz Score Report (after submission)
// --------------------------------------------------------------------------
if ($scoreData !== null):
    ?>
    <div class="quiz-container">
        <div class="glass-card score-card">
            <h2 style="color:#fff;"><?php echo htmlspecialchars($scoreData['quiz_title']); ?> Results</h2>
            <div class="score-circle">
                <span class="score-num"><?php echo $scoreData['score']; ?>%</span>
                <span class="score-label">Score</span>
            </div>
            <p style="color:var(--text-muted); font-size:1.1rem; margin-bottom:30px;">
                You got <strong><?php echo $scoreData['correct']; ?></strong> out of <strong><?php echo $scoreData['total']; ?></strong> questions correct.
            </p>
            
            <div style="display:flex; justify-content:center; gap:12px; margin-bottom:40px;">
                <a href="quiz.php?quiz_id=<?php echo $quizId; ?>" class="btn btn-secondary"><i class="fa-solid fa-arrows-rotate"></i> Retake Quiz</a>
                <a href="quiz.php" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Dashboard</a>
            </div>
            
            <!-- Accordion Answer Key Review -->
            <h3 style="color:#fff; text-align:left; margin-bottom:20px;">Review Answers</h3>
            
            <div style="display: flex; flex-direction: column; gap: 12px; text-align: left;">
                <?php foreach ($scoreData['details'] as $idx => $det): ?>
                    <div class="review-item">
                        <div class="review-header" onclick="toggleReviewBody(<?php echo $idx; ?>)">
                            <h4 style="display:flex; align-items:center; gap:10px;">
                                <?php if ($det['is_correct']): ?>
                                    <i class="fa-solid fa-circle-check" style="color:var(--success);"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-xmark" style="color:var(--danger);"></i>
                                <?php endif; ?>
                                <?php echo ($idx + 1) . '. ' . htmlspecialchars($det['question_text']); ?>
                            </h4>
                            <i class="fa-solid fa-chevron-down" id="reviewChevron-<?php echo $idx; ?>" style="color:var(--text-dark); transition:transform 0.2s;"></i>
                        </div>
                        <div class="review-body" id="reviewBody-<?php echo $idx; ?>">
                            <div class="options-review">
                                <?php 
                                $opts = ['A' => $det['option_a'], 'B' => $det['option_b'], 'C' => $det['option_c'], 'D' => $det['option_d']];
                                foreach ($opts as $key => $val):
                                    $class = '';
                                    if ($key === $det['correct']) {
                                        $class = 'correct';
                                    } elseif ($key === $det['selected'] && !$det['is_correct']) {
                                        $class = 'wrong';
                                    }
                                    ?>
                                    <div class="review-option <?php echo $class; ?>">
                                        <strong><?php echo $key; ?>:</strong> <?php echo htmlspecialchars($val); ?>
                                        <?php if ($key === $det['correct']): ?> (Correct Answer)<?php endif; ?>
                                        <?php if ($key === $det['selected'] && !$det['is_correct']): ?> (Your Answer)<?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($det['explanation'])): ?>
                                <div class="review-explanation">
                                    <strong>Explanation:</strong> <?php echo htmlspecialchars($det['explanation']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
    function toggleReviewBody(idx) {
        const body = document.getElementById('reviewBody-' + idx);
        const chevron = document.getElementById('reviewChevron-' + idx);
        if (body.style.display === 'block') {
            body.style.display = 'none';
            chevron.style.transform = 'rotate(0deg)';
        } else {
            body.style.display = 'block';
            chevron.style.transform = 'rotate(180deg)';
        }
    }
    </script>

<?php elseif ($quizId > 0):
    // View State 3: Timed Quiz Session
    // Fetch quiz
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        setFlash('error', 'Requested quiz not found.');
        header("Location: quiz.php");
        exit();
    }
    
    // Fetch questions
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();
    
    if (empty($questions)) {
        setFlash('warning', 'This quiz does not contain any questions yet.');
        header("Location: quiz.php");
        exit();
    }
    ?>
    <div class="quiz-container">
        <div class="glass-card">
            <div class="quiz-header">
                <h2 style="color:#fff;"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                <div class="quiz-timer" id="quizTimerDisplay">
                    <i class="fa-regular fa-clock"></i> <span id="timerClock">00:00</span>
                </div>
            </div>
            
            <form id="quizSubmitForm" action="quiz.php?quiz_id=<?php echo $quizId; ?>" method="POST">
                <input type="hidden" name="action" value="submit_quiz">
                <input type="hidden" name="quiz_id" value="<?php echo $quizId; ?>">
                
                <!-- Dynamic Question Block -->
                <div class="question-block" id="questionBlock">
                    <?php foreach ($questions as $idx => $q): ?>
                        <div class="question-slide" id="qSlide-<?php echo $idx; ?>" style="display: <?php echo $idx === 0 ? 'block' : 'none'; ?>;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted); margin-bottom:15px;">
                                <span>Question <?php echo ($idx + 1); ?> of <?php echo count($questions); ?></span>
                            </div>
                            <h3 class="question-title"><?php echo htmlspecialchars($q['question_text']); ?></h3>
                            
                            <div class="options-list">
                                <?php 
                                $opts = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                                foreach ($opts as $key => $val):
                                    ?>
                                    <label class="option-item" for="q-<?php echo $q['id']; ?>-<?php echo $key; ?>" onclick="selectOption(this, '<?php echo $idx; ?>')">
                                        <input type="radio" name="answers[<?php echo $q['id']; ?>]" id="q-<?php echo $q['id']; ?>-<?php echo $key; ?>" value="<?php echo $key; ?>" style="display:none;">
                                        <span class="option-prefix"><?php echo $key; ?></span>
                                        <span><?php echo htmlspecialchars($val); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quiz Form Action Buttons -->
                <div style="display:flex; justify-content:space-between; margin-top:30px; padding-top:20px; border-top:1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" id="quizPrevBtn" style="display:none;"><i class="fa-solid fa-chevron-left"></i> Back</button>
                    <button type="button" class="btn btn-primary" id="quizNextBtn" style="margin-left:auto;">Next <i class="fa-solid fa-chevron-right"></i></button>
                    <button type="submit" class="btn btn-primary" id="quizSubmitBtn" style="display:none; margin-left:auto; background:var(--success);"><i class="fa-solid fa-circle-check"></i> Submit Quiz</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Pass quiz parameters to JavaScript context -->
    <script>
        window.quizDurationSecs = <?php echo intval($quiz['duration_mins'] * 60); ?>;
        window.quizTotalQuestions = <?php echo count($questions); ?>;
    </script>

<?php else: 
    // View State 4: Quiz Catalogue & History
    $quizzes = $pdo->query("SELECT * FROM quizzes ORDER BY id ASC")->fetchAll();
    
    // Fetch recent quiz attempts by user
    $stmt = $pdo->prepare("SELECT qa.*, q.title FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id WHERE qa.user_id = ? ORDER BY qa.attempted_at DESC");
    $stmt->execute([$userId]);
    $attempts = $stmt->fetchAll();
    ?>
    <div class="page-header">
        <h1>Quiz Hub</h1>
        <p>Test your knowledge with timed quizzes and review detailed explanations.</p>
    </div>
    
    <div class="dashboard-split" style="grid-template-columns: 1.5fr 1fr;">
        <!-- Available Quizzes -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h3 class="glass-card-title"><i class="fa-solid fa-graduation-cap" style="color:var(--primary);"></i> Available Quizzes</h3>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:16px;">
                <?php if (empty($quizzes)): ?>
                    <p style="color:var(--text-dark); text-align:center; padding:30px;">No quizzes have been created yet. Log in as an Admin to create quizzes.</p>
                <?php else: ?>
                    <?php foreach ($quizzes as $q): ?>
                        <?php 
                        // Fetch question count
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
                        $stmt->execute([$q['id']]);
                        $qCount = $stmt->fetchColumn();
                        ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.03);">
                            <div style="text-align:left;">
                                <h4 style="color:#fff; font-size:1.05rem; margin-bottom:4px;"><?php echo htmlspecialchars($q['title']); ?></h4>
                                <p style="color:var(--text-muted); font-size:0.85rem; margin-bottom:4px;"><?php echo htmlspecialchars($q['description']); ?></p>
                                <span style="font-size:0.75rem; color:var(--text-dark); font-weight:600;">
                                    <i class="fa-regular fa-clock"></i> <?php echo $q['duration_mins']; ?> mins • <?php echo $qCount; ?> questions
                                </span>
                            </div>
                            <a href="quiz.php?quiz_id=<?php echo $q['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-play"></i> Start
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Score History -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h3 class="glass-card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--success);"></i> Score History</h3>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attempts)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:var(--text-dark); padding:30px;">No quiz records log.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attempts as $att): ?>
                                <tr>
                                    <td style="color:#fff; font-weight:500;"><?php echo htmlspecialchars($att['title']); ?></td>
                                    <td>
                                        <span class="user-role-badge <?php echo $att['score'] >= 70 ? 'role-student' : 'role-admin'; ?>" style="font-size:0.75rem;">
                                            <?php echo $att['score']; ?>%
                                        </span>
                                    </td>
                                    <td style="font-size:0.8rem;"><?php echo date('M d, y', strtotime($att['attempted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
