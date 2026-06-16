<?php
$pageTitle = 'Flashcards';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];
$deckId = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;

// Handle deck creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_deck') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title)) {
        try {
            $insert = $pdo->prepare("INSERT INTO flashcard_decks (user_id, title, description) VALUES (?, ?, ?)");
            $insert->execute([$userId, $title, $description]);
            setFlash('success', 'Custom deck created successfully! Now add some cards to it.');
        } catch (PDOException $e) {
            setFlash('error', 'Database error creating deck: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Please provide a deck title.');
    }
    header("Location: flashcards.php");
    exit();
}

// Handle card addition to custom deck
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_card') {
    $cardDeckId = intval($_POST['deck_id']);
    $front = trim($_POST['front']);
    $back = trim($_POST['back']);
    
    if ($cardDeckId > 0 && !empty($front) && !empty($back)) {
        try {
            // Verify deck belongs to user
            $stmt = $pdo->prepare("SELECT user_id FROM flashcard_decks WHERE id = ?");
            $stmt->execute([$cardDeckId]);
            $ownerId = $stmt->fetchColumn();
            
            if ($ownerId == $userId || $_SESSION['user_role'] === 'admin') {
                $insert = $pdo->prepare("INSERT INTO flashcards (deck_id, front, back) VALUES (?, ?, ?)");
                $insert->execute([$cardDeckId, $front, $back]);
                setFlash('success', 'Card added successfully!');
            } else {
                setFlash('error', 'Access Denied: You do not own this deck.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Database error creating card: ' . $e->getMessage());
        }
    } else {
        setFlash('error', 'Please fill in both front and back card texts.');
    }
    header("Location: flashcards.php");
    exit();
}

// Handle deck deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_deck') {
    $deleteDeckId = intval($_POST['deck_id']);
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM flashcard_decks WHERE id = ?");
        $stmt->execute([$deleteDeckId]);
        $ownerId = $stmt->fetchColumn();
        
        if ($ownerId == $userId || $_SESSION['user_role'] === 'admin') {
            $delete = $pdo->prepare("DELETE FROM flashcard_decks WHERE id = ?");
            $delete->execute([$deleteDeckId]);
            setFlash('success', 'Deck deleted successfully.');
        } else {
            setFlash('error', 'Access Denied: You cannot delete this deck.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database error deleting deck: ' . $e->getMessage());
    }
    header("Location: flashcards.php");
    exit();
}

// --------------------------------------------------------------------------
// View State 1: Study Session View
// --------------------------------------------------------------------------
if ($deckId > 0):
    // Fetch deck details
    $stmt = $pdo->prepare("SELECT * FROM flashcard_decks WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
    $stmt->execute([$deckId, $userId]);
    $deck = $stmt->fetch();
    
    if (!$deck) {
        setFlash('error', 'Requested flashcard deck not found.');
        header("Location: flashcards.php");
        exit();
    }
    
    // Fetch cards in deck
    $stmt = $pdo->prepare("SELECT * FROM flashcards WHERE deck_id = ? ORDER BY id ASC");
    $stmt->execute([$deckId]);
    $cards = $stmt->fetchAll();
    ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="flashcards.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Decks</a>
        <h2 style="color:#fff;"><?php echo htmlspecialchars($deck['title']); ?> Study Room</h2>
    </div>
    
    <div class="study-container">
        <?php if (empty($cards)): ?>
            <div class="glass-card" style="padding: 40px;">
                <i class="fa-regular fa-clone" style="font-size:3rem; color:var(--text-dark); margin-bottom:15px; display:block;"></i>
                <h3 style="color:#fff; margin-bottom:10px;">This Deck is Empty</h3>
                <p style="color:var(--text-muted); margin-bottom:20px;">Add cards to study this deck.</p>
                <?php if ($deck['user_id'] == $userId || $_SESSION['user_role'] === 'admin'): ?>
                    <button class="btn btn-primary btn-sm" onclick="openAddCardModal(<?php echo $deckId; ?>, '<?php echo htmlspecialchars($deck['title']); ?>')"><i class="fa-solid fa-plus"></i> Add Card</button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Study Session Statistics -->
            <div class="study-progress">
                <div class="study-progress-bar" id="studyProgressBar"></div>
            </div>
            
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-muted); margin-bottom:15px;">
                <span id="studyProgressText">Card 1 of <?php echo count($cards); ?></span>
                <span id="sessionScoreText">Mastery: 0%</span>
            </div>
            
            <!-- 3D Flip Card View -->
            <div class="card-view" id="flashCardView">
                <div class="card-3d">
                    <!-- Front face -->
                    <div class="card-face card-front">
                        <span style="position:absolute; top:20px; left:20px; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--primary); letter-spacing:0.05em;">Question</span>
                        <h3 id="cardFrontText">Front text loading...</h3>
                        <div class="card-hint"><i class="fa-solid fa-rotate"></i> Click to Flip</div>
                    </div>
                    <!-- Back face -->
                    <div class="card-face card-back">
                        <span style="position:absolute; top:20px; left:20px; font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--warning); letter-spacing:0.05em;">Answer</span>
                        <p id="cardBackText">Back text loading...</p>
                        <div class="card-hint"><i class="fa-solid fa-rotate"></i> Click to Flip</div>
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Controls (shown after flipped) -->
            <div class="study-controls" id="cardControls" style="opacity: 0.5; pointer-events: none; transition: opacity 0.2s;">
                <button class="btn btn-danger" id="difficultyHardBtn"><i class="fa-regular fa-face-frown"></i> Hard</button>
                <button class="btn btn-secondary" id="difficultyMediumBtn" style="color:var(--warning);"><i class="fa-regular fa-face-meh"></i> Medium</button>
                <button class="btn btn-primary" id="difficultyEasyBtn" style="background:var(--success);"><i class="fa-regular fa-face-smile"></i> Easy</button>
            </div>
            
            <!-- Finished screen mockup (hidden initially) -->
            <div class="glass-card" id="studyFinishCard" style="display:none; padding:40px; animation:fadeIn 0.3s ease-out;">
                <i class="fa-solid fa-trophy" style="font-size:3.5rem; color:var(--warning); margin-bottom:15px; display:block; animation:bounce 2s infinite;"></i>
                <h3 style="color:#fff; margin-bottom:10px;">Session Completed!</h3>
                <p style="color:var(--text-muted); margin-bottom:20px;">You reviewed <?php echo count($cards); ?> flashcards this session.</p>
                <div style="display:flex; justify-content:center; gap:12px;">
                    <button class="btn btn-secondary" onclick="window.location.reload()"><i class="fa-solid fa-arrows-rotate"></i> Study Again</button>
                    <a href="flashcards.php" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Finish</a>
                </div>
            </div>
            
            <!-- Safe store cards data in script context -->
            <script id="cardsData" type="application/json">
                <?php echo json_encode($cards); ?>
            </script>
        <?php endif; ?>
    </div>

<?php else: 
    // View State 2: Decks Catalogue View
    $stmt = $pdo->prepare("SELECT * FROM flashcard_decks WHERE user_id IS NULL OR user_id = ? ORDER BY user_id DESC, id ASC");
    $stmt->execute([$userId]);
    $decks = $stmt->fetchAll();
    ?>
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px;">
        <div>
            <h1>Study Decks</h1>
            <p>Master complex terminology and questions using spaced repetition flashcards.</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="openDeckModal()"><i class="fa-solid fa-plus"></i> Create Deck</button>
        </div>
    </div>
    
    <div class="deck-grid">
        <?php if (empty($decks)): ?>
            <div class="glass-card" style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--text-dark);">
                <i class="fa-regular fa-clone" style="font-size:3rem; margin-bottom:15px;"></i>
                <p>No study decks available. Click "Create Deck" to compile your first study list!</p>
            </div>
        <?php else: ?>
            <?php foreach ($decks as $d): ?>
                <?php 
                // Count cards in deck
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM flashcards WHERE deck_id = ?");
                $stmt->execute([$d['id']]);
                $cardCount = $stmt->fetchColumn();
                $isCustom = !is_null($d['user_id']);
                ?>
                <div class="glass-card deck-card" style="display:flex; flex-direction:column; height:100%;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <span class="meta-badge <?php echo $isCustom ? 'role-student' : 'role-admin'; ?>" style="font-size:0.75rem; font-weight:600; padding:2px 8px; border-radius:10px;">
                            <?php echo $isCustom ? 'Custom' : 'System'; ?>
                        </span>
                        <span style="font-size:0.8rem; color:var(--text-dark); font-weight:600;"><?php echo $cardCount; ?> cards</span>
                    </div>
                    
                    <h3><?php echo htmlspecialchars($d['title']); ?></h3>
                    <p><?php echo htmlspecialchars($d['description']); ?></p>
                    
                    <div style="display:flex; gap:10px; margin-top:auto; width:100%;">
                        <a href="flashcards.php?deck_id=<?php echo $d['id']; ?>" class="btn btn-primary btn-sm" style="flex:1;">
                            <i class="fa-solid fa-play"></i> Study
                        </a>
                        
                        <?php if ($isCustom || $_SESSION['user_role'] === 'admin'): ?>
                            <button class="btn btn-secondary btn-sm" onclick="openAddCardModal(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars(addslashes($d['title'])); ?>')" title="Add Cards">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            
                            <form action="flashcards.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this entire deck and all its cards?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_deck">
                                <input type="hidden" name="deck_id" value="<?php echo $d['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 6px 10px;" title="Delete Deck">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal 1: Create Deck Overlay -->
    <div class="modal-overlay" id="createDeckModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Create Study Deck</h3>
                <i class="fa-solid fa-xmark modal-close" onclick="closeDeckModal()"></i>
            </div>
            <form action="flashcards.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_deck">
                    <div class="form-group">
                        <label for="deck_title">Deck Title</label>
                        <input type="text" name="title" id="deck_title" class="form-control" placeholder="e.g. JavaScript Frameworks" required>
                    </div>
                    <div class="form-group">
                        <label for="deck_desc">Description</label>
                        <input type="text" name="description" id="deck_desc" class="form-control" placeholder="e.g. React lifecycle hook terminology">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeckModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Deck</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal 2: Add Card Overlay -->
    <div class="modal-overlay" id="addCardModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3 id="addCardModalTitle">Add Card</h3>
                <i class="fa-solid fa-xmark modal-close" onclick="closeAddCardModal()"></i>
            </div>
            <form action="flashcards.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_card">
                    <input type="hidden" name="deck_id" id="add_card_deck_id">
                    
                    <div class="form-group">
                        <label for="card_front">Front Side (Question / Term)</label>
                        <textarea name="front" id="card_front" class="form-control" rows="3" placeholder="e.g. What is JSX?" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="card_back">Back Side (Answer / Definition)</label>
                        <textarea name="back" id="card_back" class="form-control" rows="4" placeholder="e.g. An XML-like syntax extension used in React..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddCardModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Card</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal script helpers -->
    <script>
    function openDeckModal() {
        document.getElementById('createDeckModal').classList.add('active');
    }
    function closeDeckModal() {
        document.getElementById('createDeckModal').classList.remove('active');
    }
    function openAddCardModal(deckId, title) {
        document.getElementById('add_card_deck_id').value = deckId;
        document.getElementById('addCardModalTitle').textContent = 'Add Card to "' + title + '"';
        document.getElementById('addCardModal').classList.add('active');
    }
    function closeAddCardModal() {
        document.getElementById('addCardModal').classList.remove('active');
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
