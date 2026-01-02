<?php
// Include the logic file.
require_once __DIR__ . '/includes/site-status-check.php';
// Call the function and store its return value in a variable.
$is_site_open = getSiteOperatingStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="snCzXHu_8Vq3FqsHUTGQIHPcWwWCJzN8z_QMu2wjgHU" />
    <meta name="description" content="DataPadi - Buy data bundles for MTN, AirtelTigo, and Telecel Ghana. Fast, reliable, and affordable mobile data services.">
    <meta name="keywords" content="Ghana data, MTN data, AirtelTigo data, Telecel data, mobile data Ghana, cheap data bundles">
    <meta name="author" content="DataPadi">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://datapadi.shop/">
    <title>DataPadi - Ghana's Premium Data Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* CSS Variables */
        :root {
            --mtn-primary: #FFD700;
            --mtn-secondary: #000000;
            --telecel-primary: #E60000;
            --telecel-secondary: #ffffff;
            --tigo-primary: #153a8b;
            --tigo-secondary: #ffffff;
            --afa-primary: #32CD32;
            --afa-secondary: #ffffff;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --dark: #0f172a;
            --light: #ffffff;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--dark);
            color: var(--light);
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* After Hours Banner */
        .after-hours-banner {
            background: linear-gradient(90deg, #f59e0b, #f97316);
            color: white;
            text-align: center;
            padding: 12px 20px;
            font-weight: 500;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2000;
            transform: translateY(-100%);
            transition: transform 0.4s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .after-hours-banner.visible {
            transform: translateY(0);
        }

        .after-hours-banner i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Navigation Header */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1500;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar.with-banner {
            top: 48px;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            color: var(--mtn-primary);
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--mtn-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Enhanced Track Button */
        .track-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            position: relative;
            animation: trackPulse 2s infinite;
        }

        @keyframes trackPulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6); }
        }

        .track-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.5);
        }

        /* Main Container with Dynamic Background */
        .main-container {
            min-height: 100vh;
            padding-top: 70px;
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .main-container.with-banner {
            padding-top: 118px;
        }

        /* Network Selector Tabs */
        .network-tabs {
            padding: 2rem 0 1rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .network-tab {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            border: 2px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #000;;
            background: var(--glass);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
             text-shadow: 1px 1px 3px rgba(255, 255, 255, 0.6);
        }

        .network-tab.mtn.active {
            background: var(--mtn-primary);
            color: var(--mtn-secondary);
        }

        .network-tab.telecel.active {
            background: var(--telecel-primary);
            color: var(--telecel-secondary);
        }

        .network-tab.airteltigo.active {
            background: var(--tigo-primary);
            color: var(--tigo-secondary);
        }

        .network-tab.afa.active {
            background: var(--afa-primary);
            color: var(--afa-secondary);
        }

        .network-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Hero Content */
        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: flex;
            gap: 4rem;
            align-items: center;
            min-height: calc(100vh - 200px);
            position: relative;
            z-index: 1;
        }

        /* Carousel Section */
        .carousel-section {
            flex: 1;
            height: 500px;
            position: relative;
            perspective: 1000px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .carousel-3d {
            width: 200px;
            height: 260px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s;
        }

        /* Individual Card */
        .carousel-card {
            position: absolute;
            width: 200px;
            height: 260px;
            left: 0;
            top: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 
                        0 0 0 1px rgba(255, 255, 255, 0.1); 
            backface-visibility: hidden;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-card:hover {
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6),
                        0 0 0 1px rgba(255, 255, 255, 0.2); 
        }

        /* Card Header */
        .card-header {
            height: 50px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            position: relative;
            overflow: hidden;
        }

        .card-header.mtn {
            background: var(--mtn-primary);
            color: var(--mtn-secondary);
        }

        .card-header.telecel {
            background: var(--telecel-primary);
            color: var(--telecel-secondary);
        }

        .card-header.airteltigo {
            background: var(--tigo-primary);
            color: var(--tigo-secondary);
        }

        /* Card Body */
        .card-body {
            padding: 1rem;
            height: calc(100% - 50px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        .data-size {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1f2937;
            line-height: 1;
        }

        .data-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin: 0.25rem 0;
        }

        .data-features {
            width: 100%;
            font-size: 0.7rem;
            color: #6b7280;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            margin: 0.5rem 0;
        }

        .data-features span {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .data-features i {
            color: #10b981;
            font-size: 0.75rem;
        }

        .buy-btn {
            width: 100%;
            padding: 0.6rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .buy-btn.mtn {
            background: var(--mtn-primary);
            color: var(--mtn-secondary);
        }

        .buy-btn.telecel {
            background: var(--telecel-primary);
            color: var(--telecel-secondary);
        }

        .buy-btn.airteltigo {
            background: var(--tigo-primary);
            color: var(--tigo-secondary);
        }

        .buy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* View All Button */
        .view-all-btn {
            margin-top: 2rem;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            position: relative;
            animation: trackPulse 2s infinite;
        }

        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        /* Loading spinner for cards */
        .loading-card {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Info Panel */
        .info-panel {
            flex: 1;
            color: white;
            padding: 2rem;
        }

        .info-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
            color: #000;  /* Changed to black */
            text-shadow: 2px 2px 8px rgba(255, 255, 255, 0.4),
                         0 0 20px rgba(255, 255, 255, 0.3);
        }

        .info-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
            color: #000;  /* Changed to black */
            text-shadow: 2px 2px 6px rgba(255, 255, 255, 0.5);
        }

        .cta-box {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;   
        }

        .cta-text {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #000;  /* Changed to black */
            text-shadow: 1px 1px 4px rgba(255, 255, 255, 0.6);
        }

        .cta-text i {
            font-size: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 150px;
            padding: 1rem;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: #000;;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 3px rgba(255, 255, 255, 0.7);  
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Floating Help Button */
        .help-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: var(--secondary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            z-index: 1000;
        }

        .help-fab:hover {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content {
                flex-direction: column;
                gap: 2rem;
                min-height: auto;
                padding: 2rem 1rem;
            }

            .carousel-section {
                height: 400px;
            }

            .info-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .nav-links {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                background: rgba(15, 23, 42, 0.98);
                flex-direction: column;
                padding: 2rem;
                gap: 1.5rem;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .nav-links.active {
                transform: translateX(0);
            }

            .track-btn {
                padding: 0.7rem 1.5rem;
            }

            .network-tabs {
                padding: 1rem;
                gap: 0.5rem;
            }

            .network-tab {
                padding: 0.6rem 1.2rem;
                font-size: 0.875rem;
            }

            .carousel-section {
                height: 350px;
            }

            .carousel-3d {
                width: 160px;
                height: 220px;
            }

            .carousel-card {
                width: 160px;
                height: 220px;
            }

            .card-header {
                height: 45px;
                font-size: 0.75rem;
                padding: 0.5rem;
            }

            .card-body {
                padding: 0.75rem 0.5rem;
            }

            .data-size {
                font-size: 1.8rem;
            }

            .data-price {
                font-size: 0.95rem;
            }

            .data-features {
                font-size: 0.65rem;
            }

            .buy-btn {
                font-size: 0.7rem;
                padding: 0.5rem;
            }

            .info-title {
                font-size: 2rem;
            }

            .info-subtitle {
                font-size: 1rem;
            }

            .help-fab {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .carousel-3d {
                width: 140px;
                height: 200px;
            }

            .carousel-card {
                width: 140px;
                height: 200px;
            }

            .card-header {
                height: 40px;
                padding: 0.5rem;
                font-size: 0.7rem;
            }

            .card-body {
                padding: 0.5rem 0.4rem;
            }

            .data-size {
                font-size: 1.6rem;
            }

            .data-price {
                font-size: 0.85rem;
            }

            .data-features {
                font-size: 0.6rem;
            }

            .buy-btn {
                font-size: 0.65rem;
                padding: 0.4rem;
            }
        }

        /* Loading State */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            transition: opacity 0.5s ease;
        }

        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loader {
            width: 60px;
            height: 60px;
            border: 4px solid var(--glass-border);
            border-top-color: var(--mtn-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
/* Network-specific glass tinting */
.main-container.network-mtn .cta-box,
.main-container.network-mtn .action-btn,
.main-container.network-mtn .network-tab:not(.active) {
    background: rgba(255, 215, 0, 0.1);  /* Light gold tint for MTN */
}

.main-container.network-telecel .cta-box,
.main-container.network-telecel .action-btn,
.main-container.network-telecel .network-tab:not(.active) {
    background: rgba(230, 0, 0, 0.1);  /* Light red tint for Telecel */
}

.main-container.network-airteltigo .cta-box,
.main-container.network-airteltigo .action-btn,
.main-container.network-airteltigo .network-tab:not(.active) {
    background: rgba(21, 58, 139, 0.1);  /* Light blue tint for AirtelTigo */
}

.main-container.network-afa .cta-box,
.main-container.network-afa .action-btn,
.main-container.network-afa .network-tab:not(.active) {
    background: rgba(50, 205, 50, 0.1);  /* Light green tint for AFA */
}

/* Keep the glass effect on hover */
.action-btn:hover,
.network-tab:not(.active):hover {
    background: rgba(255, 255, 255, 0.15) !important;
}
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader"></div>
    </div>

    <!-- After Hours Banner -->
    <div class="after-hours-banner" id="afterHoursBanner">
        <i class="fas fa-moon"></i>
        We're currently closed for automated delivery. Orders placed now will be processed first thing in the morning!
    </div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-bolt"></i>
                <span>DataPadi</span>
            </a>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link">Home</a>
                <a href="purchase.php" class="nav-link">Buy Data</a>
                <a href="helpdesk.php" class="nav-link">Support</a>
                <a href="order-tracking.php" class="track-btn">
                    <i class="fas fa-search"></i>
                    <span>Track Order</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container" id="mainContainer">
        <!-- Network Tabs -->
        <div class="network-tabs">
            <button class="network-tab mtn active" data-network="mtn">MTN</button>
            <button class="network-tab telecel" data-network="telecel">Telecel</button>
            <button class="network-tab airteltigo" data-network="airteltigo">AirtelTigo</button>
            <button class="network-tab afa" data-network="afa">AFA Bundle</button>
        </div>

        <!-- Hero Content -->
        <div class="hero-content">
            <!-- 3D Carousel -->
            <div class="carousel-section">
                <div class="carousel-3d" id="carousel3d">
                    <div class="loading-card">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
                <!-- View All Packages Button -->
                <a href="purchase.php" class="view-all-btn" id="viewAllBtn">
                    <i class="fas fa-shopping-bag"></i>
                    <span>View All Packages</span>
                </a>
            </div>

            <!-- Info Panel -->
            <div class="info-panel">
                <h1 class="info-title">Ghana's Fastest Data Delivery</h1>
                <p class="info-subtitle">Get instant data bundles with no expiry. Best prices, automated delivery in under 60 seconds!</p>
                
                <div class="cta-box">
                    <div class="cta-text">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Padi, grab that data deal wey go make you smile!</span>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="purchase.php" class="action-btn">
                        <i class="fas fa-rocket"></i>
                        <span>Quick Buy</span>
                    </a>
                    <a href="order-tracking.php" class="action-btn">
                        <i class="fas fa-history"></i>
                        <span>Track Order</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <a href="helpdesk.php" class="help-fab">
        <i class="fas fa-headset"></i>
    </a>

    <script>
        // Global Variables
        var isSiteOpen = <?= json_encode($is_site_open) ?>;
        let packages = {};
        let currentNetwork = 'mtn';
        let currentRotation = 0;
        let autoRotateInterval;
        let isAutoRotating = true;
        let isDragging = false;
        let startX = 0;
        let currentX = 0;

        // DOM Elements
        const carousel = document.getElementById('carousel3d');
        const networkTabs = document.querySelectorAll('.network-tab');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const afterHoursBanner = document.getElementById('afterHoursBanner');
        const navbar = document.getElementById('navbar');
        const mainContainer = document.getElementById('mainContainer');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navLinks = document.getElementById('navLinks');
        const viewAllBtn = document.getElementById('viewAllBtn');
        
        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            
            if (!isSiteOpen) {
                afterHoursBanner.classList.add('visible');
                navbar.classList.add('with-banner');
                mainContainer.classList.add('with-banner');
            }

            // Fetch packages from API
            await fetchPackages();

            // Setup initial carousel
            setupCarousel('mtn');
            updateBackground('mtn');
            
            // Start auto rotation
            startAutoRotation();

            // Hide loading
            setTimeout(() => {
                loadingOverlay.classList.add('hidden');
            }, 500);

            // Mobile menu
            mobileMenuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });

            // Scroll effect
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        });

        // Fetch packages from API
        async function fetchPackages() {
            try {
                const response = await fetch('/api/get-packages.php');
                if (!response.ok) throw new Error('Failed to fetch packages');
                
                const data = await response.json();
                packages = data;
                
                // Check URL parameters for initial network
                const urlParams = new URLSearchParams(window.location.search);
                const networkFromUrl = urlParams.get('network');
                if (networkFromUrl && packages[networkFromUrl]) {
                    currentNetwork = networkFromUrl;
                    // Update active tab
                    networkTabs.forEach(tab => {
                        tab.classList.toggle('active', tab.dataset.network === networkFromUrl);
                    });
                }
            } catch (error) {
                console.error('Error fetching packages:', error);
                // Use fallback data if API fails
                packages = {
                    mtn: [
                        { size: '1GB', selling: 5.90 },
                        { size: '2GB', selling: 10.00 },
                        { size: '3GB', selling: 14.90 },
                        { size: '5GB', selling: 24.50 },
                        { size: '10GB', selling: 44.90 },
                        { size: '15GB', selling: 64.90 },
                        { size: '20GB', selling: 84.90 },
                        { size: '25GB', selling: 104.90 }
                    ],
                    telecel: [
                        { size: '1GB', selling: 7.90 },
                        { size: '2GB', selling: 15.90 },
                        { size: '5GB', selling: 25.00 },
                        { size: '10GB', selling: 44.90 },
                        { size: '20GB', selling: 79.90 }
                    ],
                    airteltigo: [
                        { size: '1GB', selling: 6.50 },
                        { size: '2GB', selling: 12.00 },
                        { size: '4GB', selling: 21.90 },
                        { size: '6GB', selling: 30.90 },
                        { size: '10GB', selling: 47.90 }
                    ]
                };
            }
        }

        // Update background based on network
        function updateBackground(network) {
            mainContainer.className = 'main-container';
            if (typeof isSiteOpen !== 'undefined' && !isSiteOpen) {
                mainContainer.classList.add('with-banner');
            }
            
                // Add network-specific class for glass tinting
            mainContainer.classList.add(`network-${network}`);
            
            // Set background image based on network
            switch(network) {
                case 'mtn':
                    mainContainer.style.backgroundImage = 'url("image/mtn-bg.jpg")';
                    break;
                case 'telecel':
                    mainContainer.style.backgroundImage = 'url("image/tel-bg.jpg")';
                    break;
                case 'airteltigo':
                    mainContainer.style.backgroundImage = 'url("image/tigo-bg.jpg")';
                    break;
                case 'afa':
                    mainContainer.style.backgroundImage = 'url("image/img4.jpg")';
                    break;
                default:
                    mainContainer.style.background = 'var(--primary-gradient)';
            }
        }

        // Setup Carousel
        function setupCarousel(network) {
            currentNetwork = network;
            
            // Clear carousel
            carousel.innerHTML = '';
            
            // Update view all button link
            viewAllBtn.href = `purchase.php?network=${network}`;
            
            // Special case for AFA
            if (network === 'afa') {
                const card = document.createElement('div');
                card.className = 'carousel-card';
                card.style.transform = 'rotateY(0deg) translateZ(0px)';
                card.innerHTML = `
                    <div class="card-header ${network}">AFA BUNDLE</div>
                    <div class="card-body">
                        <div class="data-size">Coming</div>
                        <div class="data-price">Soon!</div>
                        <div style="text-align: center; color: #6b7280; font-size: 0.85rem; padding: 1rem 0;">
                            Premium Family & Friends Bundles
                        </div>
                        <button class="buy-btn ${network}" disabled style="opacity: 0.5;">
                            Coming Soon
                        </button>
                    </div>
                `;
                carousel.appendChild(card);
                viewAllBtn.style.display = 'none';
                return;
            }
            
            // Get packages for network
            const networkPackages = packages[network] || [];
            
            if (networkPackages.length === 0) {
                carousel.innerHTML = '<div class="loading-card">No packages available</div>';
                viewAllBtn.style.display = 'none';
                return;
            }
            
            // Show max 8 cards to prevent overlap
            const maxCards = 8;
            const displayPackages = networkPackages.slice(0, maxCards);
            const totalCards = displayPackages.length;
            const angleStep = 360 / totalCards;
            const radius = window.innerWidth < 768 ? 200 : 280;
            
            // Get network display name
            const networkName = network === 'airteltigo' ? 'AirtelTigo' : 
                              network === 'mtn' ? 'MTN' : 
                              network === 'telecel' ? 'Telecel' : network.toUpperCase();
            
            // Create cards
            displayPackages.forEach((pkg, index) => {
                const card = document.createElement('div');
                card.className = 'carousel-card';
                
                // Position in 3D space
                const angle = angleStep * index;
                card.style.transform = `rotateY(${angle}deg) translateZ(${radius}px)`;
                
                card.innerHTML = `
                    <div class="card-header ${network}">${networkName}</div>
                    <div class="card-body">
                        <div class="data-size">${pkg.size}</div>
                        <div class="data-price">GH₵ ${parseFloat(pkg.selling).toFixed(2)}</div>
                        <div class="data-features">
                            <span><i class="fas fa-infinity"></i> No expiry</span>
                            <span><i class="fas fa-bolt"></i> Instant delivery</span>
                        </div>
                        <a href="purchase.php?network=${network}&package=${pkg.size}" class="buy-btn ${network}">
                            Buy Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                `;
                
                carousel.appendChild(card);
            });
            
            // Show view all button if there are packages
            viewAllBtn.style.display = networkPackages.length > 0 ? 'inline-flex' : 'none';
            
            // Reset rotation
            currentRotation = 0;
            carousel.style.transform = `rotateY(0deg)`;
        }

        // Auto Rotation
        function startAutoRotation() {
            stopAutoRotation();
            
            if (currentNetwork === 'afa') return;
            
            autoRotateInterval = setInterval(() => {
                if (!isDragging && isAutoRotating) {
                    currentRotation -= 1;
                    carousel.style.transform = `rotateY(${currentRotation}deg)`;
                }
            }, 50);
        }

        function stopAutoRotation() {
            if (autoRotateInterval) {
                clearInterval(autoRotateInterval);
            }
        }

        // Network Tab Switching
        networkTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                networkTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Get network and update
                const network = tab.dataset.network;
                updateBackground(network);
                setupCarousel(network);
                
                // Restart auto rotation
                startAutoRotation();
            });
        });

        // Mouse/Touch Drag Controls
        carousel.addEventListener('mousedown', startDrag);
        carousel.addEventListener('touchstart', startDrag);
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('touchmove', drag);
        
        document.addEventListener('mouseup', endDrag);
        document.addEventListener('touchend', endDrag);
        
        function startDrag(e) {
            if (currentNetwork === 'afa') return;
            
            isDragging = true;
            startX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            currentX = startX;
            carousel.style.cursor = 'grabbing';
            isAutoRotating = false;
        }
        
        function drag(e) {
            if (!isDragging || currentNetwork === 'afa') return;
            
            e.preventDefault();
            currentX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            const deltaX = currentX - startX;
            
            // Apply rotation based on drag distance
            const dragRotation = deltaX * 0.5;
            carousel.style.transform = `rotateY(${currentRotation + dragRotation}deg)`;
        }
        
        function endDrag() {
            if (!isDragging) return;
            
            isDragging = false;
            carousel.style.cursor = 'grab';
            
            // Update current rotation
            const deltaX = currentX - startX;
            currentRotation += deltaX * 0.5;
            
            // Resume auto rotation after 3 seconds
            setTimeout(() => {
                isAutoRotating = true;
            }, 3000);
        }

        // Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            if (currentNetwork === 'afa') return;
            
            const networkPackages = packages[currentNetwork] || [];
            const totalCards = Math.min(networkPackages.length, 8);
            if (totalCards === 0) return;
            
            const angleStep = 360 / totalCards;
            
            if (e.key === 'ArrowLeft') {
                currentRotation += angleStep;
                carousel.style.transform = `rotateY(${currentRotation}deg)`;
                isAutoRotating = false;
                setTimeout(() => { isAutoRotating = true; }, 3000);
            } else if (e.key === 'ArrowRight') {
                currentRotation -= angleStep;
                carousel.style.transform = `rotateY(${currentRotation}deg)`;
                isAutoRotating = false;
                setTimeout(() => { isAutoRotating = true; }, 3000);
            }
        });
    </script>
</body>
</html>