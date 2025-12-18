<?php
require_once __DIR__ . '/DatapacksAPI.php';

class OrderProcessor {
    private $db;
    private $api;
    private $manualWindowMinutes = 10;
    
    public function __construct($db) {
        $this->db = $db;
        $this->api = new DatapacksAPI($db);
    }
    
    public function processOrder($orderData) {
        try {
            $this->db->beginTransaction();
            
            // Generate order ID
            $orderId = $this->generateOrderId();
            
            // Get cost prices from both providers
            $apiCost = $this->getProviderCost('api', $orderData['network'], $orderData['package_size']);
            $manualCost = $this->getProviderCost('manual', $orderData['network'], $orderData['package_size']);

            // Choose an initial cost for insertion. Avoid NULL to prevent DB constraint issues.
            // Prefer manual cost, then API cost, else default to 0.00 (will be updated on processing)
            $costPrice = ($manualCost !== null) ? (float)$manualCost : (($apiCost !== null) ? (float)$apiCost : 0.0);
            
            // Insert order
            $stmt = $this->db->prepare("
                INSERT INTO orders (
                    order_id, customer_phone, customer_email, network, 
                    package_size, quantity, selling_price, cost_price,
                    payment_ref, payment_status, device_fingerprint, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, 'pending')
            ");
            
            $stmt->execute([
                $orderId,
                $orderData['phone'],
                $orderData['email'] ?? null,
                $orderData['network'],
                $orderData['package_size'],
                $orderData['quantity'] ?? 1,
                (float)$orderData['selling_price'],
                $costPrice,
                $orderData['payment_ref'] ?? null,
                $orderData['device_fingerprint'] ?? null
            ]);
            
            // Schedule automatic processing
            $this->scheduleAutomaticProcessing($orderId);
            
            // Add to status history (note if pricing was missing at creation time)
            $note = 'Order created and awaiting processing';
            if ($manualCost === null && $apiCost === null) {
                $note .= ' (provider cost not set yet - defaulted to 0.00)';
            }
            $this->addStatusHistory($orderId, 'pending', $note);
            
            $this->db->commit();
            
            // Send notification (if configured)
            $this->sendAdminNotification($orderId, $orderData);
            
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function generateOrderId() {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
    
    private function getProviderCost($provider, $network, $packageSize) {
        $stmt = $this->db->prepare("
            SELECT cost_price 
            FROM provider_pricing 
            WHERE provider = ? AND network = ? AND package_size = ? AND is_active = 1
        ");
        $stmt->execute([$provider, strtolower($network), $packageSize]);
        $result = $stmt->fetch();
        return $result ? $result['cost_price'] : null;
    }
    
    private function scheduleAutomaticProcessing($orderId) {
        $executeAt = date('Y-m-d H:i:s', strtotime("+{$this->manualWindowMinutes} minutes"));
        
        $stmt = $this->db->prepare("
            INSERT INTO scheduled_jobs (job_type, order_id, execute_at, status)
            VALUES ('auto_process_order', ?, ?, 'pending')
        ");
        
        $stmt->execute([$orderId, $executeAt]);
    }
    
    public function processManually($orderId) {
        try {
            $this->db->beginTransaction();
            
            // Check if order is still pending
            $stmt = $this->db->prepare("
                SELECT * FROM orders WHERE order_id = ? AND status = 'pending'
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found or already processed');
            }
            
            // Update order to manual processing
            $manualCost = $this->getProviderCost('manual', $order['network'], $order['package_size']);
            
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET provider = 'manual',
                    processing_method = 'manual',
                    status = 'processing',
                    cost_price = ?,
                    processed_at = NOW()
                WHERE order_id = ?
            ");
            
            $stmt->execute([$manualCost, $orderId]);
            
            // Cancel scheduled job
            $this->cancelScheduledJob($orderId);
            
            // Add to status history
            $this->addStatusHistory($orderId, 'processing', 'Order marked for manual processing');
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
public function processAutomatically($orderId) {
    try {
        // Check if API is configured
        if (!$this->api->isConfigured()) {
            // API not configured, log and keep as pending for manual processing
            $this->addStatusHistory($orderId, 'pending', 'API not configured - awaiting manual processing');
            
            // Send notification to admin
            $this->sendAdminNotification($orderId, [
                'message' => 'Order requires manual processing (API not configured)',
                'urgent' => true
            ]);
            
            // Return false but don't mark as failed
            return false;
        }
        
        $this->db->beginTransaction();
        
        // Get order details
        $stmt = $this->db->prepare("
            SELECT * FROM orders WHERE order_id = ? AND status = 'pending'
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            return false; // Already processed
        }
        
        // Convert package size to GB
        $capacity = $this->parseCapacity($order['package_size']);
        
        // Call API
        $result = $this->api->placeOrder(
            $order['network'],
            $capacity,
            $order['customer_phone'],
            $orderId
        );
        
        // Update order with API details
        $apiCost = $this->getProviderCost('api', $order['network'], $order['package_size']);
        
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET provider = 'api',
                processing_method = 'automatic',
                provider_ref = ?,
                status = 'processing',
                cost_price = ?,
                processed_at = NOW()
            WHERE order_id = ?
        ");
        
        $stmt->execute([$result['ref'], $apiCost, $orderId]);
        
        $this->addStatusHistory($orderId, 'processing', 'Order sent to API provider');
        
        $this->db->commit();
        
        return true;
        
    } catch (Exception $e) {
        $this->db->rollback();
        
        // If it's just API not configured, don't mark as failed
        if (strpos($e->getMessage(), 'API not configured') !== false) {
            $this->addStatusHistory($orderId, 'pending', 'Awaiting manual processing');
            return false;
        }
        
        // For other errors, mark as failed
        $this->markOrderFailed($orderId, $e->getMessage());
        return false;
    }
}
    
    private function parseCapacity($packageSize) {
        preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)/i', $packageSize, $matches);
        
        if (empty($matches)) {
            throw new Exception('Invalid package size format');
        }
        
        $value = floatval($matches[1]);
        $unit = strtoupper($matches[2]);
        
        if ($unit === 'MB') {
            $value = $value / 1024;
        }
        
        return $value;
    }
    
    private function cancelScheduledJob($orderId) {
        $stmt = $this->db->prepare("
            UPDATE scheduled_jobs 
            SET status = 'completed', executed_at = NOW()
            WHERE order_id = ? AND status = 'pending'
        ");
        $stmt->execute([$orderId]);
    }
    
    private function markOrderFailed($orderId, $reason) {
        $stmt = $this->db->prepare("
            UPDATE orders 
            SET status = 'failed', processed_at = NOW()
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        
        $this->addStatusHistory($orderId, 'failed', 'Error: ' . $reason);
    }
    
    private function addStatusHistory($orderId, $status, $notes) {
        $stmt = $this->db->prepare("
            INSERT INTO order_status_history (order_id, status, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$orderId, $status, $notes]);
    }
    
    private function sendAdminNotification($orderId, $orderData) {
        // Send email or SMS to admin about new order
        // You can implement this based on your preference
        
        // For now, just log it
        error_log("New order: $orderId - {$orderData['network']} {$orderData['package_size']}");
    }
}
?>