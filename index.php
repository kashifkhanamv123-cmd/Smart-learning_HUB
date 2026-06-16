<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Learning Hub</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0b0f19 0%, #111827 50%, #1e1b4b 100%);
            color: #f8fafc;
            margin: 0;
            overflow-x: hidden;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }
        
        .brand i {
            color: #6366f1;
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 20px 50px;
            position: relative;
        }
        
        .hero-content {
            max-width: 800px;
            z-index: 10;
        }

        .hero h1 {
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
        }

        .hero p {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-large {
            padding: 15px 35px;
            font-size: 1.1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: #f8fafc;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-outline:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.4);
            transform: translateY(-2px);
        }

        .features-section {
            padding: 80px 5%;
            background: rgba(17, 24, 39, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            font-weight: 700;
            color: #fff;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center;
            transition: transform 0.3s, background 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(30, 41, 59, 0.8);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: rgba(99, 102, 241, 0.1);
            color: #818cf8;
            font-size: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #fff;
        }

        .feature-card p {
            color: #94a3b8;
            line-height: 1.6;
            font-size: 1rem;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <i class="fa-solid fa-graduation-cap"></i>
            SmartLearn
        </div>
        <div class="nav-links">
            <a href="login.php" class="btn-outline btn-large" style="padding: 8px 20px; font-size: 1rem;">Log In</a>
            <a href="signup.php" class="btn-primary btn-large" style="padding: 8px 20px; font-size: 1rem;">Sign Up</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Unlock Your Potential with Smart Learning</h1>
            <p>Your all-in-one platform for courses, adaptive flashcards, and an AI-powered assistant designed to accelerate your study habits and build lasting knowledge.</p>
            <div class="hero-buttons">
                <a href="signup.php" class="btn-primary btn-large">Get Started for Free</a>
                <a href="#features" class="btn-outline btn-large">Explore Features</a>
            </div>
        </div>
    </section>

    <section id="features" class="features-section">
        <h2 class="section-title">Everything you need to excel</h2>
        <div class="features-grid">
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <h3>Structured Courses</h3>
                <p>Access high-quality, structured learning paths created by experts. Track your progress seamlessly across all your devices.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-clone"></i>
                </div>
                <h3>Adaptive Flashcards</h3>
                <p>Memorize faster using spaced repetition. Create your own decks or study from community-curated materials to ace your exams.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h3>AI Assistant</h3>
                <p>Get stuck? Ask the built-in AI tutor to explain complex topics, review your notes, or quiz you on the fly.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa-solid fa-stopwatch"></i>
                </div>
                <h3>Pomodoro Timer</h3>
                <p>Stay hyper-focused with an integrated study timer. Automatically track your active study hours and visualize your weekly activity.</p>
            </div>
            
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Smart Learning Hub. All rights reserved.</p>
    </footer>

</body>
</html>
