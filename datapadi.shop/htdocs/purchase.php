<?php
// Include the logic file.
require_once __DIR__ . '/includes/site-status-check.php';
// Call the function and store its return value in a variable.
$is_site_open = getSiteOperatingStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DataPadi - Package Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --error: #ff4444;
            --success: #00c851;
            --warning: #f59e0b;
            --mtn-color: #FFD700;
            --telecel-color: #E60000;
            --tigo-color: #153a8b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background-image 0.5s ease;
        }

        /* After Hours Banner */
        .after-hours-banner {
            background: var(--warning);
            color: #1f2937;
            text-align: center;
            padding: 0.75rem 1rem;
            font-weight: 500;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .after-hours-banner.visible {
            display: flex;
        }

/* Operator Selector Bar */
.operator-bar {
    display: flex;
    justify-content: center;
    gap: 0.3rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.5rem;
    border-radius: 50px;
    width: 100%;
    max-width: 800px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.operator-btn {
    flex: 1;
    min-width: 0;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.6rem 0.1rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #000;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 40px;
    white-space: nowrap;
    overflow: hidden;
    text-align: center;
}

.operator-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.operator-btn.active {
    transform: scale(1.02);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.operator-btn.active.mtn {
    background: var(--mtn-color);
    color: #000;
    font-weight: 700;
}

.operator-btn.active.telecel {
    background: var(--telecel-color);
    color: white;
    font-weight: 700;
}

.operator-btn.active.airteltigo {
    background: var(--tigo-color);
    color: white;
    font-weight: 700;
}

/* Mobile responsiveness - keeps horizontal layout */
@media (max-width: 640px) {
    .operator-bar {
        gap: 0.3rem;
        padding: 0.4rem;
    }
    
    .operator-btn {
        padding: 0.5rem 0.6rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 400px) {
    .operator-bar {
        gap: 0.2rem;
        padding: 0.35rem;
    }
    
    .operator-btn {
        padding: 0.45rem 0.4rem;
        font-size: 0.75rem;
    }
}

        /* Main Container */
        .container {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 680px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Step Section Styles */
        .step-section {
            margin-bottom: 2rem;
        }

        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .step-icon {
            margin-right: 0.5rem;
        }

        .step-icon .circle {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            text-align: center;
            line-height: 30px;
            font-weight: 600;
        }

        .step-header h2 {
            font-size: 1.1rem;
            color: #343a40;
        }

        /* Input & Selector Styles */
        .input-group,
        .package-selector {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #495057;
            font-size: 1.1rem;
            z-index: 1;
        }

        .phone-input,
        .email-input,
        .quantity-input,
        .selected-package {
            width: 100%;
            padding: 1rem 1rem 1rem 2.8rem;
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .phone-input:focus,
        .email-input:focus,
        .quantity-input:focus,
        .selected-package:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .error-message {
            color: var(--error);
            font-size: 0.9em;
            margin-top: 0.5rem;
            display: none;
        }

        .package-selector {
            cursor: pointer;
        }

        .selected-package {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-right: 1rem;
            cursor: pointer;
            background: white;
        }

        .package-options {
            position: absolute;
            width: 100%;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            margin-top: 0.5rem;
            max-height: 0;
            overflow-y: auto;
            transition: max-height 0.3s ease;
            z-index: 10;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .package-options.open {
            max-height: 250px;
        }

        .package-option {
            padding: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid #f1f3f5;
        }

        .package-option:last-child {
            border-bottom: none;
        }

        .package-option:hover {
            background: #f8f9fa;
        }

        .package-option.selected {
            background: var(--primary);
            color: white;
        }

        /* Summary & Price */
        .price-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            text-align: center;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .price-display.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .summary {
            background: linear-gradient(135deg, #f8f9ff, #e8eaf6);
            border-radius: 0.75rem;
            padding: 1.5rem;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .summary.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .summary-item:last-child {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--primary);
        }

        /* Payment Button */
        .pay-button {
            width: 100%;
            padding: 1.25rem;
            border: none;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary, #8b5cf6));
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .pay-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .pay-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.2);
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-content h3 {
            margin-bottom: 1rem;
            color: #343a40;
        }

        .modal-content p {
            margin-bottom: 1.5rem;
            line-height: 1.6;
            color: #4b5563;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        .btn-confirm {
            background: var(--primary);
            color: white;
        }

        .btn-cancel {
            background: #e9ecef;
            color: #495057;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        /* Floating Help Button */
        .fab-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .fab {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            font-size: 1.5rem;
        }

        .fab:hover {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        @media (max-width: 640px) {
            .container {
                padding: 1.5rem;
            }
            
            .operator-bar {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .operator-btn {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- After Hours Banner -->
    <?php if (!$is_site_open): ?>
    <div class="after-hours-banner visible">
        <i class="fas fa-moon"></i> We're currently closed. Orders will be processed first thing in the morning!
    </div>
    <?php endif; ?>

    <!-- Operator Selector Bar -->
    <header class="operator-bar">
        <button class="operator-btn mtn" data-network="mtn">MTN</button>
        <button class="operator-btn airteltigo" data-network="airteltigo">AirtelTigo</button>
        <button class="operator-btn telecel" data-network="telecel">Telecel</button>
    </header>

    <!-- Main Purchase Container -->
    <form id="dataServiceForm">
        <div class="container">
            <h1 class="main-title">Purchase Your Data Bundle</h1>

            <!-- Step 1: Mobile Number -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">1</span>
                    </div>
                    <h2>Enter your mobile number</h2>
                </div>
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <input type="tel" class="phone-input" id="phone" name="phone" placeholder="024 123 4567" maxlength="10" required>
                    <div class="error-message" id="number-error"></div>
                </div>
            </section>

            <!-- Step 2: Data Package -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">2</span>
                    </div>
                    <h2>Select your data bundle</h2>
                </div>
                <div class="package-selector">
                    <div class="selected-package" id="package-display" onclick="togglePackageOptions()">
                        <span><i class="fas fa-box-open" style="margin-right: 0.5rem;"></i>Select package</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="package-options" id="packageOptions">
                        <!-- Options will be loaded dynamically -->
                    </div>
                </div>
            </section>

            <!-- Step 3: Email (Optional) -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">3</span>
                    </div>
                    <h2>Email for receipt (optional)</h2>
                </div>
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" class="email-input" id="email" name="email" placeholder="your@email.com">
                </div>
            </section>

            <!-- Step 4: Quantity -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">4</span>
                    </div>
                    <h2>Quantity</h2>
                </div>
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <input type="number" class="quantity-input" id="quantity" name="quantity" min="1" value="1" required>
                </div>
            </section>

            <!-- Step 5: Summary -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">5</span>
                    </div>
                    <h2>Review your order</h2>
                </div>
                <div class="price-display" id="priceDisplay">
                    Total: GHS <span id="total-price">0.00</span>
                </div>
                <div class="summary" id="summary">
                    <div class="summary-item">
                        <span>Network:</span>
                        <span id="summary-operator">--</span>
                    </div>
                    <div class="summary-item">
                        <span>Mobile Number:</span>
                        <span id="summary-number">--</span>
                    </div>
                    <div class="summary-item">
                        <span>Package:</span>
                        <span id="summary-package">--</span>
                    </div>
                    <div class="summary-item">
                        <span>Quantity:</span>
                        <span id="summary-quantity">--</span>
                    </div>
                    <div class="summary-item">
                        <span>Total Price:</span>
                        <span>GHS <span id="summary-total">0.00</span></span>
                    </div>
                </div>
            </section>

            <!-- Step 6: Payment -->
            <section class="step-section">
                <div class="step-header">
                    <div class="step-icon">
                        <span class="circle">6</span>
                    </div>
                    <h2>Complete payment</h2>
                </div>
                <button type="submit" class="pay-button" id="payButton">
                    <i class="fas fa-lock"></i>
                    <span id="btnText">Proceed to Payment</span>
                    <div class="spinner" id="spinner"></div>
                </button>
            </section>
        </div>
    </form>

    <!-- Email Modal (First-time users) -->
    <div class="modal-overlay" id="emailModal">
        <div class="modal-content">
            <h3>Enter your email to receive receipts</h3>
            <input type="email" id="emailModalInput" placeholder="you@example.com" style="width: 100%; padding: 0.8rem; border: 2px solid #e9ecef; border-radius: 0.5rem; margin-bottom: 1rem;">
            <div class="modal-buttons">
                <button class="btn btn-cancel" id="modalSkip">Not needed</button>
                <button class="btn btn-confirm" id="modalSubmit">Submit</button>
            </div>
        </div>
    </div>

    <!-- After Hours Modal -->
    <div class="modal-overlay" id="afterHoursModal">
        <div class="modal-content">
            <i class="fas fa-moon" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"></i>
            <h3>Just a Heads-Up!</h3>
            <p>You're placing an order outside our normal operating hours. Your data bundle will be processed first thing when we reopen in the morning.</p>
            <div class="modal-buttons">
                <button class="btn btn-cancel" id="cancelPurchaseBtn">Cancel</button>
                <button class="btn btn-confirm" id="confirmPurchaseBtn">Proceed Anyway</button>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <div class="fab-container">
        <a href="helpdesk.php" class="fab">
            <i class="fas fa-headset"></i>
        </a>
    </div>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        // Site status from PHP
        const isSiteOpen = <?= json_encode($is_site_open) ?>;
        
        // Global variables
        let packages = {};
        let selectedNetwork = 'mtn';
        let selectedPackage = null;
        let orderDataCache = null;

        // Network configurations
        const networkConfig = {
            mtn: {
                color: '#FFD700',
                bg: 'image/mtn-bg.jpg',
                textColor: '#000'
            },
            airteltigo: {
                color: '#153a8b',
                bg: 'image/tigo-bg.jpg',
                textColor: '#fff'
            },
            telecel: {
                color: '#E60000',
                bg: 'image/tel-bg.jpg',
                textColor: '#fff'
            }
        };

        // DOM Elements
        const phoneInput = document.getElementById('phone');
        const emailInput = document.getElementById('email');
        const quantityInput = document.getElementById('quantity');
        const packageOptions = document.getElementById('packageOptions');
        const priceDisplay = document.getElementById('priceDisplay');
        const summary = document.getElementById('summary');
        const numberError = document.getElementById('number-error');

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            // Check for stored email
            checkStoredEmail();
            
            // Fetch packages from API
            await fetchPackages();
            
            // Check URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const networkFromUrl = urlParams.get('network');
            const packageFromUrl = urlParams.get('package');
            
            if (networkFromUrl && packages[networkFromUrl]) {
                selectedNetwork = networkFromUrl;
                updateNetworkUI(networkFromUrl);
                
                // Pre-select package if provided
                if (packageFromUrl) {
                    const pkgIndex = packages[networkFromUrl].findIndex(p => p.size === packageFromUrl);
                    if (pkgIndex !== -1) {
                        selectPackage(pkgIndex);
                    }
                }
            } else {
                // Default to MTN
                updateNetworkUI('mtn');
            }
        });

        // Fetch packages from API
        async function fetchPackages() {
            try {
                const response = await fetch('/api/get-packages.php');
                if (!response.ok) throw new Error('Failed to fetch packages');
                
                packages = await response.json();
                updatePackageOptions();
            } catch (error) {
                console.error('Error fetching packages:', error);
                // Fallback to static data
                packages = {
                    mtn: [
                        { size: '1GB', selling: 5.90 },
                        { size: '2GB', selling: 10.90 },
                        { size: '3GB', selling: 14.90 },
                        { size: '5GB', selling: 24.50 },
                        { size: '10GB', selling: 44.90 }
                    ],
                    telecel: [
                        { size: '1GB', selling: 7.90 },
                        { size: '2GB', selling: 15.90 },
                        { size: '5GB', selling: 25.00 },
                        { size: '10GB', selling: 44.90 }
                    ],
                    airteltigo: [
                        { size: '1GB', selling: 6.50 },
                        { size: '2GB', selling: 12.00 },
                        { size: '4GB', selling: 21.90 },
                        { size: '6GB', selling: 30.90 },
                        { size: '10GB', selling: 47.90 }
                    ]
                };
                updatePackageOptions();
            }
        }

        // Network button handlers
        document.querySelectorAll('.operator-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const network = btn.dataset.network;
                selectedNetwork = network;
                updateNetworkUI(network);
            });
        });

        // Update UI when network changes
        function updateNetworkUI(network) {
            // Update active button
            document.querySelectorAll('.operator-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.network === network) {
                    b.classList.add('active');
                }
            });
            
            // Update theme colors and background
            const config = networkConfig[network];
            document.documentElement.style.setProperty('--primary', config.color);
            document.body.style.backgroundImage = `url(${config.bg})`;
            
            // Update title color
            document.querySelector('.main-title').style.color = config.color;
            
            // Update step circles color
            document.querySelectorAll('.circle').forEach(circle => {
                circle.style.background = config.color;
            });
            
            // Reset package selection
            selectedPackage = null;
            document.getElementById('package-display').innerHTML = 
                '<span><i class="fas fa-box-open" style="margin-right: 0.5rem;"></i>Select package</span><i class="fas fa-chevron-down"></i>';
            
            // Update package options
            updatePackageOptions();
            updateSummary();
        }

        // Toggle package dropdown
        function togglePackageOptions() {
            packageOptions.classList.toggle('open');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.package-selector')) {
                packageOptions.classList.remove('open');
            }
        });

        // Update package options based on network
        function updatePackageOptions() {
            packageOptions.innerHTML = '';
            
            if (!packages[selectedNetwork]) return;
            
            packages[selectedNetwork].forEach((pkg, index) => {
                const option = document.createElement('div');
                option.className = 'package-option';
                option.dataset.index = index;
                option.innerHTML = `${pkg.size} - GHS ${parseFloat(pkg.selling).toFixed(2)}`;
                option.addEventListener('click', () => selectPackage(index));
                packageOptions.appendChild(option);
            });
        }

        // Select a package
        function selectPackage(index) {
            selectedPackage = packages[selectedNetwork][index];
            
            // Update display
            document.getElementById('package-display').innerHTML = 
                `<span><i class="fas fa-box-open" style="margin-right: 0.5rem;"></i>${selectedPackage.size} - GHS ${parseFloat(selectedPackage.selling).toFixed(2)}</span><i class="fas fa-chevron-down"></i>`;
            
            // Update option styles
            document.querySelectorAll('.package-option').forEach(opt => {
                opt.classList.remove('selected');
                if (opt.dataset.index == index) {
                    opt.classList.add('selected');
                }
            });
            
            // Close dropdown
            packageOptions.classList.remove('open');
            
            updatePrice();
            updateSummary();
        }

        // Input listeners
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            validatePhoneNumber();
            updateSummary();
        });

        quantityInput.addEventListener('input', () => {
            updatePrice();
            updateSummary();
        });

        emailInput.addEventListener('input', updateSummary);

        // Validate phone number
        function validatePhoneNumber() {
            const number = phoneInput.value;
            const isValid = number.length === 10 && number.startsWith('0');
            
            if (number && !isValid) {
                numberError.textContent = 'Please enter a valid 10-digit number';
                numberError.style.display = 'block';
                phoneInput.style.borderColor = 'var(--error)';
            } else {
                numberError.style.display = 'none';
                phoneInput.style.borderColor = isValid ? 'var(--success)' : '#e9ecef';
            }
            return isValid;
        }

        // Update price display
        function updatePrice() {
            if (selectedPackage) {
                const quantity = parseInt(quantityInput.value) || 1;
                const total = (selectedPackage.selling * quantity).toFixed(2);
                document.getElementById('total-price').textContent = total;
                priceDisplay.classList.add('visible');
            }
        }

        // Update summary
        function updateSummary() {
            const isValid = validatePhoneNumber() && selectedPackage && quantityInput.value > 0;
            
            if (isValid) {
                const quantity = parseInt(quantityInput.value) || 1;
                document.getElementById('summary-operator').textContent = selectedNetwork.toUpperCase();
                document.getElementById('summary-number').textContent = phoneInput.value;
                document.getElementById('summary-package').textContent = selectedPackage.size;
                document.getElementById('summary-quantity').textContent = quantity;
                document.getElementById('summary-total').textContent = (selectedPackage.selling * quantity).toFixed(2);
                summary.classList.add('visible');
            } else {
                summary.classList.remove('visible');
            }
        }

        // Check for stored email
        function checkStoredEmail() {
            const storedEmail = localStorage.getItem('userEmail');
            if (!storedEmail) {
                // Show email modal for first-time users
                setTimeout(() => {
                    document.getElementById('emailModal').classList.add('active');
                }, 1000);
            } else {
                emailInput.value = storedEmail;
            }
        }

        // Email modal handlers
        document.getElementById('modalSubmit').addEventListener('click', () => {
            const modalEmail = document.getElementById('emailModalInput').value;
            if (modalEmail && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(modalEmail)) {
                localStorage.setItem('userEmail', modalEmail);
                emailInput.value = modalEmail;
                document.getElementById('emailModal').classList.remove('active');
            } else {
                alert('Please enter a valid email or click "Not needed"');
            }
        });

        document.getElementById('modalSkip').addEventListener('click', () => {
            document.getElementById('emailModal').classList.remove('active');
        });

        // Form submission
        document.getElementById('dataServiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validatePhoneNumber()) {
                alert('Please enter a valid phone number');
                return;
            }
            
            if (!selectedPackage) {
                alert('Please select a package');
                return;
            }

            const quantity = parseInt(quantityInput.value) || 1;
            
            orderDataCache = {
                network: selectedNetwork,
                package_size: selectedPackage.size,
                selling_price: parseFloat(selectedPackage.selling),
                quantity: quantity,
                phone: phoneInput.value,
                email: emailInput.value || 'customer@datapadi.shop',
                total: parseFloat(selectedPackage.selling) * quantity
            };

            if (!isSiteOpen) {
                document.getElementById('afterHoursModal').classList.add('active');
            } else {
                await initiatePaymentProcess();
            }
        });

        // After hours modal handlers
        document.getElementById('confirmPurchaseBtn').addEventListener('click', async () => {
            document.getElementById('afterHoursModal').classList.remove('active');
            await initiatePaymentProcess();
        });

        document.getElementById('cancelPurchaseBtn').addEventListener('click', () => {
            document.getElementById('afterHoursModal').classList.remove('active');
            orderDataCache = null;
        });

        // Payment process
        async function initiatePaymentProcess() {
            if (!orderDataCache) return;

            const btn = document.getElementById('payButton');
            btn.disabled = true;
            document.getElementById('btnText').style.display = 'none';
            document.getElementById('spinner').style.display = 'block';
            
            const deviceFingerprint = await generateDeviceFingerprint();
            orderDataCache.device_fingerprint = deviceFingerprint;
            
            initializePayment(orderDataCache);
        }

        // Generate device fingerprint
        async function generateDeviceFingerprint() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('fingerprint', 2, 2);
            const canvasData = canvas.toDataURL();
            const screenData = `${screen.width}x${screen.height}x${screen.colorDepth}`;
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const fingerprint = `${canvasData.slice(-50)}_${screenData}_${timezone}`;
            const encoder = new TextEncoder();
            const data = encoder.encode(fingerprint);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('').slice(0, 32);
        }

        // Initialize Paystack payment
        function initializePayment(orderData) {
            // Generate an explicit unique reference per transaction
            const paystackRef = `DP-${Date.now()}-${Math.random().toString(36).slice(2,10)}`;
            const handler = PaystackPop.setup({
                key: 'pk_live_ea0c87747238f0f71e5ce276338f612c4a74f071',
                  //pk_test_1828fb257fb0cfd762bd2507e089bd367e4a4e3e  pk_live_ea0c87747238f0f71e5ce276338f612c4a74f071
                email: orderData.email,
                amount: Math.round(orderData.total * 100),
                currency: 'GHS',
                ref: paystackRef,
                metadata: {
                    custom_fields: [
                        { display_name: "Network", variable_name: "network", value: orderData.network },
                        { display_name: "Package", variable_name: "package", value: orderData.package_size },
                        { display_name: "Phone", variable_name: "phone", value: orderData.phone },
                        { display_name: "Device ID", variable_name: "device_id", value: orderData.device_fingerprint }
                    ]
                },
                callback: function(response) {
                    processOrder({ ...orderData, payment_ref: response.reference });
                },
                onClose: function() {
                    const btn = document.getElementById('payButton');
                    btn.disabled = false;
                    document.getElementById('btnText').style.display = 'inline';
                    document.getElementById('spinner').style.display = 'none';
                }
            });
            handler.openIframe();
        }

        // Process order after payment
        async function processOrder(orderData) {
            try {
                const response = await fetch('/api/process-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                const result = await response.json();
                
                if (result.success) {
                    localStorage.setItem('lastOrderId', result.order_id);
                    window.location.href = `order-success.php?id=${result.order_id}`;
                } else {
                    alert('Error processing order: ' + (result.message || 'Please contact support.'));
                    const btn = document.getElementById('payButton');
                    btn.disabled = false;
                    document.getElementById('btnText').style.display = 'inline';
                    document.getElementById('spinner').style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('A network error occurred. Please try again.');
                const btn = document.getElementById('payButton');
                btn.disabled = false;
                document.getElementById('btnText').style.display = 'inline';
                document.getElementById('spinner').style.display = 'none';
            }
        }
    </script>
</body>
</html>