<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$db = new Database();

// Get site settings
$stmt = $db->prepare("SELECT * FROM admin_settings WHERE setting_key IN ('site_status', 'site_open_hour', 'site_close_hour', 'site_timezone', 'site_closed_days', 'announcement')");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Check if site is in maintenance mode
if ($settings['site_status'] === 'maintenance') {
    echo json_encode([
        'open' => false,
        'reason' => 'maintenance',
        'message' => 'Site is under maintenance. Please check back later.',
        'announcement' => $settings['announcement'] ?? null
    ]);
    exit;
}

// Check operating hours
date_default_timezone_set($settings['site_timezone'] ?? 'Africa/Accra');
$currentHour = intval(date('H'));
$currentMinute = intval(date('i'));
$currentTime = $currentHour * 60 + $currentMinute;
$currentDay = strtolower(date('l'));

$openTime = explode(':', $settings['site_open_hour'] ?? '08:00');
$openMinutes = intval($openTime[0]) * 60 + intval($openTime[1]);

$closeTime = explode(':', $settings['site_close_hour'] ?? '20:00');
$closeMinutes = intval($closeTime[0]) * 60 + intval($closeTime[1]);

$closedDays = json_decode($settings['site_closed_days'] ?? '[]', true) ?: [];

// Check if current day is closed
if (in_array($currentDay, $closedDays)) {
    echo json_encode([
        'open' => false,
        'reason' => 'closed_day',
        'message' => 'We are closed on ' . ucfirst($currentDay) . 's.',
        'next_open' => getNextOpenTime($settings)
    ]);
    exit;
}

// Check if within operating hours
if ($currentTime < $openMinutes || $currentTime >= $closeMinutes) {
    echo json_encode([
        'open' => false,
        'reason' => 'outside_hours',
        'message' => sprintf('We are open from %s to %s daily.', 
            $settings['site_open_hour'] ?? '08:00',
            $settings['site_close_hour'] ?? '20:00'
        ),
        'next_open' => getNextOpenTime($settings)
    ]);
    exit;
}

// Site is open
echo json_encode([
    'open' => true,
    'message' => 'Site is open for business!',
    'announcement' => $settings['announcement'] ?? null,
    'closes_at' => $settings['site_close_hour']
]);

function getNextOpenTime($settings) {
    // Calculate next opening time
    date_default_timezone_set($settings['site_timezone'] ?? 'Africa/Accra');
    
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $openHour = $settings['site_open_hour'] ?? '08:00';
    
    return $tomorrow . ' ' . $openHour;
}
?>