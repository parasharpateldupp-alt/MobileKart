<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Common Public Header
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? APP_NAME;
$cartCount = get_cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Flipkart-Inspired Theme CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">

    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>
