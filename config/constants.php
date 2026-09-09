<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * System Configuration & DFD Constants
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// System Metadata
define('APP_NAME', 'Online Mobile Purchasing & Distributing System');
define('APP_SHORT_NAME', 'MobileKart Distribution');
define('APP_VERSION', '1.0.0');

// Currency & Taxes (Indian Rupees)
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');
define('DEFAULT_GST_PERCENT', 18.0); // 18% GST standard on mobile electronics
define('FREE_SHIPPING_THRESHOLD', 5000.00); // Free delivery above ₹5,000
define('DEFAULT_SHIPPING_CHARGE', 149.00); // Standard shipping fee

// Stock Reservation Timeout (DFD P1.3.3)
define('STOCK_RESERVATION_MINUTES', 15);

// User Roles (DFD P1.1)
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_SUPPLIER', 'SUPPLIER');
define('ROLE_CUSTOMER', 'CUSTOMER');

// Order States (DFD P1.3, P1.4, P1.5)
define('ORDER_PENDING_PAYMENT', 'PENDING_PAYMENT');
define('ORDER_CONFIRMED', 'ORDER_CONFIRMED');
define('ORDER_PROCESSING', 'PROCESSING');
define('ORDER_PACKED', 'PACKED');
define('ORDER_SHIPPED', 'SHIPPED');
define('ORDER_IN_TRANSIT', 'IN_TRANSIT');
define('ORDER_OUT_FOR_DELIVERY', 'OUT_FOR_DELIVERY');
define('ORDER_DELIVERED', 'DELIVERED');
define('ORDER_CANCELLED', 'CANCELLED');

// Payment States (DFD P1.4)
define('PAYMENT_PENDING', 'PENDING');
define('PAYMENT_SUCCESS', 'SUCCESS');
define('PAYMENT_FAILED', 'FAILED');
define('PAYMENT_CANCELLED', 'CANCELLED');

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

// Database Connection Settings (Environment variables take precedence, fallback to local XAMPP)
define('DB_HOST', $parsedHost ?: (getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost')));
define('DB_PORT', $parsedPort ?: (getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306')));
define('DB_NAME', $parsedName ?: (getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'online_mobile_distribution')));
define('DB_USER', $parsedUser ?: (getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root')));
define('DB_PASS', $parsedPass !== null ? $parsedPass : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '')));
define('DB_CHARSET', 'utf8mb4');
