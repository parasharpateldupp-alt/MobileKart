<?php
/**
 * Vercel Serverless Entrypoint & Front-Controller Router
 * Online Mobile Purchasing & Distributing System (MobileKart)
 */

// Enable output buffering to prevent "headers already sent"
ob_start();

// Disable deprecated and notice warnings from contaminating HTTP output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

$rootDir = dirname(__DIR__);

// Retrieve and normalize the requested URI
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$urlParts = parse_url($rawUri);
$path = trim($urlParts['path'] ?? '/', '/');

// Security: Prevent directory traversal
if (str_contains($path, '..')) {
    http_response_code(400);
    exit('Invalid request path');
}

// Maintenance Mode Check
if (file_exists($rootDir . '/maintenance.flag') && !isset($_GET['admin_preview'])) {
    http_response_code(503);
    header('Retry-After: 300');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="refresh" content="25">
        <title>System Maintenance & Catalog Upgrade | MobileKart</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            body { background: linear-gradient(135deg, #0d2040 0%, #1b5cbd 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: "Segoe UI", system-ui, sans-serif; color: #ffffff; padding: 20px; }
            .maint-card { max-width: 620px; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 20px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center; }
            .gear-icon { font-size: 3.5rem; color: #fbbf24; animation: spin 8s linear infinite; display: inline-block; margin-bottom: 20px; }
            @keyframes spin { 100% { transform: rotate(360deg); } }
            .pulse-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
            .dot { width: 8px; height: 8px; background: #fbbf24; border-radius: 50%; animation: blink 1.5s infinite; }
            @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
            .step-item { background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; text-align: left; }
        </style>
    </head>
    <body>
        <div class="maint-card">
            <div class="gear-icon"><i class="fa-solid fa-gear"></i></div>
            <div class="pulse-badge"><div class="dot"></div> Live Catalog & System Upgrade</div>
            <h2 class="fw-bold mb-3">Under Scheduled Maintenance</h2>
            <p class="text-white-50 mb-4">We are currently deploying the latest September 2026 smartphone flagship lineup, updating color variant galleries, and syncing official market prices. MobileKart will be back online shortly.</p>
            <div class="step-item"><i class="fa-solid fa-mobile-screen text-warning"></i> Updating 2026 Flagship Phone Models (iPhone 18, S26 Ultra, iPhone Duo)</div>
            <div class="step-item"><i class="fa-solid fa-palette text-info"></i> Generating High-Resolution Device Color Variant Switchers</div>
            <div class="step-item"><i class="fa-solid fa-indian-rupee-sign text-success"></i> Synchronizing Accurate Real-Time Indian Market Pricing</div>
            <p class="small text-white-50 mt-4 mb-0"><i class="fa-solid fa-arrows-rotate fa-spin me-2"></i> This page will refresh automatically every 25 seconds.</p>
        </div>
    </body>
    </html>';
    exit;
}

// 1. Root / Homepage
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $rootDir . DIRECTORY_SEPARATOR . 'index.php';
    chdir($rootDir);
    require $rootDir . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

// 2. Direct path resolution
$target = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

// If path does not have an extension, try .php
if (!file_exists($target) && file_exists($target . '.php')) {
    $target .= '.php';
    $path .= '.php';
}

// If directory, look for index.php, products.php, or dashboard.php
if (is_dir($target)) {
    if (file_exists($target . DIRECTORY_SEPARATOR . 'index.php')) {
        $target .= DIRECTORY_SEPARATOR . 'index.php';
        $path .= '/index.php';
    } elseif (file_exists($target . DIRECTORY_SEPARATOR . 'products.php')) {
        $target .= DIRECTORY_SEPARATOR . 'products.php';
        $path .= '/products.php';
    } elseif (file_exists($target . DIRECTORY_SEPARATOR . 'dashboard.php')) {
        $target .= DIRECTORY_SEPARATOR . 'dashboard.php';
        $path .= '/dashboard.php';
    }
}

// 3. If file exists, serve it
if (file_exists($target) && !is_dir($target)) {
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

    // Handle PHP script execution
    if ($ext === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = realpath($target);
        $_SERVER['SCRIPT_NAME'] = '/' . $path;
        $_SERVER['PHP_SELF'] = '/' . $path;
        chdir(dirname(realpath($target)));
        require realpath($target);
        exit;
    }

    // Static asset fallback if request passed through router
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'webp'  => 'image/webp',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf'   => 'application/pdf',
        'sql'   => 'text/plain',
        'txt'   => 'text/plain',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Content-Length: ' . filesize($target));
        readfile($target);
        exit;
    }
}

// 4. 404 Error Page
http_response_code(404);
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found | MobileKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container text-center">
        <h1 class="display-3 text-danger fw-bold">404</h1>
        <h3 class="mb-3">Page Not Found</h3>
        <p class="text-muted mb-4">The requested page <code>/' . htmlspecialchars($path) . '</code> was not found on this server.</p>
        <a href="/" class="btn btn-primary px-4">Return to Homepage</a>
    </div>
</body>
</html>';
exit;
