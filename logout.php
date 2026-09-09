<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Logout Handler
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
set_flash('info', 'You have been logged out securely.');
header("Location: " . BASE_URL . "/login.php");
exit;
