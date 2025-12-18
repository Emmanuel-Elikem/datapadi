<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['api_token'])) {
        $token = trim($_POST['api_token']);
        
        $stmt = $db->prepare("
            UPDATE admin_settings 
            SET setting_value = ?, updated_at = NOW() 
            WHERE setting_key = 'datapacks_api_token'
        ");
        $stmt->execute([$token]);
        
        $message = '<div class="alert alert-success">✅ API Token updated successfully!</div>';
    }
}

// Get current settings
$stmt = $db->prepare("SELECT * FROM admin_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - DataPadi Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            font-family: monospace;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-indicator {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-configured {
            background: #d4edda;
            color: #155724;
        }
        .status-not-configured {
            background: #f8d7da;
            color: #721c24;
        }
        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .instructions h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ System Settings</h1>
        
        <?= $message ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="api_token">
                    Datapacks API Token
                    <?php 
                    $isConfigured = !empty($settings['datapacks_api_token']) && 
                                   $settings['datapacks_api_token'] !== 'YOUR_API_TOKEN_HERE';
                    ?>
                    <span class="status-indicator <?= $isConfigured ? 'status-configured' : 'status-not-configured' ?>">
                        <?= $isConfigured ? '✓ Configured' : '✗ Not Configured' ?>
                    </span>
                </label>
                <input type="text" 
                       id="api_token" 
                       name="api_token" 
                       value="<?= htmlspecialchars($settings['datapacks_api_token'] ?? '') ?>"
                       placeholder="Enter your API token here when approved">
                <small style="color: #666;">
                    Leave as "YOUR_API_TOKEN_HERE" if not yet approved. 
                    The system will process all orders manually.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
        
        <div class="instructions">
            <h3>📋 How to Get Your API Token:</h3>
            <ol>
                <li><strong>Request API Access:</strong> Go to Datapacks.shop API page</li>
                <li><strong>Fill the form with:</strong>
                    <ul style="margin-top: 5px;">
                        <li>Client Name: DataPadi</li>
                        <li>Domain: datapadi.shop</li>
                        <li>Webhook URL: https://datapadi.shop/api/webhook.php</li>
                    </ul>
                </li>
                <li><strong>Wait for approval</strong> (usually 24-48 hours)</li>
                <li><strong>Once approved:</strong> You'll receive your API token</li>
                <li><strong>Paste the token</strong> in the field above and save</li>
            </ol>
            
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                <strong>🔄 Current System Mode:</strong><br>
                <?php if ($isConfigured): ?>
                    <span style="color: #28a745;">✅ API Mode Active</span> - Orders auto-process after 10 minutes if not handled manually
                <?php else: ?>
                    <span style="color: #dc3545;">⚠️ Manual Mode</span> - All orders require manual processing (API not configured)
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="pending-orders.php" class="btn btn-primary">View Pending Orders</a>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html> 