<?php
// webhook.php - handles both Paystack and Make.com webhooks

$input = file_get_contents("php://input");
$headers = getallheaders();

// Determine webhook source
if (isset($headers['X-Paystack-Signature'])) {
    // Handle Paystack webhook
    handlePaystackWebhook($input, $headers);
} elseif (isset($headers['X-Make-Signature']) || strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Make') !== false) {
    // Handle Make.com webhook
    handleMakeWebhook($input);
} else {
    // Handle Datapacks API webhook
    handleDatapacksWebhook($input, $headers);
}

function handlePaystackWebhook($input, $headers) {
    // Verify Paystack signature
    $paystackSecret = 'sk_test_92fc83ed1236232d6c8593211fea32d075e6c528'; // Your Paystack secret key
    $signature = $headers['X-Paystack-Signature'];
    
    if ($signature !== hash_hmac('sha512', $input, $paystackSecret)) {
        http_response_code(403);
        exit('Invalid signature');
    }
    
    $data = json_decode($input, true);
    
    // Log the webhook
    file_put_contents('paystack_webhooks.log', date('Y-m-d H:i:s') . ' - ' . $input . PHP_EOL, FILE_APPEND);
    
    // Process based on event
    if ($data['event'] === 'charge.success') {
        // Payment successful
        // The order should already be created, this is just confirmation
        require_once 'config/database.php';
        $db = new Database();
        
        $reference = $data['data']['reference'];
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE payment_ref = ?");
        $stmt->execute([$reference]);
    }
    
    http_response_code(200);
}

function handleMakeWebhook($input) {
    // Make.com is calling us
    $data = json_decode($input, true);
    
    // Forward to Make.com webhook URL (your existing integration)
    $makeUrl = "https://hook.eu2.make.com/1ndg8aavirewhevqutx97cwp152fc1hq";
    
    $ch = curl_init($makeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
}

function handleDatapacksWebhook($input, $headers) {
    // Verify webhook signature from Datapacks API
    require_once 'config/database.php';
    $db = new Database();
    
    // Get webhook secret
    $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'webhook_secret'");
    $stmt->execute();
    $secret = $stmt->fetchColumn();
    
    $signature = $headers['X-Signature'] ?? '';
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $input, $secret);
    
    if ($signature !== $expectedSignature) {
        http_response_code(403);
        exit('Invalid signature');
    }
    
    $data = json_decode($input, true);
    
    // Update order status
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = ?, 
            provider_ref = ?,
            completed_at = CASE WHEN ? = 'complete' THEN NOW() ELSE completed_at END
        WHERE order_id = ? OR provider_ref = ?
    ");
    
    $stmt->execute([
        $data['status'],
        $data['ref'],
        $data['status'],
        $data['client_ref'],
        $data['ref']
    ]);
    
    // Log status history
    $stmt = $db->prepare("
        INSERT INTO order_status_history (order_id, status, notes)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $data['client_ref'],
        $data['status'],
        'Status updated via webhook from API provider'
    ]);
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
?>