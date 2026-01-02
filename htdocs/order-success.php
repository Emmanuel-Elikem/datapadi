<?php
// This line MUST be at the very top. It runs the site status check.
require_once __DIR__ . '/includes/site-status-check.php';

// Get the Order ID from the URL, e.g., order-success.php?id=ORD-12345
$order_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : 'N/A';

// Get network from URL if passed (for theming)
$network = isset($_GET['network']) ? htmlspecialchars($_GET['network']) : 'mtn';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Successful - DataPadi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-text: #ffffff;
            --success: #10b981;
            --whatsapp: #25D366;
            --dark: #1f2937;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        /* Network-specific themes */
        body.theme-mtn {
            --primary: #FFD700;
            --primary-text: #000000;
        }

        body.theme-airteltigo {
            --primary: #153a8b;
            --primary-text: #ffffff;
        }

        body.theme-telecel {
            --primary: #E60000;
            --primary-text: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
            text-align: center;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            transition: background-image 0.5s ease;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8) 0%, rgba(139, 92, 246, 0.8) 100%);
            z-index: -1;
        }

        /* Network-specific backgrounds */
        body.theme-mtn {
            background-image: url('image/mtn-bg.jpg');
        }

        body.theme-airteltigo {
            background-image: url('image/tigo-bg.jpg');
        }

        body.theme-telecel {
            background-image: url('image/tel-bg.jpg');
        }

        .success-container {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 2.5rem;
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Success Checkmark */
        .success-icon {
            width: 90px;
            height: 90px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            position: relative;
            animation: scaleIn 0.5s ease-out 0.2s backwards;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .success-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid var(--success);
            animation: ripple 1.5s ease-out infinite;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: white;
        }

        .lead-message {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.95);
        }

        /* Order Details Box */
        .order-details {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-id-display {
            margin-bottom: 1rem;
        }

        .order-id-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .order-id-value {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-top: 0.5rem;
        }

        .copy-btn {
            background: transparent;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0.25rem;
            transition: transform 0.2s ease;
        }

        .copy-btn:hover {
            transform: scale(1.1);
        }

        .copy-btn:active {
            transform: scale(0.95);
        }

        /* Tracking Info */
        .tracking-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tracking-info p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0.5rem 0;
        }

        .tracking-info .highlight {
            color: var(--primary);
            font-weight: 600;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            flex: 1;
            min-width: 200px;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--primary-text);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Support Section */
        .support-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .support-text {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .whatsapp-btn {
            background: var(--whatsapp);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        }

        .whatsapp-btn i {
            font-size: 1.3rem;
        }

        /* Home Button */
        .home-btn-container {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
        }

        .home-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-2px);
        }

        /* Delivery Info */
        .delivery-info {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            opacity: 0.8;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            line-height: 1.6;
        }

        .delivery-info i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .success-container {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 1.8rem;
            }

            .lead-message {
                font-size: 1rem;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                min-width: 100%;
            }

            .home-btn-container {
                position: static;
                margin-bottom: 1rem;
                text-align: left;
            }

            .order-id-value {
                font-size: 1.1rem;
            }
        }

        /* Tooltip for copy feedback */
        .tooltip {
            position: absolute;
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .tooltip.show {
            opacity: 1;
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="theme-<?= strtolower($network) ?>">
    <div class="success-container">
        <!-- Home Button -->
        <div class="home-btn-container">
            <a href="index.php" class="home-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>

        <!-- Success Icon -->
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1>Purchase Successful!</h1>
        
        <p class="lead-message">
            Thank you for your order! Your payment has been received and your data bundle is being processed.
        </p>
        
        <!-- Delivery Info -->
        <div class="delivery-info">
            <i class="fas fa-bolt"></i> <strong>Fast Delivery:</strong> Your data bundle is typically delivered instantly. In rare cases of network delays, delivery may take up to 60 minutes.
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <div class="order-id-display">
                <div class="order-id-label">Your Order ID:</div>
                <div class="order-id-value">
                    <span id="orderId"><?= $order_id ?></span>
                    <button class="copy-btn" onclick="copyOrderId()" title="Copy Order ID">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="tracking-info">
                <p><i class="fas fa-info-circle"></i> You can track your order using:</p>
                <p>• Your <span class="highlight">Order ID</span> (<?= $order_id ?>)</p>
                <p>• Or your <span class="highlight">Phone Number</span> used for the purchase</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions">
            <a href="order-tracking.php?id=<?= urlencode($order_id) ?>" class="btn btn-primary">
                <i class="fas fa-search"></i> Track Your Order
            </a>
            <a href="purchase.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i> Buy More Data
            </a>
        </div>

        <!-- Support Section -->
        <div class="support-section">
            <p class="support-text">Need help with your order?</p>
            <a href="helpdesk.php" class="whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Chat with Support
            </a>
        </div>

        <!-- Tooltip for copy feedback -->
        <div class="tooltip" id="copyTooltip">Copied!</div>
    </div>

    <!-- Confetti Animation -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <script>
        // Set theme based on network
        const network = '<?= $network ?>';
        if (network && network !== 'N/A') {
            document.body.className = 'theme-' + network.toLowerCase();
        }

        // Copy Order ID function
        function copyOrderId() {
            const orderId = document.getElementById('orderId').textContent;
            navigator.clipboard.writeText(orderId).then(() => {
                // Show tooltip
                const tooltip = document.getElementById('copyTooltip');
                const copyBtn = document.querySelector('.copy-btn');
                const rect = copyBtn.getBoundingClientRect();
                
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - 40) + 'px';
                tooltip.classList.add('show');
                
                setTimeout(() => {
                    tooltip.classList.remove('show');
                }, 2000);
                
                // Change icon temporarily
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        }

        // Confetti celebration
        document.addEventListener('DOMContentLoaded', function() {
            // Launch confetti
            const duration = 3 * 1000;
            const animationEnd = Date.now() + duration;
            const defaults = { 
                startVelocity: 30, 
                spread: 360, 
                ticks: 60, 
                zIndex: 0,
                colors: ['#FFD700', '#6366f1', '#8b5cf6', '#10b981', '#ef4444']
            };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            const interval = setInterval(function() {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 50 * (timeLeft / duration);
                
                confetti({ 
                    ...defaults, 
                    particleCount, 
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } 
                });
                confetti({ 
                    ...defaults, 
                    particleCount, 
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } 
                });
            }, 250);
        });
    </script>
</body>
</html>