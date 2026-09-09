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
