<?php
/**
 * ULTRA-LITE Cron - Maximum speed, minimum operations
 * Sends pending orders to API immediately (no 10-min delay)
 */

// Set timezone to Ghana (GMT)
date_default_timezone_set('Africa/Accra');

// Security - using a simpler key without URL-problematic characters
$providedKey = $_GET['key'] ?? '';
$expectedKey = 'datapadi_cron_secret_2025';

if ($providedKey !== $expectedKey) {
    http_response_code(403);
    die('Forbidden - Invalid key');
}

// Now output OK
echo "OK - Authorized\n";
if (ob_get_level()) ob_end_flush();
flush();

// Log
$log = __DIR__ . '/../cron_logs/ultra_' . date('Y-m-d') . '.log';
@mkdir(__DIR__ . '/../cron_logs', 0755, true);
$t = date('H:i:s');

try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    
    // Get 1 pending order that needs processing (skip scheduled_jobs table entirely)
    // This is faster - directly check orders table
    // Only get orders that haven't been sent to API yet (no provider_ref)
    $stmt = $db->prepare("
        SELECT order_id, network, package_size, customer_phone, created_at
        FROM orders 
        WHERE status = 'pending'
        AND payment_status = 'paid'
        AND provider_ref IS NULL
        ORDER BY created_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    $order = $stmt->fetch();
    
    if (!$order) {
        @file_put_contents($log, "[$t] No pending orders\n", FILE_APPEND);
        echo "No work\n";
        exit;
    }
    
    @file_put_contents($log, "[$t] Processing: {$order['order_id']} - {$order['customer_phone']} - {$order['package_size']}\n", FILE_APPEND);
    
    // Get API token
    $stmt = $db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
    $stmt->execute();
    $apiToken = $stmt->fetchColumn();
    
    if (empty($apiToken) || $apiToken === 'YOUR_API_TOKEN_HERE') {
        @file_put_contents($log, "[$t] ERROR: No API token\n", FILE_APPEND);
        echo "No API token\n";
        exit;
    }
    
    // Parse capacity
    preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $order['package_size'], $m);
    if (empty($m)) {
        @file_put_contents($log, "[$t] ERROR: Bad package format: {$order['package_size']}\n", FILE_APPEND);
        // Mark as failed so we don't keep retrying
        $stmt = $db->prepare("UPDATE orders SET status = 'failed' WHERE order_id = ?");
        $stmt->execute([$order['order_id']]);
        exit;
    }
    
    $capacity = floatval($m[1]);
    if (strtoupper($m[2]) === 'MB') $capacity /= 1024;
    
    // Call API
    $ch = curl_init('https://datapacks.shop/api.php?action=order');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'network' => strtoupper($order['network']),
            'capacity' => $capacity,
            'recipient' => $order['customer_phone'],
            'client_ref' => $order['order_id']
        ])
    ]);
    
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    @file_put_contents($log, "[$t] API Response ($code): " . substr($resp, 0, 300) . "\n", FILE_APPEND);
    
    if ($err) {
        @file_put_contents($log, "[$t] CURL Error: $err\n", FILE_APPEND);
        echo "Curl error\n";
        exit;
    }
    
    $result = json_decode($resp, true);
    
    if ($code === 200 && !empty($result['success'])) {
        // SUCCESS
        $ref = $result['results'][0]['ref'] ?? null;
        
        $stmt = $db->prepare("
            UPDATE orders SET 
                status = 'processing',
                provider = 'api',
                processing_method = 'automatic',
                provider_ref = ?,
                processed_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$ref, $order['order_id']]);
        
        // Also mark scheduled job as done (if exists)
        $stmt = $db->prepare("UPDATE scheduled_jobs SET status = 'completed', executed_at = NOW() WHERE order_id = ?");
        $stmt->execute([$order['order_id']]);
        
        // Add history
        $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, 'processing', 'Sent to API via cron')");
        $stmt->execute([$order['order_id']]);
        
        @file_put_contents($log, "[$t] SUCCESS - Ref: $ref\n", FILE_APPEND);
        echo "Success: {$order['order_id']}\n";
        
    } else {
        // FAILED
        $errMsg = $result['error']['message'] ?? "HTTP $code";
        @file_put_contents($log, "[$t] FAILED: $errMsg\n", FILE_APPEND);
        
        // Don't mark as failed yet - might be temporary issue
        // Just log and try again next minute
        echo "API Error: $errMsg\n";
    }
    
    // === PART 2: CHECK STATUS OF ONE PROCESSING ORDER ===
    @file_put_contents($log, "[$t] --- Checking order status ---\n", FILE_APPEND);
    
    $stmt = $db->prepare("
        SELECT order_id, provider_ref 
        FROM orders 
        WHERE status = 'processing' 
        AND provider = 'api' 
        AND provider_ref IS NOT NULL
        ORDER BY updated_at ASC
        LIMIT 1
    ");
    $stmt->execute();
    $processingOrder = $stmt->fetch();
    
    if (!$processingOrder) {
        @file_put_contents($log, "[$t] No processing orders to check\n", FILE_APPEND);
        exit;
    }
    
    @file_put_contents($log, "[$t] Checking: {$processingOrder['order_id']} (Ref: {$processingOrder['provider_ref']})\n", FILE_APPEND);
    
    // Check status via API
    $statusUrl = 'https://datapacks.shop/api.php?action=status&ref=' . urlencode($processingOrder['provider_ref']);
    
    $ch = curl_init($statusUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken
        ]
    ]);
    
    $statusResp = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $statusErr = curl_error($ch);
    curl_close($ch);
    
    if ($statusErr) {
        @file_put_contents($log, "[$t] Status check error: $statusErr\n", FILE_APPEND);
        exit;
    }
    
    $statusResult = json_decode($statusResp, true);
    
    if ($statusCode === 200 && !empty($statusResult['success'])) {
        $apiStatus = strtolower($statusResult['status'] ?? '');
        @file_put_contents($log, "[$t] API Status: $apiStatus\n", FILE_APPEND);
        
        // Map API status to local status
        if ($apiStatus === 'completed' || $apiStatus === 'success') {
            $newStatus = 'completed';
        } elseif ($apiStatus === 'failed' || $apiStatus === 'refunded' || $apiStatus === 'cancelled') {
            $newStatus = 'failed';
        } else {
            $newStatus = null; // Keep current status
        }
        
        // If status changed, update DB
        if ($newStatus) {
            $stmt = $db->prepare("
                UPDATE orders SET 
                    status = ?,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END
                WHERE order_id = ?
            ");
            $stmt->execute([$newStatus, $newStatus, $processingOrder['order_id']]);
            
            // Add history
            $stmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
            $stmt->execute([$processingOrder['order_id'], $newStatus, "Status updated from API: $apiStatus"]);
            
            @file_put_contents($log, "[$t] Updated to: $newStatus\n", FILE_APPEND);
            echo "Updated: {$processingOrder['order_id']} -> $newStatus\n";
        } else {
            @file_put_contents($log, "[$t] Status unchanged: $apiStatus\n", FILE_APPEND);
            echo "Unchanged: {$processingOrder['order_id']}\n";
        }
    } else {
        @file_put_contents($log, "[$t] Status check failed (HTTP $statusCode)\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    @file_put_contents($log, "[$t] EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "Error\n";
}
