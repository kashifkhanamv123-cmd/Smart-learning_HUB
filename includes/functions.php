<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Resolves asset paths dynamically whether in root or admin/ subfolder
 */
function getAssetPath(string $path) {
    $currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
    if ($currentDir === 'admin') {
        return '../' . $path;
    }
    return $path;
}

/**
 * Sets a flash message to display on the next page load
 */
function setFlash(string $type, string $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Displays and clears the flash message if present
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        $colorMap = [
            'success' => 'rgba(16, 185, 129, 0.15); border-color: #10b981; color: #34d399;',
            'error' => 'rgba(244, 63, 94, 0.15); border-color: #f43f5e; color: #fda4af;',
            'warning' => 'rgba(245, 158, 11, 0.15); border-color: #f59e0b; color: #fcd34d;',
            'info' => 'rgba(59, 130, 246, 0.15); border-color: #3b82f6; color: #93c5fd;'
        ];
        
        $borderColor = isset($colorMap[$flash['type']]) ? $colorMap[$flash['type']] : $colorMap['info'];
        
        echo "<div class='flash-alert' style='padding: 14px 20px; border: 1px solid; border-radius: 12px; margin-bottom: 25px; font-weight: 500; backdrop-filter: blur(10px); animation: fadeIn 0.3s ease-out; $borderColor'>";
        echo htmlspecialchars($flash['message']);
        echo "</div>";
    }
}

/**
 * Automatically seeds default platform data if the database users table is empty
 */
function seedDatabase(\PDO $pdo) {
    if (!$pdo) return;
    
    try {
        // Auto-migration for new profile columns
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('gender', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN gender VARCHAR(50) DEFAULT NULL");
        }
        if (!in_array('country', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN country VARCHAR(100) DEFAULT NULL");
        }
        if (!in_array('profile_picture', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        }
    } catch (Exception $e) {
        error_log("DB Migration Error: " . $e->getMessage());
    }
    
    try {
        // Check if user table is empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return; // Already seeded
        }
        
        // 1. Seed Users
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $studentPass = password_hash('student123', PASSWORD_DEFAULT);
        
        $insertUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $insertUser->execute(['Administrator', 'admin@hub.com', $adminPass, 'admin']);
        $insertUser->execute(['Jane Student', 'student@hub.com', $studentPass, 'student']);
        
        $studentId = $pdo->lastInsertId(); // Jane Student ID
        
        // 2. Seed Courses & Lessons
        $pdo->exec("INSERT INTO courses (title, description, difficulty, duration) VALUES 
            ('Modern Web Development', 'Master HTML5, CSS3, ES6 JavaScript, and responsive layout structures from the ground up.', 'Beginner', '4 hours'),
            ('Python & Artificial Intelligence', 'Dive into core Python programming, data science frameworks, and machine learning structures.', 'Intermediate', '6 hours')");
        
        // Web Dev Lessons
        $webDevId = $pdo->lastInsertId() - 1; // Course 1
        $insertLesson = $pdo->prepare("INSERT INTO lessons (course_id, title, content, sort_order) VALUES (?, ?, ?, ?)");
        
        $insertLesson->execute([$webDevId, 'Introduction to HTML5 Semantic Layouts', "# Introduction to HTML5 Semantic Tags\n\nSemantic HTML markup gives structure and context to search engines and accessibility readers rather than using endless nested `<div>` blocks.\n\n### Core Semantic Elements:\n- `<header>`: The introductory section of a page or component.\n- `<nav>`: Dedicated navigation menus links wrapper.\n- `<main>`: Renders the primary central unique page contents.\n- `<article>`: Represents self-contained, shareable information blocks.\n- `<section>`: Divides page layout into themed content segments.\n- `<footer>`: Closing panel with author info, copyright dates, and links.\n\n### Example Layout Structure:\n```html\n<body>\n  <header>\n    <h1>Coding Academy</h1>\n    <nav>...</nav>\n  </header>\n  <main>\n    <article>\n      <h2>CSS Grid Mastery</h2>\n      <p>Content goes here...</p>\n    </article>\n  </main>\n  <footer>© 2026 academy. All rights reserved.</footer>\n</body>\n```", 1]);
        
        $insertLesson->execute([$webDevId, 'CSS Flexbox & CSS Grid Systems', "# CSS Flexbox and CSS Grid\n\nCSS modern alignments provide flexible layouts without floating variables.\n\n### CSS Flexbox (One-Dimensional)\nExcellent for alignment along columns OR rows. Ideal for sidebars, cards headers, and lists.\n\n```css\n.container {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n}\n```\n\n### CSS Grid (Two-Dimensional)\nDesigned for complete layout matrix blocks, handling rows and columns simultaneously. Perfect for dashboard dashboards.\n\n```css\n.grid-container {\n  display: grid;\n  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));\n  gap: 20px;\n}\n```", 2]);
        
        $insertLesson->execute([$webDevId, 'Asynchronous Javascript Operations', "# Asynchronous JavaScript\n\nLearn how code runs non-blockingly using promises, callbacks, and remote fetch queries.\n\n### Promising Mechanics:\nPromises represents future completions. They start as `pending`, resolving to `fulfilled` or rejecting to `rejected`.\n\n### Modern Async/Await syntax:\n```javascript\nasync function fetchUserData(userId) {\n  try {\n    const response = await fetch(`https://api.example.com/users/\${userId}`);\n    if (!response.ok) throw new Error('Network error');\n    const data = await response.json();\n    console.log(data);\n  } catch (error) {\n    console.error('Error fetching:', error);\n  }\n}\n```", 3]);

        // Python Lessons
        $pythonId = $webDevId + 1; // Course 2
        $insertLesson->execute([$pythonId, 'Python Basics: Loops, Variables & Functions', "# Python Language Foundations\n\nPython is a high-level dynamically-typed programming language known for clean readability.\n\n### Syntactic Foundations:\n```python\n# Variables\nusername = \"Jane\"\nscore = 95.5\n\n# Dynamic Functions\ndef greet_user(name):\n    return f\"Hello, {name}! Welcome to the Academy.\"\n\n# Conditionals\nif score >= 90:\n    print(greet_user(username)) \n```\n\n### Looping Lists:\n```python\nskills = ['HTML', 'CSS', 'Python']\nfor skill in skills:\n    print(f\"Mastering: {skill}\")\n```", 1]);
        
        $insertLesson->execute([$pythonId, 'Data Wrangling with NumPy and Pandas', "# Data manipulation using Python Libraries\n\nData analysis requires high-performance array arithmetic and clean query formats.\n\n### NumPy Arrays\nNumPy creates multidimensional grid arrays containing single types with speed vector operations.\n```python\nimport numpy as np\narr = np.array([1, 2, 3, 4, 5])\nprint(arr * 2) # Outputs: [2, 4, 6, 8, 10]\n```\n\n### Pandas DataFrames\nPandas loads data as structured tabular spreadsheets, enabling SQL-like manipulations.\n```python\nimport pandas as pd\ndata = {\n    'Student': ['Jane', 'Alex', 'Sarah'],\n    'Score': [95, 88, 91]\n}\ndf = pd.DataFrame(data)\nprint(df[df['Score'] > 90])\n```", 2]);

        // 3. Seed Flashcard Decks
        $pdo->exec("INSERT INTO flashcard_decks (user_id, title, description) VALUES 
            (NULL, 'JavaScript Advanced Core', 'Master closures, scope engines, Prototypes, and asynchronous tasks.'),
            (NULL, 'CSS Grid & Flexbox Terminology', 'Test layouts definitions, grid columns properties, and alignments.')");
        
        $jsDeckId = $pdo->lastInsertId() - 1;
        $cssDeckId = $jsDeckId + 1;
        
        $insertCard = $pdo->prepare("INSERT INTO flashcards (deck_id, front, back) VALUES (?, ?, ?)");
        
        // JS Deck Cards
        $insertCard->execute([$jsDeckId, 'What is a Closure in JavaScript?', 'A closure is the combination of a function bundled together (enclosed) with references to its surrounding state (the lexical environment). It allows inner functions to read outer scope variables even after outer execution ends.']);
        $insertCard->execute([$jsDeckId, 'What is the main difference between == and ===?', '== compares values for equivalence after resolving type coercion (translating variables to common types first). === is strict: it checks values and data types without any automatic conversion.']);
        $insertCard->execute([$jsDeckId, 'What is Prototype Inheritance?', 'Every JavaScript object contains an internal pointer linking to another object called its prototype. Objects inherit methods and properties from their chain of prototypes, ending in null.']);
        
        // CSS Deck Cards
        $insertCard->execute([$cssDeckId, 'What is a CSS Grid Track?', 'A grid track is the spatial region residing between two adjacent grid lines. Essentially, any column or row inside the grid boundary.']);
        $insertCard->execute([$cssDeckId, 'How do you align items along both axes in CSS Grid?', 'Apply the shortcut property "place-items: center;" directly on the parent grid container element. It aligns elements vertically and horizontally.']);
        
        // 4. Seed Quizzes
        $pdo->exec("INSERT INTO quizzes (title, description, duration_mins) VALUES 
            ('Web Core Technologies Quiz', 'Validate HTML5 structure layout, CSS positioning, and JS string objects properties.', 10),
            ('Python & ML Quick Check', 'Brief evaluation of lists, numpy math array structures, and simple data frames.', 15)");
        
        $webQuizId = $pdo->lastInsertId() - 1;
        $pyQuizId = $webQuizId + 1;
        
        $insertQuestion = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Web Quiz Qs
        $insertQuestion->execute([$webQuizId, 'Which HTML5 element represents self-contained, independent article content?', '<section>', '<article>', '<div>', '<aside>', 'B', 'The <article> tag is reserved for independent, reusable content blocks that can stand alone (such as blog posts, user comments, or widgets).']);
        $insertQuestion->execute([$webQuizId, 'What is the default positioning property for HTML elements in CSS?', 'relative', 'absolute', 'static', 'fixed', 'C', 'HTML elements are positioned statically by default. Static elements follow the standard layout flow, ignoring top/right/bottom/left rules.']);
        $insertQuestion->execute([$webQuizId, 'Which built-in function converts JSON text back to a standard JavaScript object?', 'JSON.stringify()', 'JSON.parse()', 'JSON.toObject()', 'Object.parse()', 'B', 'JSON.parse() processes a JSON format string and constructs corresponding JS arrays, objects, or scalar structures.']);
        
        // Python Quiz Qs
        $insertQuestion->execute([$pyQuizId, 'What is the output of the Python expression: [1, 2] * 2 ?', '[2, 4]', '[1, 2, 1, 2]', 'Error: Invalid Operator', '[[1, 2], [1, 2]]', 'B', 'In Python, multiplying a list duplicates its elements. It appends the elements repeatedly inside the list rather than running mathematical vector array multiplication.']);
        $insertQuestion->execute([$pyQuizId, 'Which Pandas function loads standard comma-separated text databases into DataFrames?', 'pd.read_table()', 'pd.open_csv()', 'pd.read_csv()', 'pd.load_csv()', 'C', 'pd.read_csv() reads standard flat CSV logs directly and parses them into high-performance DataFrames.']);
        
        // 5. Seed default student Note
        $pdo->exec("INSERT INTO notes (user_id, title, content, category) VALUES 
            ($studentId, 'HTML5 & CSS Layout Reference Sheet', '# Web Development Checklist\n\n- Write `<header>`, `<nav>`, `<main>`, `<article>`, and `<footer>` for layout semantics.\n- Create flexible CSS layouts via grid models:\n```css\n.parent {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 16px;\n}\n```\n- Use relative fonts `rem` or `em` for accessible fluid layouts.\n- Standardize responsive designs with viewport query tags.', 'Web Development')");
            
        // 6. Seed mock study sessions for the dashboard chart (last 5 days)
        $insertSession = $pdo->prepare("INSERT INTO study_sessions (user_id, session_date, duration_seconds) VALUES (?, ?, ?)");
        $minutesArr = [30, 60, 45, 75, 50];
        for ($i = 4; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $minutes = $minutesArr[4 - $i];
            $insertSession->execute([$studentId, $date, $minutes * 60]);
        }
    } catch (Exception $e) {
        // Log locally, fail gracefully to prevent blocking
        error_log("Database Seeding Error: " . $e->getMessage());
    }
}

/**
 * Verifies the Google reCAPTCHA response.
 */
function verifyRecaptcha(string $recaptchaResponse) {
    if (empty($recaptchaResponse)) {
        return false;
    }
    
    $secretKey = getenv('RECAPTCHA_SECRET_KEY');
    if (!$secretKey) {
        // Bypass if no key is configured
        return true; 
    }
    
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = @file_get_contents($verifyUrl, false, $context);
    
    if ($result === false) {
        return false;
    }
    
    $responseData = json_decode($result, true);
    return $responseData['success'] ?? false;
}
/**
 * Generates a CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token
 */
function verifyCsrfToken(string $token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Escapes HTML for XSS prevention
 */
function e(string $string = '') {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if a username contains restricted words
 */
function isUsernameAllowed(string $username) {
    $restricted_names = ['admin', 'administrator', 'system admin', 'sysadmin', 'root', 'moderator', 'system'];
    $name_lower = strtolower(trim($username));
    foreach ($restricted_names as $restricted) {
        if (strpos($name_lower, $restricted) !== false) {
            return false;
        }
    }
    return true;
}

/**
 * Validates that a name starts with an alphabet and contains only alphabets, numbers, and spaces.
 */
function isValidName(string $name) {
    return preg_match('/^[a-zA-Z][a-zA-Z0-9 ]*$/', $name) === 1;
}

/**
 * Validates email format strictly
 */
function isValidEmail(string $email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>
