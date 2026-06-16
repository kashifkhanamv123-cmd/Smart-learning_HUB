<?php
$pageTitle = 'Courses';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$lessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

// Handle lesson completion post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_lesson') {
    $postLessonId = intval($_POST['lesson_id']);
    $postCourseId = intval($_POST['course_id']);
    
    try {
        // Toggle lesson completion
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND lesson_id = ?");
        $stmt->execute([$userId, $postLessonId]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $delete = $pdo->prepare("DELETE FROM lesson_progress WHERE user_id = ? AND lesson_id = ?");
            $delete->execute([$userId, $postLessonId]);
            setFlash('info', 'Lesson marked as incomplete.');
        } else {
            $insert = $pdo->prepare("INSERT INTO lesson_progress (user_id, lesson_id) VALUES (?, ?)");
            $insert->execute([$userId, $postLessonId]);
            setFlash('success', 'Lesson marked as completed! Keep it up!');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database error toggling completion: ' . $e->getMessage());
    }
    
    // Redirect back to maintain active state
    header("Location: courses.php?course_id=$postCourseId&lesson_id=$postLessonId");
    exit();
}

// --------------------------------------------------------------------------
// View State 1: Active Lesson Player
// --------------------------------------------------------------------------
if ($courseId > 0):
    // Verify course exists
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        setFlash('error', 'Requested course not found.');
        header("Location: courses.php");
        exit();
    }
    
    // Fetch all lessons for course
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$courseId]);
    $lessons = $stmt->fetchAll();
    
    if (empty($lessons)) {
        setFlash('warning', 'This course does not contain any lessons yet.');
        header("Location: courses.php");
        exit();
    }
    
    // Determine active lesson
    $activeLesson = null;
    if ($lessonId > 0) {
        foreach ($lessons as $l) {
            if ((int)$l['id'] === $lessonId) {
                $activeLesson = $l;
                break;
            }
        }
    }
    
    // Default to first lesson if active not found
    if ($activeLesson === null) {
        $activeLesson = $lessons[0];
        $lessonId = $activeLesson['id'];
    }
    
    // Fetch completed lessons IDs for user
    $stmt = $pdo->prepare("SELECT lesson_id FROM lesson_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $completedLessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $isActiveCompleted = in_array($lessonId, $completedLessons);
    
    // Determine Prev / Next lessons
    $prevLesson = null;
    $nextLesson = null;
    for ($i = 0; $i < count($lessons); $i++) {
        if ((int)$lessons[$i]['id'] === $lessonId) {
            $prevLesson = ($i > 0) ? $lessons[$i - 1] : null;
            $nextLesson = ($i < count($lessons) - 1) ? $lessons[$i + 1] : null;
            break;
        }
    }
    ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="courses.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Courses</a>
        </div>
        <h2 style="color: #fff; text-align: right;"><?php echo htmlspecialchars($course['title']); ?></h2>
    </div>
    
    <div class="player-layout">
        <!-- Lessons Navigator Sidebar -->
        <div class="lesson-list">
            <h4 style="color:#fff; padding:10px 12px; font-weight:600; border-bottom:1px solid var(--border-color); margin-bottom:10px;">Lessons</h4>
            <?php foreach ($lessons as $index => $l): ?>
                <?php 
                $isCompleted = in_array($l['id'], $completedLessons);
                $isActive = $l['id'] === $lessonId;
                ?>
                <a href="courses.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $l['id']; ?>" class="lesson-link <?php echo $isActive ? 'active' : ''; ?>">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <span style="opacity:0.5; font-size:0.8rem;"><?php echo ($index + 1); ?>.</span>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;">
                            <?php echo htmlspecialchars($l['title']); ?>
                        </span>
                    </span>
                    <?php if ($isCompleted): ?>
                        <i class="fa-solid fa-circle-check completed"></i>
                    <?php else: ?>
                        <i class="fa-regular fa-circle" style="opacity:0.3;"></i>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Active Lesson Reader -->
        <div class="lesson-content">
            <article class="markdown-body">
                <?php
                // Standard PHP Simple Markdown parser representation
                $content = $activeLesson['content'];
                
                // Escape HTML tags to prevent cross site scripting
                $escapedContent = htmlspecialchars($content);
                
                // Basic parser logic for code blocks
                $escapedContent = preg_replace('/```([a-zA-Z0-9]+)?\r?\n(.*?)\r?\n```/s', '<pre><code class="language-$1">$2</code></pre>', $escapedContent);
                
                // Parse headers
                $escapedContent = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $escapedContent);
                $escapedContent = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $escapedContent);
                $escapedContent = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $escapedContent);
                
                // Parse bullet points
                $escapedContent = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $escapedContent);
                $escapedContent = preg_replace('/(<li>.*?<\/li>)+/s', '<ul>$0</ul>', $escapedContent);
                
                // Parse inline code
                $escapedContent = preg_replace('/`(.*?)`/U', '<code>$1</code>', $escapedContent);
                
                // Parse line breaks
                $escapedContent = nl2br($escapedContent);
                
                // Clean up duplicate bullet points wraps
                $escapedContent = str_replace("<br />\n<li>", "<li>", $escapedContent);
                $escapedContent = str_replace("</li><br />", "</li>", $escapedContent);
                $escapedContent = str_replace("<br />\n<ul>", "<ul>", $escapedContent);
                $escapedContent = str_replace("</ul><br />", "</ul>", $escapedContent);
                
                echo $escapedContent;
                ?>
            </article>
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:40px; padding-top:20px; border-top:1px solid var(--border-color);">
                <div>
                    <?php if ($prevLesson): ?>
                        <a href="courses.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $prevLesson['id']; ?>" class="btn btn-secondary"><i class="fa-solid fa-chevron-left"></i> Previous</a>
                    <?php endif; ?>
                </div>
                
                <form action="courses.php" method="POST">
                    <input type="hidden" name="action" value="complete_lesson">
                    <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                    <input type="hidden" name="lesson_id" value="<?php echo $lessonId; ?>">
                    <button type="submit" class="btn <?php echo $isActiveCompleted ? 'btn-secondary' : 'btn-primary'; ?>">
                        <?php if ($isActiveCompleted): ?>
                            <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Completed
                        <?php else: ?>
                            <i class="fa-regular fa-circle-check"></i> Mark Complete
                        <?php endif; ?>
                    </button>
                </form>
                
                <div>
                    <?php if ($nextLesson): ?>
                        <a href="courses.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $nextLesson['id']; ?>" class="btn btn-secondary">Next <i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: 
    // View State 2: Course Catalog Grid
    $courses = $pdo->query("SELECT * FROM courses ORDER BY id ASC")->fetchAll();
    ?>
    <div class="page-header">
        <h1>Course Explorer</h1>
        <p>Interactive self-paced programming guides and developer tutorials.</p>
    </div>
    
    <div class="courses-grid">
        <?php if (empty($courses)): ?>
            <div class="glass-card" style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--text-dark);">
                <i class="fa-solid fa-cubes" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <p>No courses have been created yet. Log in as an Admin to add learning content.</p>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
                <?php
                // Count total lessons
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
                $stmt->execute([$c['id']]);
                $lessonCount = $stmt->fetchColumn();
                
                // Count completed lessons for user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON lp.lesson_id = l.id WHERE lp.user_id = ? AND l.course_id = ?");
                $stmt->execute([$userId, $c['id']]);
                $completedCount = $stmt->fetchColumn();
                
                $progressPercent = $lessonCount > 0 ? round(($completedCount / $lessonCount) * 100) : 0;
                ?>
                <div class="glass-card course-card">
                    <div class="course-meta">
                        <span class="meta-badge level"><?php echo htmlspecialchars($c['difficulty']); ?></span>
                        <span class="meta-badge time"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($c['duration']); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($c['title']); ?></h3>
                    <p><?php echo htmlspecialchars($c['description']); ?></p>
                    
                    <?php if ($lessonCount > 0): ?>
                        <div style="margin-bottom: 20px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--text-muted); margin-bottom:6px;">
                                <span>Progress: <?php echo $completedCount; ?> / <?php echo $lessonCount; ?> chapters</span>
                                <span><?php echo $progressPercent; ?>%</span>
                            </div>
                            <div class="study-progress" style="margin-bottom:0;">
                                <div class="study-progress-bar" style="width: <?php echo $progressPercent; ?>%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <a href="courses.php?course_id=<?php echo $c['id']; ?>" class="btn btn-primary" style="margin-top:auto; width: 100%;">
                        <?php echo ($completedCount > 0) ? '<i class="fa-solid fa-play"></i> Resume Course' : '<i class="fa-solid fa-graduation-cap"></i> Start Course'; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
