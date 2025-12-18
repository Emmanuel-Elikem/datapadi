<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../services/OrderProcessor.php';

// Log incoming request for debugging
error_log('Process Automatic Called: ' . file_get_contents('php://input'));

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Order ID required'
    ]);
    exit;
}

try {
    $db = new Database();
    $processor = new OrderProcessor($db);
    
    // Check if order exists and is still pending
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE order_id = ? 
        AND status = 'pending'
    ");
    $stmt->execute([$input['order_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        // Order doesn't exist or already processed
        echo json_encode([
            'success' => false,
            'message' => 'Order not found or already processed',
            'order_id' => $input['order_id']
        ]);
        exit;
    }
    
    // Check how old the order is
    $orderAge = time() - strtotime($order['created_at']);
    $manualWindowSeconds = 600; // 10 minutes
    
    if ($orderAge < $manualWindowSeconds) {
        // Order is still within manual window
        echo json_encode([
            'success' => false,
            'message' => 'Order still within manual processing window',
            'order_id' => $input['order_id'],
            'age_seconds' => $orderAge,
            'wait_seconds' => $manualWindowSeconds - $orderAge
        ]);
        exit;
    }
    
    // Process automatically via API
    $success = $processor->processAutomatically($input['order_id']);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Order processed successfully via API',
            'order_id' => $input['order_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process order via API',
            'order_id' => $input['order_id']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Process Automatic Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>