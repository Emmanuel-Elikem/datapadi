<?php
// IMPORTANT: Delete this file after testing!
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Test Dashboard</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
require_once 'config/database.php';

try {
    $db = new Database();
    echo "✅ Database connected successfully!<br>";
    
    // Check tables
    $tables = ['orders', 'provider_pricing', 'admin_settings', 'scheduled_jobs'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Table '$table' exists with {$result['count']} records<br>";
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

// Test 2: API Endpoints
echo "<h2>2. API Endpoints Test</h2>";
$endpoints = [
    '/api/process-order.php',
    '/api/check-order-status.php',
    '/api/process-automatic.php'
];

foreach ($endpoints as $endpoint) {
    $file = $_SERVER['DOCUMENT_ROOT'] . $endpoint;
    if (file_exists($file)) {
        echo "✅ $endpoint exists<br>";
    } else {
        echo "❌ $endpoint NOT FOUND<br>";
    }
}

// Test 3: Service Classes
echo "<h2>3. Service Classes Test</h2>";
$services = [
    'services/OrderProcessor.php',
    'services/DatapacksAPI.php'
];

foreach ($services as $service) {
    if (file_exists($service)) {
        echo "✅ $service exists<br>";
        require_once $service;
    } else {
        echo "❌ $service NOT FOUND<br>";
    }
}

// Test 4: Check Settings
echo "<h2>4. System Settings</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM admin_settings");
    $stmt->execute();
    $settings = $stmt->fetchAll();
    
    echo "<table border='1' style='margin: 10px 0'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    foreach ($settings as $setting) {
        $displayValue = $setting['setting_key'] == 'datapacks_api_token' 
            ? '***hidden***' 
            : htmlspecialchars($setting['setting_value']);
        echo "<tr><td>{$setting['setting_key']}</td><td>$displayValue</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Cannot read settings: " . $e->getMessage();
}

// Test 5: Create Test Order Button
echo "<h2>5. Test Order Creation</h2>";
?>

<button onclick="testOrder()">Create Test Order</button>
<pre id="orderResult"></pre>

<script>
async function testOrder() {
    const testData = {
        network: 'mtn',
        package_size: '1 GB',
        selling_price: 5.90,
        quantity: 1,
        phone: '0241234567',
        email: 'test@example.com',
        payment_ref: 'TEST-' + Date.now(),
        device_fingerprint: 'test-device'
    };
    
    try {
        const response = await fetch('/api/process-order.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(testData)
        });
        
        const result = await response.json();
        document.getElementById('orderResult').textContent = JSON.stringify(result, null, 2);
        
        if (result.success) {
            alert('Test order created: ' + result.order_id);
        }
    } catch (error) {
        document.getElementById('orderResult').textContent = 'Error: ' + error.message;
    }
}
</script>

<?php
echo "<hr>";
echo "<p><strong>Security Note:</strong> Delete this test file immediately after testing!</p>";
?>