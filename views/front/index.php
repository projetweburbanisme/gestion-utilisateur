<?php 
session_start();
require_once __DIR__ . '/../../config/database.php';  // Adjusted path to database.php

// Debugging: Log session data
error_log("Session Data: " . print_r($_SESSION, true));

$page_title = "Home";
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clyptor - Your Sharing Economy Platform</title>

    <link rel="stylesheet" href="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Base Styles */
    :root {
        --primary: #6c5ce7;
        --primary-dark: #5649c0;
        --secondary: #00cec9;
        --white: #ffffff;
        --black: #121212;
        --gray: #2d3436;
        --light-gray: #636e72;
        --dark: #1e1e1e;
        --darker: #151515;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--black);
        color: var(--white);
        overflow-x: hidden;
    }
    
    a {
        text-decoration: none;
        color: inherit;
    }
    
    .btn {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-align: center;
        cursor: pointer;
    }
    
    .btn-primary {
        background-color: var(--primary);
        color: white;
        border: 2px solid var(--primary);
    }
    
    .btn-primary:hover {
        background-color: transparent;
        color: var(--primary);
        transform: translateY(-2px);
    }
    
    .btn-outline {
        background-color: transparent;
        color: var(--white);
        border: 2px solid var(--white);
    }
    
    .btn-outline:hover {
        background-color: var(--white);
        color: var(--black);
        transform: translateY(-2px);
    }
    
    .btn-admin {
        background-color: #ff7675;
        color: white;
        border: 2px solid #ff7675;
    }
    
    .btn-admin:hover {
        background-color: transparent;
        color: #ff7675;
    }
    
    .btn-small {
        padding: 8px 16px;
        font-size: 0.9rem;
    }
    
    .section-title {
        font-size: clamp(1.8rem, 5vw, 2.5rem);
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        display: inline-block;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 2px;
    }
    
    /* Header Styles */
    .page-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .header {
        background-color: var(--darker);
        padding: 1rem 5%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .logo-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: clamp(1rem, 3vw, 1.2rem);
    }
    
    .logo img {
        height: clamp(30px, 5vw, 40px);
    }
    
    .logo-3d {
        width: clamp(20px, 4vw, 30px);
        height: clamp(20px, 4vw, 30px);
        background-color: var(--primary);
        border-radius: 5px;
        transform: rotate(15deg);
    }
    
    .main-nav ul {
        display: flex;
        gap: clamp(1rem, 2vw, 1.5rem);
        list-style: none;
    }
    
    .main-nav a {
        position: relative;
        padding: 0.5rem 0;
        transition: color 0.3s ease;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .main-nav a:hover {
        color: var(--primary);
    }
    
    .main-nav a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background-color: var(--primary);
        transition: width 0.3s ease;
    }
    
    .main-nav a:hover::after {
        width: 100%;
    }
    
    .auth-buttons {
        display: flex;
        gap: clamp(0.5rem, 2vw, 1rem);
        align-items: center;
    }
    
    .profile-link {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        background-color: var(--primary);
        color: var(--white);
        transition: all 0.3s ease;
        font-weight: 600;
    }
    
    .profile-link:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .profile-link i {
        font-size: 1.2rem;
    }
    
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    /* Hero Section */
    .hero-section {
        display: flex;
        min-height: calc(100vh - 80px);
        padding: 0 5%;
        align-items: center;
        position: relative;
        overflow: hidden;
        flex: 1;
        flex-wrap: wrap;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at 70% 50%, rgba(108, 92, 231, 0.1) 0%, rgba(0, 0, 0, 0) 50%);
        z-index: -1;
    }
    
    .hero-content {
        flex: 1;
        min-width: 300px;
        max-width: 600px;
        z-index: 2;
        transform: translateY(-50px);
        opacity: 0;
        animation: fadeInUp 1s ease forwards 0.3s;
        padding: 2rem 0;
    }
    
    .hero-title {
        font-size: clamp(2.5rem, 7vw, 4rem);
        margin-bottom: 1.5rem;
        background: linear-gradient(90deg, var(--white), #e0e0e0);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        line-height: 1.2;
    }
    
    .hero-subtitle {
        font-size: clamp(1rem, 3vw, 1.2rem);
        margin-bottom: 2.5rem;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }
    
    .hero-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .hero-3d {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        height: 100%;
        min-width: 300px;
    }
    
    spline-viewer {
        width: 100%;
        height: clamp(300px, 50vw, 600px);
        --background: var(--black);
        --interaction-color: var(--primary);
    }
    
    /* Services Section */
    .services-section {
        padding: clamp(3rem, 8vw, 6rem) 5%;
        background-color: var(--darker);
        position: relative;
    }
    
    .services-section::before {
        content: '';
        position: absolute;
        top: -100px;
        left: 0;
        width: 100%;
        height: 100px;
        background: linear-gradient(to top, var(--darker), transparent);
    }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }
    
    .service-card {
        background-color: var(--dark);
        border-radius: 15px;
        padding: clamp(1.5rem, 4vw, 2.5rem);
        text-align: center;
        transition: transform 0.5s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        z-index: 1;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .service-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(108, 92, 231, 0.1) 0%, rgba(0, 206, 201, 0.1) 100%);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .service-card:hover::before {
        opacity: 1;
    }
    
    .service-card h3 {
        font-size: clamp(1.2rem, 3vw, 1.5rem);
        margin: 1.5rem 0 1rem;
    }
    
    .service-card p {
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 1.5rem;
        line-height: 1.6;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .service-icon {
        font-size: clamp(2rem, 5vw, 2.5rem);
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    /* About Section */
    .about-section {
        padding: clamp(3rem, 8vw, 6rem) 5%;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: clamp(2rem, 5vw, 5rem);
        position: relative;
    }
    
    .about-content {
        flex: 1;
        min-width: 300px;
    }
    
    .about-content p {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 1.5rem;
        line-height: 1.6;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .about-image {
        flex: 1;
        position: relative;
        height: clamp(300px, 50vw, 500px);
        min-width: 300px;
    }
    
    .floating-image {
        position: absolute;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        transition: transform 0.5s ease, box-shadow 0.3s ease;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    #floating-image-1 {
        width: clamp(150px, 40vw, 250px);
        height: clamp(200px, 50vw, 350px);
        background: url('https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover;
        top: 0;
        left: 0;
        z-index: 3;
        transform: rotate(-5deg);
        animation: float 6s ease-in-out infinite;
    }
    
    #floating-image-2 {
        width: clamp(180px, 45vw, 280px);
        height: clamp(250px, 55vw, 380px);
        background: url('https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover;
        top: clamp(30px, 10vw, 50px);
        left: clamp(80px, 20vw, 150px);
        z-index: 2;
        transform: rotate(2deg);
        animation: float 8s ease-in-out infinite 1s;
    }
    
    #floating-image-3 {
        width: clamp(160px, 42vw, 260px);
        height: clamp(230px, 52vw, 360px);
        background: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') center/cover;
        top: clamp(60px, 15vw, 100px);
        left: clamp(160px, 30vw, 300px);
        z-index: 1;
        transform: rotate(5deg);
        animation: float 7s ease-in-out infinite 0.5s;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .stat-item {
        text-align: center;
        padding: 1rem;
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: clamp(1.8rem, 5vw, 2.5rem);
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: clamp(0.8rem, 2vw, 0.9rem);
    }
    
    /* Testimonials Section */
    .testimonials-section {
        padding: clamp(3rem, 8vw, 6rem) 5%;
        background-color: var(--dark);
        position: relative;
        overflow: hidden;
    }
    
    .testimonials-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at 30% 50%, rgba(108, 92, 231, 0.05) 0%, rgba(0, 0, 0, 0) 50%);
        z-index: 0;
    }
    
    .testimonials-slider {
        display: flex;
        gap: 1.5rem;
        overflow-x: auto;
        padding: 2rem 0;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scroll-padding: 0 5%;
    }
    
    .testimonials-slider::-webkit-scrollbar {
        height: 8px;
    }
    
    .testimonials-slider::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    
    .testimonials-slider::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 10px;
    }
    
    .testimonial-card {
        min-width: min(350px, 85vw);
        background-color: var(--darker);
        border-radius: 15px;
        padding: clamp(1.5rem, 4vw, 2rem);
        scroll-snap-align: start;
        transition: transform 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.05);
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }
    
    .testimonial-card:hover {
        transform: translateY(-5px);
    }
    
    .testimonial-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(108, 92, 231, 0.1) 0%, rgba(0, 206, 201, 0.1) 100%);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .testimonial-card:hover::before {
        opacity: 1;
    }
    
    .testimonial-content {
        margin-bottom: 1.5rem;
    }
    
    .testimonial-content p {
        font-size: clamp(1rem, 2.5vw, 1.1rem);
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.9);
        font-style: italic;
        position: relative;
    }
    
    .testimonial-content p::before {
        content: '"';
        font-size: clamp(2rem, 6vw, 3rem);
        color: var(--primary);
        opacity: 0.3;
        position: absolute;
        top: -20px;
        left: -15px;
    }
    
    .testimonial-author {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .author-image {
        width: clamp(40px, 10vw, 50px);
        height: clamp(40px, 10vw, 50px);
        border-radius: 50%;
        background-color: var(--gray);
        background-size: cover;
        background-position: center;
    }
    
    .testimonial-card:nth-child(1) .author-image {
        background-image: url('https://randomuser.me/api/portraits/women/44.jpg');
    }
    
    .testimonial-card:nth-child(2) .author-image {
        background-image: url('https://randomuser.me/api/portraits/men/32.jpg');
    }
    
    .testimonial-card:nth-child(3) .author-image {
        background-image: url('https://randomuser.me/api/portraits/women/68.jpg');
    }
    
    .author-info h4 {
        font-size: clamp(1rem, 2.5vw, 1.1rem);
        margin-bottom: 0.2rem;
    }
    
    .author-info p {
        color: var(--light-gray);
        font-size: clamp(0.8rem, 2vw, 0.9rem);
    }
    
    /* Footer Styles */
    .footer {
        background-color: var(--darker);
        color: white;
        padding: clamp(2rem, 5vw, 3rem) 5%;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(250px, 100%), 1fr));
        gap: clamp(1.5rem, 4vw, 3rem);
        margin-bottom: 2rem;
    }
    
    .footer-section {
        margin-bottom: 1.5rem;
    }
    
    .footer-section.about .logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .footer-section.about .logo img {
        height: clamp(25px, 5vw, 30px);
    }
    
    .footer-section.about p {
        margin-bottom: 1.5rem;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.6;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .socials {
        display: flex;
        gap: 1rem;
    }
    
    .socials a {
        color: white;
        font-size: clamp(1rem, 3vw, 1.2rem);
        transition: color 0.3s ease;
    }
    
    .socials a:hover {
        color: var(--primary);
    }
    
    .footer-section h3 {
        font-size: clamp(1.1rem, 3vw, 1.3rem);
        margin-bottom: 1.5rem;
        position: relative;
        display: inline-block;
    }
    
    .footer-section h3::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 50px;
        height: 3px;
        background-color: var(--primary);
    }
    
    .footer-section.links ul {
        list-style: none;
    }
    
    .footer-section.links li {
        margin-bottom: 0.8rem;
    }
    
    .footer-section.links a {
        color: rgba(255, 255, 255, 0.7);
        transition: color 0.3s ease, padding-left 0.3s ease;
        display: block;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .footer-section.links a:hover {
        color: var(--primary);
        padding-left: 5px;
    }
    
    .footer-section.contact p {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 1rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .footer-section.contact i {
        color: var(--primary);
        width: 20px;
        text-align: center;
    }
    
    .footer-bottom {
        text-align: center;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.5);
        font-size: clamp(0.8rem, 2vw, 0.9rem);
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes float {
        0% {
            transform: translateY(0) rotate(-5deg);
        }
        50% {
            transform: translateY(-20px) rotate(5deg);
        }
        100% {
            transform: translateY(0) rotate(-5deg);
        }
    }
    
    /* Responsive Design */
    @media (max-width: 1024px) {
        .hero-section {
            flex-direction: column;
            padding-top: 80px;
        }
        
        .hero-content {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
        }
        
        .hero-buttons {
            justify-content: center;
        }
        
        .about-section {
            flex-direction: column;
        }
        
        .about-image {
            margin-top: 2rem;
        }
        
        .main-nav {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            background-color: var(--darker);
            padding: 1rem 5%;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(-150%);
            transition: transform 0.3s ease;
        }
        
        .main-nav.active {
            transform: translateY(0);
        }
        
        .main-nav ul {
            flex-direction: column;
            gap: 1rem;
        }
        
        .mobile-menu-toggle {
            display: block;
        }
    }
    
    @media (max-width: 768px) {
        .header {
            padding: 0.8rem 5%;
        }
        
        .logo-text {
            display: none;
        }
        
        .auth-buttons .btn {
            padding: 8px 12px;
            font-size: 0.8rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        #floating-image-1,
        #floating-image-2,
        #floating-image-3 {
            left: 50%;
            transform: translateX(-50%) rotate(var(--rotation));
        }
        
        #floating-image-1 {
            --rotation: -5deg;
            top: 0;
        }
        
        #floating-image-2 {
            --rotation: 2deg;
            top: 30%;
        }
        
        #floating-image-3 {
            --rotation: 5deg;
            top: 60%;
        }
    }
    
    @media (max-width: 480px) {
        .hero-buttons .btn {
            width: 100%;
        }
        
        .footer-content {
            grid-template-columns: 1fr;
        }
    }
    
    /* Counter Animation */
    .stat-number {
        transition: all 1s ease;
    }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="logo-container">
                <a href="index.php" class="logo">
                    <!-- <img src="assets/images/logo.png" alt="Clyptor Logo"> -->
                    <span class="logo-text">Clyptor</span>
                </a>
                <div class="logo-3d"></div>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="covoiturage.php">Carpooling</a></li>
                    <li><a href="home-rent.php">Home Rent</a></li>
                    <li><a href="car-rent.php">Car Rent</a></li>
                    <li><a href="deliver-package.php">Deliver Package</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            
            <div class="auth-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="profile-link">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Log Out</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </header>
        
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to Clyptor</h1>
                <p class="hero-subtitle">Your trusted platform for carpooling, home rentals, and car rentals</p>
                <div class="hero-buttons">
                    <a href="carpooling/carpooling.php" class="btn btn-primary">Carpooling</a>
                    <a href="home-rent.php" class="btn btn-outline">Home Rent</a>
                    <a href="car-rent.php" class="btn btn-outline">Car Rent</a>
                </div>
            </div>
            <div class="hero-3d">
                <spline-viewer url="https://prod.spline.design/ZYSp6sUHgbWRI5jn/scene.splinecode"></spline-viewer>
            </div>
        </section>
        
        <section class="services-section">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-card" data-tilt data-tilt-scale="1.05">
                    <div class="service-icon">
                        <i class="fas fa-car-alt"></i>
                    </div>
                    <h3>Carpooling</h3>
                    <p>Share rides with others going the same way. Save money and reduce your carbon footprint.</p>
                    <a href="services/carpooling.php" class="btn btn-small">Explore</a>
                </div>
                
                <div class="service-card" data-tilt data-tilt-scale="1.05">
                    <div class="service-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3>Home Rental</h3>
                    <p>Find or list homes for rent. Perfect for vacations, business trips, or temporary stays.</p>
                    <a href="services/home-rent.php" class="btn btn-small">Explore</a>
                </div>
                
                <div class="service-card" data-tilt data-tilt-scale="1.05">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Car Rental</h3>
                    <p>Rent cars for short or long periods. Wide selection of vehicles for every need.</p>
                    <a href="services/car-rent.php" class="btn btn-small">Explore</a>
                </div>
            </div>
        </section>
        
        <section class="about-section">
            <div class="about-content">
                <h2 class="section-title">About Clyptor</h2>
                <p>Clyptor was founded with the mission to make transportation and accommodation more accessible, affordable, and sustainable. Our platform connects people who need rides, homes, or cars with those who can provide them.</p>
                <p>With thousands of satisfied users, we're proud to be a trusted name in the sharing economy.</p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" data-count="10000">0</div>
                        <div class="stat-label">Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-count="5000">0</div>
                        <div class="stat-label">Listings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-count="95">0</div>
                        <div class="stat-label">% Satisfaction</div>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <div class="floating-image" id="floating-image-1"></div>
                <div class="floating-image" id="floating-image-2"></div>
                <div class="floating-image" id="floating-image-3"></div>
            </div>
        </section>
        
        <section class="testimonials-section">
            <h2 class="section-title">What Our Users Say</h2>
            <div class="testimonials-slider">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Clyptor saved me so much money on my daily commute. The community is great!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-info">
                            <h4>Sarah Johnson</h4>
                            <p>Regular User</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Renting out my spare room has never been easier. Great platform!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-info">
                            <h4>Michael Chen</h4>
                            <p>Home Owner</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Perfect solution for when I need a car for weekend trips. Highly recommend!"</p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-image"></div>
                        <div class="author-info">
                            <h4>Emma Rodriguez</h4>
                            <p>Car Renter</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<?php include 'includes/footer.php'; ?>
<script>
    // Counter animation
    document.addEventListener('DOMContentLoaded', function() {
        const counters = document.querySelectorAll('.stat-number');
        const speed = 200;
        
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-count');
            const count = +counter.innerText;
            const increment = target / speed;
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target;
            }
            
            function updateCount() {
                const current = +counter.innerText;
                if (current < target) {
                    counter.innerText = Math.ceil(current + increment);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            }
        });

        // Mobile menu toggle
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const mainNav = document.querySelector('.main-nav');
        
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('active');
        });
    });
    </script>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.0/vanilla-tilt.min.js"></script>
</body>
</html>