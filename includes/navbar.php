<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Flipkart-Inspired Navigation Bar
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
$cartCount = get_cart_count();

// Fetch categories for category strip
$categories = [];
try {
    $db = get_db_connection();
    $categories = $db->query("SELECT * FROM categories WHERE status = 'ACTIVE' ORDER BY category_id ASC LIMIT 7")->fetchAll();
} catch (Exception $e) {
    // Graceful fallback
}
?>

<!-- Primary Header -->
<header class="fk-navbar sticky-top">
    <div class="container d-flex align-items-center justify-content-between gap-3 flex-wrap">
        
        <!-- Brand Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="fk-logo">
            <i class="fa-solid fa-mobile-screen-button text-warning fs-3"></i>
            <div>
                <span class="text-white fw-bold">Mobile<span class="text-warning">Kart</span></span>
                <div class="fk-logo-sub">Purchasing & Distributing</div>
            </div>
        </a>

        <!-- Search Bar -->
        <div class="fk-search-box flex-grow-1 mx-lg-4" style="max-width: 580px;">
            <form action="<?= BASE_URL ?>/customer/products.php" method="GET" class="d-flex align-items-center m-0">
                <input type="text" name="q" class="form-control fk-search-input" placeholder="Search for Samsung, iPhone, OnePlus, 5G mobiles and more..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                <button type="submit" class="fk-search-btn" aria-label="Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="d-flex align-items-center gap-2">
            

            <!-- Cart Button -->
            <a href="<?= BASE_URL ?>/customer/cart.php" class="fk-nav-link position-relative">
                <i class="fa-solid fa-cart-shopping fs-5"></i>
                <span class="d-none d-sm-inline">Cart</span>
                <span class="fk-cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
            </a>

            <!-- User Auth Dropdown -->
            <?php if ($currentUser): ?>
                <div class="dropdown">
                    <button class="btn fk-nav-link dropdown-toggle bg-white text-primary border-0 px-3 py-1 fw-bold shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-user-circle fs-5"></i>
                        <span><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 py-2">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($currentUser['email']) ?></small>
                            <div class="mt-1"><span class="badge bg-primary"><?= htmlspecialchars($currentUser['role']) ?></span></div>
                        </li>

                        <?php if ($currentUser['role'] === ROLE_ADMIN): ?>
                            <li><a class="dropdown-item py-2 fw-semibold text-primary" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fa-solid fa-gauge-high me-2"></i> Admin Panel</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php elseif ($currentUser['role'] === ROLE_SUPPLIER): ?>
                            <li><a class="dropdown-item py-2 fw-semibold text-warning" href="<?= BASE_URL ?>/supplier/dashboard.php"><i class="fa-solid fa-warehouse me-2"></i> Supplier Portal</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>

                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/orders.php"><i class="fa-solid fa-box-archive me-2 text-muted"></i> My Orders</a></li>
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/profile.php"><i class="fa-solid fa-id-card me-2 text-muted"></i> My Profile & Address</a></li>
                        <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/customer/reviews.php"><i class="fa-solid fa-star-half-stroke me-2 text-muted"></i> My Reviews</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-light text-primary fw-bold px-3 py-1 shadow-sm">
                    <i class="fa-solid fa-user me-1"></i> Login
                </a>
                <a href="<?= BASE_URL ?>/register.php" class="fk-nav-link d-none d-lg-inline">
                    Register
                </a>
            <?php endif; ?>
        </div>

    </div>
</header>

<!-- Category Strip -->
<nav class="fk-category-strip d-none d-md-block">
    <div class="container d-flex align-items-center justify-content-between overflow-auto">
        <a href="<?= BASE_URL ?>/customer/products.php" class="fk-cat-item">
            <i class="fa-solid fa-grid-2 text-primary"></i> All Mobiles
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/customer/products.php?category=<?= urlencode($cat['slug']) ?>" class="fk-cat-item">
                <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?> text-primary"></i> <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/customer/products.php?filter=trending" class="fk-cat-item text-danger">
            <i class="fa-solid fa-fire"></i> Trending Deals
        </a>
    </div>
</nav>

<!-- System Flash Alerts -->
<div class="container mt-3">
    <?php render_flash_messages(); ?>
</div>
