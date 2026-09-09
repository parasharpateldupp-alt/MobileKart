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
    
    if (empty($docRoot) || strcasecmp($docRoot, $appDir) === 0) {
        $relPath = '';
    } else {
        $relPath = trim(str_ireplace($docRoot, '', $appDir), '/');
    }
    
    $baseUrl = $protocol . $host . ($relPath ? '/' . $relPath : '');
    define('BASE_URL', rtrim($baseUrl, '/'));
}

// Database Connection Settings
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'online_mobile_distribution');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
