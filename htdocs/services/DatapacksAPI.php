<?php
/**
 * DatapacksAPI Service Class
 * Handles all API interactions with Datapacks.shop
 */

class DatapacksAPI {
    private $apiToken;
    private $baseUrl = 'https://datapacks.shop/api.php';
    private $db;
    private $isEnabled = false;
    
    /**
     * Constructor
     * @param Database $db Database connection object
     */
    public function __construct($db) {
        $this->db = $db;
        $this->apiToken = $this->getApiToken();
        
        // Check if API is properly configured
        $this->isEnabled = !empty($this->apiToken) && 
                          $this->apiToken !== 'YOUR_API_TOKEN_HERE' &&
                          $this->apiToken !== '';
    }
    
    /**
     * Get API token from database
     * @return string API token or empty string
     */
    private function getApiToken() {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'datapacks_api_token'");
            $stmt->execute();
            $token = $stmt->fetchColumn();
            return $token ?: '';
        } catch (Exception $e) {
            error_log('Error fetching API token: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Check if API is configured and ready to use
     * @return bool
     */
    public function isConfigured() {
        return $this->isEnabled;
    }
    
    /**
     * Get available bundles and prices from API
     * @param string|null $network Optional network filter (MTN, AirtelTigo, Telecel)
     * @return array Bundle data
     * @throws Exception
     */
    public function getBundles($network = null) {
        if (!$this->isEnabled) {
            throw new Exception('API not configured. Please add API token in settings.');
        }
        
        $url = $this->baseUrl . '?action=bundles';
        if ($network) {
            $url .= '&network=' . urlencode($network);
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Network error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('API error: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['success']) || !$data['success']) {
            throw new Exception('Failed to fetch bundles: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return $data['bundles'] ?? [];
    }
    
    /**
     * Place an order through the API
     * @param string $network Network operator (MTN, AirtelTigo, Telecel)
     * @param float $capacity Data size in GB
     * @param string $recipient Phone number (10 digits, starts with 0)
     * @param string $clientRef Your order reference
     * @return array Order result with ref and status
     * @throws Exception
     */
    public function placeOrder($network, $capacity, $recipient, $clientRef) {
        // Check if API is configured
        if (!$this->isEnabled) {
            throw new Exception('API not configured yet. Please process manually.');
        }
        
        // Validate inputs
        if (!in_array(strtoupper($network), ['MTN', 'AIRTELTIGO', 'TELECEL'])) {
            throw new Exception('Invalid network: ' . $network);
        }
        
        if (!preg_match('/^0\d{9}$/', $recipient)) {
            throw new Exception('Invalid phone number format. Must be 10 digits starting with 0');
        }
        
        if ($capacity <= 0) {
            throw new Exception('Invalid capacity. Must be greater than 0');
        }
        
        // Generate idempotency key to prevent duplicate orders
        $idempotencyKey = $this->generateUUID();
        
        // Prepare order data
        $data = [
            'network' => strtoupper($network),
            'capacity' => $capacity,
            'recipient' => $recipient,
            'client_ref' => $clientRef
        ];
        
        // Make API request
        $ch = curl_init($this->baseUrl . '?action=order');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'Idempotency-Key: ' . $idempotencyKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log the request for debugging
        error_log('Datapacks API Order Request: ' . json_encode($data));
        error_log('Datapacks API Order Response: ' . $response);
        
        if ($error) {
            throw new Exception('Network error placing order: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        // Handle different response codes
        if ($httpCode === 200 && isset($result['success']) && $result['success']) {
            return $result['results'][0];
        }
        
        // Handle specific error codes
        if ($httpCode === 401) {
            throw new Exception('API authentication failed. Please check API token.');
        }
        
        if ($httpCode === 422) {
            throw new Exception('Invalid order data: ' . ($result['error']['message'] ?? 'Validation failed'));
        }
        
        if ($httpCode === 429) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }
        
        if ($httpCode === 409) {
            throw new Exception('Duplicate order detected.');
        }
        
        // Generic error
        throw new Exception('API Order failed: ' . ($result['error']['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')'));
    }
    
    /**
     * Check order status
     * @param string $ref Order reference (either your ref or API ref)
     * @return array|null Order status data
     * @throws Exception
     */
    public function checkStatus($ref) {
        if (!$this->isEnabled) {
            throw new Exception('API not configured');
        }
        
        $url = $this->baseUrl . '?action=status&ref=' . urlencode($ref);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Network error checking status: ' . $error);
        }
        
        if ($httpCode === 404) {
            return null; // Order not found
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to check status: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['success']) || !$data['success']) {
            throw new Exception('Status check failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    /**
     * Get wallet balance
     * @return array Balance data with amount and currency
     * @throws Exception
     */
    public function getBalance() {
        if (!$this->isEnabled) {
            throw new Exception('API not configured');
        }
        
        $url = $this->baseUrl . '?action=balance';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Network error fetching balance: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to fetch balance: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['success']) || !$data['success']) {
            throw new Exception('Balance check failed: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return [
            'balance' => $data['balance'] ?? 0,
            'currency' => $data['currency'] ?? 'GHS',
            'sandbox' => $data['sandbox'] ?? false
        ];
    }
    
    /**
     * Process bulk orders
     * @param array $orders Array of order data
     * @return array Results for each order
     * @throws Exception
     */
    public function bulkOrder($orders) {
        if (!$this->isEnabled) {
            throw new Exception('API not configured');
        }
        
        if (empty($orders) || !is_array($orders)) {
            throw new Exception('Invalid orders array');
        }
        
        $idempotencyKey = $this->generateUUID();
        
        $data = ['orders' => $orders];
        
        $ch = curl_init($this->baseUrl . '?action=bulk_order');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'Idempotency-Key: ' . $idempotencyKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60, // Longer timeout for bulk
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Network error processing bulk order: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Bulk order failed: HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['success']) || !$result['success']) {
            throw new Exception('Bulk order failed: ' . ($result['error']['message'] ?? 'Unknown error'));
        }
        
        return $result['results'] ?? [];
    }
    
    /**
     * Update stored bundles in database from API
     * @return int Number of bundles updated
     * @throws Exception
     */
    public function syncBundles() {
        if (!$this->isEnabled) {
            throw new Exception('API not configured');
        }
        
        $bundles = $this->getBundles();
        $updated = 0;
        
        foreach ($bundles as $bundle) {
            // Update or insert bundle pricing
            $stmt = $this->db->prepare("
                INSERT INTO provider_pricing (provider, network, package_size, cost_price, is_active)
                VALUES ('api', ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    cost_price = VALUES(cost_price),
                    updated_at = NOW()
            ");
            
            $packageSize = $bundle['capacity'] . ' GB';
            $stmt->execute([
                $bundle['network_key'],
                $packageSize,
                $bundle['price']
            ]);
            
            $updated++;
        }
        
        return $updated;
    }
    
    /**
     * Generate UUID v4 for idempotency
     * @return string UUID
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Test API connection
     * @return array Test results
     */
    public function testConnection() {
        $results = [
            'configured' => $this->isEnabled,
            'token_present' => !empty($this->apiToken),
            'can_connect' => false,
            'can_authenticate' => false,
            'balance_check' => false,
            'error' => null
        ];
        
        if (!$this->isEnabled) {
            $results['error'] = 'API not configured';
            return $results;
        }
        
        try {
            // Try to get balance as a connection test
            $balance = $this->getBalance();
            $results['can_connect'] = true;
            $results['can_authenticate'] = true;
            $results['balance_check'] = true;
            $results['balance'] = $balance['balance'];
            $results['currency'] = $balance['currency'];
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            
            // Check if it's connection or auth issue
            if (strpos($e->getMessage(), 'Network error') !== false) {
                $results['can_connect'] = false;
            } elseif (strpos($e->getMessage(), 'authentication') !== false) {
                $results['can_connect'] = true;
                $results['can_authenticate'] = false;
            }
        }
        
        return $results;
    }
}
?>