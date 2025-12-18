<?php
session_start();
require_once '../../config/database.php';

// Simple auth check - implement proper auth in production
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get recent orders (last 20)
    $stmt = $db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
    
    echo json_encode($recentOrders);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>