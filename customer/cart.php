<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Shopping Cart Page (DFD P1.3 Order Processing)
 */

$pageTitle = "Shopping Cart - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';

$db = get_db_connection();
$userId = $_SESSION['user_id'] ?? null;
$cartItems = [];
$totalMrp = 0;
$totalSelling = 0;
$hasStockIssue = false;

if ($userId) {
    // User database cart (D3 Orders DB)
    $stmt = $db->prepare("
        SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.model, p.price, p.discount, p.final_price, p.stock_quantity, p.image, p.ram, p.storage, b.name AS brand_name
        FROM cart c
        JOIN cart_items ci ON c.cart_id = ci.cart_id
        JOIN products p ON ci.product_id = p.product_id
        JOIN brands b ON p.brand_id = b.brand_id
        WHERE c.user_id = ?
        ORDER BY ci.cart_item_id DESC
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
} else {
    // Guest session cart
    if (!empty($_SESSION['guest_cart'])) {
        $pIds = array_keys($_SESSION['guest_cart']);
        if (!empty($pIds)) {
            $placeholders = implode(',', array_fill(0, count($pIds), '?'));
            $stmt = $db->prepare("
                SELECT p.product_id, p.product_name, p.model, p.price, p.discount, p.final_price, p.stock_quantity, p.image, p.ram, p.storage, b.name AS brand_name
                FROM products p
                JOIN brands b ON p.brand_id = b.brand_id
                WHERE p.product_id IN ({$placeholders})
            ");
            $stmt->execute($pIds);
            $prods = $stmt->fetchAll();
            foreach ($prods as $p) {
                $p['cart_item_id'] = $p['product_id'];
                $p['quantity'] = $_SESSION['guest_cart'][$p['product_id']] ?? 1;
                $cartItems[] = $p;
            }
        }
    }
}

// Calculate financial figures
foreach ($cartItems as $item) {
    $totalMrp += ($item['price'] * $item['quantity']);
    $totalSelling += ($item['final_price'] * $item['quantity']);
    if ($item['quantity'] > $item['stock_quantity'] || $item['stock_quantity'] <= 0) {
        $hasStockIssue = true;
    }
}

$totalDiscount = $totalMrp - $totalSelling;
$shippingCharge = ($totalSelling >= FREE_SHIPPING_THRESHOLD || empty($cartItems)) ? 0.00 : DEFAULT_SHIPPING_CHARGE;
$finalPayable = $totalSelling + $shippingCharge;
?>

<div class="container py-4">
    <!-- Header banner -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-shopping text-primary me-2"></i> Shopping Cart</h4>
            <small class="text-muted">Review your items before proceeding to checkout</small>
        </div>
        <a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>

    <?php if (empty($cartItems)): ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white">
            <div class="mb-3">
                <i class="fa-solid fa-cart-arrow-down fa-4x text-muted opacity-50"></i>
            </div>
            <h4 class="fw-bold text-dark">Your Shopping Cart is Empty!</h4>
            <p class="text-muted small mb-4">Explore India's leading 5G smartphones and flagship devices with exclusive offers.</p>
            <div>
                <a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-fk-primary px-4 py-2">
                    <i class="fa-solid fa-store me-1"></i> Browse Mobiles
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            
            <!-- Left: Cart Items List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Items in Cart (<?= count($cartItems) ?>)</span>
                        <?php if ($hasStockIssue): ?>
                            <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Stock availability issue detected</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body p-0">
                        <?php foreach ($cartItems as $index => $item): 
                            $isOutOfStock = $item['stock_quantity'] <= 0;
                            $exceedsStock = $item['quantity'] > $item['stock_quantity'];
                        ?>
                            <div class="p-4 border-bottom <?= ($index === count($cartItems) - 1) ? 'border-bottom-0' : '' ?>">
                                <div class="row g-3 align-items-center">
                                    <!-- Image -->
                                    <div class="col-3 col-md-2 text-center">
                                        <div style="height: 90px; display: flex; align-items: center; justify-content: center;">
                                            <img src="<?= product_image_url($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;" class="img-fluid">
                                        </div>
                                    </div>

                                    <!-- Details -->
                                    <div class="col-9 col-md-6">
                                        <span class="badge bg-light text-secondary border mb-1"><?= htmlspecialchars($item['brand_name']) ?></span>
                                        <h6 class="fw-bold mb-1">
                                            <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $item['product_id'] ?>" class="text-dark text-decoration-none">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </a>
                                        </h6>
                                        <div class="text-muted small mb-2"><?= htmlspecialchars($item['ram']) ?> RAM | <?= htmlspecialchars($item['storage']) ?></div>

                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="fw-bold text-dark fs-5"><?= format_inr($item['final_price'], false) ?></span>
                                            <?php if ($item['discount'] > 0): ?>
                                                <span class="text-muted small text-decoration-line-through"><?= format_inr($item['price'], false) ?></span>
                                                <span class="text-success small fw-bold"><?= $item['discount'] ?>% off</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Stock Warnings -->
                                        <?php if ($isOutOfStock): ?>
                                            <div class="text-danger small fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Out of stock. Please remove to checkout.</div>
                                        <?php elseif ($exceedsStock): ?>
                                            <div class="text-danger small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Only <?= $item['stock_quantity'] ?> left in warehouse! Reduce quantity.</div>
                                        <?php else: ?>
                                            <div class="text-success small"><i class="fa-solid fa-check me-1"></i> In Stock (<?= $item['stock_quantity'] ?> available)</div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Quantity & Remove Controls -->
                                    <div class="col-12 col-md-4 text-md-end">
                                        <div class="d-flex align-items-center justify-content-md-end gap-2 mb-2">
                                            <label class="small text-secondary fw-semibold">Qty:</label>
                                            <div class="input-group" style="width: 110px;">
                                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="updateCartQuantity(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                                <input type="text" class="form-control form-control-sm text-center fw-bold bg-white" value="<?= $item['quantity'] ?>" readonly>
                                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="updateCartQuantity(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] + 1 ?>)" <?= ($item['quantity'] >= $item['stock_quantity']) ? 'disabled' : '' ?>>+</button>
                                            </div>
                                        </div>

                                        <button type="button" class="btn btn-link text-danger text-decoration-none small p-0" onclick="removeFromCart(<?= $item['cart_item_id'] ?>)">
                                            <i class="fa-solid fa-trash-can me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Free Delivery Alert Banner -->
                <?php if ($totalSelling < FREE_SHIPPING_THRESHOLD): ?>
                    <div class="alert alert-info d-flex align-items-center rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-truck-fast fa-2x me-3 text-primary"></i>
                        <div>
                            Add <strong><?= format_inr(FREE_SHIPPING_THRESHOLD - $totalSelling) ?></strong> more worth of items to qualify for <strong>FREE Delivery</strong>!
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Order Financial Summary (Flipkart Style) -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
                    <h6 class="fw-bold text-secondary text-uppercase border-bottom pb-2 mb-3">Price Details</h6>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Price (<?= count($cartItems) ?> items)</span>
                        <span class="text-dark fw-semibold"><?= format_inr($totalMrp) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Discount on MRP</span>
                        <span class="text-success fw-semibold">- <?= format_inr($totalDiscount) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Subtotal</span>
                        <span class="text-dark fw-semibold"><?= format_inr($totalSelling) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-secondary">Delivery Charges</span>
                        <?php if ($shippingCharge == 0): ?>
                            <span class="text-success fw-bold"><span class="text-decoration-line-through text-muted me-1"><?= format_inr(DEFAULT_SHIPPING_CHARGE) ?></span> FREE</span>
                        <?php else: ?>
                            <span class="text-dark fw-semibold"><?= format_inr($shippingCharge) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="border-top border-bottom py-3 my-2 d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-dark">Total Amount</span>
                        <span class="fw-bold fs-4 text-primary"><?= format_inr($finalPayable) ?></span>
                    </div>

                    <?php if ($totalDiscount > 0): ?>
                        <div class="text-success small fw-bold my-2">
                            <i class="fa-solid fa-circle-check me-1"></i> You will save <?= format_inr($totalDiscount) ?> on this order!
                        </div>
                    <?php endif; ?>

                    <div class="d-grid mt-4">
                        <?php if ($hasStockIssue): ?>
                            <button type="button" class="btn btn-secondary btn-lg fw-bold" disabled>
                                Resolve Stock Issues to Proceed
                            </button>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/customer/checkout.php" class="btn btn-fk-orange btn-lg shadow-sm">
                                <i class="fa-solid fa-lock me-2"></i> Proceed to Checkout
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="text-center mt-3 text-muted small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Safe &amp; Secure Checkout
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
