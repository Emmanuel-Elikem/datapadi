<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    $db = new Database();
    
    // Get manual cost for profit calculation
    $stmt = $db->prepare("
        SELECT o.*, pp.cost_price as manual_cost
        FROM orders o
        LEFT JOIN provider_pricing pp 
            ON pp.provider = 'manual' 
            AND pp.network = o.network 
            AND pp.package_size = o.package_size
        WHERE o.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Update order as completed with manual processing
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = 'completed',
            provider = 'manual',
            processing_method = 'manual',
            cost_price = ?,
            processed_at = NOW(),
            completed_at = NOW()
        WHERE order_id = ?
    ");
    
    $stmt->execute([$order['manual_cost'], $orderId]);
    
    // Add to history
    $stmt = $db->prepare("
        INSERT INTO order_status_history (order_id, status, notes)
        VALUES (?, 'completed', 'Manually processed by admin')
    ");
    $stmt->execute([$orderId]);
    
    $profit = $order['selling_price'] - $order['manual_cost'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Order marked as completed',
        'profit' => number_format($profit, 2)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>