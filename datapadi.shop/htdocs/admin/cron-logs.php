<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$logDir = __DIR__ . '/../cron_logs';
$message = '';

// Handle log download
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    // Validate filename (security) - allow both jobs_ and lite_ prefixes
    if (preg_match('/^(jobs|lite)_\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
        $filepath = $logDir . '/' . $filename;
        if (file_exists($filepath)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            readfile($filepath);
            exit;
        }
    }
}

// Get all log files
$logFiles = [];
if (is_dir($logDir)) {
    $files = scandir($logDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (preg_match('/^(jobs|lite)_\d{4}-\d{2}-\d{2}\.log$/', $file)) {
            $filepath = $logDir . '/' . $file;
            $logFiles[] = [
                'name' => $file,
                'date' => substr($file, 5, 10),
                'size' => filesize($filepath),
                'modified' => filemtime($filepath)
            ];
        }
    }
}

// Get current job status
$db = new Database();
$stmt = $db->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM scheduled_jobs
    WHERE job_type = 'auto_process_order'
    GROUP BY status
");
$stmt->execute();
$jobStatsRaw = $stmt->fetchAll();
$jobStats = [];
foreach ($jobStatsRaw as $row) {
    $jobStats[$row['status']] = $row['count'];
}

// Get recent jobs
$stmt = $db->prepare("
    SELECT 
        sj.id,
        sj.order_id,
        sj.status,
        sj.execute_at,
        sj.executed_at,
        o.customer_phone,
        o.package_size,
        o.network
    FROM scheduled_jobs sj
    LEFT JOIN orders o ON o.order_id = sj.order_id
    WHERE sj.job_type = 'auto_process_order'
    ORDER BY sj.execute_at DESC
    LIMIT 20
");
$stmt->execute();
$recentJobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Job Logs - DataPadi Admin</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6366f1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #f3f4f6;
        }

        h1 {
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 2rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .card h2 {
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #6366f1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
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
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
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

        .download-btn {
            padding: 0.5rem 1rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .download-btn:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .file-list {
            list-style: none;
        }

        .file-item {
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .file-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .cron-url {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.875rem;
            word-break: break-all;
            color: #1f2937;
            margin: 1rem 0;
        }

        .copy-btn {
            padding: 0.5rem 1rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #4f46e5;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <h1>
                    <i class="fas fa-tasks"></i>
                    Cron Jobs & Logs
                </h1>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>About Cron Jobs:</strong> Automatic processing runs every minute. When an order is placed, it's scheduled to process automatically after 10 minutes (manual processing window). Your wholesale provider should see the order appear in their dashboard once processing completes.
            </div>
        </div>

        <div class="grid-2">
            <!-- Job Status Card -->
            <div class="card">
                <h2><i class="fas fa-chart-pie"></i> Job Status</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number"><?= $jobStats['pending'] ?? 0 ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $jobStats['completed'] ?? 0 ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $jobStats['processing'] ?? 0 ?></div>
                        <div class="stat-label">Processing</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $jobStats['failed'] ?? 0 ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
            </div>

            <!-- Cron Setup Card -->
            <div class="card">
                <h2><i class="fas fa-cogs"></i> Cron Configuration</h2>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    Configure your hosting's cron job to run this URL every minute:
                </p>
                <div class="cron-url">
                    curl "https://yourdomain.com/cron/process-scheduled-jobs.php?key=KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg="
                </div>
                <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">
                    <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Replace "yourdomain.com" with your actual domain.
                </p>
                <button class="copy-btn" onclick="copyToClipboard()">
                    <i class="fas fa-copy"></i> Copy Cron URL
                </button>
            </div>
        </div>

        <!-- Recent Jobs -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2><i class="fas fa-history"></i> Recent Scheduled Jobs (Last 20)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Order ID</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Scheduled For</th>
                        <th>Executed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentJobs as $job): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($job['id']) ?></td>
                        <td>
                            <a href="order-details.php?order_id=<?= htmlspecialchars($job['order_id']) ?>" 
                               style="color: #6366f1; text-decoration: none;">
                                <?= htmlspecialchars($job['order_id']) ?>
                            </a>
                        </td>
                        <td>
                            <?= htmlspecialchars($job['network'] ?? 'N/A') ?> 
                            <?= htmlspecialchars($job['package_size'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower($job['status']) ?>">
                                <?= ucfirst(htmlspecialchars($job['status'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($job['execute_at']) ?></td>
                        <td><?= $job['executed_at'] ? htmlspecialchars($job['executed_at']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Log Files -->
        <div class="card">
            <h2><i class="fas fa-file-alt"></i> Cron Log Files</h2>
            <?php if (count($logFiles) === 0): ?>
            <p style="color: #6b7280; padding: 1rem;">
                <i class="fas fa-info-circle"></i> No log files found. Logs will be created when the cron job runs.
            </p>
            <?php else: ?>
            <ul class="file-list">
                <?php foreach ($logFiles as $file): ?>
                <li class="file-item">
                    <div class="file-info">
                        <div class="file-name">
                            <i class="fas fa-file-text" style="color: #6366f1;"></i>
                            <?= htmlspecialchars($file['name']) ?>
                        </div>
                        <div class="file-meta">
                            Size: <?= round($file['size'] / 1024, 2) ?> KB | 
                            Modified: <?= date('Y-m-d H:i:s', $file['modified']) ?>
                        </div>
                    </div>
                    <a href="?download=<?= urlencode($file['name']) ?>" class="download-btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Help Section -->
        <div class="card" style="margin-top: 2rem; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
            <h2 style="color: #166534;"><i class="fas fa-lightbulb"></i> Troubleshooting</h2>
            <ul style="margin-left: 1.5rem; line-height: 2; color: #166534;">
                <li><strong>No logs appearing?</strong> Your cron job may not be running. Check with your hosting provider.</li>
                <li><strong>Jobs stuck in 'processing'?</strong> The cron job crashed. Check the error log file for details.</li>
                <li><strong>Order not showing on wholesale provider?</strong> Check job status — it should say 'completed'. If 'failed', contact support with the log file.</li>
                <li><strong>To share with your provider:</strong> Download the latest log file and send it to them. It shows exactly what happened during processing.</li>
            </ul>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const cronUrl = 'curl "https://yourdomain.com/cron/process-scheduled-jobs.php?key=KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg="';
            const textarea = document.createElement('textarea');
            textarea.value = cronUrl;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            alert('Cron URL copied to clipboard!');
        }
    </script>
</body>
</html>
