<?php
session_start();
// Use a robust path for including files from a subdirectory.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security-headers.php';

// Authentication Check: Ensure only logged-in admins can access this page.
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';

// --- FORM SUBMISSION HANDLER ---
// This block runs only when the "Save Schedule" button is clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A list of all settings this page is responsible for.
    $settings_to_update = [
        'site_open_hour'   => $_POST['open_hour'] ?? '08:00',
        'site_close_hour'  => $_POST['close_hour'] ?? '22:00',
        'site_timezone'    => $_POST['timezone'] ?? 'Africa/Accra',
        'site_closed_days' => json_encode($_POST['closed_days'] ?? []) // Safely encode the array of closed days.
    ];
    
    try {
        // Loop through each setting and update it in the database.
        // The ON DUPLICATE KEY UPDATE clause is very efficient: it will INSERT a setting if it's missing,
        // or UPDATE it if it already exists, all in one command.
        foreach ($settings_to_update as $key => $value) {
            $stmt = $db->prepare("
                INSERT INTO admin_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value]);
        }
        
        $message = '<div class="alert alert-success">Automatic site hours schedule updated successfully!</div>';

    } catch (Exception $e) {
        error_log("Site Hours Update Error: " . $e->getMessage());
        $message = '<div class="alert alert-danger">An error occurred while saving the schedule.</div>';
    }
}
// --- END OF FORM HANDLER ---


// Fetch all current settings from the database to display on the page.
$stmt = $db->prepare("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key LIKE 'site_%'");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Safely decode the JSON string of closed days into a PHP array.
$closedDays = json_decode($settings['site_closed_days'] ?? '[]', true) ?: [];
$current_operating_status = $settings['site_operating_status'] ?? 'open';
$display_timezone = $settings['site_timezone'] ?? 'Africa/Accra';

// Set the timezone for this page to accurately display the current time.
date_default_timezone_set($display_timezone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Hours Schedule - DataPadi Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #6366f1; --success: #10b981; --warning: #f59e0b; --error: #ef4444; --dark: #1f2937; --light: #f9fafb; }
        body { font-family: 'Inter', sans-serif; background: var(--light); color: #374151; margin: 0; }
        .dashboard-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .sidebar { background: var(--dark); color: white; padding: 2rem 0; }
        .sidebar-header { padding: 0 1.5rem 2rem; border-bottom: 1px solid #374151; }
        .sidebar-logo { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .sidebar-menu { padding: 1rem 0; list-style: none; margin: 0; }
        .menu-item a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; color: #d1d5db; text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .menu-item a:hover { background: #374151; color: white; }
        .menu-item.active a { color: white; font-weight: 600; border-left-color: var(--primary); }
        .main-content { padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h1 { margin-bottom: 2rem; color: var(--dark); display: flex; align-items: center; gap: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        input[type="time"], select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
        .days-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; }
        .day-checkbox { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 6px; }
        .day-checkbox input:checked + label { color: var(--primary); font-weight: 600; }
        .btn-primary { background: var(--primary); color: white; padding: 0.8rem 1.6rem; border:none; border-radius: 8px; cursor:pointer; font-size: 1rem; font-weight: 600; }
        .btn-primary:hover { background: var(--primary-dark); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; }
        .alert-success { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .alert-info { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .alert-warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .alert a { color: inherit; font-weight: 600; text-decoration: underline; }
        .current-status { padding: 1.5rem; background: #f3f4f6; border-radius: 8px; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-bolt"></i> DataPadi Admin</div></div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="dashboard.php"><i class="fas fa-home fa-fw"></i> Dashboard</a></li>
                <li class="menu-item"><a href="pending-orders.php"><i class="fas fa-clock fa-fw"></i> Pending Orders</a></li>
                <li class="menu-item"><a href="package-manager.php"><i class="fas fa-box fa-fw"></i> Package Manager</a></li>
                <li class="menu-item"><a href="profit-report.php"><i class="fas fa-chart-line fa-fw"></i> Profit Report</a></li>
                <li class="menu-item"><a href="customer-notifications.php"><i class="fas fa-bell fa-fw"></i> Notifications</a></li>
                <li class="menu-item active"><a href="site-hours.php"><i class="fas fa-calendar-alt fa-fw"></i> Site Hours</a></li>
                <li class="menu-item"><a href="settings.php"><i class="fas fa-cog fa-fw"></i> Settings</a></li>
                <li class="menu-item"><a href="logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="container">
                <h1><i class="fas fa-calendar-alt"></i> Automatic Operating Hours Schedule</h1>
                
                <?= $message ?>

                <div class="alert alert-info">
                    <p>This page sets the <strong>automatic schedule</strong> for your site. Orders placed outside this schedule will trigger an "after-hours" notice.</p>
                    <p style="margin-top: 0.5rem;">You can always manually override this schedule with the "Operating Status" toggle on the main <a href="dashboard.php">dashboard</a>.</p>
                </div>
                
                <div class="current-status">
                    <p>Current Time (<?= htmlspecialchars($display_timezone) ?>): <strong><?= date('l, H:i') ?></strong></p>
                    <p>Manual Override Status: <strong><?= ucfirst(htmlspecialchars($current_operating_status)) ?></strong></p>
                    <?php if ($current_operating_status === 'closed'): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem;">
                        <strong>Note:</strong> The site is currently manually closed. The schedule below will not take effect until you set the operating status back to "Open" on the dashboard.
                    </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="site-hours.php">
                    <div class="form-group">
                        <label for="open_hour">Opening Time</label>
                        <input type="time" id="open_hour" name="open_hour" value="<?= htmlspecialchars($settings['site_open_hour'] ?? '08:00') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="close_hour">Closing Time</label>
                        <input type="time" id="close_hour" name="close_hour" value="<?= htmlspecialchars($settings['site_close_hour'] ?? '22:00') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone">
                            <option value="Africa/Accra" <?= ($settings['site_timezone'] ?? 'Africa/Accra') === 'Africa/Accra' ? 'selected' : '' ?>>Africa/Accra (Ghana)</option>
                            <option value="UTC" <?= ($settings['site_timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Days to Automatically Stay Closed</label>
                        <div class="days-grid">
                            <?php
                            $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                            foreach ($days as $day): ?>
                            <div class="day-checkbox">
                                <input type="checkbox" id="day_<?= $day ?>" name="closed_days[]" value="<?= $day ?>" <?= in_array($day, $closedDays) ? 'checked' : '' ?>>
                                <label for="day_<?= $day ?>"><?= ucfirst($day) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Schedule</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>