<?php
/**
 * One-time cleanup script to clear stale scheduled jobs
 * Run this once to clean up old pending jobs for already-delivered orders
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Security - only allow logged-in admins or with secret key
$secret = $_GET['key'] ?? '';
$expectedSecret = 'KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=';

if ($secret !== $expectedSecret && !isset($_SESSION['admin_logged_in'])) {
    die('Unauthorized. Please login to admin first or use ?key=YOUR_SECRET');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cleanup Scheduled Jobs - DataPadi</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .card { background: #f9f9f9; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; }
        .btn { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>🧹 Cleanup Scheduled Jobs</h1>

<?php
$db = new Database();

// Get current stats
$stmt = $db->prepare("
    SELECT 
        sj.status as job_status,
        o.status as order_status,
        COUNT(*) as count
    FROM scheduled_jobs sj
    LEFT JOIN orders o ON o.order_id = sj.order_id
    GROUP BY sj.status, o.status
    ORDER BY sj.status, o.status
");
$stmt->execute();
$stats = $stmt->fetchAll();

echo '<div class="card">';
echo '<h2>📊 Current Status</h2>';
echo '<table>';
echo '<tr><th>Job Status</th><th>Order Status</th><th>Count</th></tr>';
foreach ($stats as $stat) {
    echo "<tr><td>{$stat['job_status']}</td><td>{$stat['order_status']}</td><td>{$stat['count']}</td></tr>";
}
echo '</table>';
echo '</div>';

// Check for stale jobs (pending jobs for non-pending orders)
$stmt = $db->prepare("
    SELECT COUNT(*) as count
    FROM scheduled_jobs sj
    JOIN orders o ON o.order_id = sj.order_id
    WHERE sj.status = 'pending'
    AND o.status != 'pending'
");
$stmt->execute();
$staleCount = $stmt->fetchColumn();

echo '<div class="info">';
echo "<strong>Found {$staleCount} stale jobs</strong> (pending jobs for orders that are already completed/failed/processing)";
echo '</div>';

// Handle cleanup action
if (isset($_POST['cleanup'])) {
    $db->beginTransaction();
    
    try {
        // Mark all pending jobs for non-pending orders as 'cancelled'
        $stmt = $db->prepare("
            UPDATE scheduled_jobs sj
            JOIN orders o ON o.order_id = sj.order_id
            SET sj.status = 'cancelled', 
                sj.executed_at = NOW()
            WHERE sj.status = 'pending'
            AND o.status != 'pending'
        ");
        $stmt->execute();
        $cancelledCount = $stmt->rowCount();
        
        $db->commit();
        
        echo '<div class="success">';
        echo "<strong>✅ Successfully cancelled {$cancelledCount} stale jobs!</strong>";
        echo '</div>';
        
    } catch (Exception $e) {
        $db->rollback();
        echo '<div class="warning">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle full purge action
if (isset($_POST['purge_all'])) {
    $db->beginTransaction();
    
    try {
        // Delete all completed/cancelled/failed jobs older than 7 days
        $stmt = $db->prepare("
            DELETE FROM scheduled_jobs 
            WHERE status IN ('completed', 'cancelled', 'failed')
            AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $deletedOld = $stmt->rowCount();
        
        // Cancel ALL remaining pending jobs
        $stmt = $db->prepare("
            UPDATE scheduled_jobs 
            SET status = 'cancelled', 
                executed_at = NOW()
            WHERE status = 'pending'
        ");
        $stmt->execute();
        $cancelledAll = $stmt->rowCount();
        
        $db->commit();
        
        echo '<div class="success">';
        echo "<strong>✅ Purge complete!</strong><br>";
        echo "- Deleted {$deletedOld} old completed jobs<br>";
        echo "- Cancelled {$cancelledAll} pending jobs";
        echo '</div>';
        
    } catch (Exception $e) {
        $db->rollback();
        echo '<div class="warning">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Re-fetch stats after cleanup
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM scheduled_jobs GROUP BY status");
$stmt->execute();
$newStats = $stmt->fetchAll();

echo '<div class="card">';
echo '<h2>📈 Updated Job Counts</h2>';
echo '<table>';
echo '<tr><th>Status</th><th>Count</th></tr>';
foreach ($newStats as $stat) {
    echo "<tr><td>{$stat['status']}</td><td>{$stat['count']}</td></tr>";
}
echo '</table>';
echo '</div>';
?>

<div class="card">
    <h2>🔧 Actions</h2>
    
    <form method="POST" style="display: inline-block; margin-right: 10px;">
        <button type="submit" name="cleanup" class="btn btn-success" onclick="return confirm('This will cancel all pending jobs for orders that are already delivered. Continue?')">
            ✅ Cancel Stale Jobs Only
        </button>
    </form>
    
    <form method="POST" style="display: inline-block;">
        <button type="submit" name="purge_all" class="btn" onclick="return confirm('⚠️ This will cancel ALL pending jobs and delete old records. Are you sure?')">
            🗑️ Purge Everything
        </button>
    </form>
    
    <p style="margin-top: 15px; color: #666;">
        <strong>Cancel Stale Jobs:</strong> Only cancels pending jobs where the order is already completed/failed.<br>
        <strong>Purge Everything:</strong> Cancels ALL pending jobs and deletes old records. Use if you want a fresh start.
    </p>
</div>

<p><a href="dashboard.php">← Back to Dashboard</a></p>

</body>
</html>
