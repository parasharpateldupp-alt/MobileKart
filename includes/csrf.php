<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * CSRF Protection Module
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or get existing CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Return hidden HTML input containing CSRF token
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST or headers
 */
function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken) || empty($token)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Enforce CSRF check or terminate with 403
 */
function require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf_token()) {
            http_response_code(403);
            die("Security Error: Invalid or expired CSRF token. Please refresh the page and try again.");
        }
    }
}
