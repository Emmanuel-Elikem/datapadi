<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

// Simple auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Get all pending orders
$stmt = $db->prepare("
    SELECT o.*, 
           TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as age_minutes,
           pp_manual.cost_price as manual_cost,
           (o.selling_price - pp_manual.cost_price) as potential_profit
    FROM orders o
    LEFT JOIN provider_pricing pp_manual 
        ON pp_manual.provider = 'manual' 
        AND pp_manual.network = o.network 
        AND pp_manual.package_size = o.package_size
    WHERE o.status = 'pending'
    ORDER BY o.created_at ASC
");
$stmt->execute();
$pendingOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders - DataPadi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .page-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            text-decoration: none;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-outline {
            background: white;
            color: var(--dark);
            border: 2px solid #e5e7eb;
        }

        .btn-outline:hover {
            background: var(--light);
        }

        /* Stats bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-card.urgent {
            border-left: 4px solid var(--error);
        }

        /* Alert boxes */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: start;
            gap: 0.75rem;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        /* Orders list */
        .orders-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .order-card {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.2s;
        }

        .order-card:hover {
            background: #f9fafb;
        }

        .order-card:last-child {
            border-bottom: none;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }

        .order-id {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.875rem;
        }

        .age-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .age-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .age-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .age-urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .detail-item {
            font-size: 0.875rem;
        }

        .detail-label {
            color: #6b7280;
            font-size: 0.75rem;
        }

        .detail-value {
            color: var(--dark);
            font-weight: 600;
        }

        .profit-highlight {
            color: var(--success);
            font-weight: 700;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Desktop table view (hidden on mobile) */
        .table-view {
            display: none;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-icon {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6b7280;
        }

        /* Instructions box */
        .instructions {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .instructions h3 {
            font-size: 1.125rem;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instructions ol {
            margin-left: 1.25rem;
            line-height: 1.8;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

            .page-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .page-title {
                font-size: 2rem;
            }

            /* Show table view on desktop */
            .orders-container.card-view {
                display: none;
            }

            .table-view {
                display: block;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                text-align: left;
                padding: 1rem;
                background: #f9fafb;
                font-weight: 600;
                color: #6b7280;
                font-size: 0.875rem;
            }

            td {
                padding: 1rem;
                border-top: 1px solid #e5e7eb;
            }

            tr:hover {
                background: #f9fafb;
            }
        }

        @media (min-width: 1024px) {
            .stats-bar {
                grid-template-columns: repeat(4, 1fr);
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
                <li class="menu-item"><a href="dashboard.php"><i class="fas fa-home fa-fw"></i> Dashboard</a></li>
                <li class="menu-item active"><a href="pending-orders.php"><i class="fas fa-clock fa-fw"></i> Pending Orders</a></li>
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
            <div class="mobile-title">Pending Orders</div>
            <div style="width: 2rem;"></div>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-clock"></i>
                    Pending Orders
                </h1>
                <div class="action-buttons">
                    <?php if (count($pendingOrders) > 0): ?>
                    <button class="btn btn-success" onclick="markAllCompleted()" id="markAllBtn">
                        <i class="fas fa-check-double"></i>
                        Mark All Completed
                    </button>
                    <?php endif; ?>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Dashboard
                    </a>
                </div>
            </div>

            <?php
            // Check API status
            $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
            $stmt->execute();
            $apiToken = $stmt->fetchColumn();
            $apiConfigured = !empty($apiToken) && $apiToken !== 'YOUR_API_TOKEN_HERE';
            ?>

            <?php if (!$apiConfigured): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>API Not Configured</strong><br>
                    All orders require manual processing. Configure API in <a href="settings.php" style="text-decoration: underline;">Settings</a> to enable auto-processing.
                </div>
            </div>
            <?php endif; ?>

            <?php if (count($pendingOrders) > 0): ?>
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-card urgent">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value"><?= count($pendingOrders) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Profit</div>
                    <div class="stat-value">
                        GHS <?= number_format(array_sum(array_column($pendingOrders, 'potential_profit')), 2) ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Oldest Order</div>
                    <div class="stat-value">
                        <?= max(array_column($pendingOrders, 'age_minutes')) ?> min
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Urgent (>15m)</div>
                    <div class="stat-value">
                        <?= count(array_filter($pendingOrders, fn($o) => $o['age_minutes'] > 15)) ?>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="orders-container card-view">
                <?php foreach ($pendingOrders as $order): ?>
                <?php
                    $ageClass = 'age-ok';
                    if ($order['age_minutes'] > 15) $ageClass = 'age-urgent';
                    elseif ($order['age_minutes'] > 10) $ageClass = 'age-warning';
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id"><?= htmlspecialchars($order['order_id']) ?></div>
                        <span class="age-badge <?= $ageClass ?>">
                            <?= $order['age_minutes'] ?> min
                        </span>
                    </div>
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Customer</div>
                            <div class="detail-value"><?= htmlspecialchars($order['customer_phone']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Network</div>
                            <div class="detail-value"><?= strtoupper($order['network']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Package</div>
                            <div class="detail-value"><?= htmlspecialchars($order['package_size']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Profit</div>
                            <div class="detail-value profit-highlight">GHS <?= number_format($order['potential_profit'], 2) ?></div>
                        </div>
                    </div>
                    <div class="order-actions">
                        <button class="btn btn-success btn-small" onclick="markProcessed('<?= $order['order_id'] ?>')">
                            <i class="fas fa-check"></i> Mark Completed
                        </button>
                        <button class="btn btn-primary btn-small" onclick="viewDetails('<?= $order['order_id'] ?>')">
                            <i class="fas fa-eye"></i> Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop Table View -->
            <div class="orders-container table-view">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Network</th>
                            <th>Package</th>
                            <th>Age</th>
                            <th>Profit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingOrders as $order): ?>
                        <?php
                            $ageClass = 'age-ok';
                            if ($order['age_minutes'] > 15) $ageClass = 'age-urgent';
                            elseif ($order['age_minutes'] > 10) $ageClass = 'age-warning';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['order_id']) ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                            <td><?= htmlspecialchars(strtoupper($order['network'])) ?></td>
                            <td><?= htmlspecialchars($order['package_size']) ?></td>
                            <td>
                                <span class="age-badge <?= $ageClass ?>">
                                    <?= $order['age_minutes'] ?> min
                                </span>
                            </td>
                            <td class="profit-highlight">GHS <?= number_format($order['potential_profit'], 2) ?></td>
                            <td>
                                <button class="btn btn-success btn-small" onclick="markProcessed('<?= $order['order_id'] ?>')">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                                <button class="btn btn-primary btn-small" onclick="viewDetails('<?= $order['order_id'] ?>')">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <!-- Empty state -->
            <div class="orders-container">
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="empty-title">All Caught Up!</div>
                    <div class="empty-text">No pending orders at the moment.</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Instructions -->
            <div class="instructions">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Processing Instructions
                </h3>
                <ol>
                    <li>Go to your cheaper provider's website</li>
                    <li>Purchase the data package for the customer's number</li>
                    <li>Click "Mark Completed" once processed</li>
                    <li>Or use "Mark All Completed" if you've processed all orders</li>
                </ol>
                <?php if (!$apiConfigured): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: #e0e7ff; border-radius: 8px;">
                    <strong>🔄 Enable Auto-Processing:</strong>
                    <ol style="margin-top: 0.5rem;">
                        <li>Get API token from Datapacks</li>
                        <li>Update in <a href="settings.php" style="text-decoration: underline;">Settings</a></li>
                        <li>Orders will auto-process after 10 minutes</li>
                    </ol>
                </div>
                <?php endif; ?>
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

        // Mark single order as processed
        function markProcessed(orderId) {
            if (!confirm('Have you completed this order on the provider website?')) {
                return;
            }

            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processing...';

            fetch('/api/admin/mark-completed.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({order_id: orderId})
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Order marked as completed! Profit: GHS ' + result.profit);
                    location.reload();
                } else {
                    alert('❌ Error: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }

        // Mark all orders as completed
        function markAllCompleted() {
            const orderCount = <?= count($pendingOrders) ?>;
            
            if (!confirm(`Are you sure you want to mark ALL ${orderCount} pending orders as completed?\n\nOnly do this if you have processed all orders on your provider's website.`)) {
                return;
            }

            const btn = document.getElementById('markAllBtn');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processing all orders...';

            fetch('/api/admin/mark-all-completed.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(`✅ Success!\n\n${result.count} orders marked as completed\nTotal Profit: GHS ${result.total_profit}`);
                    location.reload();
                } else {
                    alert('❌ Error: ' + result.message);
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        }

        // View order details
        function viewDetails(orderId) {
            window.open('order-details.php?order_id=' + encodeURIComponent(orderId), '_blank');
        }

        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
