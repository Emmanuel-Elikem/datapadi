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
    <title>Track Your Order - DataPadi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            position: relative;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveGrid 20s linear infinite;
        }

        @keyframes moveGrid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Blur Overlay for Support Showcase */
        .blur-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        .blur-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Navigation Bar */
        .nav-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: filter 0.5s ease;
        }

        .nav-bar.blurred {
            filter: blur(3px);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .nav-btn {
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-btn.primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .nav-btn.primary:hover {
            background: var(--primary-dark);
        }

        .logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            color: #FFD700;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 6rem auto 2rem;
            animation: fadeIn 0.6s ease;
            transition: filter 0.5s ease;
        }

        .main-container.blurred {
            filter: blur(3px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Search Section */
        .search-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .search-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .search-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .search-header h1 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .search-header p {
            color: #6b7280;
            font-size: 1rem;
        }

        /* After Hours Notice */
        .after-hours-notice {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: <?= $is_site_open ? 'none' : 'flex' ?>;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .after-hours-notice i {
            font-size: 1.5rem;
            color: #92400e;
        }

        /* Search Form */
        .search-form {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 2rem;
            align-items: end;
        }

        .input-group {
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .or-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-weight: 500;
            padding-top: 1.5rem;
        }

        .track-btn {
            grid-column: 1 / -1;
            padding: 1.2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .track-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .track-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Results Section */
        .results-container {
            display: grid;
            gap: 1.5rem;
            animation: fadeIn 0.5s ease;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--light), white);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .order-icon {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .order-info h3 {
            color: var(--dark);
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .order-info p {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .expand-btn {
            background: transparent;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .order-card.expanded .expand-btn {
            transform: rotate(180deg);
        }

        /* Status Badge */
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Expandable Details */
        .order-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }

        .order-card.expanded .order-details {
            max-height: 800px;
        }

        .details-content {
            padding: 2rem;
        }

        /* Progress Tracker */
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-tracker::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e5e7eb;
            z-index: 0;
        }

        .progress-line {
            position: absolute;
            top: 25px;
            left: 10%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            z-index: 1;
            transition: width 0.5s ease;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .progress-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            transition: all 0.4s ease;
        }

        .progress-step.active .progress-circle {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
        }

        .progress-step.completed .progress-circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .progress-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-align: center;
            font-weight: 500;
        }

        /* Order Info Grid */
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--dark);
            font-weight: 600;
        }

        /* Announcement Box */
        .announcement-box {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            gap: 1rem;
            align-items: start;
        }

        .announcement-icon {
            font-size: 1.5rem;
            color: #92400e;
        }

        .announcement-content h4 {
            color: #92400e;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .announcement-content p {
            color: #78350f;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6b7280;
        }

        /* Floating Help Button with Showcase */
        .help-fab-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 999;
        }

        .help-fab {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50px;
            padding: 1rem;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            text-decoration: none;
            overflow: hidden;
            width: 60px;
            height: 60px;
            position: relative;
        }

        .help-fab.showcasing {
            width: auto;
            padding: 0.9rem 1.3rem;
            transform: scale(1.1);
            box-shadow: 0 0 0 20px rgba(99, 102, 241, 0.1),
                        0 0 0 40px rgba(99, 102, 241, 0.05),
                        0 20px 40px rgba(99, 102, 241, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 20px rgba(99, 102, 241, 0.1),
                           0 0 0 40px rgba(99, 102, 241, 0.05),
                           0 20px 40px rgba(99, 102, 241, 0.4);
            }
            50% {
                box-shadow: 0 0 0 30px rgba(99, 102, 241, 0.15),
                           0 0 0 60px rgba(99, 102, 241, 0.08),
                           0 25px 50px rgba(99, 102, 241, 0.5);
            }
        }

        .help-fab:hover {
            width: auto;
            padding: 1rem 1.5rem;
            transform: scale(1.05);
        }

        .help-fab i {
            font-size: 1.5rem;
        }

        .help-fab.showcasing i {
            animation: ring 1s ease-in-out infinite;
        }

        @keyframes ring {
            0%, 100% { transform: rotate(0deg) scale(1); }
            25% { transform: rotate(-15deg) scale(1.1); }
            75% { transform: rotate(15deg) scale(1.1); }
        }

        .help-text {
            max-width: 0;
            opacity: 0;
            white-space: nowrap;
            margin-left: 0;
            transition: all 0.4s ease;
            font-weight: 500;
            font-size: 0.85rem;  
        }

        .help-fab.showcasing .help-text,
        .help-fab:hover .help-text {
            max-width: 250px;
            opacity: 1;
            margin-left: 0.75rem;
        }

        /* Pointer arrow for showcase */
        .pointer-arrow {
            position: fixed;
            bottom: 5.5rem;
            right: 1rem;
            color: white;
            font-size: 2rem;
            animation: pointDown 1s ease-in-out infinite;
            z-index: 999;
            opacity: 0;
            transition: opacity 0.5s ease;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .pointer-arrow.show {
            opacity: 1;
        }

        @keyframes pointDown {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(10px); }
        }

        /* Skip button for showcase */
        .skip-showcase {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            color: var(--primary);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .skip-showcase.show {
            opacity: 1;
            pointer-events: auto;
        }

        .skip-showcase:hover {
            transform: translate(-50%, -50%) scale(1.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .nav-bar {
                padding: 1rem;
            }

            .nav-buttons {
                display: none;
            }

            .search-card {
                padding: 1.5rem;
            }

            .search-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .or-divider {
                padding: 1rem 0;
            }

            .order-header {
                flex-direction: column;
                align-items: start;
                gap: 1rem;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .progress-tracker {
                overflow-x: auto;
                padding-bottom: 1rem;
            }

            .help-fab {
                width: 50px;
                height: 50px;
            }

            .help-fab i {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .search-header h1 {
                font-size: 1.5rem;
            }

            .progress-label {
                font-size: 0.7rem;
            }

            .progress-circle {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>

    <!-- Blur Overlay -->
    <div class="blur-overlay" id="blurOverlay"></div>

    <!-- Pointer Arrow -->
    <div class="pointer-arrow" id="pointerArrow">
        <i class="fas fa-arrow-down"></i>
    </div>

    <!-- Skip Showcase Button -->
    <button class="skip-showcase" id="skipShowcase">
        Got it! <i class="fas fa-check"></i>
    </button>

    <!-- Navigation Bar -->
    <nav class="nav-bar" id="navBar">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            <span>DataPadi</span>
        </div>
        <div class="nav-buttons">
            <a href="index.php" class="nav-btn">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="order-success.php" class="nav-btn">
                <i class="fas fa-check-circle"></i>
                <span>Order Success</span>
            </a>
            <a href="purchase.php" class="nav-btn primary">
                <i class="fas fa-shopping-cart"></i>
                <span>New Order</span>
            </a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container" id="mainContainer">
        <!-- Search Card -->
        <div class="search-card">
            <div class="search-header">
                <h1>
                    <i class="fas fa-search"></i>
                    Track Your Order
                </h1>
                <p>Enter your order details to see real-time delivery status</p>
            </div>

            <?php if (!$is_site_open): ?>
            <div class="after-hours-notice">
                <i class="fas fa-moon"></i>
                <div>
                    <strong>We're currently closed</strong>
                    <p style="margin: 0; font-size: 0.9rem;">Pending orders will be processed when we reopen in the morning.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="search-form">
                <div class="input-group">
                    <label for="orderId">Order ID</label>
                    <div class="input-wrapper">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" id="orderId" placeholder="e.g., ORD-12345">
                    </div>
                </div>

                <div class="or-divider">OR</div>

                <div class="input-group">
                    <label for="phoneNumber">Phone Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phoneNumber" placeholder="e.g., 0241234567" maxlength="10">
                    </div>
                </div>

                <button class="track-btn" onclick="trackOrder()" id="trackBtn">
                    <i class="fas fa-search"></i>
                    <span id="btnText">Track Order</span>
                    <div class="spinner" id="spinner"></div>
                </button>
            </div>
        </div>

        <!-- Results Container -->
        <div id="resultsContainer">
            <!-- Results will be dynamically inserted here -->
        </div>
    </div>

    <!-- Floating Help Button -->
    <div class="help-fab-container">
        <a href="helpdesk.php" class="help-fab" id="helpFab">
            <i class="fas fa-headset"></i>
            <span class="help-text">Need help? Contact our support team!</span>
        </a>
    </div>

    <script>
        let allFoundOrders = [];
        let hasSeenShowcase = localStorage.getItem('hasSeenSupportShowcase');

        // Support button showcase on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Check for order ID in URL
            const urlParams = new URLSearchParams(window.location.search);
            const orderIdFromUrl = urlParams.get('id');
            
            if (orderIdFromUrl) {
                document.getElementById('orderId').value = orderIdFromUrl;
                trackOrder();
            }

            // Show support showcase if first time
            if (!hasSeenShowcase) {
                setTimeout(showSupportShowcase, 5000);
            }
        });

        function showSupportShowcase() {
            const blurOverlay = document.getElementById('blurOverlay');
            const navBar = document.getElementById('navBar');
            const mainContainer = document.getElementById('mainContainer');
            const helpFab = document.getElementById('helpFab');
            const pointerArrow = document.getElementById('pointerArrow');
            const skipButton = document.getElementById('skipShowcase');

            // Blur everything except support button
            blurOverlay.classList.add('active');
            navBar.classList.add('blurred');
            mainContainer.classList.add('blurred');
            
            // Showcase the support button
            helpFab.classList.add('showcasing');
            pointerArrow.classList.add('show');
            skipButton.classList.add('show');

            // Auto-hide after 5 seconds
            const autoHideTimeout = setTimeout(() => {
                hideSupportShowcase();
            }, 5000);

            // Skip button click
            skipButton.addEventListener('click', () => {
                clearTimeout(autoHideTimeout);
                hideSupportShowcase();
            });

            // Click on help button during showcase
            helpFab.addEventListener('click', (e) => {
                if (helpFab.classList.contains('showcasing')) {
                    clearTimeout(autoHideTimeout);
                    hideSupportShowcase();
                    // Small delay before navigating
                    setTimeout(() => {
                        window.location.href = 'helpdesk.php';
                    }, 300);
                    e.preventDefault();
                }
            });
        }

        function hideSupportShowcase() {
            const blurOverlay = document.getElementById('blurOverlay');
            const navBar = document.getElementById('navBar');
            const mainContainer = document.getElementById('mainContainer');
            const helpFab = document.getElementById('helpFab');
            const pointerArrow = document.getElementById('pointerArrow');
            const skipButton = document.getElementById('skipShowcase');

            // Remove all showcase effects
            blurOverlay.classList.remove('active');
            navBar.classList.remove('blurred');
            mainContainer.classList.remove('blurred');
            helpFab.classList.remove('showcasing');
            pointerArrow.classList.remove('show');
            skipButton.classList.remove('show');

            // Save that user has seen the showcase
            localStorage.setItem('hasSeenSupportShowcase', 'true');
        }

        async function trackOrder() {
            const orderId = document.getElementById('orderId').value.trim();
            const phone = document.getElementById('phoneNumber').value.trim();
            
            if (!orderId && !phone) {
                showNotification('Please enter an Order ID or Phone Number', 'error');
                return;
            }
            
            const btn = document.getElementById('trackBtn');
            btn.disabled = true;
            document.getElementById('btnText').style.display = 'none';
            document.getElementById('spinner').style.display = 'block';

            try {
                const url = `/api/check-order-status.php?id=${encodeURIComponent(orderId)}&phone=${encodeURIComponent(phone)}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.orders && data.orders.length > 0) {
                    allFoundOrders = data.orders;
                    displayOrders();
                } else {
                    displayEmptyState();
                }
            } catch (error) {
                console.error("Tracking Error:", error);
                displayErrorState();
            } finally {
                btn.disabled = false;
                document.getElementById('btnText').style.display = 'inline';
                document.getElementById('spinner').style.display = 'none';
            }
        }

        function displayOrders() {
            const container = document.getElementById('resultsContainer');
            let html = '<div class="results-container">';
            
            allFoundOrders.forEach((order, index) => {
                const isExpanded = index === 0 ? 'expanded' : '';
                html += createOrderCard(order, isExpanded);
            });
            
            html += '</div>';
            container.innerHTML = html;
            
            // Add click handlers for expand/collapse
            document.querySelectorAll('.order-card').forEach(card => {
                const header = card.querySelector('.order-header');
                header.addEventListener('click', () => toggleOrderCard(card));
            });
        }

        function createOrderCard(order, expandedClass = '') {
            const statusProgress = getStatusProgress(order.status);
            const progressWidth = statusProgress === 1 ? '0%' : statusProgress === 2 ? '50%' : statusProgress === 3 ? '100%' : '0%';
            
            return `
                <div class="order-card ${expandedClass}" id="order-${order.order_id}">
                    <div class="order-header">
                        <div class="order-header-left">
                            <div class="order-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="order-info">
                                <h3>Order #${order.order_id}</h3>
                                <p>${order.network.toUpperCase()} - ${order.package_size}</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="status-badge status-${order.status}">${order.status}</span>
                            <button class="expand-btn">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="details-content">
                            <div class="progress-tracker">
                                <div class="progress-line" style="width: ${progressWidth}"></div>
                                <div class="progress-step ${statusProgress >= 1 ? 'completed' : ''}">
                                    <div class="progress-circle">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="progress-label">Order Placed</div>
                                </div>
                                <div class="progress-step ${statusProgress === 2 ? 'active' : (statusProgress > 2 ? 'completed' : '')}">
                                    <div class="progress-circle">
                                        <i class="fas ${statusProgress === 2 ? 'fa-spinner fa-spin' : 'fa-cog'}"></i>
                                    </div>
                                    <div class="progress-label">Processing</div>
                                </div>
                                <div class="progress-step ${statusProgress >= 3 ? 'completed' : ''}">
                                    <div class="progress-circle">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                    <div class="progress-label">Completed</div>
                                </div>
                            </div>
                            
                            <div class="order-info-grid">
                                <div class="info-item">
                                    <span class="info-label">Network</span>
                                    <span class="info-value">${order.network.toUpperCase()}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Package Size</span>
                                    <span class="info-value">${order.package_size}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone Number</span>
                                    <span class="info-value">${order.customer_phone}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Order Date</span>
                                    <span class="info-value">${formatDate(order.created_at)}</span>
                                </div>
                                ${order.completed_at ? `
                                <div class="info-item">
                                    <span class="info-label">Completed Date</span>
                                    <span class="info-value">${formatDate(order.completed_at)}</span>
                                </div>
                                ` : ''}
                            </div>
                            
                            ${order.announcement && order.announcement.trim() !== '' ? `
                            <div class="announcement-box">
                                <i class="fas fa-exclamation-circle announcement-icon"></i>
                                <div class="announcement-content">
                                    <h4>Important Update</h4>
                                    <p>${order.announcement}</p>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function toggleOrderCard(card) {
            card.classList.toggle('expanded');
        }

        function getStatusProgress(status) {
            const statusMap = {
                'pending': 1,
                'processing': 2,
                'completed': 3,
                'failed': 0
            };
            return statusMap[status] || 0;
        }

        function formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function displayEmptyState() {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox empty-icon"></i>
                    <h3>No Orders Found</h3>
                    <p>We couldn't find any orders matching your search criteria.</p>
                    <p>Please check your Order ID or Phone Number and try again.</p>
                </div>
            `;
        }

        function displayErrorState() {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle empty-icon" style="color: var(--error);"></i>
                    <h3>Oops! Something went wrong</h3>
                    <p>We couldn't retrieve your order information.</p>
                    <p>Please try again later or contact support.</p>
                </div>
            `;
        }

        function showNotification(message, type = 'info') {
            alert(message);
        }

        // Add enter key support for inputs
        document.getElementById('orderId').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') trackOrder();
        });

        document.getElementById('phoneNumber').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') trackOrder();
        });
    </script>
</body>
</html>