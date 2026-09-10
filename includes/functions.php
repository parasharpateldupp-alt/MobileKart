<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Common Helper Functions & Utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';

/**
 * Return resolved absolute URL for product image
 */
function product_image_url($imagePath) {
    if (empty($imagePath)) {
        return BASE_URL . '/assets/images/products/s24_ultra.svg';
    }
    if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
        return $imagePath;
    }
    return BASE_URL . '/' . ltrim($imagePath, '/');
}

/**
 * Render brand emblem / logo HTML (supports array or string)
 */
function brand_logo_html($brand, $height = 20) {
    if (is_array($brand)) {
        $rawName = $brand['name'] ?? '';
        $slug = strtolower(trim($brand['slug'] ?? ''));
        $icon = htmlspecialchars($brand['logo_icon'] ?? 'fa-mobile-screen');
    } else {
        $rawName = (string)$brand;
        $slug = strtolower(trim($rawName));
        $icon = 'fa-mobile-screen';
    }

    $name = htmlspecialchars($rawName);

    // Map common brand variations to canonical SVG filename
    $key = preg_replace('/[^a-z0-9]/', '', $slug);
    if (str_contains($slug, 'apple') || $key === 'apple') $cleanSlug = 'apple';
    elseif (str_contains($slug, 'samsung') || $key === 'samsung') $cleanSlug = 'samsung';
    elseif (str_contains($slug, 'google') || $key === 'google') $cleanSlug = 'google';
    elseif (str_contains($slug, 'oneplus') || $key === 'oneplus') $cleanSlug = 'oneplus';
    elseif (str_contains($slug, 'xiaomi') || str_contains($slug, 'redmi') || $key === 'xiaomi') $cleanSlug = 'xiaomi';
    elseif (str_contains($slug, 'vivo') || $key === 'vivo') $cleanSlug = 'vivo';
    elseif (str_contains($slug, 'realme') || $key === 'realme') $cleanSlug = 'realme';
    elseif (str_contains($slug, 'motorola') || str_contains($slug, 'moto') || $key === 'motorola') $cleanSlug = 'motorola';
    elseif (str_contains($slug, 'nothing') || $key === 'nothing') $cleanSlug = 'nothing';
    elseif (str_contains($slug, 'iqoo') || $key === 'iqoo') $cleanSlug = 'iqoo';
    else $cleanSlug = $slug;

    $relPath = '/assets/images/brands/' . $cleanSlug . '.svg';
    $fullPath = dirname(__DIR__) . $relPath;

    if (file_exists($fullPath)) {
        return '<img src="' . BASE_URL . $relPath . '" alt="' . $name . '" style="height: ' . (int)$height . 'px; max-width: 48px; object-fit: contain; vertical-align: middle;" class="brand-badge-img">';
    }

    $prefix = (str_starts_with($icon, 'fa-brands') || str_starts_with($icon, 'fa-solid')) ? '' : 'fa-solid ';
    return '<i class="' . $prefix . $icon . '"></i>';
}

/**
 * Format currency in Indian Rupees format (e.g. ₹1,21,499.00 or ₹1,21,499)
 */
function format_inr($amount, $showDecimals = true) {
    $amount = (float)$amount;
    $negative = $amount < 0;
    $amount = abs($amount);
    
    $parts = explode('.', number_format($amount, 2, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1];

    $len = strlen($intPart);
    if ($len > 3) {
        $lastThree = substr($intPart, -3);
        $remaining = substr($intPart, 0, -3);
        // Format groups of two
        $groups = [];
        while (strlen($remaining) > 2) {
            $groups[] = substr($remaining, -2);
            $remaining = substr($remaining, 0, -2);
        }
        if ($remaining !== '') {
            $groups[] = $remaining;
        }
        $groups = array_reverse($groups);
        $formatted = implode(',', $groups) . ',' . $lastThree;
    } else {
        $formatted = $intPart;
    }

    $sym = (defined('APP_CURRENCY_SYMBOL') && is_string(APP_CURRENCY_SYMBOL)) ? APP_CURRENCY_SYMBOL : '₹';
    $result = $sym . ($negative ? '-' : '') . $formatted;
    if ($showDecimals) {
        $result .= '.' . $decPart;
    }
    return $result;
}

/**
 * Safe HTML string escaping
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash alert message
 */
function set_flash($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash messages
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as Bootstrap alerts
 */
function render_flash_messages() {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return;
    }
    foreach ($messages as $msg) {
        $icon = match($msg['type']) {
            'success' => 'fa-circle-check',
            'danger' => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            default => 'fa-circle-info'
        };
        echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="fa-solid ' . $icon . ' me-2 fs-5"></i>
            <div>' . htmlspecialchars($msg['message']) . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

/**
 * Render rating stars (FontAwesome)
 */
function render_rating_stars($rating) {
    $rating = (float)$rating;
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;

    $html = '<span class="text-warning rating-stars">';
    for ($i = 0; $i < $full; $i++) {
        $html .= '<i class="fa-solid fa-star"></i>';
    }
    if ($half) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    for ($i = 0; $i < $empty; $i++) {
        $html .= '<i class="fa-regular fa-star"></i>';
    }
    $html .= '</span> <small class="fw-bold text-muted ms-1">' . number_format($rating, 1) . '</small>';
    return $html;
}

/**
 * Status badge styling for Orders
 */
function get_order_status_badge($status) {
    $map = [
        ORDER_PENDING_PAYMENT => ['bg' => 'secondary', 'icon' => 'fa-clock', 'label' => 'Payment Pending'],
        ORDER_CONFIRMED => ['bg' => 'info', 'icon' => 'fa-check', 'label' => 'Order Confirmed'],
        ORDER_PROCESSING => ['bg' => 'primary', 'icon' => 'fa-gears', 'label' => 'Processing'],
        ORDER_PACKED => ['bg' => 'indigo', 'icon' => 'fa-box', 'label' => 'Packed'],
        ORDER_SHIPPED => ['bg' => 'warning', 'icon' => 'fa-truck', 'label' => 'Shipped'],
        ORDER_IN_TRANSIT => ['bg' => 'primary', 'icon' => 'fa-route', 'label' => 'In Transit'],
        ORDER_OUT_FOR_DELIVERY => ['bg' => 'warning text-dark', 'icon' => 'fa-motorcycle', 'label' => 'Out For Delivery'],
        ORDER_DELIVERED => ['bg' => 'success', 'icon' => 'fa-house-circle-check', 'label' => 'Delivered'],
        ORDER_CANCELLED => ['bg' => 'danger', 'icon' => 'fa-ban', 'label' => 'Cancelled']
    ];

    $item = $map[$status] ?? ['bg' => 'secondary', 'icon' => 'fa-question', 'label' => $status];
    return '<span class="badge bg-' . $item['bg'] . ' px-2 py-1"><i class="fa-solid ' . $item['icon'] . ' me-1"></i> ' . htmlspecialchars($item['label']) . '</span>';
}

/**
 * Status badge styling for Payments
 */
function get_payment_status_badge($status) {
    return match($status) {
        PAYMENT_SUCCESS => '<span class="badge bg-success px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Paid</span>',
        PAYMENT_FAILED => '<span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i> Failed</span>',
        PAYMENT_CANCELLED => '<span class="badge bg-secondary px-2 py-1"><i class="fa-solid fa-circle-stop me-1"></i> Cancelled</span>',
        default => '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-clock me-1"></i> Pending</span>'
    };
}

/**
 * Fetch a key from system_settings with optional fallback
 */
function get_setting($key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        $cache[$key] = ($val !== false) ? $val : $default;
        return $cache[$key];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Retrieve current cart item count for navbar badge
 */
function get_cart_count() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        try {
            $db = get_db_connection();
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(ci.quantity), 0) 
                FROM cart c 
                JOIN cart_items ci ON c.cart_id = ci.cart_id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    // Session fallback for guests
    $count = 0;
    if (isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $qty) {
            $count += (int)$qty;
        }
    }
    return $count;
}
