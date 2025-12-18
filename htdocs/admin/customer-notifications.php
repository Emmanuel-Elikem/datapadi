<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Get all notifications, joining with the orders table to get customer info
$stmt = $db->prepare("
    SELECT 
        cn.*, 
        o.customer_phone, 
        o.network, 
        o.package_size
    FROM customer_notifications cn
    JOIN orders o ON cn.order_id = o.order_id
    ORDER BY cn.created_at DESC
    LIMIT 100
");
$stmt->execute();
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Notifications - DataPadi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #1f2937; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: #6366f1; text-decoration: none; margin-bottom: 2rem; }
        .notification-table { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; background: #f9fafb; font-weight: 600; color: #6b7280; font-size: 0.875rem; }
        td { padding: 1rem; border-top: 1px solid #e5e7eb; }
        .type-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .type-announcement { background: #fef3c7; color: #92400e; }
        .type-delay { background: #fee2e2; color: #991b1b; }
        .time { font-size: 0.875rem; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1><i class="fas fa-bell"></i> Customer Notification Log</h1>
        
        <div class="notification-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Customer / Order</th>
                        <th>Message Sent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #6b7280; padding: 2rem;">No notifications have been sent yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td class="time"><?= date('M d, H:i', strtotime($notification['created_at'])) ?></td>
                            <td>
                                <span class="type-badge type-<?= strtolower($notification['type']) ?>">
                                    <?= ucfirst($notification['type']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($notification['customer_phone']) ?></strong><br>
                                <small style="color: #6b7280;"><?= htmlspecialchars($notification['order_id']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>