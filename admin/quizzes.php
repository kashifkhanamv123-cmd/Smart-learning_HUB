<?php
$pageTitle = 'Manage Quizzes';
require_once dirname(__DIR__) . '/auth.php'; // also loads db.php
require_once dirname(__DIR__) . '/includes/functions.php';

// Guard: show setup page if DB is unavailable before any queries run
checkDbConnection();
/** @var \PDO $pdo */

// Enforce admin role
require_admin();

// Handle Add Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_quiz') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = intval($_POST['duration_mins']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid CSRF token.');
        header("Location: quizzes.php");
        exit();
    }

    if (!empty($title)) {
        try {
            $insert = $pdo->prepare("INSERT INTO quizzes (title, description, duration_mins) VALUES (?, ?, ?)");
            $insert->execute([$title, $description, $duration]);
            setFlash('success', 'Quiz created successfully!');
        } catch (PDOException $e) {
            setFlash('error', 'Error creating quiz: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Quiz title is required.');
    }
    header("Location: quizzes.php");
    exit();
}

// Handle Add Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $quizId = intval($_POST['quiz_id']);
    $qText = trim($_POST['question_text']);
    $optA = trim($_POST['option_a']);
    $optB = trim($_POST['option_b']);
    $optC = trim($_POST['option_c']);
    $optD = trim($_POST['option_d']);
    $correct = trim($_POST['correct_option']);
    $explanation = trim($_POST['explanation']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid CSRF token.');
        header("Location: quizzes.php");
        exit();
    }

    if ($quizId > 0 && !empty($qText) && !empty($optA) && !empty($optB) && !empty($optC) && !empty($optD) && !empty($correct)) {
        try {
            $insert = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$quizId, $qText, $optA, $optB, $optC, $optD, $correct, $explanation]);
            setFlash('success', 'Question added to quiz successfully!');
        } catch (PDOException $e) {
            setFlash('error', 'Error adding question: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Please fill in all options, select correct answer, and enter question text.');
    }
    header("Location: quizzes.php");
    exit();
}

// Handle Delete Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_quiz') {
    $deleteQuizId = intval($_POST['quiz_id']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid CSRF token.');
        header("Location: quizzes.php");
        exit();
    }
    try {
        $delete = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $delete->execute([$deleteQuizId]);
        setFlash('success', 'Quiz deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Error deleting quiz: ' . $e->getMessage());
    }
    header("Location: quizzes.php");
    exit();
}

// Handle Delete Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    $deleteQuestionId = intval($_POST['question_id']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid CSRF token.');
        header("Location: quizzes.php");
        exit();
    }
    try {
        $delete = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $delete->execute([$deleteQuestionId]);
        setFlash('success', 'Question deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Error deleting question: ' . $e->getMessage());
    }
    header("Location: quizzes.php");
    exit();
}

// Fetch all quizzes
$quizzes = $pdo->query("SELECT * FROM quizzes ORDER BY id ASC")->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
    <div>
        <h1>Quiz Management</h1>
        <p>Compile assessment tests and write multiple-choice questions.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary btn-sm" onclick="openAddQuizModal()"><i class="fa-solid fa-plus"></i> Add Quiz</button>
        <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Panel</a>
    </div>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Quiz Title</th>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Questions Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quizzes)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:var(--text-dark); padding:30px;">No quizzes recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quizzes as $q): ?>
                        <?php
                        // Fetch questions count
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
                        $stmt->execute([$q['id']]);
                        $qCount = $stmt->fetchColumn();
                        ?>
                        <tr>
                            <td style="color:#fff; font-weight:500;"><?php echo htmlspecialchars($q['title']); ?></td>
                            <td><?php echo htmlspecialchars($q['description']); ?></td>
                            <td><?php echo $q['duration_mins']; ?> mins</td>
                            <td><strong><?php echo $qCount; ?></strong> questions</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="action-btn edit" title="Manage Questions" onclick="openManageQuestionsModal(<?php echo $q['id']; ?>, '<?php echo htmlspecialchars(addslashes($q['title'])); ?>')">
                                        <i class="fa-solid fa-circle-question"></i>
                                    </button>

                                    <form action="quizzes.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this quiz and all its questions?');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_quiz">
                                        <input type="hidden" name="quiz_id" value="<?php echo $q['id']; ?>">
                                        <button type="submit" class="action-btn delete" title="Delete Quiz">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Create Quiz Overlay -->
<div class="modal-overlay" id="addQuizModal">
    <div class="modal-content" style="max-width:450px;">
        <div class="modal-header">
            <h3>Add New Quiz</h3>
            <i class="fa-solid fa-xmark modal-close" onclick="closeAddQuizModal()"></i>
        </div>
        <form action="quizzes.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_quiz">

                <div class="form-group">
                    <label for="quiz_title">Quiz Title</label>
                    <input type="text" name="title" id="quiz_title" class="form-control" placeholder="e.g. JavaScript Arrays Check" required>
                </div>

                <div class="form-group">
                    <label for="quiz_desc">Description</label>
                    <input type="text" name="description" id="quiz_desc" class="form-control" placeholder="e.g. Evaluates filter, map, and reduce methods">
                </div>

                <div class="form-group">
                    <label for="quiz_dur">Duration (Minutes)</label>
                    <input type="number" name="duration_mins" id="quiz_dur" class="form-control" value="15" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddQuizModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Quiz</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Manage Questions Overlay -->
<div class="modal-overlay" id="manageQuestionsModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="manageQuestionsTitle">Questions List</h3>
            <i class="fa-solid fa-xmark modal-close" onclick="closeManageQuestionsModal()"></i>
        </div>
        <div class="modal-body">
            <!-- Form to add new question -->
            <form action="quizzes.php" method="POST" style="margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid var(--border-color);">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="quiz_id" id="questions_quiz_id">

                <h4 style="color:#fff; margin-bottom:15px;"><i class="fa-solid fa-plus" style="color:var(--success);"></i> Add Question</h4>

                <div class="form-group">
                    <label for="question_text">Question Text</label>
                    <textarea name="question_text" id="question_text" class="form-control" rows="3" placeholder="Write question details..." required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="opt_a">Option A</label>
                        <input type="text" name="option_a" id="opt_a" class="form-control" placeholder="First option" required>
                    </div>
                    <div class="form-group">
                        <label for="opt_b">Option B</label>
                        <input type="text" name="option_b" id="opt_b" class="form-control" placeholder="Second option" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="opt_c">Option C</label>
                        <input type="text" name="option_c" id="opt_c" class="form-control" placeholder="Third option" required>
                    </div>
                    <div class="form-group">
                        <label for="opt_d">Option D</label>
                        <input type="text" name="option_d" id="opt_d" class="form-control" placeholder="Fourth option" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="correct_opt">Correct Option</label>
                        <select name="correct_option" id="correct_opt" class="form-control" style="background:#0f172a;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="explanation_text">Detailed Explanation (Visible on review)</label>
                        <input type="text" name="explanation" id="explanation_text" class="form-control" placeholder="Why is this option correct?">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Save Question</button>
            </form>

            <h4 style="color:#fff; margin-bottom:15px;">Existing Questions</h4>

            <!-- Table listing existing questions -->
            <div class="table-responsive">
                <table class="admin-table" style="font-size:0.85rem;">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Correct</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="questionsTableBody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Raw questions data for quick dynamic lookup -->
<script id="questionsRawData" type="application/json">
    <?php
    $allQuestions = $pdo->query("SELECT id, quiz_id, question_text, correct_option FROM questions ORDER BY id ASC")->fetchAll();
    echo json_encode($allQuestions);
    ?>
</script>

<script>
    function openAddQuizModal() {
        document.getElementById('addQuizModal').classList.add('active');
    }

    function closeAddQuizModal() {
        document.getElementById('addQuizModal').classList.remove('active');
    }

    function openManageQuestionsModal(quizId, quizTitle) {
        document.getElementById('questions_quiz_id').value = quizId;
        document.getElementById('manageQuestionsTitle').textContent = 'Questions in "' + quizTitle + '"';

        const rawQuestions = JSON.parse(document.getElementById('questionsRawData').textContent);
        const quizQuestions = rawQuestions.filter(q => q.quiz_id == quizId);

        const tbody = document.getElementById('questionsTableBody');
        tbody.innerHTML = '';

        if (quizQuestions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:var(--text-dark); padding:20px;">No questions found in this quiz.</td></tr>';
        } else {
            quizQuestions.forEach((q, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td style="color:#fff; text-align:left;">
                    <span style="opacity:0.5; font-size:0.75rem;">${idx + 1}.</span> 
                    ${escapeHtml(q.question_text)}
                </td>
                <td><span class="user-role-badge role-student">${q.correct_option}</span></td>
                <td>
                    <form action="quizzes.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this question?');" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete_question">
                        <input type="hidden" name="question_id" value="${q.id}">
                        <button type="submit" class="action-btn delete" style="width:26px; height:26px; font-size:0.8rem;" title="Delete Question">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('manageQuestionsModal').classList.add('active');
    }

    function closeManageQuestionsModal() {
        document.getElementById('manageQuestionsModal').classList.remove('active');
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>