<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Panel Header & Sidebar Layout
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();

$user = current_user();
$activePage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?> - MobileKart System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<!-- Admin Sidebar -->
<aside class="admin-sidebar shadow">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-brand">
        <i class="fa-solid fa-screwdriver-wrench text-warning"></i>
        <span>Admin Panel</span>
    </a>

    <ul class="admin-nav">
        <li class="admin-nav-header">Core Operations</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/analytics.php" class="admin-nav-link <?= $activePage === 'analytics' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> <span>Analytics Engine</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                <i class="fa-solid fa-file-csv"></i> <span>Reports &amp; Export</span>
            </a>
        </li>

        <li class="admin-nav-header">User Management</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-link <?= $activePage === 'users' ? 'active' : '' ?>">
                <i class="fa-solid fa-users-gear"></i> <span>Users Directory</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/customers.php" class="admin-nav-link <?= $activePage === 'customers' ? 'active' : '' ?>">
                <i class="fa-solid fa-user-tag"></i> <span>Customers</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/suppliers.php" class="admin-nav-link <?= $activePage === 'suppliers' ? 'active' : '' ?>">
                <i class="fa-solid fa-warehouse"></i> <span>Suppliers</span>
            </a>
        </li>

        <li class="admin-nav-header">Catalog &amp; Inventory</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/products.php" class="admin-nav-link <?= $activePage === 'products' ? 'active' : '' ?>">
                <i class="fa-solid fa-mobile-screen"></i> <span>Products Master</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/categories.php" class="admin-nav-link <?= $activePage === 'categories' ? 'active' : '' ?>">
                <i class="fa-solid fa-tags"></i> <span>Categories &amp; Brands</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/inventory.php" class="admin-nav-link <?= $activePage === 'inventory' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory Management</span>
            </a>
        </li>

        <li class="admin-nav-header">Orders &amp; Payments</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/orders.php" class="admin-nav-link <?= $activePage === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> <span>Orders Processing</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/payments.php" class="admin-nav-link <?= $activePage === 'payments' ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card"></i> <span>Payments Ledger</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/invoices.php" class="admin-nav-link <?= $activePage === 'invoices' ? 'active' : '' ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> <span>Invoices Repository</span>
            </a>
        </li>

        <li class="admin-nav-header">Fulfillment &amp; Logistics</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/fulfillment.php" class="admin-nav-link <?= $activePage === 'fulfillment' ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-fast"></i> <span>Fulfillment Pipeline</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/reviews.php" class="admin-nav-link <?= $activePage === 'reviews' ? 'active' : '' ?>">
                <i class="fa-solid fa-star-half-stroke"></i> <span>Reviews Moderation</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/promotions.php" class="admin-nav-link <?= $activePage === 'promotions' ? 'active' : '' ?>">
                <i class="fa-solid fa-ticket"></i> <span>Coupons &amp; Promos</span>
            </a>
        </li>

        <li class="admin-nav-header">System Governance</li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/logs.php" class="admin-nav-link <?= $activePage === 'logs' ? 'active' : '' ?>">
                <i class="fa-solid fa-shield-halved"></i> <span>Activity Audit Trail</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-link <?= $activePage === 'settings' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i> <span>System Settings</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/index.php" class="admin-nav-link" target="_blank">
                <i class="fa-solid fa-store"></i> <span>Customer Store</span>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="admin-nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Main Area -->
<div class="admin-main">
    <header class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button" onclick="document.querySelector('.admin-sidebar').classList.toggle('show')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($pageTitle ?? 'Administrator Panel') ?></h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-bold">
                <i class="fa-solid fa-shield me-1"></i> Root Administrator
            </span>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-tie fa-2x text-secondary"></i>
                <div class="d-none d-sm-block text-start">
                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($user['name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-content">
        <?php render_flash_messages(); ?>
