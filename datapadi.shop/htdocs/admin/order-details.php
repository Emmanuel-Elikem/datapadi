<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? '';
if ($orderId === '') {
    http_response_code(400);
    echo 'Missing order_id parameter';
    exit;
}

$db = new Database();

// Fetch order with related info
$stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo 'Order not found';
    exit;
}

// Status history
$stmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC");
$stmt->execute([$orderId]);
$history = $stmt->fetchAll();

// Scheduled job (if any)
$stmt = $db->prepare("SELECT * FROM scheduled_jobs WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$job = $stmt->fetch();

// Provider manual/api cost comparison (if pricing exists)
$stmt = $db->prepare("SELECT provider, cost_price FROM provider_pricing WHERE network = ? AND package_size = ?");
$stmt->execute([$order['network'], $order['package_size']]);
$pricing = $stmt->fetchAll();
$pricingMap = [];
foreach ($pricing as $p) { $pricingMap[$p['provider']] = $p['cost_price']; }

$manualCost = $pricingMap['manual'] ?? null;
$apiCost = $pricingMap['api'] ?? null;
$profitIfManual = $manualCost !== null ? ($order['selling_price'] - $manualCost) : null;
$profitIfApi = $apiCost !== null ? ($order['selling_price'] - $apiCost) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Order Details - <?= htmlspecialchars($orderId) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root { --primary:#6366f1; --success:#10b981; --warning:#f59e0b; --error:#ef4444; --dark:#1f2937; --light:#f9fafb; }
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Inter',sans-serif; background:var(--light); color:#374151; padding:1rem; }
.container { max-width:1000px; margin:0 auto; }
.header { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.5rem; }
.title { font-size:1.5rem; font-weight:700; display:flex; align-items:center; gap:.5rem; }
.badge { display:inline-block; padding:.35rem .7rem; border-radius:9999px; font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.status-pending { background:#fef3c7; color:#92400e; }
.status-processing { background:#dbeafe; color:#1e40af; }
.status-completed { background:#d1fae5; color:#065f46; }
.status-failed { background:#fee2e2; color:#991b1b; }
.card { background:#fff; border-radius:12px; padding:1.25rem 1.25rem 1rem; box-shadow:0 1px 3px rgba(0,0,0,.1); margin-bottom:1rem; }
.card h3 { font-size:1rem; font-weight:600; margin-bottom:.75rem; display:flex; align-items:center; gap:.5rem; }
.grid { display:grid; gap:.75rem; }
.details-grid { grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
.detail { background:#f9fafb; padding:.75rem .75rem .6rem; border-radius:8px; }
.detail label { display:block; font-size:.65rem; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:.25rem; }
.detail span { font-size:.8rem; font-weight:600; color:#1f2937; word-break:break-word; }
.separator { height:1px; background:#e5e7eb; margin:1rem 0 .5rem; }
.chip { padding:.3rem .6rem; background:#eef2ff; color:#3730a3; font-size:.65rem; font-weight:600; border-radius:6px; }
.flex { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.table { width:100%; border-collapse:collapse; font-size:.75rem; }
.table th { text-align:left; padding:.5rem; background:#f9fafb; font-weight:600; color:#6b7280; font-size:.65rem; }
.table td { padding:.5rem; border-top:1px solid #e5e7eb; }
.muted { color:#6b7280; font-size:.65rem; }
.profit-positive { color:var(--success); font-weight:600; }
.profit-potential { color:#2563eb; font-weight:600; }
.actions { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.5rem; }
.btn { border:none; padding:.6rem .9rem; border-radius:8px; font-size:.75rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; }
.btn-primary { background:var(--primary); color:#fff; }
.btn-success { background:var(--success); color:#fff; }
.btn-outline { background:#fff; color:#374151; border:2px solid #e5e7eb; }
.btn-outline:hover { background:#f9fafb; }
.code-block { background:#111827; color:#f8fafc; font-family:monospace; font-size:.7rem; padding:.75rem; border-radius:8px; overflow-x:auto; }
.history-empty { text-align:center; padding:1rem; color:#6b7280; font-size:.7rem; }
@media (min-width:768px){ .title{font-size:1.8rem;} }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="title">
            <i class="fas fa-file-alt"></i> Order Details
            <span class="badge status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
        </div>
        <div class="flex">
            <a href="pending-orders.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Pending Orders</a>
            <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-home"></i> Dashboard</a>
            <?php if ($order['status'] !== 'completed'): ?>
            <button class="btn btn-success" onclick="markCompleted('<?= htmlspecialchars($orderId) ?>')"><i class="fas fa-check"></i> Mark Completed</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Core Information -->
    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Core Info</h3>
        <div class="grid details-grid">
            <div class="detail"><label>Order ID</label><span><?= htmlspecialchars($order['order_id']) ?></span></div>
            <div class="detail"><label>Customer Phone</label><span><?= htmlspecialchars($order['customer_phone']) ?></span></div>
            <div class="detail"><label>Network</label><span><?= strtoupper($order['network']) ?></span></div>
            <div class="detail"><label>Package Size</label><span><?= htmlspecialchars($order['package_size']) ?></span></div>
            <div class="detail"><label>Quantity</label><span><?= (int)$order['quantity'] ?></span></div>
            <div class="detail"><label>Status</label><span><?= htmlspecialchars($order['status']) ?></span></div>
            <div class="detail"><label>Provider</label><span><?= $order['provider'] ?: '—' ?></span></div>
            <div class="detail"><label>Processing Method</label><span><?= $order['processing_method'] ?: '—' ?></span></div>
            <div class="detail"><label>Created At</label><span><?= htmlspecialchars($order['created_at']) ?></span></div>
            <div class="detail"><label>Processed At</label><span><?= $order['processed_at'] ?: '—' ?></span></div>
            <div class="detail"><label>Completed At</label><span><?= $order['completed_at'] ?: '—' ?></span></div>
        </div>
    </div>

    <!-- Financials -->
    <div class="card">
        <h3><i class="fas fa-coins"></i> Financials</h3>
        <div class="grid details-grid">
            <div class="detail"><label>Selling Price (Unit)</label><span>GHS <?= number_format($order['selling_price'],2) ?></span></div>
            <div class="detail"><label>Cost Price (Recorded)</label><span><?= $order['cost_price'] !== null ? 'GHS '.number_format($order['cost_price'],2) : '—' ?></span></div>
            <div class="detail"><label>Recorded Profit</label><span class="profit-positive"><?= $order['profit'] !== null ? 'GHS '.number_format($order['profit'],2) : '—' ?></span></div>
            <div class="detail"><label>Potential (Manual)</label><span class="profit-potential"><?= $profitIfManual !== null ? 'GHS '.number_format($profitIfManual,2) : 'N/A' ?></span></div>
            <div class="detail"><label>Potential (API)</label><span><?= $profitIfApi !== null ? 'GHS '.number_format($profitIfApi,2) : 'N/A' ?></span></div>
            <div class="detail"><label>Total Revenue</label><span>GHS <?= number_format($order['selling_price'] * $order['quantity'],2) ?></span></div>
        </div>
    </div>

    <!-- Pricing Reference -->
    <?php if($manualCost !== null || $apiCost !== null): ?>
    <div class="card">
        <h3><i class="fas fa-tags"></i> Pricing Reference</h3>
        <div class="grid details-grid">
            <div class="detail"><label>Manual Cost</label><span><?= $manualCost !== null ? 'GHS '.number_format($manualCost,2) : 'N/A' ?></span></div>
            <div class="detail"><label>API Cost</label><span><?= $apiCost !== null ? 'GHS '.number_format($apiCost,2) : 'N/A' ?></span></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status History -->
    <div class="card">
        <h3><i class="fas fa-stream"></i> Status History</h3>
        <?php if(empty($history)): ?>
            <div class="history-empty">No status history entries.</div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Time</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach($history as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['created_at']) ?></td>
                    <td><span class="badge status-<?= htmlspecialchars($h['status']) ?>"><?= htmlspecialchars($h['status']) ?></span></td>
                    <td><?= htmlspecialchars($h['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Scheduled Job -->
    <div class="card">
        <h3><i class="fas fa-robot"></i> Automation Job</h3>
        <?php if($job): ?>
        <div class="grid details-grid">
            <div class="detail"><label>Execute At</label><span><?= htmlspecialchars($job['execute_at']) ?></span></div>
            <div class="detail"><label>Job Status</label><span><?= htmlspecialchars($job['status']) ?></span></div>
        </div>
        <?php else: ?>
            <div class="history-empty">No scheduled job for this order.</div>
        <?php endif; ?>
    </div>

    <!-- Raw JSON (Debug) -->
    <div class="card">
        <h3><i class="fas fa-code"></i> Raw Order JSON</h3>
        <div class="code-block"><?= htmlspecialchars(json_encode($order, JSON_PRETTY_PRINT)) ?></div>
    </div>
</div>
<script>
async function markCompleted(orderId){
    if(!confirm('Mark this order as completed?')) return;
    try {
        const res = await fetch('/api/admin/mark-completed.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({order_id: orderId})
        });
        const data = await res.json();
        if(data.success){
            alert('Order marked completed');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(e){
        alert('Network error');
    }
}
</script>
</body>
</html>
