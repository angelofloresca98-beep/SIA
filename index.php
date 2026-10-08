<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriBayan | Local Farms, Bigger Dreams</title>
    <meta name="description" content="AgriBayan connects farmers and buyers in one platform, making it easier to sell, buy, and support local agricultural products.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Caveat:wght@600&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary: #154c34;
            --primary-hover: #103b28;
            --accent: #d3eadc;
            --accent-soft: #edf5f0;
            --bg: #f8fbf9;
            --text-dark: #11231a;
            --text-muted: #56695f;
            --border: #e2ede6;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(21, 76, 52, 0.04);
            --shadow-md: 0 8px 24px rgba(21, 76, 52, 0.08);
            --radius-md: 12px;
            --radius-lg: 20px;
            --radius-pill: 99px;
            
            --cat-1: #f2e7d5;
            --cat-2: #e3f0e5;
            --cat-3: #fcf1d8;
            --cat-4: #f7e6e5;
            --cat-5: #f2efe9;
            --cat-6: #eef3e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--bg);
            color: var(--text-dark);
            line-height: 1.5;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1.5px solid transparent;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(21, 76, 52, 0.2);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--accent-soft);
        }

        .btn-white {
            background-color: var(--white);
            color: var(--primary);
            border-color: var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-white:hover {
            border-color: var(--primary);
        }

        /* Navbar */
        .navbar {
            background-color: var(--white);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            font-size: 28px;
            color: var(--primary);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-text strong {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .brand-text small {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-links a {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-muted);
            position: relative;
            padding: 8px 0;
            transition: color 0.2s;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
            border-radius: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-icon {
            color: var(--text-dark);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
        }
        
        .search-icon:hover {
            color: var(--primary);
        }

        /* Hero Section */
        .hero {
            position: relative;
            padding: 80px 0 120px;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 65%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1595841696650-6edbf679bebe?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            mask-image: linear-gradient(to right, transparent, black 40%);
            -webkit-mask-image: linear-gradient(to right, transparent, black 40%);
            z-index: -1;
            border-radius: 0 0 0 100px;
        }

        .hero .container {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
        }

        .hero-content {
            max-width: 600px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--accent-soft);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: var(--radius-pill);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .hero p {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 32px;
            max-width: 480px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .script-text {
            font-family: 'Caveat', cursive;
            font-size: 32px;
            color: var(--primary);
            position: absolute;
            right: 40px;
            top: 20px;
            transform: rotate(-5deg);
        }

        /* Categories */
        .categories {
            padding: 80px 0;
            background-color: var(--white);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 40px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .section-title i {
            font-size: 32px;
            color: var(--primary);
        }

        .section-title h2 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--primary);
        }

        .section-title p {
            color: var(--text-muted);
            margin-top: 4px;
        }

        .view-all {
            color: var(--primary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
        }

        .cat-card {
            border-radius: var(--radius-lg);
            padding: 20px 20px 0;
            height: 180px;
            position: relative;
            overflow: hidden;
            display: block;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .cat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .cat-1 { background-color: var(--cat-1); }
        .cat-2 { background-color: var(--cat-2); }
        .cat-3 { background-color: var(--cat-3); }
        .cat-4 { background-color: var(--cat-4); }
        .cat-5 { background-color: var(--cat-5); }
        .cat-6 { background-color: var(--cat-6); }

        .cat-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 16px;
            position: relative;
            z-index: 2;
        }

        .cat-icon {
            width: 28px;
            height: 28px;
            background-color: rgba(255,255,255,0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .cat-img {
            position: absolute;
            bottom: -10px;
            right: -20px;
            width: 110%;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            z-index: 1;
            mix-blend-mode: multiply;
            opacity: 0.9;
        }
        
        /* Features */
        .features {
            background-color: var(--accent-soft);
            padding: 60px 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .feature-item {
            display: flex;
            gap: 16px;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background-color: var(--primary);
            color: var(--white);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .feature-item h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .feature-item p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* How It Works */
        .how-it-works {
            padding: 100px 0;
            background-color: var(--white);
            position: relative;
        }

        .steps-container {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin-top: 40px;
        }

        .step {
            flex: 1;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -18px;
            top: 24px;
            color: var(--border);
            font-size: 14px;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .step-num {
            width: 24px;
            height: 24px;
            background-color: var(--primary);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .step-icon {
            font-size: 24px;
            color: var(--primary);
        }

        .step h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .step p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .hiw-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .banner-img {
            background-color: var(--cat-2);
            border-radius: var(--radius-lg);
            height: 200px;
            position: relative;
            overflow: hidden;
            background-image: url('https://images.unsplash.com/photo-1595841696650-6edbf679bebe?q=80&w=800&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
        
        .banner-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-5deg);
            font-family: 'Caveat', cursive;
            font-size: 32px;
            color: var(--primary);
            text-align: center;
            line-height: 1;
            white-space: nowrap;
            text-shadow: 2px 2px 10px rgba(255,255,255,0.8);
        }

        /* Footer */
        .footer {
            background-color: var(--primary);
            color: var(--white);
            padding: 60px 0 30px;
            position: relative;
            margin-top: 40px;
        }
        
        .footer-wave {
            position: absolute;
            top: -40px;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
        }
        
        .footer-wave svg {
            display: block;
            width: calc(100% + 1.3px);
            height: 40px;
        }
        
        .footer-wave .shape-fill {
            fill: var(--primary);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 40px;
            margin-bottom: 30px;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer-brand i {
            font-size: 28px;
            color: var(--white);
        }

        .footer-brand strong {
            font-size: 22px;
            font-weight: 800;
        }

        .footer-links {
            display: flex;
            gap: 32px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            font-size: 14px;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .footer-social {
            display: flex;
            gap: 16px;
        }

        .footer-social a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        
        .footer-social a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .category-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .hiw-layout {
                grid-template-columns: 1fr;
            }
            .hero h1 { font-size: 48px; }
            .hero-bg { width: 100%; mask-image: linear-gradient(to bottom, transparent, black 60%); -webkit-mask-image: linear-gradient(to bottom, transparent, black 60%); }
            .script-text { display: none; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero { padding: 40px 0 60px; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
            .steps-container { flex-direction: column; gap: 40px; }
            .step:not(:last-child)::after { display: none; }
            .footer-content { flex-direction: column; gap: 24px; text-align: center; }
        }
        
        @media (max-width: 480px) {
            .category-grid { grid-template-columns: 1fr; }
            .feature-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 36px; }
        }
        
        /* Floating Leaves Decoration */
        .leaf-decor {
            position: absolute;
            z-index: 0;
            opacity: 0.8;
        }
        
        .leaf-1 { top: 100px; left: -20px; width: 80px; transform: rotate(15deg); }
        .leaf-2 { top: 400px; left: 20px; width: 60px; transform: rotate(-30deg); }
        .leaf-3 { bottom: 50px; right: 5%; width: 50px; transform: rotate(45deg); }

        /* --- Interactivity & Animations --- */
        
        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
        }

        .toast {
            background-color: var(--primary);
            color: var(--white);
            padding: 14px 24px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast i {
            color: var(--accent);
            font-size: 18px;
        }

        /* Scroll Animations */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Ripple Effect on Buttons */
        .btn {
            position: relative;
            overflow: hidden;
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-anim 0.6s linear;
            background-color: rgba(255, 255, 255, 0.3);
            pointer-events: none;
        }
        
        .btn-outline .ripple, .btn-white .ripple {
            background-color: rgba(21, 76, 52, 0.1);
        }

        @keyframes ripple-anim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="brand">
                <i class="fa-solid fa-leaf brand-icon"></i>
                <div class="brand-text">
                    <strong>AgriBayan</strong>
                    <small>Local Farms, Bigger Dreams</small>
                </div>
            </a>
            
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#categories">Products</a></li>
                <li><a href="#">About Us</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="script-text">Healthy Farms<br>Stronger Communities &hearts;</div>
        
        <div class="container">
            <div class="hero-content">
                <div class="tag">
                    <i class="fa-solid fa-leaf"></i> Fresh Farm Products &bull; Direct from Farmers
                </div>
                
                <h1>Support Local Farmers,<br>Get Fresh Products</h1>
                
                <p>AgriBayan connects farmers and buyers in one platform, making it easier to sell, buy, and support local agricultural products.</p>
                
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">
                        Browse Products <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="login.php" class="btn btn-white">
                        <i class="fa-solid fa-user-plus"></i> Join as a Farmer
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories" id="categories">
        <div class="container">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fa-solid fa-leaf"></i>
                        <div>
                            <h2>Shop by Category</h2>
                            <p>Discover a wide variety of fresh farm products.</p>
                        </div>
                    </div>
                </div>
                <a href="#" class="view-all">View All Products <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="category-grid">
                <!-- Card 1 -->
                <a href="#" class="cat-card cat-1">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-wheat-awn"></i></div> Rice
                    </div>
                    <img src="https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400&q=80" alt="Rice" class="cat-img">
                </a>
                
                <!-- Card 2 -->
                <a href="#" class="cat-card cat-2">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-carrot"></i></div> Vegetables
                    </div>
                    <img src="https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400&q=80" alt="Vegetables" class="cat-img">
                </a>
                
                <!-- Card 3 -->
                <a href="#" class="cat-card cat-3">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-apple-whole"></i></div> Fruits
                    </div>
                    <img src="https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=400&q=80" alt="Fruits" class="cat-img">
                </a>
                
                <!-- Card 4 -->
                <a href="#" class="cat-card cat-4">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-seedling"></i></div> Root Crops
                    </div>
                    <img src="https://images.unsplash.com/photo-1596547609652-9fc5d8d42e74?w=400&q=80" alt="Root Crops" class="cat-img">
                </a>
                
                <!-- Card 5 -->
                <a href="#" class="cat-card cat-5">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-egg"></i></div> Eggs & Poultry
                    </div>
                    <img src="https://images.unsplash.com/photo-1598965675045-45c5e72c7d05?w=400&q=80" alt="Eggs & Poultry" class="cat-img">
                </a>
                
                <!-- Card 6 -->
                <a href="#" class="cat-card cat-6">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-jar"></i></div> Other Products
                    </div>
                    <img src="https://images.unsplash.com/photo-1587049352847-4d4b126a5405?w=400&q=80" alt="Other Products" class="cat-img">
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-leaf"></i></div>
                    <div>
                        <h3>Direct from Farmers</h3>
                        <p>Get fresh and quality products straight from local farmers.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-shield-check"></i></div>
                    <div>
                        <h3>Safe & Trusted</h3>
                        <p>Verified sellers and secure transactions.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
                    <div>
                        <h3>Support Local</h3>
                        <p>Buy from our farmers and strengthen local communities.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon"><i class="fa-solid fa-spa"></i></div>
                    <div>
                        <h3>Fresh & Healthy</h3>
                        <p>Enjoy naturally grown and chemical-free produce.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="hiw-layout">
                <div>
                    <div class="section-title">
                        <i class="fa-solid fa-leaf"></i>
                        <div>
                            <h2>How It Works</h2>
                            <p>Getting fresh farm products is easy!</p>
                        </div>
                    </div>
                    
                    <div class="steps-container">
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">1</div>
                                <div class="step-icon"><i class="fa-regular fa-id-badge"></i></div>
                            </div>
                            <h3>Create an Account</h3>
                            <p>Sign up as a farmer, buyer, or both.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">2</div>
                                <div class="step-icon"><i class="fa-solid fa-store"></i></div>
                            </div>
                            <h3>Browse or List Products</h3>
                            <p>Find fresh products or add your own farm items.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">3</div>
                                <div class="step-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                            </div>
                            <h3>Place an Order</h3>
                            <p>Buyers can order directly from farmers.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">4</div>
                                <div class="step-icon"><i class="fa-solid fa-truck"></i></div>
                            </div>
                            <h3>Track & Receive</h3>
                            <p>Get updates and receive your products safely.</p>
                        </div>
                    </div>
                </div>
                
                <div class="banner-img">
                    <div class="banner-text">Together for<br>a Greener Tomorrow &hearts;</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V0C75.32,18.4,146.7,35.5,214.34,47.88c42,7.6,84,12.55,126,11.5Z" class="shape-fill"></path>
            </svg>
        </div>
        
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <i class="fa-solid fa-leaf"></i>
                    <strong>AgriBayan</strong>
                </div>
                
                <div class="footer-links">
                    <a href="#">Home</a>
                    <a href="#">Products</a>
                    <a href="#">About Us</a>
                    <a href="#">Contact</a>
                </div>
                
                <div class="footer-social">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 AgriBayan. All rights reserved.</p>
                <div style="opacity:0.6;">Design inspired by a premium mockup.</div>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Interactivity Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // 1. Toast Notification System
            const toastContainer = document.getElementById('toast-container');
            
            function showToast(message, icon = 'fa-solid fa-circle-info') {
                const toast = document.createElement('div');
                toast.classList.add('toast');
                toast.innerHTML = `<i class="${icon}"></i> <span>${message}</span>`;
                toastContainer.appendChild(toast);
                
                // Trigger animation
                setTimeout(() => {
                    toast.classList.add('show');
                }, 10);
                
                // Remove toast
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toast.remove();
                    }, 400); // Wait for transition
                }, 3000);
            }

            // 2. Handle empty/dummy links
            const dummyLinks = document.querySelectorAll('a[href="#"]');
            dummyLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    showToast('This feature is coming soon!', 'fa-solid fa-rocket');
                });
            });

            // 3. Smooth scrolling for internal anchors
            document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetEl = document.querySelector(targetId);
                    if(targetEl) {
                        targetEl.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                        
                        // Update active state in nav
                        document.querySelectorAll('.nav-links a').forEach(a => a.classList.remove('active'));
                        if(this.closest('.nav-links')) {
                            this.classList.add('active');
                        }
                    }
                });
            });

            // 4. Ripple effect on buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    const rect = e.target.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const ripple = document.createElement('span');
                    ripple.classList.add('ripple');
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    // Prevent multiple ripples from building up indefinitely
                    const existingRipple = this.querySelector('.ripple');
                    if(existingRipple) existingRipple.remove();
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // 5. Scroll Animations (Intersection Observer)
            const revealElements = document.querySelectorAll('.cat-card, .feature-item, .step, .section-title, .hero-content');
            
            // Add reveal class to all targets
            revealElements.forEach(el => {
                el.classList.add('reveal');
            });

            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        // Stagger effect
                        setTimeout(() => {
                            entry.target.classList.add('active');
                        }, (index % 4) * 150); // Stagger up to 4 items at a time
                        observer.unobserve(entry.target); // Only animate once
                    }
                });
            }, {
                root: null,
                threshold: 0.1, // Trigger when 10% visible
                rootMargin: "0px 0px -50px 0px"
            });

            revealElements.forEach(el => {
                revealObserver.observe(el);
            });
            
        });
    </script>
</body>
</html>
