<?php
$pageTitle = 'Notes';
require_once __DIR__ . '/auth.php'; // also loads db.php
require_once __DIR__ . '/includes/functions.php';

// Guard: show setup page if DB is unavailable before any queries run
checkDbConnection();
/** @var \PDO $pdo */

$userId = $_SESSION['user_id'];

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_note') {
        try {
            $insert = $pdo->prepare("INSERT INTO notes (user_id, title, content, category) VALUES (?, 'New Note', '# New Note\\n\\nStart writing...', 'General')");
            $insert->execute([$userId]);
            $newId = $pdo->lastInsertId();
            setFlash('success', 'New note created!');
            header("Location: notes.php?note_id=$newId");
            exit();
        } catch (PDOException $e) {
            setFlash('error', 'Error creating note: ' . $e->getMessage());
        }
    }
    
    if ($action === 'update_note') {
        $noteId = intval($_POST['note_id']);
        $title = trim($_POST['title']);
        $content = $_POST['content']; // HTML / raw text format
        $category = trim($_POST['category']);
        
        try {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT user_id FROM notes WHERE id = ?");
            $stmt->execute([$noteId]);
            if ($stmt->fetchColumn() == $userId) {
                $update = $pdo->prepare("UPDATE notes SET title = ?, content = ?, category = ? WHERE id = ?");
                $update->execute([$title, $content, $category, $noteId]);
                setFlash('success', 'Note saved successfully.');
            } else {
                setFlash('error', 'Access Denied: You do not own this note.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error saving note: ' . $e->getMessage());
        }
        header("Location: notes.php?note_id=$noteId");
        exit();
    }
    
    if ($action === 'delete_note') {
        $noteId = intval($_POST['note_id']);
        try {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT user_id FROM notes WHERE id = ?");
            $stmt->execute([$noteId]);
            if ($stmt->fetchColumn() == $userId) {
                $delete = $pdo->prepare("DELETE FROM notes WHERE id = ?");
                $delete->execute([$noteId]);
                setFlash('success', 'Note deleted successfully.');
            } else {
                setFlash('error', 'Access Denied: You cannot delete this note.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error deleting note: ' . $e->getMessage());
        }
        header("Location: notes.php");
        exit();
    }
}

// Fetch all notes for active user
$stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$userId]);
$notes = $stmt->fetchAll();

// Determine active note to display in editor
$activeNoteId = isset($_GET['note_id']) ? intval($_GET['note_id']) : 0;
$activeNote = null;
if ($activeNoteId > 0) {
    foreach ($notes as $n) {
        if ($n['id'] === $activeNoteId) {
            $activeNote = $n;
            break;
        }
    }
}

// Default to most recent note if exists and no ID selected
if ($activeNote === null && !empty($notes)) {
    $activeNote = $notes[0];
    $activeNoteId = $activeNote['id'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
    <div>
        <h1>Study Guides & Notes</h1>
        <p>Draft summaries in markdown and preview formatted outcomes side-by-side.</p>
    </div>
    <form action="notes.php" method="POST">
        <input type="hidden" name="action" value="create_note">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Note</button>
    </form>
</div>

<div class="notes-layout">
    <!-- Notes Sidebar list -->
    <div class="notes-sidebar">
        <div class="notes-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Filter notes..." id="globalSearchInput">
        </div>
        
        <div class="notes-list">
            <?php if (empty($notes)): ?>
                <div style="text-align:center; color:var(--text-dark); margin-top:30px;">
                    <i class="fa-solid fa-folder-open" style="font-size:2rem; margin-bottom:10px; display:block;"></i>
                    No notes found.
                </div>
            <?php else: ?>
                <?php foreach ($notes as $n): ?>
                    <?php $isActive = $n['id'] === $activeNoteId; ?>
                    <a href="notes.php?note_id=<?php echo $n['id']; ?>" class="note-item <?php echo $isActive ? 'active' : ''; ?>">
                        <h4><?php echo htmlspecialchars($n['title']); ?></h4>
                        <div class="note-item-meta">
                            <span><?php echo htmlspecialchars($n['category']); ?></span>
                            <span><?php echo date('M d, y', strtotime($n['updated_at'])); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Active Editor Workspace -->
    <div class="notes-workspace">
        <?php if ($activeNote === null): ?>
            <div style="flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; color:var(--text-dark); padding:40px;">
                <i class="fa-solid fa-note-sticky" style="font-size:4rem; margin-bottom:15px; opacity:0.3;"></i>
                <h3>No Note Selected</h3>
                <p>Click "New Note" at the top to draft your first study sheet.</p>
            </div>
        <?php else: ?>
            <form id="noteEditorForm" action="notes.php" method="POST" style="flex:1; display:flex; flex-direction:column; min-height:0;">
                <input type="hidden" name="action" value="update_note">
                <input type="hidden" name="note_id" value="<?php echo $activeNote['id']; ?>">
                
                <!-- Toolbar with Title & Category Inputs -->
                <div class="notes-toolbar">
                    <div style="display:flex; gap:12px; flex:1; max-width:70%;">
                        <input type="text" name="title" class="form-control" style="background:rgba(255,255,255,0.02); border-color:transparent; font-size:1.15rem; font-weight:600; padding:6px 12px; color:#fff;" placeholder="Note Title" value="<?php echo htmlspecialchars($activeNote['title']); ?>" required>
                        
                        <input type="text" name="category" class="form-control" style="background:rgba(255,255,255,0.02); border-color:transparent; font-size:0.9rem; padding:6px 12px; max-width:180px;" placeholder="Category (e.g. Web Dev)" value="<?php echo htmlspecialchars($activeNote['category']); ?>">
                    </div>
                    
                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                        
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $activeNote['id']; ?>)"><i class="fa-solid fa-trash"></i> Delete</button>
                    </div>
                </div>
                
                <!-- Split Editor Pane -->
                <div class="notes-editor-split">
                    <!-- Textarea Editor -->
                    <div class="notes-editor-pane">
                        <textarea name="content" id="markdownInput" placeholder="# Notes header..." required><?php echo htmlspecialchars($activeNote['content']); ?></textarea>
                    </div>
                    
                    <!-- HTML Previewer -->
                    <div class="notes-preview-pane markdown-body" id="markdownPreview">
                        <!-- Preview injected dynamically by JS -->
                    </div>
                </div>
            </form>
            
            <!-- Hidden delete form -->
            <form id="deleteNoteForm" action="notes.php" method="POST" style="display:none;">
                <input type="hidden" name="action" value="delete_note">
                <input type="hidden" name="note_id" id="delete_note_id">
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(noteId) {
    if (confirm("Are you sure you want to permanently delete this note?")) {
        document.getElementById('delete_note_id').value = noteId;
        document.getElementById('deleteNoteForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
