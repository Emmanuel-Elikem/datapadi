<?php
// Webhook handler for API status updates
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Verify webhook signature
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$expectedSignature = 'sha256=' . hash_hmac('sha256', $input, 'YOUR_WEBHOOK_SECRET');

if ($signature !== $expectedSignature) {
    http_response_code(403);
    exit('Invalid signature');
}

// Log webhook data
file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . ' - ' . $input . PHP_EOL, FILE_APPEND);

// Update order status in database
require_once '../config/database.php';
$db = new Database();

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

// Send notification to customer if completed
if ($data['status'] === 'complete') {
    // Send SMS or email notification
    notifyCustomer($data['recipient'], 'Your data package has been delivered!');
}

http_response_code(200);
echo json_encode(['status' => 'success']);