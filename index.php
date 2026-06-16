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
            scroll-behavior: smooth;
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
            text-decoration: none;
        }
        
        .brand i {
            color: #6366f1;
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .nav-item {
            color: #e2e8f0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-item:hover {
            color: #818cf8;
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
            border: none;
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

        /* Features Section */
        .features-section {
            padding: 100px 5%;
            background: rgba(17, 24, 39, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            color: #fff;
        }
        
        .section-subtitle {
            text-align: center;
            color: #94a3b8;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 60px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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

        /* About Section */
        .about-section {
            padding: 100px 5%;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .about-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #fff;
        }
        
        .about-content p {
            font-size: 1.15rem;
            color: #94a3b8;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .about-visual {
            position: relative;
        }
        
        .about-visual-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(192, 132, 252, 0.2));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .about-visual-box i {
            font-size: 8rem;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .about-blob {
            position: absolute;
            top: -20px;
            right: -20px;
            width: 100%;
            height: 100%;
            background: #4f46e5;
            border-radius: 24px;
            filter: blur(40px);
            opacity: 0.3;
            z-index: 1;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 100px 5%;
            background: rgba(17, 24, 39, 0.4);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .steps-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            position: relative;
        }
        
        .steps-container::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: rgba(255,255,255,0.1);
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #6366f1;
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }
        
        .step h3 {
            color: #fff;
            margin-bottom: 10px;
        }
        
        .step p {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Premium Footer */
        .premium-footer {
            background: #0b0f19;
            padding: 80px 5% 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .footer-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .footer-brand h2 {
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .footer-brand i { color: #6366f1; }
        .footer-brand p { color: #94a3b8; line-height: 1.6; margin-bottom: 20px; }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #6366f1;
            transform: translateY(-3px);
        }
        
        .footer-links h4 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li { margin-bottom: 12px; }
        
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover { color: #818cf8; }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 992px) {
            .about-section { grid-template-columns: 1fr; text-align: center; }
            .steps-container { grid-template-columns: 1fr 1fr; gap: 40px; }
            .steps-container::before { display: none; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero-buttons { flex-direction: column; }
            .nav-links .nav-item { display: none; }
            .steps-container { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .social-links { justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="#" class="brand">
            <i class="fa-solid fa-graduation-cap"></i>
            SmartLearn
        </a>
        <div class="nav-links">
            <a href="#about" class="nav-item">About</a>
            <a href="#features" class="nav-item">Features</a>
            <a href="#how-it-works" class="nav-item">How it Works</a>
            <div style="width: 1px; height: 24px; background: rgba(255,255,255,0.2); margin: 0 10px;"></div>
            <a href="login.php" class="btn-outline btn-large" style="padding: 8px 20px; font-size: 1rem;">Log In</a>
            <a href="signup.php" class="btn-primary btn-large" style="padding: 8px 20px; font-size: 1rem;">Sign Up</a>
        </div>
    </nav>

    <!-- Hero Section -->
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
    
    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="about-content">
            <h2>Transforming the way you study.</h2>
            <p>At Smart Learning Hub, we believe education shouldn't be a struggle. We built this platform to unify the best studying techniques—like spaced repetition and structured pathways—under one beautiful, distraction-free environment.</p>
            <p>Our mission is to empower students and professionals globally. By integrating AI-powered tutoring directly into your study flow, we make mastering complex subjects faster, easier, and much more engaging.</p>
            <a href="signup.php" class="btn-outline btn-large" style="margin-top: 15px;">Join the Community</a>
        </div>
        <div class="about-visual">
            <div class="about-blob"></div>
            <div class="about-visual-box">
                <i class="fa-solid fa-brain"></i>
                <h3 style="color: #fff; margin-top: 20px; font-size: 1.5rem;">Study Smarter, Not Harder</h3>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <h2 class="section-title">Everything you need to excel</h2>
        <p class="section-subtitle">A comprehensive toolkit designed specifically to help you retain information longer and perform better on your exams.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-book-open"></i></div>
                <h3>Structured Courses</h3>
                <p>Access high-quality, structured learning paths. Track your progress seamlessly across all your devices.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-clone"></i></div>
                <h3>Adaptive Flashcards</h3>
                <p>Memorize faster using spaced repetition. Create your own decks to ace your upcoming exams.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-robot"></i></div>
                <h3>AI Assistant</h3>
                <p>Get stuck? Ask the built-in AI tutor to explain complex topics, review your notes, or quiz you on the fly.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-stopwatch"></i></div>
                <h3>Pomodoro Timer</h3>
                <p>Stay hyper-focused with an integrated study timer. Automatically track your active study hours.</p>
            </div>
        </div>
    </section>
    
    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <h2 class="section-title">How It Works</h2>
        <p class="section-subtitle">Four simple steps to elevate your academic performance.</p>
        
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create Account</h3>
                <p>Sign up in seconds and access your personalized learning dashboard.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Pick a Course</h3>
                <p>Browse our extensive library and enroll in topics that interest you.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Study & Practice</h3>
                <p>Use flashcards, take quizzes, and chat with the AI to reinforce concepts.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Track Growth</h3>
                <p>Watch your average scores and study hours grow in real-time analytics.</p>
            </div>
        </div>
    </section>

    <!-- Premium Footer -->
    <footer class="premium-footer">
        <div class="footer-grid">
            
            <div class="footer-brand">
                <h2><i class="fa-solid fa-graduation-cap"></i> SmartLearn</h2>
                <p>Your ultimate companion for structured learning, spaced repetition, and AI-driven studying. Accelerate your knowledge acquisition today.</p>
                <div class="social-links">
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-github"></i></a>
                    <a href="#"><i class="fa-brands fa-discord"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Platform</h4>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How it Works</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#">Pricing (Coming Soon)</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Community Forum</a></li>
                    <li><a href="#">Study Tips Blog</a></li>
                    <li><a href="#">API Documentation</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Smart Learning Hub. All rights reserved. Built for students, by students.</p>
        </div>
    </footer>

</body>
</html>
