<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die('Unauthorized');
}

require_once 'config/database.php';
$db = new Database();
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Monitor</title>
    <meta http-equiv="refresh" content="30">
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .status.active { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.failed { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>📊 System Monitor</h1>
    
    <div class="card">
        <h2>System Status</h2>
        <?php
        // Check API Token
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
        $stmt->execute();
        $token = $stmt->fetchColumn();
        echo "API Token: " . ($token && $token !== 'YOUR_API_TOKEN_HERE' ? '<span class="status active">Configured</span>' : '<span class="status failed">Not Set</span>') . "<br>";
        
        // Check maintenance mode
        $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $maintenance = $stmt->fetchColumn();
        echo "Site Status: " . ($maintenance === 'false' ? '<span class="status active">Live</span>' : '<span class="status failed">Maintenance</span>') . "<br>";
        ?>
    </div>
    
    <div class="card">
        <h2>Recent Orders (Last 10)</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Package</th>
                <th>Status</th>
                <th>Provider</th>
                <th>Created</th>
            </tr>
            <?php
            $stmt = $db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            $orders = $stmt->fetchAll();
            
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>{$order['order_id']}</td>";
                echo "<td>{$order['customer_phone']}</td>";
                echo "<td>{$order['network']} {$order['package_size']}</td>";
                echo "<td><span class='status {$order['status']}'>{$order['status']}</span></td>";
                echo "<td>{$order['provider']}</td>";
                echo "<td>" . date('H:i:s', strtotime($order['created_at'])) . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
    
    <div class="card">
        <h2>Pending Jobs</h2>
        <?php
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM scheduled_jobs WHERE status = 'pending'");
        $stmt->execute();
        $pending = $stmt->fetch();
        echo "Pending automatic processing: {$pending['count']} orders";
        ?>
    </div>
</body>
</html>