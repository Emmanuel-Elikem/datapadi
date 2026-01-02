<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Get date range
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

// Get profit data - FIXED: properly calculating profit from completed orders
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        network,
        package_size,
        provider,
        COUNT(*) as order_count,
        SUM(selling_price * quantity) as revenue,
        SUM(cost_price * quantity) as cost,
        SUM((selling_price - cost_price) * quantity) as profit
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status IN ('completed','processing')
    GROUP BY DATE(created_at), network, package_size, provider
    ORDER BY date DESC, profit DESC
");
$stmt->execute([$startDate, $endDate]);
$profitData = $stmt->fetchAll();

// Calculate totals
$totals = [
    'revenue' => 0,
    'cost' => 0,
    'profit' => 0,
    'orders' => 0
];

foreach ($profitData as $row) {
    $totals['revenue'] += $row['revenue'];
    $totals['cost'] += $row['cost'];
    $totals['profit'] += $row['profit'];
    $totals['orders'] += $row['order_count'];
}

// Get daily profit chart data
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        SUM((selling_price - cost_price) * quantity) as daily_profit
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status IN ('completed','processing')
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$startDate, $endDate]);
$chartData = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Report - DataPadi Admin</title>
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
            max-width: 100%;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .date-filter {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .date-filter-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-filter input {
            flex: 1;
            min-width: 120px;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
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
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .summary-card h3 {
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .summary-card.profit {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }

        .summary-card.profit .summary-value {
            color: #065f46;
        }

        /* Chart */
        .chart-container {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            max-width: 100%;
            overflow: hidden;
        }

        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .chart-wrapper {
            position: relative;
            height: 250px;
            max-width: 100%;
        }

        /* Data Table */
        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .table-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .table-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            text-align: left;
            padding: 0.75rem;
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        td {
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.875rem;
        }

        .provider-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .provider-api {
            background: #dbeafe;
            color: #1e40af;
        }

        .provider-manual {
            background: #fef3c7;
            color: #92400e;
        }

        .profit-positive {
            color: var(--success);
            font-weight: 600;
        }

        .profit-negative {
            color: var(--error);
            font-weight: 600;
        }

        /* Export modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1001;
            padding: 1rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .export-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .export-option {
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .export-option:hover {
            border-color: var(--primary);
            background: #f9fafb;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .tips-box {
            background: #e0e7ff;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .tips-box h3 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            color: var(--dark);
        }

        .tips-box ul {
            margin-left: 1.25rem;
            line-height: 1.6;
            font-size: 0.875rem;
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

            .page-title {
                font-size: 2rem;
            }

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: start;
            }

            .date-filter {
                flex-direction: row;
                align-items: center;
            }

            .chart-wrapper {
                height: 300px;
            }

            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .summary-value {
                font-size: 2rem;
            }

            th {
                font-size: 0.875rem;
                padding: 1rem;
            }

            td {
                padding: 1rem;
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
                <li class="menu-item"><a href="pending-orders.php"><i class="fas fa-clock fa-fw"></i> Pending Orders</a></li>
                <li class="menu-item"><a href="package-manager.php"><i class="fas fa-box fa-fw"></i> Package Manager</a></li>
                <li class="menu-item active"><a href="profit-report.php"><i class="fas fa-chart-line fa-fw"></i> Profit Report</a></li>
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
            <div class="mobile-title">Profit Report</div>
            <div style="width: 2rem;"></div>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <div class="container">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-line"></i>
                        Profit Analytics
                    </h1>
                    <div class="date-filter">
                        <div class="date-filter-row">
                            <input type="date" id="startDate" value="<?= $startDate ?>">
                            <span style="color: #6b7280;">to</span>
                            <input type="date" id="endDate" value="<?= $endDate ?>">
                        </div>
                        <div class="date-filter-row">
                            <button class="btn btn-primary" onclick="applyFilter()">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <button class="btn btn-success" onclick="showExportModal()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <h3>Total Revenue</h3>
                        <div class="summary-value">GHS <?= number_format($totals['revenue'], 2) ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Cost</h3>
                        <div class="summary-value">GHS <?= number_format($totals['cost'], 2) ?></div>
                    </div>
                    <div class="summary-card profit">
                        <h3>Total Profit</h3>
                        <div class="summary-value">GHS <?= number_format($totals['profit'], 2) ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Orders</h3>
                        <div class="summary-value"><?= number_format($totals['orders']) ?></div>
                    </div>
                </div>
                
                <!-- Profit Chart -->
                <div class="chart-container">
                    <h2 class="chart-title">Daily Profit Trend</h2>
                    <div class="chart-wrapper">
                        <canvas id="profitChart"></canvas>
                    </div>
                </div>
                
                <!-- Detailed Table -->
                <div class="data-table">
                    <div class="table-header">
                        <h2 class="table-title">Detailed Profit Breakdown</h2>
                        <span style="color: #6b7280; font-size: 0.875rem;">
                            <?= count($profitData) ?> entries
                        </span>
                    </div>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Network</th>
                                    <th>Package</th>
                                    <th>Provider</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                    <th>Cost</th>
                                    <th>Profit</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($profitData)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; color: #6b7280; padding: 2rem;">
                                        No completed orders in this date range
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($profitData as $row): ?>
                                <?php 
                                    $margin = $row['revenue'] > 0 ? ($row['profit'] / $row['revenue']) * 100 : 0;
                                    $profitClass = $row['profit'] >= 0 ? 'profit-positive' : 'profit-negative';
                                ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td><?= strtoupper($row['network']) ?></td>
                                    <td><?= $row['package_size'] ?></td>
                                    <td>
                                        <span class="provider-badge provider-<?= $row['provider'] ?>">
                                            <?= ucfirst($row['provider']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['order_count'] ?></td>
                                    <td>GHS <?= number_format($row['revenue'], 2) ?></td>
                                    <td>GHS <?= number_format($row['cost'], 2) ?></td>
                                    <td class="<?= $profitClass ?>">GHS <?= number_format($row['profit'], 2) ?></td>
                                    <td><?= number_format($margin, 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tips -->
                <div class="tips-box">
                    <h3><i class="fas fa-lightbulb"></i> Understanding Your Profit</h3>
                    <ul>
                        <li><strong>Revenue:</strong> Total amount customers paid</li>
                        <li><strong>Cost:</strong> What you paid to providers</li>
                        <li><strong>Profit:</strong> Your earnings (Revenue - Cost)</li>
                        <li><strong>Margin:</strong> Profit percentage (Profit / Revenue × 100)</li>
                        <li><strong>Manual processing</strong> typically yields higher profit margins</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Export Modal -->
    <div class="modal" id="exportModal">
        <div class="modal-content">
            <h2 class="modal-title">Export Profit Report</h2>
            <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                Choose your export format for the period:<br>
                <strong><?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?></strong>
            </p>
            <div class="export-options">
                <div class="export-option" onclick="exportData('csv')">
                    <i class="fas fa-file-csv" style="color: #10b981; margin-right: 0.5rem;"></i>
                    <strong>CSV File</strong>
                    <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">
                        Best for Excel and spreadsheet analysis
                    </p>
                </div>
                <div class="export-option" onclick="exportData('pdf')">
                    <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 0.5rem;"></i>
                    <strong>PDF Report</strong>
                    <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">
                        Professional report with charts
                    </p>
                </div>
            </div>
            <button class="btn" style="background: #e5e7eb; width: 100%; color: var(--dark);" onclick="closeExportModal()">
                Cancel
            </button>
        </div>
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

        // Chart setup
        const ctx = document.getElementById('profitChart').getContext('2d');
        const chartData = <?= json_encode($chartData) ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Daily Profit',
                    data: chartData.map(d => d.daily_profit),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Profit: GHS ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'GHS ' + value;
                            }
                        }
                    }
                }
            }
        });
        
        function applyFilter() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            window.location.href = `?start=${start}&end=${end}`;
        }
        
        function showExportModal() {
            document.getElementById('exportModal').classList.add('active');
        }
        
        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('active');
        }
        
        function exportData(format) {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            alert(`Export feature coming soon! Format: ${format}`);
            closeExportModal();
        }
    </script>
</body>
</html>
