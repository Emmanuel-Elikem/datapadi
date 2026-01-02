<?php
/**
 * Scheduled Jobs Processor with Comprehensive Logging
 * This file should be run every minute via cron job
 * Configure as: curl "https://yourdomain.com/cron/process-scheduled-jobs.php?key=YOUR_SECRET_KEY"
 */

// === CRON LOG SETUP ===
// Log file path - logs every cron execution with date rotation
$logFile = __DIR__ . '/../cron_logs/jobs_' . date('Y-m-d') . '.log';
$logDir = __DIR__ . '/../cron_logs';

// Create logs directory if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log function with timestamps and severity levels
function logCron($message, $logFile, $severity = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$severity}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

// START LOGGING
logCron("=== Cron Job Started ===", $logFile, 'START');
logCron("Execution Time: " . date('Y-m-d H:i:s T'), $logFile, 'INFO');
logCron("Server Time Zone: " . date_default_timezone_get(), $logFile, 'INFO');

// Security: Only allow requests with secret key
$secret = $_GET['key'] ?? '';
$expectedSecret = 'KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=';

if ($secret !== $expectedSecret) {
    logCron("Invalid or missing secret key provided", $logFile, 'ERROR');
    http_response_code(403);
    exit('Forbidden');
}

logCron("✓ Secret key validated successfully", $logFile, 'OK');

require_once '../config/database.php';
require_once '../services/OrderProcessor.php';

try {
    $db = new Database();
    $processor = new OrderProcessor($db);
    
    logCron("✓ Database connection established", $logFile, 'OK');
    logCron("Database: " . $db->getConnection()->getAttribute(PDO::ATTR_CONNECTION_STATUS), $logFile, 'DEBUG');
    
    // Get pending jobs that should be executed
    $stmt = $db->prepare("
        SELECT id, order_id, job_type, execute_at, created_at
        FROM scheduled_jobs 
        WHERE status = 'pending' 
        AND execute_at <= NOW()
        AND job_type = 'auto_process_order'
        ORDER BY execute_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll();
    
    $jobCount = count($jobs);
    logCron("Query executed - Found {$jobCount} pending job(s) to process", $logFile, 'INFO');
    
    if ($jobCount === 0) {
        logCron("No jobs to process at this time", $logFile, 'INFO');
        logCron("=== Cron Job Ended (SUCCESS - No Work) ===", $logFile, 'END');
        exit("No pending jobs.\n");
    }
    
    // Process each job
    $successCount = 0;
    $failureCount = 0;
    
    foreach ($jobs as $job) {
        logCron("", $logFile, 'INFO');
        logCron("--- Processing Job ---", $logFile, 'INFO');
        logCron("Job ID: {$job['id']}", $logFile, 'INFO');
        logCron("Order ID: {$job['order_id']}", $logFile, 'INFO');
        logCron("Job Type: {$job['job_type']}", $logFile, 'INFO');
        logCron("Scheduled For: {$job['execute_at']}", $logFile, 'INFO');
        logCron("Created At: {$job['created_at']}", $logFile, 'DEBUG');
        logCron("Time Until Execution: " . ((time() - strtotime($job['execute_at'])) / 60) . " minutes late", $logFile, 'DEBUG');
        
        try {
            // Mark job as processing
            $updateStmt = $db->prepare("UPDATE scheduled_jobs SET status = 'processing' WHERE id = ?");
            $updateStmt->execute([$job['id']]);
            logCron("  → Status updated to 'processing'", $logFile, 'DEBUG');
            
            // Process the order
            logCron("  → Calling processAutomatically() for order {$job['order_id']}", $logFile, 'INFO');
            $success = $processor->processAutomatically($job['order_id']);
            
            // Update job status
            $finalStatus = $success ? 'completed' : 'failed';
            $updateStmt = $db->prepare("UPDATE scheduled_jobs SET status = ?, executed_at = NOW() WHERE id = ?");
            $updateStmt->execute([$finalStatus, $job['id']]);
            
            if ($success) {
                logCron("  ✓ SUCCESS - Order sent to API provider and marked completed", $logFile, 'OK');
                $successCount++;
            } else {
                logCron("  ✗ FAILED - Order processing returned false (may require manual intervention)", $logFile, 'WARN');
                $failureCount++;
            }
            
        } catch (Exception $e) {
            logCron("  ✗ EXCEPTION: " . $e->getMessage(), $logFile, 'ERROR');
            
            // Mark as failed
            $updateStmt = $db->prepare("UPDATE scheduled_jobs SET status = 'failed', executed_at = NOW(), notes = ? WHERE id = ?");
            $updateStmt->execute([$e->getMessage(), $job['id']]);
            $failureCount++;
        }
    }
    
    logCron("", $logFile, 'INFO');
    logCron("=== Summary ===", $logFile, 'INFO');
    logCron("Total Jobs Processed: {$jobCount}", $logFile, 'INFO');
    logCron("Successful: {$successCount}", $logFile, 'OK');
    logCron("Failed: {$failureCount}", $logFile, 'WARN');

    // === PART 2: CHECK STATUS OF PROCESSING ORDERS ===
    logCron("", $logFile, 'INFO');
    logCron("=== Checking Status of Processing Orders ===", $logFile, 'INFO');

    // Get orders that are 'processing' via API
    // Limit to 5 to prevent timeouts
    $stmt = $db->prepare("
        SELECT order_id, provider_ref 
        FROM orders 
        WHERE status = 'processing' 
        AND provider = 'api' 
        AND provider_ref IS NOT NULL
        ORDER BY updated_at ASC
        LIMIT 5
    ");
    $stmt->execute();
    $processingOrders = $stmt->fetchAll();
    
    $checkCount = count($processingOrders);
    logCron("Found {$checkCount} orders to check status", $logFile, 'INFO');

    if ($checkCount > 0) {
        foreach ($processingOrders as $order) {
            logCron("Checking status for Order #{$order['order_id']} (Ref: {$order['provider_ref']})", $logFile, 'INFO');
            
            try {
                $result = $processor->checkAndUpdateStatus($order['order_id']);
                
                if ($result['success']) {
                    logCron("  → " . $result['message'], $logFile, 'OK');
                } else {
                    logCron("  → Failed: " . $result['message'], $logFile, 'WARN');
                }
                
                // Small delay to be nice to the API
                usleep(500000); // 0.5 seconds
                
            } catch (Exception $e) {
                logCron("  → Exception: " . $e->getMessage(), $logFile, 'ERROR');
            }
        }
    } else {
        logCron("No processing orders to check", $logFile, 'INFO');
    }

    logCron("=== Cron Job Ended (SUCCESS) ===", $logFile, 'END');
    
} catch (Exception $e) {
    logCron("FATAL ERROR: " . $e->getMessage(), $logFile, 'FATAL');
    logCron("Stack Trace: " . $e->getTraceAsString(), $logFile, 'FATAL');
    logCron("=== Cron Job Ended (ERROR) ===", $logFile, 'END');
    error_log("Cron job error: " . $e->getMessage());
}
?>