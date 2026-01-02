<?php
// Simple FTP deploy script to push both sites (datapadi.shop and fallback wuaze) sequentially.
// Run from repo root (d:\datapadi): php deploy.php

// Configuration for both targets
$targets = [
    [
        'name' => 'datapadi-shop',
        'host' => 'ftpupload.net',
        'user' => 'if0_38664997',
        'pass' => '49p5qd32',
        'local' => __DIR__ . '/datapadi.shop/htdocs',
        'remote' => '/datapadi.shop/htdocs',
    ],
    [
        'name' => 'datapadi-fallback',
        'host' => 'ftpupload.net',
        'user' => 'if0_38664997',
        'pass' => '49p5qd32',
        'local' => __DIR__ . '/htdocs',
        'remote' => '/htdocs',
    ],
];

// Ignore patterns (fnmatch style)
$ignore = [
    '.git',
    '.git/*',
    '.vscode',
    '.vscode/*',
    'node_modules',
    'node_modules/*',
    '*.log',
    'cron_logs',
    'cron_logs/*',
    'phpinfo.php.bak',
];

function shouldIgnore($relativePath, $ignore) {
    foreach ($ignore as $pattern) {
        if (fnmatch($pattern, $relativePath, FNM_PATHNAME | FNM_CASEFOLD)) {
            return true;
        }
    }
    return false;
}

function ensureRemoteDir($conn, $path) {
    $parts = array_filter(explode('/', trim($path, '/')));
    $built = '';
    foreach ($parts as $part) {
        $built .= '/' . $part;
        @ftp_mkdir($conn, $built);
    }
}

function uploadDir($conn, $localRoot, $remoteRoot, $ignore) {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        $localPath = $file->getPathname();
        $relative = ltrim(str_replace('\\', '/', substr($localPath, strlen($localRoot))), '/');

        if ($relative === '') {
            continue;
        }

        if (shouldIgnore($relative, $ignore)) {
            continue;
        }

        $remotePath = rtrim($remoteRoot, '/') . '/' . $relative;

        if ($file->isDir()) {
            ensureRemoteDir($conn, $remotePath);
            continue;
        }

        // Ensure remote dir exists
        ensureRemoteDir($conn, dirname($remotePath));

        $ok = ftp_put($conn, $remotePath, $localPath, FTP_BINARY);
        if (!$ok) {
            throw new RuntimeException("Upload failed: $relative");
        }
    }
}

foreach ($targets as $t) {
    echo "\n=== Deploying {$t['name']} ===\n";
    $conn = ftp_connect($t['host'], 21, 30);
    if (!$conn) {
        throw new RuntimeException("Could not connect to {$t['host']}");
    }
    if (!ftp_login($conn, $t['user'], $t['pass'])) {
        throw new RuntimeException("FTP login failed for {$t['name']}");
    }
    ftp_pasv($conn, true);

    uploadDir($conn, $t['local'], $t['remote'], $ignore);

    ftp_close($conn);
    echo "Deployed {$t['name']} successfully.\n";
}

echo "\nAll deployments completed.\n";
?>