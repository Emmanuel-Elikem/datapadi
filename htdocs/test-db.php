<?php
// REMOVE THIS FILE AFTER TESTING!
require_once 'config/database.php';

try {
    $db = new Database();
    echo "✅ Database connection successful!<br>";
    
    // Test query
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "Orders in database: " . $result['count'];
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>