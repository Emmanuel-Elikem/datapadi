<?php
session_start();

// Simple authentication check (implement proper auth in production)
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get today's stats
    $today = date('Y-m-d');
    
    // Total orders today
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$today]);
    $todayOrders = $stmt->fetch()['count'];
    
    // Revenue today
    $stmt = $db->prepare("
        SELECT SUM(selling_price * quantity) as total 
        FROM orders 
        WHERE DATE(created_at) = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$today]);
    $todayRevenue = $stmt->fetch()['total'] ?? 0;
    
    // Profit today
    $stmt = $db->prepare("
        SELECT SUM(profit * quantity) as total 
        FROM orders 
        WHERE DATE(created_at) = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$today]);
    $todayProfit = $stmt->fetch()['total'] ?? 0;
    
    // Pending manual orders
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE status = 'pending' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute();
    $pendingManual = $stmt->fetch()['count'];
    
    // Get manual queue
    $stmt = $db->prepare("
        SELECT o.*,
               pp_manual.cost_price as manual_cost,
               pp_api.cost_price as api_cost,
               (o.selling_price - pp_manual.cost_price) as manual_profit,
               (o.selling_price - pp_api.cost_price) as api_profit,
               DATE_ADD(o.created_at, INTERVAL 10 MINUTE) as expires_at
        FROM orders o
        LEFT JOIN provider_pricing pp_manual 
            ON pp_manual.provider = 'manual' 
            AND pp_manual.network = o.network 
            AND pp_manual.package_size = o.package_size
        LEFT JOIN provider_pricing pp_api 
            ON pp_api.provider = 'api' 
            AND pp_api.network = o.network 
            AND pp_api.package_size = o.package_size
        WHERE o.status = 'pending'
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ORDER BY o.created_at ASC
    ");
    $stmt->execute();
    $manualQueue = $stmt->fetchAll();
    
    // Get recent orders
    $stmt = $db->prepare("
        SELECT * FROM orders 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
    
    echo json_encode([
        'stats' => [
            'today_orders' => $todayOrders,
            'today_revenue' => number_format($todayRevenue, 2),
            'today_profit' => number_format($todayProfit, 2),
            'pending_manual' => $pendingManual
        ],
        'manual_queue' => $manualQueue,
        'recent_orders' => $recentOrders
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>