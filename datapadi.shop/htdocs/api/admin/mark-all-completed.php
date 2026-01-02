<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get all pending orders with their manual costs
    $stmt = $db->prepare("
        SELECT o.*, pp.cost_price as manual_cost
        FROM orders o
        LEFT JOIN provider_pricing pp 
            ON pp.provider = 'manual' 
            AND pp.network = o.network 
            AND pp.package_size = o.package_size
        WHERE o.status = 'pending'
    ");
    $stmt->execute();
    $pendingOrders = $stmt->fetchAll();
    
    if (count($pendingOrders) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No pending orders to process',
            'count' => 0
        ]);
        exit;
    }
    
    // Start transaction using Database helper methods
    $db->beginTransaction();
    
    $completedCount = 0;
    $totalProfit = 0.0;
    
    foreach ($pendingOrders as $order) {
        // Fallback if manual cost is missing
        $manualCost = isset($order['manual_cost']) && $order['manual_cost'] !== null
            ? (float)$order['manual_cost']
            : (float)($order['cost_price'] ?? 0);

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
        $stmt->execute([$manualCost, $order['order_id']]);
        
        // Add to history
        $stmt = $db->prepare("
            INSERT INTO order_status_history (order_id, status, notes)
            VALUES (?, 'completed', 'Bulk completed by admin')
        ");
        $stmt->execute([$order['order_id']]);
        
        $profit = ((float)$order['selling_price']) - $manualCost;
        $totalProfit += $profit;
        $completedCount++;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'All pending orders marked as completed',
        'count' => $completedCount,
        'total_profit' => number_format($totalProfit, 2)
    ]);
    exit;
    
} catch (Exception $e) {
    // Attempt rollback safely
    try { 
        if (isset($db)) { 
            $db->rollback(); 
        } 
    } catch (Exception $ignored) {}
    
    error_log("Bulk mark completed error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process orders: ' . $e->getMessage()
    ]);
    exit;
}
