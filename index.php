<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#154c34">
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

            /* Fluid spacing used by sections */
            --section-pad: clamp(48px, 8vw, 100px);
            --gutter: clamp(16px, 4vw, 24px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            scroll-padding-top: 90px; /* keeps anchored sections clear of sticky navbar */
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--bg);
            color: var(--text-dark);
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
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

        :focus-visible {
            outline: 3px solid #2f8f62;
            outline-offset: 2px;
            border-radius: 6px;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--gutter);
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
            white-space: nowrap;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
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

        /* Lift effect only on devices that actually hover (avoids "stuck" hover on touch) */
        @media (hover: hover) {
            .btn-primary:hover { transform: translateY(-1px); }
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
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
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
            gap: 12px;
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

        /* Mobile menu toggle (hidden on desktop) */
        .menu-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: var(--accent-soft);
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .menu-toggle:hover {
            background: var(--accent);
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
            background-image: url('https://images.unsplash.com/photo-1500937386664-56d1dfef3854?q=80&w=2000&auto=format&fit=crop');
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
            font-size: clamp(34px, 5.2vw, 56px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .hero p {
            font-size: clamp(16px, 2vw, 18px);
            color: var(--text-muted);
            margin-bottom: 32px;
            max-width: 480px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
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
            z-index: 2;
        }

        /* Categories */
        .categories {
            padding: var(--section-pad) 0;
            background-color: var(--white);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
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
            font-size: clamp(24px, 3.6vw, 32px);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--primary);
            line-height: 1.2;
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

        @media (hover: hover) {
            .cat-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-md);
            }
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
            flex-shrink: 0;
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

        /* About Us */
        .about-us {
            padding: var(--section-pad) 0;
            background: linear-gradient(135deg, var(--accent-soft) 0%, var(--white) 50%, var(--accent-soft) 100%);
            position: relative;
            overflow: hidden;
        }

        .about-us::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(21, 76, 52, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .about-us::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(21, 76, 52, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .about-us .container {
            position: relative;
            z-index: 1;
        }

        .about-header {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 48px;
        }

        .about-header .tag {
            margin-bottom: 16px;
        }

        .about-header h2 {
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--primary);
            line-height: 1.15;
            margin-bottom: 12px;
        }

        .about-header .subtitle {
            font-family: 'Caveat', cursive;
            font-size: clamp(20px, 3vw, 26px);
            color: var(--primary);
            opacity: 0.7;
            margin-bottom: 24px;
        }

        .about-intro {
            max-width: 800px;
            margin: 0 auto 48px;
            text-align: center;
        }

        .about-intro p {
            font-size: clamp(15px, 1.8vw, 17px);
            color: var(--text-muted);
            line-height: 1.75;
            margin-bottom: 16px;
        }

        .about-intro p:last-child {
            margin-bottom: 0;
        }

        .about-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
            max-width: 900px;
            margin: 0 auto;
        }

        .about-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            border: 1.5px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .about-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #2f8f62);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        @media (hover: hover) {
            .about-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-md);
            }
        }

        .about-card-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), #2f8f62);
            color: var(--white);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .about-card h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }

        .about-card p {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .about-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .about-card {
                padding: 28px 24px;
            }
        }

        /* Features */
        .features {
            background-color: var(--accent-soft);
            padding: clamp(40px, 6vw, 60px) 0;
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
            padding: var(--section-pad) 0;
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
            min-width: 0;
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
            background-image: url('https://images.unsplash.com/photo-1625246333195-78d9c38ad449?q=80&w=1000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
        
        .banner-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-5deg);
            font-family: 'Caveat', cursive;
            font-size: clamp(24px, 4vw, 32px);
            color: var(--primary);
            text-align: center;
            line-height: 1;
            white-space: nowrap;
            text-shadow: 2px 2px 10px rgba(255,255,255,0.8);
        }

        /* Contact Us - Visual Green Theme */
        .contact-section {
            padding: calc(var(--section-pad) + 40px) 0 var(--section-pad);
            background: linear-gradient(145deg, #0d3824 0%, #154c34 50%, #1d6143 100%);
            position: relative;
            color: var(--white);
            overflow: hidden;
        }

        /* Top transition wave */
        .contact-wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            line-height: 0;
            transform: rotate(180deg);
            z-index: 2;
        }

        .contact-wave svg {
            display: block;
            width: calc(100% + 1.3px);
            height: 44px;
        }

        .contact-wave .shape-fill {
            fill: var(--white);
        }

        /* Ambient background glow orbs */
        .contact-section::before {
            content: '';
            position: absolute;
            top: 15%;
            right: -120px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(74, 222, 128, 0.16) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .contact-section::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -120px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .contact-section .container {
            position: relative;
            z-index: 1;
        }

        .contact-section .section-title i {
            color: #6ee7b7;
            font-size: 34px;
            filter: drop-shadow(0 2px 8px rgba(110, 231, 183, 0.35));
        }

        .contact-section .section-title h2 {
            color: #ffffff;
        }

        .contact-section .section-title p {
            color: #bbf7d0;
            opacity: 0.92;
        }

        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            gap: 40px;
            align-items: stretch;
            margin-top: 40px;
        }

        /* Left Column: Frosted Glass Information Card */
        .contact-info-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.22);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .contact-info-card .tag {
            background: rgba(110, 231, 183, 0.16);
            color: #a7f3d0;
            border: 1px solid rgba(110, 231, 183, 0.3);
            margin-bottom: 20px;
            width: fit-content;
        }

        .contact-info-card h3 {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }

        .contact-info-card p.contact-desc {
            color: #e2f4ea;
            font-size: 15px;
            line-height: 1.65;
            margin-bottom: 30px;
        }

        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 12px;
            border-radius: 12px;
            transition: background 0.2s;
        }

        .contact-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .contact-item-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            color: #6ee7b7;
            border: 1px solid rgba(255, 255, 255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .contact-item:hover .contact-item-icon {
            background: #6ee7b7;
            color: #0d3824;
            transform: scale(1.08) rotate(-4deg);
            box-shadow: 0 6px 18px rgba(110, 231, 183, 0.45);
        }

        .contact-item-content h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #a7f3d0;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .contact-item-content a,
        .contact-item-content span {
            font-size: 15px;
            font-weight: 600;
            color: #ffffff;
            text-decoration: none;
            transition: color 0.2s;
        }

        .contact-item-content a:hover {
            color: #6ee7b7;
            text-decoration: underline;
        }

        /* Right Column: Elevated Crisp Form Card */
        .contact-form-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 42px 36px;
            box-shadow: 0 24px 50px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }

        .contact-form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #10b981, #059669, #047857);
        }

        .contact-form-card h3 {
            font-size: 20px;
            font-weight: 800;
            color: #11231a;
            margin-bottom: 6px;
        }

        .contact-form-card .form-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 22px;
        }

        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 13.5px;
            font-weight: 700;
            color: #1c2a24;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group label i {
            color: #059669;
            font-size: 12px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #d5e2d9;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 15px;
            color: var(--text-dark);
            background-color: #fbfdfc;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.16);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        .btn-send {
            background: linear-gradient(135deg, #154c34 0%, #1e6b49 100%);
            color: #ffffff;
            font-weight: 700;
            padding: 13px 32px;
            border-radius: var(--radius-pill);
            box-shadow: 0 4px 14px rgba(21, 76, 52, 0.25);
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            width: fit-content;
        }

        .btn-send:hover {
            background: linear-gradient(135deg, #103b28 0%, #175439 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(21, 76, 52, 0.35);
        }

        @media (max-width: 900px) {
            .contact-wrapper {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .contact-form-card,
            .contact-info-card {
                padding: 32px 24px;
            }
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Footer */
        .footer {
            background-color: var(--primary);
            color: var(--white);
            padding: 60px 0 30px;
            position: relative;
            margin-top: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-wave {
            display: none;
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
            flex-wrap: wrap;
            gap: 12px 32px;
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
            width: 40px;
            height: 40px;
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
            flex-wrap: wrap;
            gap: 16px 24px;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
        }

        .footer-disclaimer {
            background: #fff8d9;
            border: 1px solid #f0e3a8;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 0 0 30px;
            font-size: 15px;
            line-height: 1.6;
            color: #2b2f2c;       /* dark text so it's readable on the yellow */
        }

        .footer-disclaimer strong { font-weight: 700; }

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
            max-width: calc(100vw - 32px);
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
            width: 100px;
            height: 100px;
            margin: -50px 0 0 -50px;
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

        /* =========================================================
           RESPONSIVE BREAKPOINTS
           1280 large laptop · 1100 small laptop · 1024 tablet landscape
           900 tablet portrait · 768 large phone · 600 phone · 400 small phone
           ========================================================= */

        /* Large laptops / small desktops */
        @media (max-width: 1280px) {
            .category-grid { gap: 16px; }
            .cat-card { padding: 16px 16px 0; }
        }

        /* Small laptops: free up room for the 4 steps */
        @media (max-width: 1100px) {
            .hiw-layout {
                grid-template-columns: 1fr;
                gap: 48px;
            }
            .banner-img { height: clamp(180px, 28vw, 260px); }
            .category-grid { grid-template-columns: repeat(3, 1fr); }
        }

        /* Tablet landscape */
        @media (max-width: 1024px) {
            .hero { padding: 64px 0 100px; }
            .hero-bg {
                width: 100%;
                opacity: 0.45;
                border-radius: 0;
                mask-image: linear-gradient(to bottom, transparent, black 70%);
                -webkit-mask-image: linear-gradient(to bottom, transparent, black 70%);
            }
            .script-text { display: none; }
        }

        /* Tablet portrait: collapse navigation into a menu */
        @media (max-width: 900px) {
            .menu-toggle { display: inline-flex; }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                background-color: var(--white);
                padding: 8px var(--gutter) 16px;
                border-top: 1px solid var(--border);
                box-shadow: 0 16px 24px rgba(21, 76, 52, 0.10);
                max-height: calc(100vh - 80px);
                overflow-y: auto;
                opacity: 0;
                visibility: hidden;
                transform: translateY(-8px);
                transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
            }

            .nav-links.open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .nav-links li + li { border-top: 1px solid var(--border); }

            .nav-links a {
                display: block;
                padding: 16px 4px;
                font-size: 16px;
            }

            .nav-links a.active::after { display: none; }
            .nav-links a.active { color: var(--primary); font-weight: 700; }

            .feature-grid { grid-template-columns: repeat(2, 1fr); gap: 28px 24px; }
        }

        /* Large phones */
        @media (max-width: 768px) {
            .navbar .container { height: 68px; }
            .nav-links { max-height: calc(100vh - 68px); }
            html { scroll-padding-top: 78px; }

            .hero { padding: 40px 0 72px; }

            .section-header { margin-bottom: 28px; }

            .category-grid { grid-template-columns: repeat(2, 1fr); }
            .cat-card { height: 170px; }

            .steps-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 32px 24px;
                margin-top: 32px;
            }
            .step:not(:last-child)::after { display: none; }

            .footer { padding: 48px 0 24px; }
            .footer-content {
                flex-direction: column;
                gap: 24px;
                text-align: center;
            }
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            .footer-links { justify-content: center; }
            .footer-disclaimer { font-size: 14px; padding: 16px; }
        }

        /* Phones */
        @media (max-width: 600px) {
            .nav-actions { gap: 8px; }
            .nav-actions .btn { padding: 8px 16px; font-size: 14px; }
            .brand-icon { font-size: 24px; }
            .brand-text strong { font-size: 19px; }
            .brand-text small { display: none; }

            .hero h1 br { display: none; }
            .hero-buttons { gap: 12px; }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .section-title { align-items: flex-start; gap: 12px; }
            .section-title i { font-size: 26px; margin-top: 2px; }

            #toast-container { left: 16px; right: 16px; bottom: 16px; }
        }

        /* Small phones */
        @media (max-width: 480px) {
            .hero-buttons { flex-direction: column; align-items: stretch; }
            .hero-buttons .btn { width: 100%; padding: 13px 24px; }
            .tag { font-size: 12px; padding: 6px 12px; }

            .category-grid { gap: 12px; }
            .cat-card { height: 150px; padding: 14px 14px 0; }
            .cat-title { font-size: 14px; gap: 6px; }
            .cat-icon { width: 24px; height: 24px; font-size: 12px; }
            .cat-img { height: 100px; }

            .feature-grid { grid-template-columns: 1fr; gap: 24px; }
            .steps-container { grid-template-columns: 1fr; gap: 28px; }

            .banner-text { white-space: normal; width: 90%; }
        }

        /* Very small phones (≤ 400px) */
        @media (max-width: 400px) {
            .navbar .container { gap: 8px; }
            .search-icon { display: none; }
            .nav-actions { gap: 6px; }
            .nav-actions .btn { padding: 7px 11px; font-size: 13px; }
            .menu-toggle { width: 40px; height: 40px; font-size: 16px; }
            .brand { gap: 6px; }
            .brand-icon { font-size: 21px; }
            .brand-text strong { font-size: 17px; }
            .footer-links { gap: 10px 20px; }
        }

        /* Tiny screens (≤ 340px): tighten a little more so nothing overflows */
        @media (max-width: 340px) {
            .nav-actions .btn { padding: 6px 9px; font-size: 12px; }
            .brand-text strong { font-size: 16px; }
        }

        /* Very short landscape phones: keep the menu scrollable */
        @media (max-height: 480px) and (orientation: landscape) {
            .hero { padding: 32px 0 56px; }
        }

        /* Respect reduced-motion preferences */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .reveal { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" aria-label="Main navigation">
        <div class="container">
            <a href="index.php" class="brand">
                <i class="fa-solid fa-leaf brand-icon"></i>
                <div class="brand-text">
                    <strong>AgriBayan</strong>
                    <small>Local Farms, Bigger Dreams</small>
                </div>
            </a>
            
            <ul class="nav-links" id="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#about-us">About Us</a></li>
                <li><a href="#categories">Products</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <i class="fa-solid fa-magnifying-glass search-icon" role="button" aria-label="Search"></i>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Sign Up</a>
                <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="nav-links">
                    <i class="fa-solid fa-bars"></i>
                </button>
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

    <!-- About Us Section -->
    <section class="about-us" id="about-us">
        <div class="container">
            <div class="about-header">
                <div class="tag">
                    <i class="fa-solid fa-users"></i> Get to Know Us
                </div>
                <h2>About Us</h2>
                <div class="subtitle">Connecting Farmers and Buyers Through Technology</div>
            </div>

            <div class="about-intro">
                <p>Welcome to our Farm Products and Market Sales Management System, a web-based platform designed to make buying and selling agricultural products easier, more organized, and more accessible.</p>
                <p>Our goal is to help farmers connect directly with potential buyers while providing a convenient way for consumers to find farm products that match their needs and budget. We also aim to help farmers manage their product listings, monitor inventory, track orders, and record sales in one organized system.</p>
            </div>

            <div class="about-cards">
                <div class="about-card">
                    <div class="about-card-icon">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>Our mission is to support farmers and consumers by providing a reliable and user-friendly digital platform that improves agricultural product trading, simplifies inventory management, and promotes better communication between buyers and sellers.</p>
                </div>

                <div class="about-card">
                    <div class="about-card-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>Our vision is to create a more connected agricultural marketplace where farmers can reach more customers, buyers can easily find available farm products at clear prices, and businesses can manage their operations more efficiently through technology.</p>
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
            </div>

            <div class="category-grid">
                <!-- Card 1 -->
                <a href="#" class="cat-card cat-1">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-wheat-awn"></i></div> Rice
                    </div>
                    <img src="https://images.unsplash.com/photo-1536304929831-ee1ca9d44906?w=600&auto=format&fit=crop&q=80" alt="Rice and Grains" class="cat-img" loading="lazy">
                </a>
                
                <!-- Card 2 -->
                <a href="#" class="cat-card cat-2">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-carrot"></i></div> Vegetables
                    </div>
                    <img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&auto=format&fit=crop&q=80" alt="Fresh Farm Vegetables" class="cat-img" loading="lazy">
                </a>
                
                <!-- Card 3 -->
                <a href="#" class="cat-card cat-3">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-apple-whole"></i></div> Fruits
                    </div>
                    <img src="https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=600&auto=format&fit=crop&q=80" alt="Fresh Farm Fruits" class="cat-img" loading="lazy">
                </a>
                
                <!-- Card 4 -->
                <a href="#" class="cat-card cat-4">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-seedling"></i></div> Root Crops
                    </div>
                    <img src="https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&auto=format&fit=crop&q=80" alt="Harvested Root Crops" class="cat-img" loading="lazy">
                </a>
                
                <!-- Card 5 -->
                <a href="#" class="cat-card cat-5">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-egg"></i></div> Eggs &amp; Poultry
                    </div>
                    <img src="https://images.unsplash.com/photo-1559738928-5d5dc5c2458d?w=600&auto=format&fit=crop&q=80" alt="Farm Fresh Eggs &amp; Poultry" class="cat-img" loading="lazy">
                </a>
                
                <!-- Card 6 -->
                <a href="#" class="cat-card cat-6">
                    <div class="cat-title">
                        <div class="cat-icon"><i class="fa-solid fa-jar"></i></div> Other Products
                    </div>
                    <img src="https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=600&auto=format&fit=crop&q=80" alt="Artisanal Honey and Farm Preserves" class="cat-img" loading="lazy">
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
                    <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <h3>Safe &amp; Trusted</h3>
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
                        <h3>Fresh &amp; Healthy</h3>
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
                            <p>Connecting farmers and buyers with streamlined order and inventory management.</p>
                        </div>
                    </div>
                    
                    <div class="steps-container">
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">1</div>
                                <div class="step-icon"><i class="fa-solid fa-id-card-clip"></i></div>
                            </div>
                            <h3>Register &amp; Access Dashboard</h3>
                            <p>Sign up as a farmer or buyer to access your dedicated role-based management dashboard.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">2</div>
                                <div class="step-icon"><i class="fa-solid fa-boxes-packing"></i></div>
                            </div>
                            <h3>Post Products or Request by Budget</h3>
                            <p>Farmers list harvest stocks while buyers can browse or post custom requests with quantity and budget.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">3</div>
                                <div class="step-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                            </div>
                            <h3>Review, Negotiate &amp; Approve</h3>
                            <p>Farmers evaluate buyer requests, approve or negotiate pricing, and track live status from Pending to Approved.</p>
                        </div>
                        
                        <div class="step">
                            <div class="step-header">
                                <div class="step-num">4</div>
                                <div class="step-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            </div>
                            <h3>Digital Receipts &amp; Inventory Sync</h3>
                            <p>Get automated digital receipts and order breakdowns while product stocks and sales update automatically.</p>
                        </div>
                    </div>
                </div>
                
                <div class="banner-img">
                    <div class="banner-text">Together for<br>a Greener Tomorrow &hearts;</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section class="contact-section" id="contact">
        <!-- Top transition wave -->
        <div class="contact-wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V0C75.32,18.4,146.7,35.5,214.34,47.88c42,7.6,84,12.55,126,11.5Z" class="shape-fill"></path>
            </svg>
        </div>

        <div class="container">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <div>
                            <h2>Contact Us</h2>
                            <p>Have questions, need assistance, or want to partner with us? We'd love to hear from you!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-wrapper">
                <!-- Left: Contact Information (Frosted Glass Card) -->
                <div class="contact-info-card">
                    <div>
                        <div class="tag">
                            <i class="fa-solid fa-headset"></i> Support &amp; Inquiries
                        </div>
                        <h3>Get In Touch</h3>
                        <p class="contact-desc">
                            Whether you are a local farmer looking to list your produce or a buyer searching for fresh farm goods, reach out to our team anytime.
                        </p>

                        <div class="contact-list">
                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div class="contact-item-content">
                                    <h4>Email Us</h4>
                                    <a href="mailto:support@agribayan.com">support@agribayan.com</a>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div class="contact-item-content">
                                    <h4>Call Us</h4>
                                    <a href="tel:+639123456789">+63 (912) 345-6789</a>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div class="contact-item-content">
                                    <h4>Location</h4>
                                    <span>Dagupan City, Pangasinan</span>
                                </div>
                            </div>

                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div class="contact-item-content">
                                    <h4>Office Hours</h4>
                                    <span>Mon - Sat: 8:00 AM - 5:00 PM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Contact Form (Elevated Crisp Card) -->
                <div class="contact-form-card">
                    <h3>Send Us a Message</h3>
                    <p class="form-subtitle">Fill out the form below and our team will get back to you shortly.</p>
                    
                    <form class="contact-form" id="contactForm" onsubmit="event.preventDefault(); handleContactSubmit(this);">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact-name"><i class="fa-regular fa-user"></i> Full Name</label>
                                <input type="text" id="contact-name" name="name" placeholder="Juan Dela Cruz" required>
                            </div>
                            <div class="form-group">
                                <label for="contact-email"><i class="fa-regular fa-envelope"></i> Email Address</label>
                                <input type="email" id="contact-email" name="email" placeholder="juan@example.com" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact-subject"><i class="fa-regular fa-comment-dots"></i> Subject</label>
                            <input type="text" id="contact-subject" name="subject" placeholder="Inquiry about farm products or ordering" required>
                        </div>

                        <div class="form-group">
                            <label for="contact-message"><i class="fa-solid fa-pencil"></i> Message</label>
                            <textarea id="contact-message" name="message" rows="4" placeholder="Write your message or inquiry here..." required></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn-send">
                                <i class="fa-solid fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
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
            </div>

            <div class="footer-disclaimer" role="note">
                <strong>Disclaimer:</strong>
                This website is created for demonstration and academic purposes only as part of an SIA subject project.
                It is not a real company, official service, registered business, or commercial website.
                No claims are made regarding actual business operations, legal standing, or real-world services.
            </div>
            
            
            <div class="footer-bottom">
                <p>&copy; 2026 AgriBayan. All rights reserved.</p>

                <div class="footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms</a>
                    <a href="moderation.php">Moderation</a>
                    <a href="copyright.php">Copyright</a>
                </div>

                <div style="opacity:0.6;">Design inspired by a premium mockup.</div>
            </div>
        </div>
    </footer>

    <!-- Toast Container -->
    <div id="toast-container" aria-live="polite"></div>

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

            // 2. Mobile menu (hamburger)
            const menuToggle = document.getElementById('menu-toggle');
            const navLinks = document.getElementById('nav-links');
            const menuIcon = menuToggle.querySelector('i');

            function setMenu(open) {
                navLinks.classList.toggle('open', open);
                menuToggle.setAttribute('aria-expanded', String(open));
                menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                menuIcon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            }

            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                setMenu(!navLinks.classList.contains('open'));
            });

            // Close when a link is tapped, when tapping outside, on Escape, or when resizing to desktop
            navLinks.addEventListener('click', (e) => {
                if (e.target.closest('a')) setMenu(false);
            });
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.navbar')) setMenu(false);
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') setMenu(false);
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth > 900) setMenu(false);
            });


            // 4. Smooth scrolling for internal anchors
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

            // 5. Ripple effect on buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    const rect = this.getBoundingClientRect();
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

            // 6. Scroll Animations (Intersection Observer)
            const revealElements = document.querySelectorAll('.cat-card, .feature-item, .step, .section-title, .hero-content, .about-header, .about-intro, .about-card, .contact-info-card, .contact-form-card');
            
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

            // 7. Contact Form Submission Handler
            window.handleContactSubmit = function(form) {
                const name = form.querySelector('[name="name"]').value;
                showToast(`Thank you, ${name}! Your message has been sent successfully.`, 'fa-solid fa-paper-plane');
                form.reset();
            };
            
        });
    </script>
</body>
</html>