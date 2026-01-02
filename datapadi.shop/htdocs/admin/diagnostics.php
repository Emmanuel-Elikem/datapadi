<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$issues = [];
$warnings = [];
$successes = [];

// Check 1: API Configuration
$stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
$stmt->execute();
$apiToken = $stmt->fetchColumn();

if (empty($apiToken) || $apiToken === 'YOUR_API_TOKEN_HERE') {
    $issues[] = "API token is not configured. Orders will not be sent to your wholesale provider.";
} else {
    $successes[] = "API token is configured.";
}

// Check 2: Scheduled Jobs Table
$stmt = $db->prepare("SELECT COUNT(*) as count FROM scheduled_jobs WHERE job_type = 'auto_process_order' AND status = 'pending'");
$stmt->execute();
$result = $stmt->fetch();
$pendingJobs = $result['count'];

if ($pendingJobs > 20) {
    $warnings[] = "You have {$pendingJobs} pending jobs. If this grows continuously, your cron may not be running.";
}

// Check 3: Recent Cron Execution
$stmt = $db->prepare("
    SELECT MAX(executed_at) as last_executed 
    FROM scheduled_jobs 
    WHERE executed_at IS NOT NULL
");
$stmt->execute();
$result = $stmt->fetch();
$lastExecution = $result['last_executed'];

if ($lastExecution) {
    $minutesAgo = (time() - strtotime($lastExecution)) / 60;
    if ($minutesAgo > 5) {
        $warnings[] = "Last cron execution was {$minutesAgo} minutes ago. Cron may not be running frequently.";
    } else {
        $successes[] = "Cron executed recently ({$minutesAgo} minutes ago).";
    }
} else {
    $issues[] = "No cron executions found. Cron has never run or is not configured.";
}

// Check 4: Database Orders vs Scheduled Jobs
$stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stmt->execute();
$pendingOrders = $stmt->fetch()['count'];

if ($pendingOrders > 0 && $pendingJobs === 0) {
    $warnings[] = "You have {$pendingOrders} pending orders but no scheduled jobs. Orders may not be processed.";
}

// Check 5: Failed Jobs
$stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM scheduled_jobs 
    WHERE status = 'failed' 
    AND executed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$failedJobs = $stmt->fetch()['count'];

if ($failedJobs > 0) {
    $issues[] = "You have {$failedJobs} failed jobs in the last 24 hours. Check the cron log for details.";
}

// Check 6: Log Files Exist
$logDir = __DIR__ . '/../cron_logs';
if (is_dir($logDir) && count(scandir($logDir)) > 2) {
    $successes[] = "Cron log files are being created.";
} else {
    $warnings[] = "No cron log files found. Logs will be created once cron runs.";
}

// Get sample order that should have been processed
$stmt = $db->prepare("
    SELECT 
        o.order_id,
        o.created_at,
        o.status,
        sj.id as job_id,
        sj.status as job_status,
        sj.execute_at,
        sj.executed_at
    FROM orders o
    LEFT JOIN scheduled_jobs sj ON sj.order_id = o.order_id AND sj.job_type = 'auto_process_order'
    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostics - DataPadi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6366f1;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        h1 {
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 2rem;
        }

        .status-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .status-card {
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .status-card.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .status-card.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .status-card.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%);
            color: #991b1b;
        }

        .status-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.875rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-processing {
            background: #e0e7ff;
            color: #3730a3;
        }

        .recommendation {
            background: linear-gradient(135deg, #e0e7ff 0%, #f3f4f6 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1rem;
            border-left: 4px solid #6366f1;
        }

        .recommendation h4 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .recommendation p {
            color: #4b5563;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .status-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <h1>
                <i class="fas fa-stethoscope"></i>
                System Health & Diagnostics
            </h1>
        </div>

        <!-- Status Summary -->
        <div class="status-summary">
            <div class="status-card success">
                <div class="status-number"><?= count($successes) ?></div>
                <div class="status-label">Healthy</div>
            </div>
            <div class="status-card warning">
                <div class="status-number"><?= count($warnings) ?></div>
                <div class="status-label">Warnings</div>
            </div>
            <div class="status-card error">
                <div class="status-number"><?= count($issues) ?></div>
                <div class="status-label">Issues</div>
            </div>
        </div>

        <!-- Issues -->
        <?php if (!empty($issues)): ?>
        <div class="card">
            <h2><i class="fas fa-exclamation-circle"></i> Critical Issues</h2>
            <?php foreach ($issues as $issue): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($issue) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Warnings -->
        <?php if (!empty($warnings)): ?>
        <div class="card">
            <h2><i class="fas fa-exclamation-triangle"></i> Warnings</h2>
            <?php foreach ($warnings as $warning): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($warning) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Successes -->
        <?php if (!empty($successes)): ?>
        <div class="card">
            <h2><i class="fas fa-check-circle"></i> Healthy Components</h2>
            <?php foreach ($successes as $success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Recent Orders & Jobs -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Recent Orders (Last Hour)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Created</th>
                        <th>Order Status</th>
                        <th>Job ID</th>
                        <th>Job Status</th>
                        <th>Scheduled For</th>
                        <th>Executed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>
                            <a href="order-details.php?order_id=<?= urlencode($order['order_id']) ?>" 
                               style="color: #6366f1; text-decoration: none;">
                                <?= htmlspecialchars($order['order_id']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($order['created_at']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                            </span>
                        </td>
                        <td><?= $order['job_id'] ? '#' . htmlspecialchars($order['job_id']) : '—' ?></td>
                        <td>
                            <?php if ($order['job_status']): ?>
                                <span class="status-badge status-<?= strtolower($order['job_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($order['job_status'])) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #6b7280;">No job</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $order['execute_at'] ? htmlspecialchars($order['execute_at']) : '—' ?></td>
                        <td><?= $order['executed_at'] ? htmlspecialchars($order['executed_at']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="recommendation">
                <h4><i class="fas fa-lightbulb"></i> How to Read This Table</h4>
                <p><strong>Order Status:</strong> "pending" = waiting for processing, "processing" = sent to API, "completed" = done</p>
                <p><strong>Job Status:</strong> "pending" = scheduled but not run yet, "completed" = cron ran successfully, "failed" = cron encountered error</p>
                <p><strong>Timeline:</strong> Order created → after 10 min → job scheduled → cron runs → sent to API → appears on provider's dashboard</p>
            </div>
        </div>

        <!-- Action Items -->
        <div class="card">
            <h2><i class="fas fa-tasks"></i> Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="cron-logs.php" style="padding: 1rem; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.2s;" 
                   onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <i class="fas fa-file-alt"></i> View Cron Logs
                </a>
                <a href="dashboard.php" style="padding: 1rem; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.2s;"
                   onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="pending-orders.php" style="padding: 1rem; background: #6366f1; color: white; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.2s;"
                   onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    <i class="fas fa-hourglass-half"></i> Pending Orders
                </a>
            </div>
        </div>
    </div>
</body>
</html>
