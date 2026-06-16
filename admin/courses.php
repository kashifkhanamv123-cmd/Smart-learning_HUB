<?php
$pageTitle = 'Manage Courses';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Enforce admin role
require_admin();

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $difficulty = trim($_POST['difficulty']);
    $duration = trim($_POST['duration']);
    
    if (!empty($title)) {
        try {
            $insert = $pdo->prepare("INSERT INTO courses (title, description, difficulty, duration) VALUES (?, ?, ?, ?)");
            $insert->execute([$title, $description, $difficulty, $duration]);
            setFlash('success', 'Course added successfully!');
        } catch (PDOException $e) {
            setFlash('error', 'Error adding course: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Course title is required.');
    }
    header("Location: courses.php");
    exit();
}

// Handle Add Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_lesson') {
    $courseId = intval($_POST['course_id']);
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $sortOrder = intval($_POST['sort_order']);
    
    if ($courseId > 0 && !empty($title)) {
        try {
            $insert = $pdo->prepare("INSERT INTO lessons (course_id, title, content, sort_order) VALUES (?, ?, ?, ?)");
            $insert->execute([$courseId, $title, $content, $sortOrder]);
            setFlash('success', 'Lesson added to course successfully!');
        } catch (PDOException $e) {
            setFlash('error', 'Error adding lesson: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Lesson title and course are required.');
    }
    header("Location: courses.php");
    exit();
}

// Handle Delete Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_course') {
    $deleteCourseId = intval($_POST['course_id']);
    try {
        $delete = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $delete->execute([$deleteCourseId]);
        setFlash('success', 'Course deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Error deleting course: ' . $e->getMessage());
    }
    header("Location: courses.php");
    exit();
}

// Handle Delete Lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_lesson') {
    $deleteLessonId = intval($_POST['lesson_id']);
    try {
        $delete = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $delete->execute([$deleteLessonId]);
        setFlash('success', 'Lesson removed from course.');
    } catch (PDOException $e) {
        setFlash('error', 'Error deleting lesson: ' . $e->getMessage());
    }
    header("Location: courses.php");
    exit();
}

// Fetch all courses
$courses = $pdo->query("SELECT * FROM courses ORDER BY id ASC")->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
    <div>
        <h1>Course Management</h1>
        <p>Edit dynamic courses list and write tutorial chapters.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary btn-sm" onclick="openAddCourseModal()"><i class="fa-solid fa-plus"></i> Add Course</button>
        <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Panel</a>
    </div>
</div>

<div class="glass-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Difficulty</th>
                    <th>Duration</th>
                    <th>Chapters</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:var(--text-dark); padding:30px;">No courses recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $c): ?>
                        <?php 
                        // Fetch lessons count
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
                        $stmt->execute([$c['id']]);
                        $lessonCount = $stmt->fetchColumn();
                        ?>
                        <tr>
                            <td style="color:#fff; font-weight:500;"><?php echo htmlspecialchars($c['title']); ?></td>
                            <td>
                                <span class="meta-badge level" style="font-size:0.75rem; font-weight:600;"><?php echo htmlspecialchars($c['difficulty']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($c['duration']); ?></td>
                            <td><strong><?php echo $lessonCount; ?></strong> lessons</td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="action-btn edit" title="Manage Lessons" onclick="openManageLessonsModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['title'])); ?>')">
                                        <i class="fa-solid fa-list-check"></i>
                                    </button>
                                    
                                    <form action="courses.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this course and all its lessons? This action is irreversible.');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_course">
                                        <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="action-btn delete" title="Delete Course">
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

<!-- Modal 1: Create Course Overlay -->
<div class="modal-overlay" id="addCourseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Course</h3>
            <i class="fa-solid fa-xmark modal-close" onclick="closeAddCourseModal()"></i>
        </div>
        <form action="courses.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_course">
                
                <div class="form-group">
                    <label for="course_title">Course Title</label>
                    <input type="text" name="title" id="course_title" class="form-control" placeholder="e.g. Master React JS" required>
                </div>
                
                <div class="form-group">
                    <label for="course_desc">Description</label>
                    <textarea name="description" id="course_desc" class="form-control" rows="3" placeholder="Brief outline of course details..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_diff">Difficulty Level</label>
                        <select name="difficulty" id="course_diff" class="form-control" style="background:#0f172a;">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_dur">Duration</label>
                        <input type="text" name="duration" id="course_dur" class="form-control" placeholder="e.g. 5 hours" value="3 hours">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddCourseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Manage Lessons / Chapters Dialog Overlay -->
<div class="modal-overlay" id="manageLessonsModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="manageLessonsTitle">Lessons list</h3>
            <i class="fa-solid fa-xmark modal-close" onclick="closeManageLessonsModal()"></i>
        </div>
        <div class="modal-body">
            <!-- Form to add new lesson inside this course -->
            <form action="courses.php" method="POST" style="margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid var(--border-color);">
                <input type="hidden" name="action" value="add_lesson">
                <input type="hidden" name="course_id" id="lessons_course_id">
                
                <h4 style="color:#fff; margin-bottom:15px;"><i class="fa-solid fa-plus" style="color:var(--success);"></i> Add Lesson</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson_title">Lesson Title</label>
                        <input type="text" name="title" id="lesson_title" class="form-control" placeholder="e.g. Setting up node modules" required>
                    </div>
                    <div class="form-group">
                        <label for="lesson_sort">Sort Order (0, 1, 2...)</label>
                        <input type="number" name="sort_order" id="lesson_sort" class="form-control" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="lesson_content">Lesson Content (Markdown style supported)</label>
                    <textarea name="content" id="lesson_content" class="form-control" rows="8" placeholder="# Header&#10;&#10;Use markdown syntax elements for listing and code segments..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Save Lesson</button>
            </form>
            
            <h4 style="color:#fff; margin-bottom:15px;">Existing Lessons</h4>
            
            <!-- Table listing existing lessons for course -->
            <div class="table-responsive">
                <table class="admin-table" style="font-size:0.85rem;">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Lesson Title</th>
                            <th>Words</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="lessonsTableBody">
                        <!-- Loaded dynamically via inline JSON parameters -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Load raw lessons lists in scripts context for quick modal lookup -->
<script id="lessonsRawData" type="application/json">
    <?php 
    // Fetch all lessons in DB grouped by course
    $allLessons = $pdo->query("SELECT id, course_id, title, content, sort_order FROM lessons ORDER BY sort_order ASC, id ASC")->fetchAll();
    echo json_encode($allLessons);
    ?>
</script>

<script>
function openAddCourseModal() {
    document.getElementById('addCourseModal').classList.add('active');
}
function closeAddCourseModal() {
    document.getElementById('addCourseModal').classList.remove('active');
}

function openManageLessonsModal(courseId, courseTitle) {
    document.getElementById('lessons_course_id').value = courseId;
    document.getElementById('manageLessonsTitle').textContent = 'Lessons in "' + courseTitle + '"';
    
    // Parse raw lessons database
    const rawLessons = JSON.parse(document.getElementById('lessonsRawData').textContent);
    
    // Filter lessons matching active course
    const courseLessons = rawLessons.filter(l => l.course_id == courseId);
    
    const tbody = document.getElementById('lessonsTableBody');
    tbody.innerHTML = '';
    
    if (courseLessons.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--text-dark); padding:20px;">No lessons found in this course.</td></tr>';
    } else {
        courseLessons.forEach(l => {
            const wordCount = l.content ? l.content.split(/\s+/).length : 0;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${l.sort_order}</td>
                <td style="color:#fff; font-weight:500;">${escapeHtml(l.title)}</td>
                <td>${wordCount} words</td>
                <td>
                    <form action="courses.php" method="POST" onsubmit="return confirm('Are you sure you want to remove this lesson?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_lesson">
                        <input type="hidden" name="lesson_id" value="${l.id}">
                        <button type="submit" class="action-btn delete" style="width:26px; height:26px; font-size:0.8rem;" title="Delete Lesson">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    document.getElementById('manageLessonsModal').classList.add('active');
}

function closeManageLessonsModal() {
    document.getElementById('manageLessonsModal').classList.remove('active');
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
