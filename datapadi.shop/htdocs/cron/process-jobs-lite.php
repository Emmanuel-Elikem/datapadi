<?php
/**
 * LITE Cron Processor - Designed to complete in under 10 seconds
 * Processes only 1 order at a time to avoid timeouts on free hosting
 */

// Set max execution time
set_time_limit(25);

// Quick response headers
header('Content-Type: text/plain');

// Security check FIRST (before any DB)
$secret = $_GET['key'] ?? '';
$expectedSecret = 'KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=';

if ($secret !== $expectedSecret) {
    http_response_code(403);
    die('Forbidden');
}

echo "OK - Starting\n";
flush();

// Simple log function
$logFile = __DIR__ . '/../cron_logs/lite_' . date('Y-m-d') . '.log';
$logDir = __DIR__ . '/../cron_logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

function quickLog($msg) {
    global $logFile;
    $line = "[" . date('H:i:s') . "] $msg\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
    flush();
}

quickLog("=== LITE CRON START ===");

try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    quickLog("DB Connected");
    
    // STEP 1: Get ONLY 1 pending job (to be fast)
    $stmt = $db->prepare("
        SELECT sj.id, sj.order_id, o.network, o.package_size, o.customer_phone
        FROM scheduled_jobs sj
        JOIN orders o ON o.order_id = sj.order_id
        WHERE sj.status = 'pending' 
        AND sj.execute_at <= NOW()
        AND sj.job_type = 'auto_process_order'
        AND o.status = 'pending'
        ORDER BY sj.execute_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    $job = $stmt->fetch();
    
    if (!$job) {
        quickLog("No pending jobs to process");
        quickLog("=== LITE CRON END (no work) ===");
        exit;
    }
    
    quickLog("Processing Job #{$job['id']} - Order: {$job['order_id']}");
    quickLog("  Phone: {$job['customer_phone']}, Package: {$job['package_size']}, Network: {$job['network']}");
    
    // Mark job as processing
    $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'processing' WHERE id = ?");
    $stmt->execute([$job['id']]);
    
    // Get API token
    $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
    $stmt->execute();
    $apiToken = $stmt->fetchColumn();
    
    if (empty($apiToken) || $apiToken === 'YOUR_API_TOKEN_HERE') {
        quickLog("ERROR: API token not configured");
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'failed' WHERE id = ?");
        $stmt->execute([$job['id']]);
        exit;
    }
    
    // Parse capacity from package size (e.g., "5 GB" -> 5)
    preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $job['package_size'], $matches);
    if (empty($matches)) {
        quickLog("ERROR: Invalid package size format: {$job['package_size']}");
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'failed' WHERE id = ?");
        $stmt->execute([$job['id']]);
        exit;
    }
    
    $capacity = floatval($matches[1]);
    if (strtoupper($matches[2]) === 'MB') {
        $capacity = $capacity / 1024;
    }
    
    quickLog("  Capacity: {$capacity} GB");
    
    // Call the API with a SHORT timeout
    $apiUrl = 'https://datapacks.shop/api.php?action=order';
    $postData = json_encode([
        'network' => strtoupper($job['network']),
        'capacity' => $capacity,
        'recipient' => $job['customer_phone'],
        'client_ref' => $job['order_id']
    ]);
    
    quickLog("  Calling API...");
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'Idempotency-Key: ' . uniqid('cron_', true)
        ],
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 15,        // 15 second max
        CURLOPT_CONNECTTIMEOUT => 5,  // 5 second connect timeout
        CURLOPT_SSL_VERIFYPEER => false // Skip SSL verification for speed
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    quickLog("  API Response Code: $httpCode");
    
    if ($curlError) {
        quickLog("  CURL Error: $curlError");
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'pending' WHERE id = ?");
        $stmt->execute([$job['id']]);
        exit;
    }
    
    $result = json_decode($response, true);
    quickLog("  API Response: " . substr($response, 0, 200));
    
    if ($httpCode === 200 && isset($result['success']) && $result['success']) {
        // SUCCESS!
        $providerRef = $result['results'][0]['ref'] ?? null;
        
        quickLog("  SUCCESS! Provider Ref: $providerRef");
        
        // Update order
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'processing', 
                provider = 'api',
                processing_method = 'automatic',
                provider_ref = ?,
                processed_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$providerRef, $job['order_id']]);
        
        // Mark job completed
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'completed', executed_at = NOW() WHERE id = ?");
        $stmt->execute([$job['id']]);
        
        // Add history
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'processing', 'Sent to API automatically')");
        $stmt->execute([$job['order_id']]);
        
        quickLog("  Order updated to 'processing'");
        
    } else {
        // FAILED
        $errorMsg = $result['error']['message'] ?? "HTTP $httpCode";
        quickLog("  FAILED: $errorMsg");
        
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'failed', executed_at = NOW() WHERE id = ?");
        $stmt->execute([$job['id']]);
        
        // Mark order as failed too
        $stmt = $db->prepare("UPDATE orders SET status = 'failed' WHERE order_id = ?");
        $stmt->execute([$job['order_id']]);
        
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'failed', ?)");
        $stmt->execute([$job['order_id'], "API Error: $errorMsg"]);
    }
    
    quickLog("=== LITE CRON END (success) ===");
    
} catch (Exception $e) {
    quickLog("FATAL: " . $e->getMessage());
    exit;
}
