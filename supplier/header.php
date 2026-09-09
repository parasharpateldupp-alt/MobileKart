<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Portal Header & Sidebar Layout
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_supplier();

$user = current_user();
$supplierId = $_SESSION['supplier_id'] ?? 0;
$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Supplier Portal') ?> - MobileKart</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="admin-sidebar shadow">
    <a href="<?= BASE_URL ?>/supplier/dashboard.php" class="admin-brand">
        <i class="fa-solid fa-warehouse text-warning"></i>
        <span>Supplier Portal</span>
    </a>

    <ul class="admin-nav">
        <li class="admin-nav-header">Overview</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/dashboard.php" class="admin-nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
        </li>

        <li class="admin-nav-header">Catalog &amp; Inventory</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/products.php" class="admin-nav-link <?= in_array($activePage, ['products', 'product_edit']) ? 'active' : '' ?>">
                <i class="fa-solid fa-mobile-screen-button"></i> <span>My Products</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/product_add.php" class="admin-nav-link <?= $activePage === 'product_add' ? 'active' : '' ?>">
                <i class="fa-solid fa-square-plus"></i> <span>Add New Mobile</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/inventory.php" class="admin-nav-link <?= $activePage === 'inventory' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Stock Management</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/restock.php" class="admin-nav-link <?= $activePage === 'restock' ? 'active' : '' ?>">
                <i class="fa-solid fa-triangle-exclamation text-warning"></i> <span>Restock Alerts</span>
            </a>
        </li>

        <li class="admin-nav-header">Sales &amp; Logistics</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/orders.php" class="admin-nav-link <?= $activePage === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-flatbed"></i> <span>Purchase Orders</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/sales.php" class="admin-nav-link <?= $activePage === 'sales' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> <span>Sales Breakdown</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/shipments.php" class="admin-nav-link <?= $activePage === 'shipments' ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-fast"></i> <span>Shipment Dispatch</span>
            </a>
        </li>

        <li class="admin-nav-header">Account</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/supplier/profile.php" class="admin-nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i> <span>Company Profile</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/index.php" class="admin-nav-link" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Customer Store</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="admin-nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Main Wrapper -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button" onclick="document.querySelector('.admin-sidebar').classList.toggle('show')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($pageTitle ?? 'Supplier Portal') ?></h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Verified Supplier</span>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-user fa-2x text-secondary"></i>
                <div class="d-none d-sm-block text-start">
                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($user['name']) ?></div>
                    <small class="text-muted">Supplier ID: #<?= $supplierId ?></small>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-content">
        <?php render_flash_messages(); ?>
