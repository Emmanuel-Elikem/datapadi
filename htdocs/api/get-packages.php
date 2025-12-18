<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$db = new Database();

$stmt = $db->prepare("
    SELECT 
        sp.network,
        sp.package_size as size,
        sp.price as selling,
        pp_api.cost_price as apiCost,
        pp_manual.cost_price as manualCost
    FROM selling_prices sp
    LEFT JOIN provider_pricing pp_api ON pp_api.network = sp.network AND pp_api.package_size = sp.package_size AND pp_api.provider = 'api'
    LEFT JOIN provider_pricing pp_manual ON pp_manual.network = sp.network AND pp_manual.package_size = sp.package_size AND pp_manual.provider = 'manual'
");
$stmt->execute();
$all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formatted_packages = [];
foreach ($all_packages as $pkg) {
    $network = $pkg['network'];
    unset($pkg['network']);
    $formatted_packages[$network][] = $pkg;
}

echo json_encode($formatted_packages);
?>