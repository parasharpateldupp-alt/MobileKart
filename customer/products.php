<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Mobile Products Catalog & Faceted Search (DFD P1.2)
 */

$pageTitle = "Mobile Phones Catalog & Search - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';

$db = get_db_connection();

// Filters Extraction
$q = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$brandSlugs = (array)($_GET['brand'] ?? []);
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$ramFilter = (array)($_GET['ram'] ?? []);
$storageFilter = (array)($_GET['storage'] ?? []);
$minRating = isset($_GET['rating']) && is_numeric($_GET['rating']) ? (float)$_GET['rating'] : null;
$inStockOnly = !empty($_GET['in_stock']);
$sort = trim($_GET['sort'] ?? 'popularity');
$filterSpecial = trim($_GET['filter'] ?? '');

// Build Query
$where = ["p.status = 'ACTIVE'"];
$params = [];

if ($q !== '') {
    $where[] = "(p.product_name LIKE ? OR p.model LIKE ? OR b.name LIKE ? OR p.processor LIKE ?)";
    $term = "%{$q}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($categorySlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if (!empty($brandSlugs)) {
    $placeholders = implode(',', array_fill(0, count($brandSlugs), '?'));
    $where[] = "b.slug IN ({$placeholders})";
    foreach ($brandSlugs as $b) {
        $params[] = $b;
    }
}

if ($minPrice !== null) {
    $where[] = "p.final_price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = "p.final_price <= ?";
    $params[] = $maxPrice;
}

if (!empty($ramFilter)) {
    $placeholders = implode(',', array_fill(0, count($ramFilter), '?'));
    $where[] = "p.ram IN ({$placeholders})";
    foreach ($ramFilter as $r) {
        $params[] = $r;
    }
}

if (!empty($storageFilter)) {
    $placeholders = implode(',', array_fill(0, count($storageFilter), '?'));
    $where[] = "p.storage IN ({$placeholders})";
    foreach ($storageFilter as $s) {
        $params[] = $s;
    }
}

if ($minRating !== null) {
    $where[] = "p.rating >= ?";
    $params[] = $minRating;
}

if ($inStockOnly) {
    $where[] = "p.stock_quantity > 0";
}

if ($filterSpecial === 'featured') {
    $where[] = "p.is_featured = 1";
} elseif ($filterSpecial === 'trending') {
    $where[] = "p.is_trending = 1";
}

// Sorting logic
$orderBy = match($sort) {
    'price_asc'     => "p.final_price ASC",
    'price_desc'    => "p.final_price DESC",
    'rating_desc'   => "p.rating DESC",
    'discount_desc' => "p.discount DESC",
    'newest'        => "p.created_at DESC",
    default         => "p.rating DESC, p.reviews_count DESC"
};

$sql = "
    SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch filter options for sidebar
$allCategories = $db->query("SELECT * FROM categories WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();
$allBrands = $db->query("SELECT * FROM brands WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();
$allRams = ['8 GB', '12 GB', '16 GB'];
$allStorages = ['128 GB', '256 GB', '512 GB'];
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/products.php" class="text-decoration-none">Mobiles</a></li>
            <?php if ($categorySlug): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars(ucwords(str_replace('-', ' ', $categorySlug))) ?></li>
            <?php endif; ?>
            <?php if ($q): ?>
                <li class="breadcrumb-item active">Search: "<?= htmlspecialchars($q) ?>"</li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="row g-4">
        
        <!-- Left Sidebar: Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white sticky-top" style="top: 80px;">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-sliders text-primary me-2"></i> Filters</h6>
                    <a href="<?= BASE_URL ?>/customer/products.php" class="text-danger small fw-bold text-decoration-none">Reset All</a>
                </div>

                <form action="<?= BASE_URL ?>/customer/products.php" method="GET">
                    <?php if ($q): ?>
                        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                    <?php endif; ?>

                    <!-- Price Filter -->
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small text-secondary">PRICE RANGE (₹)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= htmlspecialchars($minPrice ?? '') ?>" step="1000">
                            <span class="text-muted">-</span>
                            <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= htmlspecialchars($maxPrice ?? '') ?>" step="1000">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small text-secondary">CATEGORY</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Brand Checkboxes -->
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small text-secondary">BRAND</label>
                        <div style="max-height: 160px; overflow-y: auto;" class="pe-1">
                            <?php foreach ($allBrands as $brand): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="brand[]" value="<?= htmlspecialchars($brand['slug']) ?>" id="brand_<?= htmlspecialchars($brand['slug']) ?>" <?= in_array($brand['slug'], $brandSlugs) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="brand_<?= htmlspecialchars($brand['slug']) ?>">
                                        <?= htmlspecialchars($brand['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- RAM Filter -->
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small text-secondary">RAM</label>
                        <?php foreach ($allRams as $ram): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="ram[]" value="<?= htmlspecialchars($ram) ?>" id="ram_<?= md5($ram) ?>" <?= in_array($ram, $ramFilter) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="ram_<?= md5($ram) ?>">
                                    <?= htmlspecialchars($ram) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Storage Filter -->
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold small text-secondary">STORAGE</label>
                        <?php foreach ($allStorages as $st): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="storage[]" value="<?= htmlspecialchars($st) ?>" id="storage_<?= md5($st) ?>" <?= in_array($st, $storageFilter) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="storage_<?= md5($st) ?>">
                                    <?= htmlspecialchars($st) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Stock Availability -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStockCheck" <?= $inStockOnly ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-success" for="inStockCheck">
                                <i class="fa-solid fa-circle-check me-1"></i> In Stock Only
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-fk-primary btn-sm w-100 fw-bold">
                        <i class="fa-solid fa-filter me-1"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Content: Product Grid -->
        <div class="col-lg-9">
            
            <!-- Top Controls Bar -->
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Mobile Phones Catalog</h5>
                        <small class="text-muted">Showing <strong><?= count($products) ?></strong> smartphones matching criteria</small>
                    </div>

                    <!-- Sorting Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-secondary fw-semibold text-nowrap mb-0">Sort By:</label>
                        <form method="GET" class="d-inline" id="sortForm">
                            <?php 
                            // Preserve other query parameters
                            foreach ($_GET as $key => $val) {
                                if ($key !== 'sort') {
                                    if (is_array($val)) {
                                        foreach ($val as $subVal) {
                                            echo '<input type="hidden" name="' . htmlspecialchars($key) . '[]" value="' . htmlspecialchars($subVal) . '">';
                                        }
                                    } else {
                                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '">';
                                    }
                                }
                            }
                            ?>
                            <select name="sort" class="form-select form-select-sm" onchange="document.getElementById('sortForm').submit();">
                                <option value="popularity" <?= $sort === 'popularity' ? 'selected' : '' ?>>Popularity</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Customer Rating</option>
                                <option value="discount_desc" <?= $sort === 'discount_desc' ? 'selected' : '' ?>>Discount</option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products List -->
            <?php if (empty($products)): ?>
                <div class="card border-0 shadow-sm p-5 text-center rounded-3 bg-white">
                    <i class="fa-solid fa-mobile-screen-button fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="fw-bold text-dark">No smartphones match your filter</h5>
                    <p class="text-muted small">Try broadening your search term or resetting the price and brand filters.</p>
                    <div class="mt-2">
                        <a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-fk-primary">Clear All Filters</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($products as $product): ?>
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="fk-product-card">
                                <div class="fk-product-img-wrapper">
                                    <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>">
                                        <img src="<?= product_image_url($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="fk-product-img">
                                    </a>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($product['brand_name']) ?></span>
                                    <span class="fk-rating-badge"><?= number_format($product['rating'], 1) ?> ★</span>
                                </div>

                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $product['product_id'] ?>" class="fk-product-title">
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </a>

                                <div class="mb-2">
                                    <span class="fk-spec-pill"><?= htmlspecialchars($product['ram']) ?> RAM</span>
                                    <span class="fk-spec-pill"><?= htmlspecialchars($product['storage']) ?></span>
                                </div>

                                <div class="small text-muted mb-2 text-truncate" title="<?= htmlspecialchars($product['processor']) ?>">
                                    <i class="fa-solid fa-microchip me-1"></i> <?= htmlspecialchars($product['processor']) ?>
                                </div>

                                <div class="mt-auto">
                                    <div class="d-flex align-items-baseline mb-2">
                                        <span class="fk-price"><?= format_inr($product['final_price'], false) ?></span>
                                        <?php if ($product['discount'] > 0): ?>
                                            <span class="fk-original-price"><?= format_inr($product['price'], false) ?></span>
                                            <span class="fk-discount-badge"><?= $product['discount'] ?>% off</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Stock Status Indicator -->
                                    <div class="mb-2">
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] <= $product['minimum_stock']): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Only <?= $product['stock_quantity'] ?> left!</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> In Stock</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" class="btn btn-outline-primary btn-sm w-100 fw-bold" onclick="addToCart(<?= $product['product_id'] ?>, 1, false)" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="fa-solid fa-cart-plus me-1"></i> Cart
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" class="btn btn-fk-orange btn-sm w-100" onclick="addToCart(<?= $product['product_id'] ?>, 1, true)" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="fa-solid fa-bolt me-1"></i> Buy
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
