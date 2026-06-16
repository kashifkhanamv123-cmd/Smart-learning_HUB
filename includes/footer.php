            </main>
            
            <footer class="app-footer">
                <p>&copy; <?php echo date('Y'); ?> Smart Learning Hub. Hosted on InfinityFree.</p>
            </footer>
        </div>
    </div>

    <!-- Core Interactions Script -->
    <script src="<?php echo getAssetPath('js/main.js'); ?>"></script>
    
    <!-- Page Specific Scripts Loading -->
    <?php
    $currentFile = basename($_SERVER['SCRIPT_NAME']);
    $currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
    
    if ($currentDir === 'admin') {
        echo '<script src="' . getAssetPath('js/admin.js') . '"></script>';
    } else {
        switch ($currentFile) {
            case 'timer.php':
                echo '<script src="' . getAssetPath('js/timer.js') . '"></script>';
                break;
            case 'flashcards.php':
                echo '<script src="' . getAssetPath('js/flashcards.js') . '"></script>';
                break;
            case 'notes.php':
                echo '<script src="' . getAssetPath('js/notes.js') . '"></script>';
                break;
            case 'quiz.php':
                echo '<script src="' . getAssetPath('js/quiz.js') . '"></script>';
                break;
        }
    }
    ?>
</body>
</html>
