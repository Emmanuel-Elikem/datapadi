<?php
require_once 'config/database.php';

$orderId = $_GET['id'] ?? 'ORD-20251013-F79C09'; // Your test order

$db = new Database();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if ($order) {
    echo "<h2>Order Found in Database ✅</h2>";
    echo "<pre>";
    echo "Order ID: " . $order['order_id'] . "\n";
    echo "Status: " . $order['status'] . "\n";
    echo "Provider: " . ($order['provider'] ?: 'Not set') . "\n";
    echo "Created: " . $order['created_at'] . "\n";
    echo "Network: " . $order['network'] . "\n";
    echo "Package: " . $order['package_size'] . "\n";
    echo "Phone: " . $order['customer_phone'] . "\n";
    echo "Cost Price: GHS " . $order['cost_price'] . "\n";
    echo "Selling Price: GHS " . $order['selling_price'] . "\n";
    echo "Profit: GHS " . $order['profit'] . "\n";
    echo "</pre>";
    
    // Check if scheduled job exists
    $stmt = $db->prepare("SELECT * FROM scheduled_jobs WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $job = $stmt->fetch();
    
    if ($job) {
        echo "<h3>Scheduled Job:</h3><pre>";
        echo "Execute at: " . $job['execute_at'] . "\n";
        echo "Status: " . $job['status'] . "\n";
        echo "</pre>";
    }
} else {
    echo "<h2>Order not found ❌</h2>";
}
?>