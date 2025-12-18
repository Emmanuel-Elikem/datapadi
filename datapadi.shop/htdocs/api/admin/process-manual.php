<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../services/OrderProcessor.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID required']);
    exit;
}

try {
    $db = new Database();
    $processor = new OrderProcessor($db);
    
    $success = $processor->processManually($orderId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Order marked for manual processing'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process order'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>