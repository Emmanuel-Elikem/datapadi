<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// --- AJAX ACTION HANDLER ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    try {
        switch ($_GET['action']) {
            case 'toggle_maintenance':
                $stmt = $db->prepare("UPDATE admin_settings SET setting_value = CASE WHEN setting_value = 'active' THEN 'maintenance' ELSE 'active' END WHERE setting_key = 'site_status'");
                if ($stmt->execute()) {
                    $response['success'] = true;
                }
                break;

            case 'set_operating_status':
                $new_status = $_POST['status'] ?? 'auto';
                if (in_array($new_status, ['auto', 'open', 'closed'])) {
                    $stmt = $db->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('site_operating_status', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    if ($stmt->execute([$new_status])) {
                        $response['success'] = true;
                    }
                }
                break;

            case 'update_announcement':
                $announcement = $_POST['announcement'] ?? '';
                $stmt = $db->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = 'announcement'");
                if ($stmt->execute([$announcement])) {
                    if (!empty($announcement)) {
                        notifyAffectedCustomers($db, $announcement);
                    }
                    $response['success'] = true;
                }
                break;
        }
    } catch (Exception $e) {
        error_log("Admin Dashboard AJAX Error: " . $e->getMessage());
        $response['message'] = 'Database operation failed.';
    }
    
    echo json_encode($response);
    exit;
}

function notifyAffectedCustomers($db, $message) {
    $stmt = $db->prepare("SELECT DISTINCT order_id FROM orders WHERE status IN ('pending', 'processing')");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as $order) {
        $stmt_insert = $db->prepare("
            INSERT INTO customer_notifications (order_id, type, message, created_at) 
            VALUES (?, 'announcement', ?, NOW()) 
            ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = NOW()
        ");
        $stmt_insert->execute([$order['order_id'], $message]);
    }
}

// Get all settings
$stmt = $db->prepare("SELECT setting_key, setting_value FROM admin_settings");
$stmt->execute();
$settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = [
    'site_status' => $settings_raw['site_status'] ?? 'active',
    'site_operating_status' => $settings_raw['site_operating_status'] ?? 'auto',
    'announcement' => $settings_raw['announcement'] ?? '',
    'datapacks_api_token' => $settings_raw['datapacks_api_token'] ?? ''
];

// Enhanced dashboard statistics
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$thisMonth = date('Y-m-01');

// Today's stats
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(selling_price * quantity) as revenue, SUM((selling_price - cost_price) * quantity) as profit FROM orders WHERE DATE(created_at) = ? AND status = 'completed'");
$stmt->execute([$today]);
$todayData = $stmt->fetch(PDO::FETCH_ASSOC);

// Yesterday's stats for comparison
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(selling_price * quantity) as revenue FROM orders WHERE DATE(created_at) = ? AND status = 'completed'");
$stmt->execute([$yesterday]);
$yesterdayData = $stmt->fetch(PDO::FETCH_ASSOC);

// This month's stats
$stmt = $db->prepare("SELECT COUNT(*) as count, SUM(selling_price * quantity) as revenue, SUM((selling_price - cost_price) * quantity) as profit FROM orders WHERE DATE(created_at) >= ? AND status = 'completed'");
$stmt->execute([$thisMonth]);
$monthData = $stmt->fetch(PDO::FETCH_ASSOC);

// Pending orders
$stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->fetchColumn() ?? 0;

// Recent orders (last 10)
$stmt = $db->prepare("
    SELECT order_id, customer_phone, network, package_size, status, created_at, selling_price
    FROM orders 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentOrders = $stmt->fetchAll();

// Network breakdown
$stmt = $db->prepare("
    SELECT network, COUNT(*) as count, SUM((selling_price - cost_price) * quantity) as profit
    FROM orders 
    WHERE DATE(created_at) = ? AND status = 'completed'
    GROUP BY network
");
$stmt->execute([$today]);
$networkStats = $stmt->fetchAll();

// API status
$apiConfigured = !empty($settings['datapacks_api_token']) && $settings['datapacks_api_token'] !== 'YOUR_API_TOKEN_HERE';

// Calculate percentage changes
$ordersChange = $yesterdayData['count'] > 0 ? (($todayData['count'] - $yesterdayData['count']) / $yesterdayData['count']) * 100 : 0;
$revenueChange = $yesterdayData['revenue'] > 0 ? (($todayData['revenue'] - $yesterdayData['revenue']) / $yesterdayData['revenue']) * 100 : 0;

$stats = [
    'today_orders' => $todayData['count'] ?? 0,
    'today_revenue' => $todayData['revenue'] ?? 0,
    'today_profit' => $todayData['profit'] ?? 0,
    'pending_orders' => $pendingCount,
    'month_orders' => $monthData['count'] ?? 0,
    'month_revenue' => $monthData['revenue'] ?? 0,
    'month_profit' => $monthData['profit'] ?? 0,
    'orders_change' => $ordersChange,
    'revenue_change' => $revenueChange
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataPadi Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --dark: #1f2937;
            --light: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: #374151;
        }

        /* Mobile-first sidebar */
        .dashboard-container {
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100vh;
            background: var(--dark);
            color: white;
            padding: 2rem 0;
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid #374151;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-menu {
            padding: 1rem 0;
            list-style: none;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .menu-item a:hover {
            background: #374151;
            color: white;
        }

        .menu-item.active a {
            color: white;
            font-weight: 600;
            border-left-color: var(--primary);
            background: #374151;
        }

        /* Mobile header */
        .mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hamburger {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark);
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Main content */
        .main-content {
            padding: 1rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 0.875rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .quick-action {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            text-decoration: none;
            color: var(--dark);
        }

        .quick-action:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .quick-action-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .quick-action-label {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
        }

        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.error::before { background: var(--error); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .stat-title {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 1.25rem;
            opacity: 0.5;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--error); }

        /* Control Cards */
        .control-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .control-card {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .control-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .control-title {
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-indicator.active, .status-indicator.open { background: var(--success); }
        .status-indicator.maintenance, .status-indicator.closed { background: var(--error); }
        .status-indicator.auto { background: var(--warning); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-error {
            background: var(--error);
            color: white;
        }

        .btn-outline {
            background: white;
            color: var(--dark);
            border: 2px solid #e5e7eb;
        }

        .btn-outline.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Recent Orders Widget */
        .widget {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .widget-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-title {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .widget-body {
            max-height: 400px;
            overflow-y: auto;
        }

        .order-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            transition: background 0.2s;
        }

        .order-item:hover {
            background: #f9fafb;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info {
            flex: 1;
            min-width: 0;
        }

        .order-id {
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--dark);
        }

        .order-details {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.625rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-failed { background: #fee2e2; color: #991b1b; }

        /* Network Stats */
        .network-stats {
            display: grid;
            gap: 0.75rem;
            padding: 1rem;
        }

        .network-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 8px;
        }

        .network-name {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        .network-profit {
            color: var(--success);
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Announcement Box */
        .announcement-box {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .announcement-box h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .announcement-box p {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }

        textarea {
            width: 100%;
            min-height: 60px;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
        }

        /* Desktop styles */
        @media (min-width: 768px) {
            .dashboard-container {
                display: grid;
                grid-template-columns: 250px 1fr;
            }

            .sidebar {
                position: static;
                left: 0;
            }

            .sidebar-overlay {
                display: none !important;
            }

            .mobile-header {
                display: none;
            }

            .main-content {
                padding: 2rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .stat-value {
                font-size: 2rem;
            }

            .control-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-bolt"></i> DataPadi Admin
                </div>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item active"><a href="dashboard.php"><i class="fas fa-home fa-fw"></i> Dashboard</a></li>
                <li class="menu-item"><a href="pending-orders.php"><i class="fas fa-clock fa-fw"></i> Pending Orders</a></li>
                <li class="menu-item"><a href="package-manager.php"><i class="fas fa-box fa-fw"></i> Package Manager</a></li>
                <li class="menu-item"><a href="profit-report.php"><i class="fas fa-chart-line fa-fw"></i> Profit Report</a></li>
                <li class="menu-item"><a href="customer-notifications.php"><i class="fas fa-bell fa-fw"></i> Notifications</a></li>
                <li class="menu-item"><a href="site-hours.php"><i class="fas fa-calendar-alt fa-fw"></i> Site Hours</a></li>
                <li class="menu-item"><a href="settings.php"><i class="fas fa-cog fa-fw"></i> Settings</a></li>
                <li class="menu-item"><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Mobile header -->
        <div class="mobile-header">
            <button class="hamburger" id="hamburger">
                <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-title">Dashboard</div>
            <div style="width: 2rem;"></div>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <div class="welcome-banner">
                <div class="welcome-title">
                    <i class="fas fa-sparkles"></i> Welcome back, Admin!
                </div>
                <div class="welcome-subtitle">
                    <?= date('l, F j, Y') ?> · Here's what's happening today
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="pending-orders.php" class="quick-action">
                    <div class="quick-action-icon" style="color: var(--warning);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="quick-action-label">Pending Orders</div>
                    <?php if ($pendingCount > 0): ?>
                    <div style="margin-top: 0.25rem; color: var(--error); font-weight: 700; font-size: 1.25rem;">
                        <?= $pendingCount ?>
                    </div>
                    <?php endif; ?>
                </a>
                <a href="package-manager.php" class="quick-action">
                    <div class="quick-action-icon" style="color: var(--primary);">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="quick-action-label">Packages</div>
                </a>
                <a href="profit-report.php" class="quick-action">
                    <div class="quick-action-icon" style="color: var(--success);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-action-label">Reports</div>
                </a>
                <a href="settings.php" class="quick-action">
                    <div class="quick-action-icon" style="color: #6b7280;">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="quick-action-label">Settings</div>
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Today's Orders</div>
                        <div class="stat-icon" style="color: var(--primary);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $stats['today_orders'] ?></div>
                    <?php if ($stats['orders_change'] != 0): ?>
                    <div class="stat-change <?= $stats['orders_change'] > 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $stats['orders_change'] > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs(round($stats['orders_change'], 1)) ?>% from yesterday
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Today's Revenue</div>
                        <div class="stat-icon" style="color: var(--success);">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">GHS <?= number_format($stats['today_revenue'], 0) ?></div>
                    <?php if ($stats['revenue_change'] != 0): ?>
                    <div class="stat-change <?= $stats['revenue_change'] > 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $stats['revenue_change'] > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= abs(round($stats['revenue_change'], 1)) ?>% from yesterday
                    </div>
                    <?php endif; ?>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Today's Profit</div>
                        <div class="stat-icon" style="color: var(--success);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">GHS <?= number_format($stats['today_profit'], 0) ?></div>
                </div>

                <div class="stat-card <?= $pendingCount > 0 ? 'warning' : '' ?>">
                    <div class="stat-header">
                        <div class="stat-title">Pending Orders</div>
                        <div class="stat-icon" style="color: var(--warning);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $pendingCount ?></div>
                    <?php if ($pendingCount > 0): ?>
                    <a href="pending-orders.php" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">
                        Process now →
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Control Cards -->
            <div class="control-grid">
                <div class="control-card">
                    <div class="control-header">
                        <div class="control-title">
                            <span class="status-indicator <?= $settings['site_status'] ?>"></span>
                            Site Status
                        </div>
                        <button class="btn <?= $settings['site_status'] === 'active' ? 'btn-error' : 'btn-success' ?>" 
                                id="maintenanceBtn"
                                onclick="toggleMaintenance()">
                            <?= $settings['site_status'] === 'active' ? 'Go Offline' : 'Go Live' ?>
                        </button>
                    </div>
                    <div style="font-size: 0.75rem; color: #6b7280;">
                        Currently: <strong><?= ucfirst($settings['site_status']) ?></strong>
                    </div>
                </div>

                <div class="control-card">
                    <div class="control-header">
                        <div class="control-title">
                            <span class="status-indicator <?= $settings['site_operating_status'] ?>"></span>
                            Operating Mode
                        </div>
                    </div>
                    <div class="btn-group" style="margin-top: 0.5rem;">
                        <button class="btn btn-outline <?= $settings['site_operating_status'] === 'open' ? 'active' : '' ?>" 
                                onclick="setOperatingStatus('open')">Open</button>
                        <button class="btn btn-outline <?= $settings['site_operating_status'] === 'auto' ? 'active' : '' ?>" 
                                onclick="setOperatingStatus('auto')">Auto</button>
                        <button class="btn btn-outline <?= $settings['site_operating_status'] === 'closed' ? 'active' : '' ?>" 
                                onclick="setOperatingStatus('closed')">Closed</button>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div>
                    <!-- Recent Orders Widget -->
                    <div class="widget">
                        <div class="widget-header">
                            <div class="widget-title">
                                <i class="fas fa-receipt"></i>
                                Recent Orders
                            </div>
                            <a href="pending-orders.php" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">
                                View All →
                            </a>
                        </div>
                        <div class="widget-body">
                            <?php foreach ($recentOrders as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-id"><?= htmlspecialchars($order['order_id']) ?></div>
                                    <div class="order-details">
                                        <?= htmlspecialchars($order['customer_phone']) ?> · 
                                        <?= strtoupper($order['network']) ?> · 
                                        <?= htmlspecialchars($order['package_size']) ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Announcement Box -->
                    <div class="announcement-box">
                        <h3><i class="fas fa-bullhorn"></i> System Announcement</h3>
                        <p>Broadcast message to customers with active orders</p>
                        <textarea id="announcement" placeholder="e.g., 'Temporary delay with MTN services...'"><?= htmlspecialchars($settings['announcement']) ?></textarea>
                        <button class="btn btn-primary" style="margin-top: 0.75rem; width: 100%;" onclick="updateAnnouncement()">
                            <i class="fas fa-paper-plane"></i> Broadcast
                        </button>
                    </div>
                </div>

                <div>
                    <!-- Network Stats -->
                    <?php if (!empty($networkStats)): ?>
                    <div class="widget">
                        <div class="widget-header">
                            <div class="widget-title">
                                <i class="fas fa-signal"></i>
                                Today's Network Breakdown
                            </div>
                        </div>
                        <div class="network-stats">
                            <?php foreach ($networkStats as $network): ?>
                            <div class="network-item">
                                <div>
                                    <div class="network-name"><?= $network['network'] ?></div>
                                    <div style="font-size: 0.75rem; color: #6b7280;"><?= $network['count'] ?> orders</div>
                                </div>
                                <div class="network-profit">GHS <?= number_format($network['profit'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- System Health -->
                    <div class="widget" style="margin-top: 1rem;">
                        <div class="widget-header">
                            <div class="widget-title">
                                <i class="fas fa-heartbeat"></i>
                                System Health
                            </div>
                        </div>
                        <div style="padding: 1rem;">
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; font-weight: 600;">API Status</span>
                                    <span style="font-size: 0.875rem; color: <?= $apiConfigured ? 'var(--success)' : 'var(--warning)' ?>;">
                                        <?= $apiConfigured ? 'Configured' : 'Not Configured' ?>
                                    </span>
                                </div>
                                <div style="height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                    <div style="width: <?= $apiConfigured ? '100' : '0' ?>%; height: 100%; background: var(--success);"></div>
                                </div>
                            </div>

                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; font-weight: 600;">Database</span>
                                    <span style="font-size: 0.875rem; color: var(--success);">Healthy</span>
                                </div>
                                <div style="height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                    <div style="width: 100%; height: 100%; background: var(--success);"></div>
                                </div>
                            </div>

                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-size: 0.875rem; font-weight: 600;">Orders Processing</span>
                                    <span style="font-size: 0.875rem; color: <?= $pendingCount === 0 ? 'var(--success)' : 'var(--warning)' ?>;">
                                        <?= $pendingCount === 0 ? 'All Clear' : $pendingCount . ' Pending' ?>
                                    </span>
                                </div>
                                <div style="height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                    <div style="width: <?= $pendingCount === 0 ? '100' : '70' ?>%; height: 100%; background: <?= $pendingCount === 0 ? 'var(--success)' : 'var(--warning)' ?>;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="widget" style="margin-top: 1rem;">
                        <div class="widget-header">
                            <div class="widget-title">
                                <i class="fas fa-calendar-check"></i>
                                This Month
                            </div>
                        </div>
                        <div style="padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; color: #6b7280;">Orders</span>
                                <span style="font-weight: 600;"><?= $stats['month_orders'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                <span style="font-size: 0.875rem; color: #6b7280;">Revenue</span>
                                <span style="font-weight: 600;">GHS <?= number_format($stats['month_revenue'], 2) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-size: 0.875rem; color: #6b7280;">Profit</span>
                                <span style="font-weight: 600; color: var(--success);">GHS <?= number_format($stats['month_profit'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        hamburger?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay?.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // Maintenance Toggle
        async function toggleMaintenance() {
            if (!confirm('This will take the entire site offline (or bring it back online). Are you sure?')) return;
            
            try {
                const response = await fetch('?action=toggle_maintenance', { method: 'POST' });
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Action failed. Please try again.');
                }
            } catch (error) {
                alert('An error occurred.');
            }
        }

        // Operating Status Toggle
        async function setOperatingStatus(status) {
            const messages = {
                'open': 'Force the site to be operational, ignoring the schedule?',
                'auto': 'Make the site follow the automatic schedule?',
                'closed': 'Force the site into "after-hours" mode?'
            };
            
            if (!confirm(messages[status])) return;

            const formData = new FormData();
            formData.append('status', status);

            try {
                const response = await fetch('?action=set_operating_status', { method: 'POST', body: formData });
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to update status.');
                }
            } catch (error) {
                alert('An error occurred.');
            }
        }

        // Announcement Function
        async function updateAnnouncement() {
            const announcement = document.getElementById('announcement').value;
            const formData = new FormData();
            formData.append('announcement', announcement);
            
            try {
                const response = await fetch('?action=update_announcement', { method: 'POST', body: formData });
                if (response.ok) {
                    alert('✅ Announcement broadcasted successfully!');
                } else {
                    alert('Failed to broadcast announcement.');
                }
            } catch (error) {
                alert('An error occurred while broadcasting.');
            }
        }

        // Auto-refresh stats every 60 seconds
        setTimeout(() => location.reload(), 60000);
    </script>
</body>
</html>
