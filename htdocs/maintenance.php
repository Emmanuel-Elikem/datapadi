<?php
// This line is the key to making the page smart.
// It checks if the site is open, and if so, redirects to the homepage.
require_once __DIR__ . '/includes/site-status-check.php';

// Set the HTTP status code to 503 Service Unavailable for SEO purposes.
header("HTTP/1.1 503 Service Unavailable");
header("Retry-After: 3600"); // Suggest search engines retry in 1 hour.

// You can fetch a dynamic message from the database if you want.
$stmt = (new Database())->getConnection()->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'announcement'");
$announcement = $stmt->fetchColumn();
$maintenance_message = !empty($announcement) ? $announcement : "We are currently performing scheduled maintenance to improve our services. We'll be back online shortly. Thank you for your patience!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Maintenance | DataPadi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem; text-align: center; }
        .container { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(15px); border-radius: 24px; padding: 3rem; max-width: 600px; width: 100%; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.2); }
        .icon { font-size: 4rem; color: white; margin-bottom: 1.5rem; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        p { font-size: 1.1rem; line-height: 1.7; opacity: 0.9; margin-bottom: 2rem; }
        .support-link { display: inline-flex; align-items: center; gap: 0.5rem; background-color: #25D366; color: white; padding: 0.8rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .support-link:hover { background-color: #128C7E; transform: scale(1.05); }
    </style>
    <!-- This meta tag tells the browser to refresh the page every 5 minutes.
         If the site is back online, the PHP script at the top will redirect them. -->
    <meta http-equiv="refresh" content="300">
</head>
<body>
    <div class="container">
        <div class="icon"><i class="fas fa-tools"></i></div>
        <h1>We'll Be Back Soon!</h1>
        <p><?= htmlspecialchars($maintenance_message) ?></p>
        <a href="helpdesk.php" class="support-link"><i class="fab fa-whatsapp"></i> Contact Support</a>
    </div>
</body>
</html>