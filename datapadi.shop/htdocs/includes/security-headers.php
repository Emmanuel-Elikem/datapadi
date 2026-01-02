<?php
// Common security headers for admin and site pages
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');

    $csp = [
        "default-src 'self'",
        "img-src 'self' data:",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "connect-src 'self'",
        "frame-ancestors 'none'"
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
