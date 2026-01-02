<?php
// This file should be run every minute via cron job
    
// Security: Only allow requests with secret key
$secret = $_GET['key'] ?? '';
if ($secret !== 'KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=') {
    http_response_code(403);
    exit('Forbidden');
}

require_once '../config/database.php';
require_once '../services/OrderProcessor.php';

try {
    $db = new Database();
    $processor = new OrderProcessor($db);
    
    // Get pending jobs that should be executed
    $stmt = $db->prepare("
        SELECT * FROM scheduled_jobs 
        WHERE status = 'pending' 
        AND execute_at <= NOW()
        AND job_type = 'auto_process_order'
        LIMIT 10
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll();
    
    foreach ($jobs as $job) {
        // Mark job as processing
        $updateStmt = $db->prepare("
            UPDATE scheduled_jobs 
            SET status = 'processing' 
            WHERE id = ?
        ");
        $updateStmt->execute([$job['id']]);
        
        // Process the order
        $success = $processor->processAutomatically($job['order_id']);
        
        // Update job status
        $finalStatus = $success ? 'completed' : 'failed';
        $updateStmt = $db->prepare("
            UPDATE scheduled_jobs 
            SET status = ?, executed_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$finalStatus, $job['id']]);
        
        echo "Processed order {$job['order_id']}: " . ($success ? 'Success' : 'Failed') . "\n";
    }
    
} catch (Exception $e) {
    error_log("Cron job error: " . $e->getMessage());
}
?>