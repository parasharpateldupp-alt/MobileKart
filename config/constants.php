<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * System Configuration & DFD Constants
 */

if (!defined('APP_INIT')) define('APP_INIT', true);

// System Metadata
if (!defined('APP_NAME')) define('APP_NAME', 'Online Mobile Purchasing & Distributing System');
if (!defined('APP_SHORT_NAME')) define('APP_SHORT_NAME', 'MobileKart Distribution');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');

// Currency & Taxes (Indian Rupees)
if (!defined('APP_CURRENCY_SYMBOL')) define('APP_CURRENCY_SYMBOL', '₹');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'INR');
if (!defined('DEFAULT_GST_PERCENT')) define('DEFAULT_GST_PERCENT', 18.0);
if (!defined('FREE_SHIPPING_THRESHOLD')) define('FREE_SHIPPING_THRESHOLD', 5000.00);
if (!defined('DEFAULT_SHIPPING_CHARGE')) define('DEFAULT_SHIPPING_CHARGE', 149.00);

// Stock Reservation Timeout (DFD P1.3.3)
if (!defined('STOCK_RESERVATION_MINUTES')) define('STOCK_RESERVATION_MINUTES', 15);

// User Roles (DFD P1.1)
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'ADMIN');
if (!defined('ROLE_SUPPLIER')) define('ROLE_SUPPLIER', 'SUPPLIER');
if (!defined('ROLE_CUSTOMER')) define('ROLE_CUSTOMER', 'CUSTOMER');

// Order States (DFD P1.3, P1.4, P1.5)
if (!defined('ORDER_PENDING_PAYMENT')) define('ORDER_PENDING_PAYMENT', 'PENDING_PAYMENT');
if (!defined('ORDER_CONFIRMED')) define('ORDER_CONFIRMED', 'ORDER_CONFIRMED');
if (!defined('ORDER_PROCESSING')) define('ORDER_PROCESSING', 'PROCESSING');
if (!defined('ORDER_PACKED')) define('ORDER_PACKED', 'PACKED');
if (!defined('ORDER_SHIPPED')) define('ORDER_SHIPPED', 'SHIPPED');
if (!defined('ORDER_IN_TRANSIT')) define('ORDER_IN_TRANSIT', 'IN_TRANSIT');
if (!defined('ORDER_OUT_FOR_DELIVERY')) define('ORDER_OUT_FOR_DELIVERY', 'OUT_FOR_DELIVERY');
if (!defined('ORDER_DELIVERED')) define('ORDER_DELIVERED', 'DELIVERED');
if (!defined('ORDER_CANCELLED')) define('ORDER_CANCELLED', 'CANCELLED');

// Payment States (DFD P1.4)
if (!defined('PAYMENT_PENDING')) define('PAYMENT_PENDING', 'PENDING');
if (!defined('PAYMENT_SUCCESS')) define('PAYMENT_SUCCESS', 'SUCCESS');
if (!defined('PAYMENT_FAILED')) define('PAYMENT_FAILED', 'FAILED');
if (!defined('PAYMENT_CANCELLED')) define('PAYMENT_CANCELLED', 'CANCELLED');

// Dynamic Base URL Detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine script directory relative to document root (case-insensitive for Windows)
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    
    if (!empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || str_contains($host, 'vercel.app')) {
        $baseUrl = 'https://' . $host;
    } elseif (empty($docRoot) || strcasecmp($docRoot, $appDir) === 0) {
        $relPath = '';
        $baseUrl = $protocol . $host;
    } else {
        $relPath = trim(str_ireplace($docRoot, '', $appDir), '/');
        $baseUrl = $protocol . $host . ($relPath ? '/' . $relPath : '');
    }
    define('BASE_URL', rtrim($baseUrl, '/'));
}

// Parse DATABASE_URL / MYSQL_URL if provided (common on cloud hosts)
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
$parsedHost = null;
$parsedPort = null;
$parsedName = null;
$parsedUser = null;
$parsedPass = null;

if ($dbUrl) {
    $parts = parse_url($dbUrl);
    if ($parts) {
        $parsedHost = $parts['host'] ?? null;
        $parsedPort = isset($parts['port']) ? (string)$parts['port'] : null;
        $parsedUser = $parts['user'] ?? null;
        $parsedPass = $parts['pass'] ?? null;
        $parsedName = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
    }
}

$isCloud = (!empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || str_contains($host, 'vercel.app'));

if ($isCloud) {
    $defaultHost = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
    $defaultPort = '4000';
    $defaultName = 'test';
    $defaultUser = '4AVQkGYiwq2zQBT.root';
    $defaultPass = 'BNdSSFFOMEjJYR1K';
} else {
    $defaultHost = 'localhost';
    $defaultPort = '3306';
    $defaultName = 'online_mobile_distribution';
    $defaultUser = 'root';
    $defaultPass = '';
}

// Database Connection Settings (Environment variables take precedence, fallback to sensible defaults)
if (!defined('DB_HOST')) define('DB_HOST', $parsedHost ?: (getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: $defaultHost)));
if (!defined('DB_PORT')) define('DB_PORT', $parsedPort ?: (getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: $defaultPort)));
if (!defined('DB_NAME')) define('DB_NAME', $parsedName ?: (getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: $defaultName)));
if (!defined('DB_USER')) define('DB_USER', $parsedUser ?: (getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: $defaultUser)));
if (!defined('DB_PASS')) define('DB_PASS', $parsedPass !== null ? $parsedPass : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : $defaultPass)));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
