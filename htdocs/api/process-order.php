<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../services/OrderProcessor.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db = new Database();
    $processor = new OrderProcessor($db);
    
    // Process the order
    $orderId = $processor->processOrder($input);
    
    // Send to Make.com for automation
    triggerMakeWebhook($orderId, $input);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order processed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Trigger Make.com webhook for order automation
 */
function triggerMakeWebhook($orderId, $orderData) {
    // Your Make.com webhook URL
    $makeWebhookUrl = 'https://hook.eu2.make.com/qvvp5da4kq6fp6d18ilst77xvff3ls8v'; // Update with your actual URL
    
    $webhookData = [
        'order_id' => $orderId,
        'created_at' => date('Y-m-d H:i:s'),
        'network' => $orderData['network'],
        'package_size' => $orderData['package_size'],
        'phone' => $orderData['phone'],
        'email' => $orderData['email'] ?? '',
        'quantity' => $orderData['quantity'] ?? 1,
        'device_fingerprint' => $orderData['device_fingerprint'] ?? ''
    ];
    
    $ch = curl_init($makeWebhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($webhookData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 5 // Don't wait too long
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the webhook trigger
    error_log("Make.com webhook triggered for order $orderId - Response: $httpCode");
    
    return $httpCode === 200;
}
?>