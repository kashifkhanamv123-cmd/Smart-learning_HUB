<?php
// Function to load environment variables from .env file
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Strip optional quotes around value
                if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Load the .env configuration in project root
loadEnv(__DIR__ . '/.env');

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'smart_learning_hub';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';

$pdo = null;
$db_error = null;

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// Helper to check the database connection and show setup utility page if failing
function checkDbConnection() {
    global $pdo, $db_error, $db_host, $db_name;
    if ($pdo === null) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Required - Smart Learning Hub</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --bg-gradient: linear-gradient(135deg, #0b0f19 0%, #111827 50%, #1e1b4b 100%);
                    --primary: #6366f1;
                    --text: #f8fafc;
                    --text-muted: #94a3b8;
                    --glass-bg: rgba(17, 24, 39, 0.7);
                    --glass-border: rgba(255, 255, 255, 0.08);
                    --danger: #f43f5e;
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Outfit', sans-serif;
                    background: var(--bg-gradient);
                    color: var(--text);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .container {
                    background: var(--glass-bg);
                    border: 1px solid var(--glass-border);
                    backdrop-filter: blur(20px);
                    border-radius: 24px;
                    padding: 40px;
                    max-width: 600px;
                    width: 100%;
                    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
                    text-align: center;
                }
                .icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                    display: inline-block;
                    animation: pulse 2s infinite ease-in-out;
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                h1 {
                    font-size: 2rem;
                    margin-bottom: 15px;
                    color: #fff;
                    font-weight: 700;
                }
                p {
                    color: var(--text-muted);
                    font-size: 1.05rem;
                    margin-bottom: 25px;
                    line-height: 1.6;
                }
                .error-box {
                    background: rgba(244, 63, 94, 0.1);
                    border-left: 4px solid var(--danger);
                    color: #fda4af;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: left;
                    font-family: monospace;
                    font-size: 0.9rem;
                    margin-bottom: 25px;
                    word-break: break-all;
                }
                .instructions {
                    text-align: left;
                    margin-bottom: 30px;
                }
                .instructions h3 {
                    margin-bottom: 12px;
                    font-size: 1.15rem;
                    color: #fff;
                }
                .instructions ol {
                    margin-left: 20px;
                    color: var(--text-muted);
                    line-height: 1.8;
                }
                .btn {
                    display: inline-block;
                    background: var(--primary);
                    color: white;
                    text-decoration: none;
                    padding: 12px 28px;
                    border-radius: 12px;
                    font-weight: 600;
                    transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
                    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
                }
                .btn:hover {
                    background: #4f46e5;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">⚡</div>
                <h1>Database Connection Required</h1>
                <p>We could not establish a connection to your MySQL database. Verify configuration settings in your <code>.env</code> file.</p>
                
                <div class="error-box">
                    <strong>MySQL Error:</strong> <?php echo htmlspecialchars($db_error); ?>
                </div>

                <div class="instructions">
                    <h3>Setup Checklist:</h3>
                    <ol>
                        <li>Ensure your local MySQL service is running (XAMPP, WAMP, Docker, etc.).</li>
                        <li>Open the file <code>.env</code> in your project folder.</li>
                        <li>Configure <code>DB_HOST</code>, <code>DB_USER</code>, and <code>DB_PASSWORD</code>.</li>
                        <li>Create a database named <code><?php echo htmlspecialchars($db_name); ?></code>.</li>
                        <li>Import <code>schema.sql</code> to create all required tables and default credentials.</li>
                        <li>Refresh this webpage.</li>
                    </ol>
                </div>
                
                <a href="" class="btn">Check Connection Again</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
