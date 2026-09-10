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
$bypassKey = 'mk_admin_2026';
$isBypassed = (isset($_GET['preview_key']) && $_GET['preview_key'] === $bypassKey)
    || (isset($_COOKIE['mk_preview_token']) && $_COOKIE['mk_preview_token'] === $bypassKey)
    || (isset($_GET['admin_preview']));

$isMaintenanceActive = file_exists($rootDir . '/maintenance.flag') || true;

if ($isMaintenanceActive && !$isBypassed) {
    // Allow static asset requests to pass through
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf)$/i', $rawUri)) {
        // proceed to asset serving below
    } else {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Scheduled Maintenance - MobileKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 50%, #415a77 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #ffffff; margin: 0; padding: 20px; }
        .maint-card { max-width: 580px; width: 100%; background: rgba(255, 255, 255, 0.07); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 20px; padding: 48px 36px; box-shadow: 0 20px 40px rgba(0,0,0,0.35); text-align: center; }
        .brand-badge { display: inline-flex; align-items: center; gap: 10px; background: #2874f0; color: #fff; padding: 8px 18px; border-radius: 50px; font-weight: 700; font-size: 1.1rem; margin-bottom: 28px; box-shadow: 0 4px 14px rgba(40, 116, 240, 0.4); }
        .pulse-icon { width: 80px; height: 80px; line-height: 80px; border-radius: 50%; background: rgba(255, 193, 7, 0.15); color: #ffc107; font-size: 2.4rem; margin: 0 auto 24px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(0.96); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); } 70% { transform: scale(1.04); box-shadow: 0 0 0 16px rgba(255, 193, 7, 0); } 100% { transform: scale(0.96); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); } }
        h1 { font-size: 1.85rem; font-weight: 700; margin-bottom: 14px; letter-spacing: -0.5px; }
        p.lead-text { color: #e0e6ed; font-size: 1.05rem; line-height: 1.65; margin-bottom: 24px; }
        .status-pill { display: inline-flex; align-items: center; gap: 8px; background: rgba(40, 167, 69, 0.18); border: 1px solid rgba(40, 167, 69, 0.35); color: #51cf66; padding: 6px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 600; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #51cf66; animation: blink 1.2s infinite ease-in-out; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .divider { height: 1px; background: rgba(255, 255, 255, 0.12); margin: 28px 0 20px; }
        .footer-note { color: #94a3b8; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="maint-card">
        <div class="brand-badge">
            <i class="fa-solid fa-bolt text-warning"></i> MobileKart
        </div>
        <div class="pulse-icon">
            <i class="fa-solid fa-server"></i>
        </div>
        <h1>Under Scheduled Maintenance</h1>
        <p class="lead-text">
            MobileKart is currently undergoing scheduled platform upgrades and routine system maintenance to improve performance and reliability.
        </p>
        <div class="mb-3">
            <span class="status-pill">
                <span class="status-dot"></span> System Upgrade In Progress
            </span>
        </div>
        <p class="text-white-50 small mb-0">
            We anticipate being back online shortly. Thank you for your patience and understanding.
        </p>
        <div class="divider"></div>
        <div class="footer-note">
            &copy; ' . date('Y') . ' MobileKart Distribution Platform &bull; All Rights Reserved
        </div>
    </div>
</body>
</html>';
        exit;
    }
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
