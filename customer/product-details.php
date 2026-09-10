<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Product Details Page with Full Specifications & Reviews (DFD P1.2)
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$productId = (int)($_GET['id'] ?? 0);
$db = get_db_connection();

$stmt = $db->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name, s.company_name AS supplier_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN categories c ON p.category_id = c.category_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE p.product_id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: " . BASE_URL . "/customer/products.php");
    exit;
}

$pageTitle = htmlspecialchars($product['product_name']) . " - Specs & Price - MobileKart";

// Fetch color variant images
$imgStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, is_primary DESC");
$imgStmt->execute([$productId]);
$variantImages = $imgStmt->fetchAll();

// Check verified purchase eligibility for reviews
$user = current_user();
$canReview = false;
$hasReviewed = false;

if ($user && $user['role'] === ROLE_CUSTOMER) {
    // Check if user has ordered this product and it was delivered
    $ordCheck = $db->prepare("
        SELECT COUNT(*) 
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        WHERE o.customer_id = ? AND oi.product_id = ? AND o.order_status = 'DELIVERED'
    ");
    $ordCheck->execute([$user['id'], $productId]);
    $deliveredCount = (int)$ordCheck->fetchColumn();

    if ($deliveredCount > 0) {
        $canReview = true;
        // Check if already reviewed
        $revCheck = $db->prepare("SELECT COUNT(*) FROM reviews WHERE customer_id = ? AND product_id = ?");
        $revCheck->execute([$user['id'], $productId]);
        if ((int)$revCheck->fetchColumn() > 0) {
            $hasReviewed = true;
        }
    }
}

// Fetch approved reviews
$revStmt = $db->prepare("
    SELECT r.*, u.name AS reviewer_name 
    FROM reviews r 
    JOIN users u ON r.customer_id = u.user_id 
    WHERE r.product_id = ? AND r.status = 'APPROVED' 
    ORDER BY r.created_at DESC
");
$revStmt->execute([$productId]);
$reviews = $revStmt->fetchAll();

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    require_csrf();
    if ($canReview && !$hasReviewed) {
        $rating = (int)($_POST['rating'] ?? 5);
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if ($rating >= 1 && $rating <= 5 && !empty($title) && !empty($comment)) {
            $insRev = $db->prepare("
                INSERT INTO reviews (product_id, customer_id, rating, title, comment, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'APPROVED', NOW())
            ");
            $insRev->execute([$productId, $user['id'], $rating, $title, $comment]);

            // Update product average rating and count
            $avgStmt = $db->prepare("SELECT AVG(rating), COUNT(*) FROM reviews WHERE product_id = ? AND status = 'APPROVED'");
            $avgStmt->execute([$productId]);
            [$newAvg, $newCount] = $avgStmt->fetch(PDO::FETCH_NUM);

            $upProd = $db->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE product_id = ?");
            $upProd->execute([round($newAvg, 2), $newCount, $productId]);

            set_flash('success', 'Thank you! Your verified review has been published.');
            header("Location: " . BASE_URL . "/customer/product-details.php?id=" . $productId . "#reviewsSection");
            exit;
        }
    }
}

// Simulated EMI (36 months calculation)
$emiMonth = ceil($product['final_price'] / 24);

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/products.php" class="text-decoration-none">Mobiles</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/products.php?brand=<?= urlencode(strtolower($product['brand_name'])) ?>" class="text-decoration-none"><?= htmlspecialchars($product['brand_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['product_name']) ?></li>
        </ol>
    </nav>

    <!-- Main Product Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="row g-4">
            
            <!-- Left: Graphic / Image & Actions -->
            <div class="col-lg-5 text-center">
                <div class="bg-light p-4 rounded-4 mb-3 position-relative overflow-hidden" style="min-height: 420px; display: flex; align-items: center; justify-content: center;">
                    <img id="mainProductImage" src="<?= product_image_url($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" style="max-height: 380px; max-width: 100%; object-fit: contain; transition: opacity 0.2s ease, transform 0.2s ease;" class="img-fluid drop-shadow">
                </div>

                <?php if (count($variantImages) > 1): ?>
                    <!-- Variant Image Thumbnail Bar -->
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-3 flex-wrap">
                        <?php foreach ($variantImages as $vImg): ?>
                            <button type="button" 
                                    class="variant-thumb-btn border rounded-3 p-1 bg-white <?= $vImg['is_primary'] ? 'active-thumb border-primary shadow-sm' : 'border-light-subtle' ?>" 
                                    style="width: 54px; height: 54px; cursor: pointer; transition: all 0.2s ease; overflow: hidden;"
                                    onclick="switchProductColor('<?= htmlspecialchars($vImg['image_url']) ?>', '<?= htmlspecialchars(addslashes($vImg['color_name'] ?? '')) ?>', this)"
                                    title="<?= htmlspecialchars($vImg['color_name'] ?? '') ?>">
                                <img src="<?= product_image_url($vImg['image_url']) ?>" alt="<?= htmlspecialchars($vImg['color_name'] ?? '') ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    <label class="fw-bold small text-secondary">Quantity:</label>
                    <div class="input-group" style="width: 120px;">
                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="changeDetailQty(-1)">-</button>
                        <input type="number" id="detailQuantity" class="form-control form-control-sm text-center fw-bold" value="1" min="1" max="<?= max(1, $product['stock_quantity']) ?>" readonly>
                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="changeDetailQty(1)">+</button>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <button type="button" class="btn btn-fk-primary w-100 py-2 fw-bold shadow-sm" onclick="addCurrentToCart(false)" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-cart-shopping me-2"></i> Add to Cart
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-fk-orange w-100 py-2 fw-bold shadow-sm" onclick="addCurrentToCart(true)" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-bolt me-2"></i> Buy Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Specs, Pricing, Offers -->
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-light text-dark border fw-bold d-inline-flex align-items-center gap-2 py-1 px-3">
                        <?= brand_logo_html($product['brand_name'], 18) ?>
                        <span><?= htmlspecialchars($product['brand_name']) ?></span>
                    </span>
                    <span class="badge bg-light text-secondary border"><?= htmlspecialchars($product['category_name']) ?></span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto"><i class="fa-solid fa-shield-check me-1"></i> 100% Genuine</span>
                </div>

                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($product['product_name']) ?></h3>
                <div class="text-muted small mb-3">Model: <code><?= htmlspecialchars($product['model']) ?></code> • Active Color: <strong id="selectedColorLabel" class="text-primary"><?= htmlspecialchars($product['color']) ?></strong></div>

                <?php if (!empty($variantImages)): ?>
                    <!-- Color Swatches Selector -->
                    <div class="mb-3 p-3 rounded-3 bg-light border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-uppercase text-secondary" style="letter-spacing: 0.5px;"><i class="fa-solid fa-palette text-primary me-1"></i> Choose Color Variant:</span>
                            <span class="badge bg-white text-dark border small shadow-sm" id="colorBadgeName"><?= htmlspecialchars($product['color']) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="colorSwatchesContainer">
                            <?php foreach ($variantImages as $v): ?>
                                <button type="button"
                                        class="btn btn-sm variant-swatch-btn d-flex align-items-center gap-2 py-1 px-3 rounded-pill <?= $v['is_primary'] ? 'active-swatch border-primary bg-white shadow-sm' : 'border-secondary-subtle bg-white' ?>"
                                        style="cursor: pointer; transition: all 0.2s ease; border-width: 2px;"
                                        onclick="switchProductColor('<?= htmlspecialchars($v['image_url']) ?>', '<?= htmlspecialchars(addslashes($v['color_name'] ?? '')) ?>', this)">
                                    <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background-color: <?= htmlspecialchars($v['color_hex'] ?? '#333') ?>; border: 1px solid rgba(0,0,0,0.25);" class="shadow-sm"></span>
                                    <span class="fw-semibold small text-dark"><?= htmlspecialchars($v['color_name'] ?? 'Standard') ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rating -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="fk-rating-badge fs-6 px-2 py-1"><?= number_format($product['rating'], 1) ?> ★</span>
                    <span class="text-muted small fw-semibold"><?= (int)$product['reviews_count'] ?> Verified Ratings &amp; Reviews</span>
                </div>

                <!-- Price Section -->
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="display-6 fw-bold text-dark"><?= format_inr($product['final_price'], false) ?></span>
                        <?php if ($product['discount'] > 0): ?>
                            <span class="fs-5 text-muted text-decoration-line-through"><?= format_inr($product['price'], false) ?></span>
                            <span class="fs-5 fw-bold text-success"><?= $product['discount'] ?>% off</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="fa-solid fa-receipt text-primary me-1"></i> Inclusive of all taxes (18% GST). Free shipping applicable.
                    </div>
                </div>

                <!-- Stock State -->
                <div class="mb-3">
                    <?php if ($product['stock_quantity'] <= 0): ?>
                        <div class="alert alert-danger py-2 px-3 small fw-bold d-inline-flex align-items-center">
                            <i class="fa-solid fa-circle-xmark me-2"></i> Currently Out of Stock
                        </div>
                    <?php elseif ($product['stock_quantity'] <= $product['minimum_stock']): ?>
                        <div class="alert alert-warning py-2 px-3 small fw-bold d-inline-flex align-items-center">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Hurry, only <?= $product['stock_quantity'] ?> units left in stock!
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success py-2 px-3 small fw-bold d-inline-flex align-items-center">
                            <i class="fa-solid fa-circle-check me-2"></i> In Stock (<?= $product['stock_quantity'] ?> units available)
                        </div>
                    <?php endif; ?>
                    <div class="small text-muted mt-1">Supplied by: <strong><?= htmlspecialchars($product['supplier_name']) ?></strong></div>
                </div>

                <!-- Available Offers -->
                <div class="card border rounded-3 p-3 mb-4 bg-white">
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-tags text-success me-2"></i> Available Offers</h6>
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Bank Offer:</strong> Instant ₹500 discount with promo code <code>FESTIVE500</code> on checkout.</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Payment Offer:</strong> Instant checkout with UPI, Cards &amp; Net Banking.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Easy EMI:</strong> No Cost EMI starting at <strong>₹<?= number_format($emiMonth) ?>/month</strong>.</li>
                    </ul>
                </div>

                <!-- Key Specs Grid -->
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-microchip text-primary me-2"></i> Key Highlights</h6>
                <div class="row g-2 mb-4">
                    <div class="col-6 col-md-4">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">RAM &amp; Storage</small>
                            <strong><?= htmlspecialchars($product['ram']) ?> | <?= htmlspecialchars($product['storage']) ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Display</small>
                            <strong><?= htmlspecialchars($product['display']) ?></strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Battery</small>
                            <strong><?= htmlspecialchars($product['battery']) ?></strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Specifications Table Tab Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="fa-solid fa-list-check text-primary me-2"></i> Complete Technical Specifications
        </h5>

        <div class="table-responsive">
            <table class="table table-bordered specs-table align-middle">
                <tbody>
                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-circle-info me-2"></i> General</th>
                    </tr>
                    <tr>
                        <th>Brand</th>
                        <td><?= htmlspecialchars($product['brand_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Model Name &amp; Number</th>
                        <td><?= htmlspecialchars($product['product_name']) ?> (<?= htmlspecialchars($product['model']) ?>)</td>
                    </tr>
                    <tr>
                        <th>Color</th>
                        <td><?= htmlspecialchars($product['color']) ?></td>
                    </tr>
                    <tr>
                        <th>Operating System</th>
                        <td><?= htmlspecialchars($product['operating_system']) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-desktop me-2"></i> Display Features</th>
                    </tr>
                    <tr>
                        <th>Display Type &amp; Refresh Rate</th>
                        <td><?= htmlspecialchars($product['display']) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-gauge-high me-2"></i> Processor &amp; Memory</th>
                    </tr>
                    <tr>
                        <th>Processor Chipset</th>
                        <td><?= htmlspecialchars($product['processor']) ?></td>
                    </tr>
                    <tr>
                        <th>System RAM</th>
                        <td><?= htmlspecialchars($product['ram']) ?> LPDDR5X</td>
                    </tr>
                    <tr>
                        <th>Internal Storage</th>
                        <td><?= htmlspecialchars($product['storage']) ?> UFS 4.0</td>
                    </tr>

                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-camera me-2"></i> Camera Setup</th>
                    </tr>
                    <tr>
                        <th>Rear Camera System</th>
                        <td><?= htmlspecialchars($product['camera']) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-battery-full me-2"></i> Battery &amp; Power</th>
                    </tr>
                    <tr>
                        <th>Battery Capacity &amp; Fast Charging</th>
                        <td><?= htmlspecialchars($product['battery']) ?></td>
                    </tr>

                    <tr>
                        <th colspan="2" class="bg-primary text-white py-2"><i class="fa-solid fa-shield me-2"></i> Warranty &amp; In-the-Box</th>
                    </tr>
                    <tr>
                        <th>Warranty Period</th>
                        <td><?= htmlspecialchars($product['warranty']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ratings & Reviews Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4" id="reviewsSection">
        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-star text-warning me-2"></i> Customer Reviews &amp; Ratings</h5>
                <small class="text-muted">Authentic feedback from verified smartphone buyers</small>
            </div>
            
            <?php if ($canReview && !$hasReviewed): ?>
                <button type="button" class="btn btn-fk-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Write a Review
                </button>
            <?php elseif ($hasReviewed): ?>
                <span class="badge bg-success py-2 px-3"><i class="fa-solid fa-circle-check me-1"></i> You have reviewed this product</span>
            <?php endif; ?>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fa-regular fa-comment-dots fa-3x mb-2 opacity-50"></i>
                <p class="mb-0">No reviews yet. Be the first verified customer to share your thoughts!</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($reviews as $rev): ?>
                    <div class="col-12">
                        <div class="p-3 border rounded-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fk-rating-badge"><?= (int)$rev['rating'] ?> ★</span>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($rev['title']) ?></span>
                                </div>
                                <small class="text-muted"><?= date('d M, Y', strtotime($rev['created_at'])) ?></small>
                            </div>
                            <p class="mb-2 text-secondary small"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                            <div class="d-flex align-items-center gap-2 small text-muted">
                                <i class="fa-solid fa-user-circle"></i>
                                <span><?= htmlspecialchars($rev['reviewer_name']) ?></span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check-circle me-1"></i> Verified Buyer</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review Submission Modal -->
<?php if ($canReview && !$hasReviewed): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-nib me-2"></i> Write Verified Customer Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="submit_review" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Rating (1 to 5 Stars)</label>
                        <select name="rating" class="form-select" required>
                            <option value="5" selected>5 ★ - Excellent / Outstanding</option>
                            <option value="4">4 ★ - Very Good</option>
                            <option value="3">3 ★ - Average</option>
                            <option value="2">2 ★ - Below Average</option>
                            <option value="1">1 ★ - Poor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Review Headline</label>
                        <input type="text" name="title" class="form-control" placeholder="Summarize your experience or opinion" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Detailed Feedback</label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="Write your detailed review and thoughts about this smartphone..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function changeDetailQty(delta) {
    const input = document.getElementById('detailQuantity');
    let val = parseInt(input.value) + delta;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 99;
    if (val < min) val = min;
    if (val > max) val = max;
    input.value = val;
}

function addCurrentToCart(buyNow) {
    const qty = parseInt(document.getElementById('detailQuantity').value) || 1;
    addToCart(<?= $productId ?>, qty, buyNow);
}

function switchProductColor(imageUrl, colorName, clickedEl) {
    const mainImg = document.getElementById('mainProductImage');
    const colorLabel = document.getElementById('selectedColorLabel');
    const colorBadge = document.getElementById('colorBadgeName');

    if (mainImg) {
        mainImg.style.opacity = '0.35';
        mainImg.style.transform = 'scale(0.97)';
        setTimeout(() => {
            const cleanUrl = imageUrl.replace(/^\/+/, '');
            mainImg.src = '<?= BASE_URL ?>/' + cleanUrl;
            mainImg.style.opacity = '1';
            mainImg.style.transform = 'scale(1)';
        }, 120);
    }

    if (colorLabel) {
        colorLabel.textContent = colorName;
    }
    if (colorBadge) {
        colorBadge.textContent = colorName;
    }

    // Update active swatch state
    document.querySelectorAll('.variant-swatch-btn').forEach(btn => {
        btn.classList.remove('active-swatch', 'border-primary', 'shadow-sm');
        btn.classList.add('border-secondary-subtle');
    });
    if (clickedEl && clickedEl.classList.contains('variant-swatch-btn')) {
        clickedEl.classList.add('active-swatch', 'border-primary', 'shadow-sm');
        clickedEl.classList.remove('border-secondary-subtle');
    } else {
        // Highlight matching swatch by text
        document.querySelectorAll('.variant-swatch-btn').forEach(btn => {
            if (btn.innerText.trim().toLowerCase() === colorName.trim().toLowerCase()) {
                btn.classList.add('active-swatch', 'border-primary', 'shadow-sm');
                btn.classList.remove('border-secondary-subtle');
            }
        });
    }

    // Update active thumbnail state
    document.querySelectorAll('.variant-thumb-btn').forEach(tb => {
        tb.classList.remove('active-thumb', 'border-primary', 'shadow-sm');
        tb.classList.add('border-light-subtle');
        if (tb.getAttribute('title') && tb.getAttribute('title').trim().toLowerCase() === colorName.trim().toLowerCase()) {
            tb.classList.add('active-thumb', 'border-primary', 'shadow-sm');
            tb.classList.remove('border-light-subtle');
        }
    });
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
