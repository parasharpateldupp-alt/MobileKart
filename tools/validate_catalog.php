<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Automated Catalog Data-Integrity Audit & Validation Tool
 * 
 * Executable via CLI: php tools/validate_catalog.php
 * Executable via Web: https://mobile-kart-zeta.vercel.app/tools/validate_catalog.php
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$isCli = (php_sapi_name() === 'cli');

try {
    $db = get_db_connection();
} catch (Exception $e) {
    if ($isCli) {
        echo "ERROR: Failed to connect to database: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        die("<h3>Database Connection Error:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

// Fetch all products
$stmt = $db->query("
    SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.product_id ASC
");
$products = $stmt->fetchAll();

// Preload relational metadata in 3 bulk queries to eliminate per-iteration latency
$allImages = $db->query("SELECT * FROM product_images ORDER BY is_primary DESC, sort_order ASC")->fetchAll();
$imagesByProduct = [];
foreach ($allImages as $im) {
    $pid = (int)$im['product_id'];
    if (!isset($imagesByProduct[$pid])) {
        $imagesByProduct[$pid] = $im;
    }
}

$catCounts = $db->query("SELECT product_id, COUNT(*) AS cnt FROM product_categories GROUP BY product_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$varCounts = $db->query("SELECT product_id, COUNT(*) AS cnt FROM product_variants GROUP BY product_id")->fetchAll(PDO::FETCH_KEY_PAIR);

$results = [];
$totalChecks = 0;
$passedChecks = 0;
$failedChecks = 0;

$brandKeywords = [
    'Apple' => ['iPhone', 'Apple'],
    'Samsung' => ['Galaxy', 'Samsung'],
    'Google' => ['Pixel', 'Google'],
    'OnePlus' => ['OnePlus'],
    'Xiaomi' => ['Xiaomi'],
    'Vivo' => ['Vivo'],
    'Realme' => ['Realme'],
    'Motorola' => ['Motorola', 'Moto'],
    'Nothing' => ['Nothing'],
    'iQOO' => ['iQOO'],
    'Redmi' => ['Redmi']
];

foreach ($products as $p) {
    $pId = (int)$p['product_id'];
    $name = $p['product_name'];
    $brand = $p['brand_name'];
    $issues = [];

    // 1. Check Brand Mapping
    $totalChecks++;
    $brandValid = false;
    $expected = $brandKeywords[$brand] ?? [$brand];
    foreach ($expected as $kw) {
        if (stripos($name, $kw) !== false) {
            $brandValid = true;
            break;
        }
    }
    if (!$brandValid) {
        $issues[] = ['code' => 'WRONG_BRAND', 'msg' => "Product name '{$name}' does not match brand '{$brand}'."];
    }

    // 2. Check Primary Image & Registration
    $totalChecks++;
    $img = $imagesByProduct[$pId] ?? null;
    if (!$img) {
        $issues[] = ['code' => 'WRONG_IMAGE', 'msg' => "No verified primary image registered in product_images metadata table."];
    } else {
        $localPath = dirname(__DIR__) . '/' . ltrim($img['local_path'] ?? $img['image_url'], '/');
        if (!file_exists($localPath) && !str_starts_with($img['image_url'], 'http')) {
            $issues[] = ['code' => 'WRONG_IMAGE', 'msg' => "Image file does not exist on disk: {$img['image_url']}"];
        }
    }

    // 3. Check Multi-Category Mapping
    $totalChecks++;
    $catCount = (int)($catCounts[$pId] ?? 0);
    if ($catCount === 0) {
        $issues[] = ['code' => 'WRONG_CATEGORY', 'msg' => "No category associations found in product_categories table."];
    }

    // 4. Check Price
    $totalChecks++;
    if ($p['price'] <= 0) {
        $issues[] = ['code' => 'INVALID_PRICE', 'msg' => "Base MRP price must be greater than zero."];
    }

    // 5. Check Discount Calculation
    $totalChecks++;
    $expectedFinal = round($p['price'] * (1 - ($p['discount'] / 100)));
    if (abs($p['final_price'] - $expectedFinal) > 1.0) {
        $issues[] = ['code' => 'INVALID_DISCOUNT', 'msg' => "final_price ({$p['final_price']}) does not match formula round(price * (1 - discount/100)) = {$expectedFinal}."];
    }

    // 6. Check Variants
    $totalChecks++;
    $varCount = (int)($varCounts[$pId] ?? 0);
    if ($varCount === 0) {
        $issues[] = ['code' => 'MISSING_VARIANT', 'msg' => "No product variants configured in product_variants table."];
    }

    // 7. Check Stock Quantities
    $totalChecks++;
    if ($p['stock_quantity'] < 0 || $p['minimum_stock'] < 0) {
        $issues[] = ['code' => 'INVALID_STOCK', 'msg' => "Stock quantity or minimum threshold cannot be negative."];
    }

    // 8. Check Rating and Review counts
    $totalChecks++;
    if ($p['rating'] < 0.0 || $p['rating'] > 5.0 || $p['reviews_count'] < 0) {
        $issues[] = ['code' => 'INVALID_RATING', 'msg' => "Rating must be between 0.0 and 5.0 and review count non-negative."];
    }

    $status = empty($issues) ? 'PASSED' : 'FAILED';
    if ($status === 'PASSED') {
        $passedChecks += 8;
    } else {
        $failedCount = count($issues);
        $failedChecks += $failedCount;
        $passedChecks += (8 - $failedCount);
    }

    $results[] = [
        'id' => $pId,
        'name' => $name,
        'brand' => $brand,
        'price' => $p['price'],
        'discount' => $p['discount'],
        'final_price' => $p['final_price'],
        'stock' => $p['stock_quantity'],
        'rating' => $p['rating'],
        'status' => $status,
        'issues' => $issues
    ];
}

$score = round(($passedChecks / max(1, $totalChecks)) * 100, 1);

// -------------------------------------------------------------
// CLI OUTPUT
// -------------------------------------------------------------
if ($isCli) {
    echo "\n" . str_repeat("=", 85) . "\n";
    echo " MOBILEKART CATALOG DATA-INTEGRITY AUDIT REPORT (DFD P1.2 / Section 41)\n";
    echo str_repeat("=", 85) . "\n\n";

    printf("%-4s | %-32s | %-12s | %-10s | %-8s | %-6s\n", "ID", "Product Name", "Brand", "Price (INR)", "Stock", "Status");
    echo str_repeat("-", 85) . "\n";

    foreach ($results as $r) {
        printf(
            "%-4d | %-32s | %-12s | ₹%-9s | %-8d | %s\n",
            $r['id'],
            mb_strimwidth($r['name'], 0, 32, "..."),
            $r['brand'],
            number_format($r['final_price']),
            $r['stock'],
            $r['status'] === 'PASSED' ? "[32mPASSED[0m" : "[31mFAILED[0m"
        );
        if (!empty($r['issues'])) {
            foreach ($r['issues'] as $iss) {
                echo "     ↳ [31m[{$iss['code']}][0m {$iss['msg']}\n";
            }
        }
    }

    echo str_repeat("=", 85) . "\n";
    echo "SUMMARY:\n";
    echo "  Total Products Audited : " . count($results) . "\n";
    echo "  Total Integrity Checks : {$totalChecks}\n";
    echo "  Checks Passed          : {$passedChecks}\n";
    echo "  Checks Failed          : {$failedChecks}\n";
    echo "  Integrity Health Score : {$score}%\n";
    echo str_repeat("=", 85) . "\n\n";
    exit($failedChecks === 0 ? 0 : 1);
}

// -------------------------------------------------------------
// WEB DASHBOARD OUTPUT
// -------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog Integrity Validation Report - MobileKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .score-card { border-radius: 16px; border: none; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-clipboard-check text-primary me-2"></i> Catalog Data-Integrity Audit
            </h3>
            <p class="text-muted mb-0">Phase 41 Automated Catalog Quality & Integrity Validator</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline-primary btn-sm me-2">
                <i class="fa-solid fa-arrow-left me-1"></i> Admin Catalog
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-store me-1"></i> Storefront
            </a>
        </div>
    </div>

    <!-- Summary KPI Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card score-card shadow-sm p-4 bg-white">
                <div class="text-muted small text-uppercase fw-bold mb-1">Integrity Score</div>
                <div class="fs-2 fw-bold <?= $score >= 95 ? 'text-success' : 'text-danger' ?>"><?= $score ?>%</div>
                <small class="text-muted">Target: 100% Clean</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card score-card shadow-sm p-4 bg-white">
                <div class="text-muted small text-uppercase fw-bold mb-1">Products Scanned</div>
                <div class="fs-2 fw-bold text-dark"><?= count($results) ?> / 18</div>
                <small class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> 100% Canonical Lineup</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card score-card shadow-sm p-4 bg-white">
                <div class="text-muted small text-uppercase fw-bold mb-1">Total Rules Checked</div>
                <div class="fs-2 fw-bold text-primary"><?= $totalChecks ?></div>
                <small class="text-muted">8 Validation Rules / Product</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card score-card shadow-sm p-4 bg-white">
                <div class="text-muted small text-uppercase fw-bold mb-1">Violations Found</div>
                <div class="fs-2 fw-bold <?= $failedChecks === 0 ? 'text-success' : 'text-danger' ?>"><?= $failedChecks ?></div>
                <small class="<?= $failedChecks === 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                    <?= $failedChecks === 0 ? '<i class="fa-solid fa-shield-check me-1"></i> 0 Anomalies' : 'Action Required' ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">Audit Results by Smartphone Product</h6>
            <span class="badge bg-success-subtle text-success px-3 py-2 fw-bold">Audit Complete</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Product Details</th>
                        <th>Brand</th>
                        <th>Selling Price</th>
                        <th>Discount</th>
                        <th>Stock Units</th>
                        <th>Rating</th>
                        <th class="text-center">Integrity Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted"><?= $r['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['name']) ?></div>
                                <?php if (!empty($r['issues'])): ?>
                                    <div class="mt-1">
                                        <?php foreach ($r['issues'] as $iss): ?>
                                            <span class="badge bg-danger-subtle text-danger small me-1">
                                                [<?= htmlspecialchars($iss['code']) ?>] <?= htmlspecialchars($iss['msg']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['brand']) ?></span></td>
                            <td class="fw-bold text-dark"><?= format_inr($r['final_price']) ?></td>
                            <td><span class="badge bg-success-subtle text-success"><?= $r['discount'] ?>% OFF</span></td>
                            <td>
                                <span class="badge <?= $r['stock'] > 10 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $r['stock'] ?> units
                                </span>
                            </td>
                            <td>
                                <span class="text-warning"><i class="fa-solid fa-star"></i></span>
                                <span class="fw-bold text-dark small"><?= number_format($r['rating'], 1) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($r['status'] === 'PASSED'): ?>
                                    <span class="badge bg-success px-3 py-2 fw-bold">
                                        <i class="fa-solid fa-circle-check me-1"></i> VALID
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger px-3 py-2 fw-bold">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= count($r['issues']) ?> ISSUES
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
