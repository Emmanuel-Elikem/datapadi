<?php
// api/check-order-status.php (Corrected and Final Version)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allows the script to be called from anywhere

require_once __DIR__ . '/../config/database.php';

$orderId = $_GET['id'] ?? '';
$phone = $_GET['phone'] ?? '';

if (empty($orderId) && empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID or Phone Number is required.']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $response = ['success' => false, 'message' => 'No orders found.'];

    $orders = [];
    if (!empty($orderId)) {
        // Search by a single Order ID
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if ($order) {
            $orders[] = $order;
        }
    } elseif (!empty($phone)) {
        // Search by Phone Number, get all recent orders
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$phone]);
        $orders = $stmt->fetchAll();
    }

    if (!empty($orders)) {
        $response['success'] = true;
        $response['message'] = 'Orders found.';
        
        // Fetch any relevant system-wide announcement
        $stmt_announcement = $pdo->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'announcement'");
        $announcement = $stmt_announcement->fetchColumn();

        // Attach the announcement to each pending/processing order
        foreach ($orders as &$order) { // Use '&' to modify the array directly
            if (in_array($order['status'], ['pending', 'processing']) && !empty($announcement)) {
                $order['announcement'] = $announcement;
            } else {
                $order['announcement'] = null;
            }
        }
        
        $response['orders'] = $orders;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Check Order Status API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
?>