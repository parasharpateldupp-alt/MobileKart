<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Storefront Homepage (Flipkart-Inspired)
 */

$pageTitle = "MobileKart - India's Premier Mobile Purchasing & Distributing Hub";
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

try {
    $db = get_db_connection();

    // Featured Mobiles
    $featuredStmt = $db->query("
        SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'ACTIVE' AND p.is_featured = 1
        ORDER BY p.product_id ASC
        LIMIT 4
    ");
    $featuredProducts = $featuredStmt->fetchAll();

    // Trending Mobiles
    $trendingStmt = $db->query("
        SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'ACTIVE' AND p.is_trending = 1
        ORDER BY p.rating DESC
        LIMIT 4
    ");
    $trendingProducts = $trendingStmt->fetchAll();

    // Top Discount Deals
    $discountStmt = $db->query("
        SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'ACTIVE' AND p.discount > 0
        ORDER BY p.discount DESC
        LIMIT 4
    ");
    $discountProducts = $discountStmt->fetchAll();

    // Brands list for marquee
    $brands = $db->query("SELECT * FROM brands WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();

} catch (Exception $e) {
    $featuredProducts = [];
    $trendingProducts = [];
    $discountProducts = [];
    $brands = [];
}
?>

<!-- Hero Banner Carousel -->
<div class="container mt-3">
    <div id="heroCarousel" class="carousel slide rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <!-- Slide 1: Apple iPhone 18 Pro Max -->
            <div class="carousel-item active" style="background: linear-gradient(110deg, #18181b 0%, #27272a 60%, #3f3f46 100%); min-height: 380px;">
                <div class="container py-5 px-4 px-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-7 text-white">
                            <span class="badge bg-warning text-dark px-3 py-2 text-uppercase fw-bold mb-2">2026 TSMC 2nm Flagship</span>
                            <h1 class="fw-black display-5 fw-bold mb-2">Apple iPhone 18 Pro Max</h1>
                            <p class="text-white-50 fs-5 mb-4">Aerospace Titanium • 48MP Fusion Triple Camera with 10x Tetraprism • iOS 20</p>
                            <div class="d-flex align-items-center gap-3">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=1" class="btn btn-fk-yellow btn-lg">
                                    <i class="fa-solid fa-cart-shopping me-2"></i> Shop Now from ₹1,59,706
                                </a>
                                <span class="text-white-50 small">No Cost EMI Available</span>
                            </div>
                        </div>
                        <div class="col-md-5 text-center d-none d-md-block">
                            <img src="<?= BASE_URL ?>/assets/images/products/iphone18_promax_natural.png" alt="iPhone 18 Pro Max" style="max-height: 290px;" class="drop-shadow">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Samsung Galaxy S26 Ultra -->
            <div class="carousel-item" style="background: linear-gradient(110deg, #0f172a 0%, #1e293b 60%, #334155 100%); min-height: 380px;">
                <div class="container py-5 px-4 px-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-7 text-white">
                            <span class="badge bg-primary px-3 py-2 text-uppercase fw-bold mb-2">Next-Gen Galaxy AI</span>
                            <h1 class="fw-black display-5 fw-bold mb-2">Samsung Galaxy S26 Ultra 5G</h1>
                            <p class="text-white-50 fs-5 mb-4">Snapdragon 8 Gen 5 Ultra • 200MP Quad Zoom Camera • Titanium Gray Armor</p>
                            <div class="d-flex align-items-center gap-3">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=5" class="btn btn-fk-yellow btn-lg">
                                    <i class="fa-solid fa-bolt me-2"></i> Grab Deal at ₹1,27,399
                                </a>
                                <span class="text-white-50 small">Built-in S-Pen</span>
                            </div>
                        </div>
                        <div class="col-md-5 text-center d-none d-md-block">
                            <img src="<?= BASE_URL ?>/assets/images/products/s26_ultra_gray.png" alt="Samsung Galaxy S26 Ultra" style="max-height: 290px;" class="drop-shadow">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Apple iPhone Duo (Foldable) -->
            <div class="carousel-item" style="background: linear-gradient(110deg, #31103f 0%, #4a1d6d 60%, #6b21a8 100%); min-height: 380px;">
                <div class="container py-5 px-4 px-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-7 text-white">
                            <span class="badge bg-light text-dark px-3 py-2 text-uppercase fw-bold mb-2">Revolutionary Foldable</span>
                            <h1 class="fw-black display-5 fw-bold mb-2">Apple iPhone Duo Foldable</h1>
                            <p class="text-white-50 fs-5 mb-4">8.1" Inner Ceramic OLED • Titanium Flex Hinge • Spatial Video 8K Recording</p>
                            <div class="d-flex align-items-center gap-3">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=2" class="btn btn-fk-yellow btn-lg">
                                    <i class="fa-solid fa-fire me-2"></i> Pre-Order at ₹1,89,905
                                </a>
                                <span class="text-white-50 small">Dual MagSafe Induction</span>
                            </div>
                        </div>
                        <div class="col-md-5 text-center d-none d-md-block">
                            <img src="<?= BASE_URL ?>/assets/images/products/iphone_duo_open.jpg" alt="Apple iPhone Duo" style="max-height: 280px; border-radius: 16px;" class="drop-shadow">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</div>

<!-- Brand Quick-Access Strip -->
<div class="container mt-4">
    <div class="card border-0 shadow-sm p-3 rounded-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-tags text-primary me-2"></i> Shop by Top Brands</h6>
            <a href="<?= BASE_URL ?>/customer/products.php" class="text-primary text-decoration-none small fw-bold">View All <i class="fa-solid fa-chevron-right ms-1"></i></a>
        </div>
        <div class="d-flex align-items-center gap-2 overflow-auto py-2">
            <?php foreach ($brands as $brand): ?>
                <a href="<?= BASE_URL ?>/customer/products.php?brand=<?= urlencode($brand['slug']) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 text-nowrap d-flex align-items-center gap-2">
                    <?= brand_logo_html($brand, 18) ?>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($brand['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 1. Featured Mobiles Section -->
<div class="container mt-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-crown text-warning me-2"></i> Featured Flagship Smartphones
                </h5>
                <small class="text-muted">Top-tier smartphones with cutting edge processors & camera innovations</small>
            </div>
            <a href="<?= BASE_URL ?>/customer/products.php?filter=featured" class="btn btn-fk-primary btn-sm">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="fk-product-card">
                            <div class="fk-product-img-wrapper">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>">
                                    <img src="<?= product_image_url($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="fk-product-img">
                                </a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                                    <?= brand_logo_html($product, 14) ?>
                                    <span><?= htmlspecialchars($product['brand_name']) ?></span>
                                </span>
                                <?= product_rating_badge_html($product['rating'], $product['reviews_count']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>" class="fk-product-title">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </a>
                            <div class="mb-2">
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['ram']) ?> RAM</span>
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['storage']) ?></span>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex align-items-baseline mb-3">
                                    <span class="fk-price"><?= format_inr($product['final_price'], false) ?></span>
                                    <?php if ($product['discount'] > 0): ?>
                                        <span class="fk-original-price"><?= format_inr($product['price'], false) ?></span>
                                        <span class="fk-discount-badge"><?= $product['discount'] ?>% off</span>
                                    <?php endif; ?>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="addToCart(<?= $product['product_id'] ?>, 1, false)">
                                            <i class="fa-solid fa-cart-plus me-1"></i> Cart
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-fk-orange btn-sm w-100" onclick="addToCart(<?= $product['product_id'] ?>, 1, true)">
                                            <i class="fa-solid fa-bolt me-1"></i> Buy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. Trending Mobiles Section -->
<div class="container mt-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-fire text-danger me-2"></i> Trending 5G Smartphones
                </h5>
                <small class="text-muted">High demand phones popular among buyers this week</small>
            </div>
            <a href="<?= BASE_URL ?>/customer/products.php?filter=trending" class="btn btn-fk-primary btn-sm">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <?php foreach ($trendingProducts as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="fk-product-card">
                            <div class="fk-product-img-wrapper">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>">
                                    <img src="<?= product_image_url($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="fk-product-img">
                                </a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                                    <?= brand_logo_html($product, 14) ?>
                                    <span><?= htmlspecialchars($product['brand_name']) ?></span>
                                </span>
                                <?= product_rating_badge_html($product['rating'], $product['reviews_count']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>" class="fk-product-title">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </a>
                            <div class="mb-2">
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['ram']) ?> RAM</span>
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['storage']) ?></span>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex align-items-baseline mb-3">
                                    <span class="fk-price"><?= format_inr($product['final_price'], false) ?></span>
                                    <?php if ($product['discount'] > 0): ?>
                                        <span class="fk-original-price"><?= format_inr($product['price'], false) ?></span>
                                        <span class="fk-discount-badge"><?= $product['discount'] ?>% off</span>
                                    <?php endif; ?>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="addToCart(<?= $product['product_id'] ?>, 1, false)">
                                            <i class="fa-solid fa-cart-plus me-1"></i> Cart
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-fk-orange btn-sm w-100" onclick="addToCart(<?= $product['product_id'] ?>, 1, true)">
                                            <i class="fa-solid fa-bolt me-1"></i> Buy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- 3. Top Discount Deals Section -->
<div class="container mt-4">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-percent text-success me-2"></i> Best Discount Deals (Up to 18% Off)
                </h5>
                <small class="text-muted">Unbeatable value on premier Android &amp; iOS devices</small>
            </div>
            <a href="<?= BASE_URL ?>/customer/products.php?sort=discount_desc" class="btn btn-fk-primary btn-sm">
                View All Deals <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <?php foreach ($discountProducts as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="fk-product-card">
                            <div class="fk-product-img-wrapper">
                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>">
                                    <img src="<?= product_image_url($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="fk-product-img">
                                </a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1 py-1 px-2">
                                    <?= brand_logo_html($product, 14) ?>
                                    <span><?= htmlspecialchars($product['brand_name']) ?></span>
                                </span>
                                <?= product_rating_badge_html($product['rating'], $product['reviews_count']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>" class="fk-product-title">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </a>
                            <div class="mb-2">
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['ram']) ?> RAM</span>
                                <span class="fk-spec-pill"><?= htmlspecialchars($product['storage']) ?></span>
                            </div>
                            <div class="mt-auto">
                                <div class="d-flex align-items-baseline mb-3">
                                    <span class="fk-price"><?= format_inr($product['final_price'], false) ?></span>
                                    <span class="fk-original-price"><?= format_inr($product['price'], false) ?></span>
                                    <span class="fk-discount-badge"><?= $product['discount'] ?>% off</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="addToCart(<?= $product['product_id'] ?>, 1, false)">
                                            <i class="fa-solid fa-cart-plus me-1"></i> Cart
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-fk-orange btn-sm w-100" onclick="addToCart(<?= $product['product_id'] ?>, 1, true)">
                                            <i class="fa-solid fa-bolt me-1"></i> Buy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Trust & Feature Highlights -->
<div class="container my-5">
    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-3">
                <i class="fa-solid fa-shield-halved fa-2x text-primary mb-3"></i>
                <h6 class="fw-bold mb-1">100% Genuine Mobiles</h6>
                <p class="text-muted small mb-0">Direct from authorized Indian distributors with full brand warranty.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-3">
                <i class="fa-solid fa-truck-fast fa-2x text-warning mb-3"></i>
                <h6 class="fw-bold mb-1">Free Express Delivery</h6>
                <p class="text-muted small mb-0">Free doorstep delivery on orders over ₹5,000 via premier couriers.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-3">
                <i class="fa-solid fa-lock fa-2x text-success mb-3"></i>
                <h6 class="fw-bold mb-1">100% Secure Payments</h6>
                <p class="text-muted small mb-0">256-bit encrypted checkout with UPI, Credit/Debit Cards &amp; Net Banking.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 rounded-3">
                <i class="fa-solid fa-arrows-rotate fa-2x text-info mb-3"></i>
                <h6 class="fw-bold mb-1">7-Day Replacement</h6>
                <p class="text-muted small mb-0">Hassle-free replacement policy for defective or damaged shipments.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
