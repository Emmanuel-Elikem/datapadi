<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';

// Handle package deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_package'])) {
    $network = $_POST['network'];
    $package_size = $_POST['package_size'];

    try {
        $db->getConnection()->beginTransaction();
        
        // Delete from selling_prices
        $stmt1 = $db->prepare("DELETE FROM selling_prices WHERE network = ? AND package_size = ?");
        $stmt1->execute([$network, $package_size]);
        
        // Delete from provider_pricing
        $stmt2 = $db->prepare("DELETE FROM provider_pricing WHERE network = ? AND package_size = ?");
        $stmt2->execute([$network, $package_size]);
        
        $db->getConnection()->commit();
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Package deleted successfully!</div>';
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        error_log("Delete Package Error: " . $e->getMessage());
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error deleting package.</div>';
    }
}

// Handle adding new package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package'])) {
    $network = $_POST['network'];
    $package_size = $_POST['package_size'];
    $selling_price = filter_input(INPUT_POST, 'selling_price', FILTER_VALIDATE_FLOAT);
    $api_cost = filter_input(INPUT_POST, 'api_cost', FILTER_VALIDATE_FLOAT);
    $manual_cost = filter_input(INPUT_POST, 'manual_cost', FILTER_VALIDATE_FLOAT);

    try {
        $db->getConnection()->beginTransaction();
        
        // Insert into selling_prices
        $stmt1 = $db->prepare("
            INSERT INTO selling_prices (network, package_size, price) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE price = VALUES(price)
        ");
        $stmt1->execute([$network, $package_size, $selling_price]);
        
        // Insert API cost if provided
        if ($api_cost !== false && $api_cost >= 0) {
            $stmt2 = $db->prepare("
                INSERT INTO provider_pricing (provider, network, package_size, cost_price, is_active)
                VALUES ('api', ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cost_price = VALUES(cost_price)
            ");
            $stmt2->execute([$network, $package_size, $api_cost]);
        }
        
        // Insert Manual cost if provided
        if ($manual_cost !== false && $manual_cost >= 0) {
            $stmt3 = $db->prepare("
                INSERT INTO provider_pricing (provider, network, package_size, cost_price, is_active)
                VALUES ('manual', ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cost_price = VALUES(cost_price)
            ");
            $stmt3->execute([$network, $package_size, $manual_cost]);
        }
        
        $db->getConnection()->commit();
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Package added successfully!</div>';
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        error_log("Add Package Error: " . $e->getMessage());
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error adding package.</div>';
    }
}

// Handle package updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_package'])) {
    $network = $_POST['network'];
    $package_size = $_POST['package_size'];
    $selling_price = filter_input(INPUT_POST, 'selling_price', FILTER_VALIDATE_FLOAT);
    $api_cost = filter_input(INPUT_POST, 'api_cost', FILTER_VALIDATE_FLOAT);
    $manual_cost = filter_input(INPUT_POST, 'manual_cost', FILTER_VALIDATE_FLOAT);

    try {
        // Update Selling Price
        $stmt_selling = $db->prepare("
            UPDATE selling_prices 
            SET price = ? 
            WHERE network = ? AND package_size = ?
        ");
        $stmt_selling->execute([$selling_price, $network, $package_size]);

        // Update API Provider Cost
        if ($api_cost !== false && $api_cost >= 0) {
            $stmt_api = $db->prepare("
                INSERT INTO provider_pricing (provider, network, package_size, cost_price, is_active)
                VALUES ('api', ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cost_price = VALUES(cost_price)
            ");
            $stmt_api->execute([$network, $package_size, $api_cost]);
        }
        
        // Update Manual Provider Cost
        if ($manual_cost !== false && $manual_cost >= 0) {
            $stmt_manual = $db->prepare("
                INSERT INTO provider_pricing (provider, network, package_size, cost_price, is_active)
                VALUES ('manual', ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE cost_price = VALUES(cost_price)
            ");
            $stmt_manual->execute([$network, $package_size, $manual_cost]);
        }

        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Package updated successfully!</div>';

    } catch (Exception $e) {
        error_log("Package Manager Error: " . $e->getMessage());
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Error updating prices.</div>';
    }
}

// Get all packages with pricing
$stmt = $db->prepare("
    SELECT 
        sp.network,
        sp.package_size,
        sp.price as selling_price,
        pp_api.cost_price as api_cost,
        pp_manual.cost_price as manual_cost,
        (sp.price - LEAST(IFNULL(pp_api.cost_price, 999), IFNULL(pp_manual.cost_price, 999))) as max_profit,
        (sp.price - GREATEST(IFNULL(pp_api.cost_price, 0), IFNULL(pp_manual.cost_price, 0))) as min_profit
    FROM selling_prices sp
    LEFT JOIN provider_pricing pp_api 
        ON pp_api.network = sp.network 
        AND pp_api.package_size = sp.package_size 
        AND pp_api.provider = 'api'
    LEFT JOIN provider_pricing pp_manual 
        ON pp_manual.network = sp.network 
        AND pp_manual.package_size = sp.package_size 
        AND pp_manual.provider = 'manual'
    ORDER BY sp.network, CAST(sp.package_size AS UNSIGNED)
");
$stmt->execute();
$packages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Manager - DataPadi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 1rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #6366f1;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #f3f4f6;
        }

        h1 {
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
        }

        .add-package-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
        }

        .add-package-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .package-grid {
            display: grid;
            gap: 1.5rem;
        }

        .network-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .network-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .network-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .network-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
            color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .network-icon.mtn { background: linear-gradient(135deg, #ffcb05 0%, #ffa500 100%); }
        .network-icon.airteltigo { background: linear-gradient(135deg, #0066ff 0%, #004ec7 100%); }
        .network-icon.telecel { background: linear-gradient(135deg, #e60000 0%, #b30000 100%); }

        .network-badge {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
        }

        /* Mobile Card View */
        .package-cards {
            display: grid;
            gap: 1rem;
        }

        .package-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.25rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }

        .package-card:hover {
            border-color: #6366f1;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
        }

        .package-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .package-size {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .delete-btn {
            padding: 0.5rem 0.75rem;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .delete-btn:hover {
            background: #fca5a5;
        }

        .package-field {
            margin-bottom: 1rem;
        }

        .field-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .field-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }

        .field-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .field-input.selling {
            border-color: #fbbf24;
            background: #fffbeb;
        }

        .profit-display {
            padding: 0.75rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 8px;
            text-align: center;
        }

        .profit-label {
            font-size: 0.75rem;
            color: #065f46;
            margin-bottom: 0.25rem;
        }

        .profit-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #047857;
        }

        .save-btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }

        /* Desktop Table View */
        .package-table {
            display: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .editable {
            background: white;
            border: 2px solid #e5e7eb;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            width: 100px;
            transition: all 0.2s;
        }

        .editable:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .editable.selling {
            border-color: #fbbf24;
            background: #fffbeb;
        }

        .profit-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .profit-high {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .profit-medium {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .profit-low {
            background: linear-gradient(135deg, #fee2e2 0%, #fca5a5 100%);
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .table-save-btn {
            padding: 0.5rem 1rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .table-save-btn:hover {
            background: #4f46e5;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .close-modal:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-submit {
            flex: 1;
            padding: 0.875rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            flex: 1;
            padding: 0.875rem;
            background: #f3f4f6;
            color: #374151;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
        }

        .tips-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .tips-section h3 {
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tips-section ul {
            margin-left: 1.5rem;
            line-height: 1.8;
            color: #4b5563;
        }

        .tips-section li {
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (min-width: 768px) {
            body {
                padding: 2rem;
            }

            .header {
                padding: 2rem;
            }

            h1 {
                font-size: 2rem;
            }

            .package-cards {
                display: none;
            }

            .package-table {
                display: block;
            }

            .network-section {
                padding: 2rem;
            }
        }

        @media (min-width: 1024px) {
            .package-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <h1>
                    <i class="fas fa-box"></i>
                    Package Manager
                </h1>
            </div>
            <button class="add-package-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Package
            </button>
        </div>
        
        <?= $message ?>
        
        <div class="package-grid">
            <?php 
            $networks = ['mtn', 'airteltigo', 'telecel'];
            foreach ($networks as $network): 
                $networkPackages = array_filter($packages, fn($p) => $p['network'] === $network);
            ?>
            <div class="network-section">
                <div class="network-header">
                    <div class="network-title">
                        <div class="network-icon <?= $network ?>">
                            <i class="fas fa-signal"></i>
                        </div>
                        <h2><?= strtoupper($network) ?></h2>
                    </div>
                    <span class="network-badge"><?= count($networkPackages) ?> Packages</span>
                </div>
                
                <!-- Mobile Card View -->
                <div class="package-cards">
                    <?php foreach ($networkPackages as $package): ?>
                    <div class="package-card">
                        <form method="POST">
                            <input type="hidden" name="network" value="<?= $package['network'] ?>">
                            <input type="hidden" name="package_size" value="<?= $package['package_size'] ?>">
                            
                            <div class="package-card-header">
                                <div class="package-size"><?= $package['package_size'] ?></div>
                                <button type="button" class="delete-btn" 
                                        onclick="confirmDelete('<?= $package['network'] ?>', '<?= $package['package_size'] ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                            
                            <div class="package-field">
                                <label class="field-label">API Cost (GHS)</label>
                                <input type="number" step="0.01" name="api_cost" 
                                       value="<?= $package['api_cost'] ?>" class="field-input" placeholder="0.00">
                            </div>
                            
                            <div class="package-field">
                                <label class="field-label">Manual Cost (GHS)</label>
                                <input type="number" step="0.01" name="manual_cost" 
                                       value="<?= $package['manual_cost'] ?>" class="field-input" placeholder="0.00">
                            </div>
                            
                            <div class="package-field">
                                <label class="field-label">Selling Price (GHS)</label>
                                <input type="number" step="0.01" name="selling_price" 
                                       value="<?= $package['selling_price'] ?>" class="field-input selling" required>
                            </div>
                            
                            <div class="profit-display">
                                <div class="profit-label">MAX PROFIT</div>
                                <div class="profit-value">GHS <?= number_format($package['max_profit'], 2) ?></div>
                            </div>
                            
                            <button type="submit" name="update_package" class="save-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Desktop Table View -->
                <div class="package-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>API Cost</th>
                                <th>Manual Cost</th>
                                <th>Selling Price</th>
                                <th>Max Profit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($networkPackages as $package): ?>
                            <tr>
                                <form method="POST" style="display: contents;">
                                    <input type="hidden" name="network" value="<?= $package['network'] ?>">
                                    <input type="hidden" name="package_size" value="<?= $package['package_size'] ?>">
                                    
                                    <td><strong><?= $package['package_size'] ?></strong></td>
                                    <td>
                                        <input type="number" step="0.01" name="api_cost" 
                                               value="<?= $package['api_cost'] ?>" class="editable" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="manual_cost" 
                                               value="<?= $package['manual_cost'] ?>" class="editable" placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="selling_price" 
                                               value="<?= $package['selling_price'] ?>" class="editable selling" required>
                                    </td>
                                    <td>
                                        <?php 
                                        $profitClass = 'profit-low';
                                        if ($package['max_profit'] > 2) $profitClass = 'profit-high';
                                        elseif ($package['max_profit'] > 1) $profitClass = 'profit-medium';
                                        ?>
                                        <span class="profit-badge <?= $profitClass ?>">
                                            GHS <?= number_format($package['max_profit'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="submit" name="update_package" class="table-save-btn">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <button type="button" class="delete-btn" 
                                                    onclick="confirmDelete('<?= $package['network'] ?>', '<?= $package['package_size'] ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="tips-section">
            <h3><i class="fas fa-lightbulb"></i> Quick Tips</h3>
            <ul>
                <li><strong>API Cost:</strong> The price from Datapacks.shop (when using API)</li>
                <li><strong>Manual Cost:</strong> The price from your cheaper provider (manual processing)</li>
                <li><strong>Selling Price:</strong> What customers pay on your website</li>
                <li><strong>Max Profit:</strong> Profit when using the cheaper provider</li>
                <li><strong>Delete:</strong> Removes package from selling_prices and provider_pricing tables</li>
                <li>Changes take effect immediately for new orders</li>
            </ul>
        </div>
    </div>

    <!-- Add Package Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Package</h2>
                <button class="close-modal" onclick="closeAddModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Network</label>
                    <select name="network" class="form-select" required>
                        <option value="">Select Network</option>
                        <option value="mtn">MTN</option>
                        <option value="airteltigo">AirtelTigo</option>
                        <option value="telecel">Telecel</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Package Size (e.g., 1GB, 500MB)</label>
                    <input type="text" name="package_size" class="form-input" 
                           placeholder="e.g., 5GB" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Selling Price (GHS)</label>
                    <input type="number" step="0.01" name="selling_price" class="form-input" 
                           placeholder="0.00" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">API Cost (GHS) - Optional</label>
                    <input type="number" step="0.01" name="api_cost" class="form-input" 
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Manual Cost (GHS) - Optional</label>
                    <input type="number" step="0.01" name="manual_cost" class="form-input" 
                           placeholder="0.00">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_package" class="btn-submit">
                        <i class="fas fa-check"></i> Add Package
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="network" id="deleteNetwork">
        <input type="hidden" name="package_size" id="deletePackageSize">
        <input type="hidden" name="delete_package" value="1">
    </form>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function confirmDelete(network, packageSize) {
            if (confirm(`Are you sure you want to delete ${packageSize} ${network.toUpperCase()} package?\n\nThis will permanently remove it from the database.`)) {
                document.getElementById('deleteNetwork').value = network;
                document.getElementById('deletePackageSize').value = packageSize;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddModal();
            }
        });
    </script>
</body>
</html>