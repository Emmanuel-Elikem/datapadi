<?php
// FINAL, CORRECTED VERSION - This returns a value instead of using globals.

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

/**
 * This is the main function that checks the site's status.
 * IT NOW RETURNS A BOOLEAN VALUE:
 * - true: Site is open for automated delivery.
 * - false: Site is in "after-hours" mode.
 * It also handles the hard redirect for full maintenance.
 */
function getSiteOperatingStatus() {
    $current_page = basename($_SERVER['PHP_SELF']);
    $always_accessible_pages = ['maintenance.php', 'login.php', 'helpdesk.php'];

    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key LIKE 'site_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // --- PART 1: HARD MAINTENANCE CHECK (Redirects) ---
        if (($settings['site_status'] ?? 'active') === 'maintenance') {
            if (!in_array($current_page, $always_accessible_pages)) {
                header("Location: /maintenance.php");
                exit;
            }
        } else {
            if ($current_page === 'maintenance.php') {
                header("Location: /index.php");
                exit;
            }
        }

        // --- PART 2: OPERATING HOURS CHECK (Returns true or false) ---
        // Default to 'auto' if the setting is missing.
        $operating_status = $settings['site_operating_status'] ?? 'auto';

        if ($operating_status === 'open') {
            return true; // Manual override: Force Open
        }
        if ($operating_status === 'closed') {
            return false; // Manual override: Force Close
        }

        // If status is 'auto', check the schedule.
        date_default_timezone_set($settings['site_timezone'] ?? 'Africa/Accra');
        
        $now = time();
        $open_time = strtotime($settings['site_open_hour'] ?? '08:00');
        $close_time = strtotime($settings['site_close_hour'] ?? '22:00');
        $closed_days = json_decode($settings['site_closed_days'] ?? '[]', true) ?: [];
        $today_name = strtolower(date('l', $now));

        if (in_array($today_name, $closed_days)) {
            return false; // It's a designated closed day.
        }
        if ($now < $open_time || $now >= $close_time) {
            return false; // It's outside of operating hours.
        }

        // If we've passed all checks, the site is open.
        return true;

    } catch (Exception $e) {
        error_log("CRITICAL ERROR in getSiteOperatingStatus(): " . $e->getMessage());
        return true; // Default to 'open' to prevent DB errors from taking the site down.
    }
}

// We no longer call the function here. Each page will call it and store the result.
?>