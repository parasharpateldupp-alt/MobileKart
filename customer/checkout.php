<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Checkout & Order Processing Controller
 * DFD Implementation:
 *   - P1.3.1: Verify Order
 *   - P1.3.2: Calculate Sub-total
 *   - P1.3.3: Reserve Stock
 *   - P1.4.1: Verify Customer Auth (Token Generation)
 */

$pageTitle = "Checkout & Order Verification - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/dfd_helper.php';

// P1.3.1: Customer Authentication Check
require_login(BASE_URL . '/customer/checkout.php');

$db = get_db_connection();
$userId = $_SESSION['user_id'];

// Fetch customer profile & default address
$uStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$uStmt->execute([$userId]);
$customer = $uStmt->fetch();

// Fetch Cart Items
$cStmt = $db->prepare("
    SELECT ci.cart_item_id, ci.quantity, p.product_id, p.supplier_id, p.product_name, p.model, p.price, p.discount, p.final_price, p.stock_quantity, p.status, p.image, p.ram, p.storage, p.color, b.name AS brand_name
    FROM cart c
    JOIN cart_items ci ON c.cart_id = ci.cart_id
    JOIN products p ON ci.product_id = p.product_id
    JOIN brands b ON p.brand_id = b.brand_id
    WHERE c.user_id = ?
");
$cStmt->execute([$userId]);
$cartItems = $cStmt->fetchAll();

if (empty($cartItems)) {
    set_flash('warning', 'Your shopping cart is empty. Please add smartphones to proceed.');
    header("Location: " . BASE_URL . "/customer/cart.php");
    exit;
}

// Promo Code Handling
$couponCode = trim($_POST['coupon_code'] ?? ($_GET['coupon'] ?? ''));
$promoDiscount = 0.00;
$promoMessage = '';
$promoSuccess = false;

// Subtotal Calculations (P1.3.2)
$rawSubtotal = 0.00;
foreach ($cartItems as $item) {
    $rawSubtotal += ($item['final_price'] * $item['quantity']);
}

if (!empty($couponCode)) {
    $pStmt = $db->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_to >= CURDATE()");
    $pStmt->execute([strtoupper($couponCode)]);
    $promo = $pStmt->fetch();

    if ($promo) {
        if ($rawSubtotal >= $promo['minimum_order']) {
            if ($promo['discount_percent'] > 0) {
                $calc = ($rawSubtotal * $promo['discount_percent']) / 100;
                if ($promo['max_discount'] > 0 && $calc > $promo['max_discount']) {
                    $calc = $promo['max_discount'];
                }
                $promoDiscount = $calc;
            } elseif ($promo['discount_amount'] > 0) {
                $promoDiscount = min($rawSubtotal, $promo['discount_amount']);
            }
            $promoMessage = "Coupon '{$promo['code']}' applied successfully!";
            $promoSuccess = true;
        } else {
            $promoMessage = "Minimum order value of " . format_inr($promo['minimum_order']) . " required for this coupon.";
        }
    } else {
        $promoMessage = "Invalid or expired promotional coupon code.";
    }
}

// P1.3.2 Calculate Sub-total, 18% GST tax, and Shipping charges
$discountedSubtotal = max(0, $rawSubtotal - $promoDiscount);
// GST 18% calculation (included in total or itemized)
$taxAmount = round(($discountedSubtotal * DEFAULT_GST_PERCENT) / (100 + DEFAULT_GST_PERCENT), 2);
$baseAmount = $discountedSubtotal - $taxAmount;
$shippingCharge = ($discountedSubtotal >= FREE_SHIPPING_THRESHOLD) ? 0.00 : DEFAULT_SHIPPING_CHARGE;
$grandTotal = $discountedSubtotal + $shippingCharge;

$error = '';

// Handle Checkout Submission (P1.3 Order Creation & P1.3.3 Stock Reservation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    require_csrf();

    $shipping_name    = trim($_POST['shipping_name'] ?? '');
    $shipping_phone   = trim($_POST['shipping_phone'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $shipping_city    = trim($_POST['shipping_city'] ?? '');
    $shipping_state   = trim($_POST['shipping_state'] ?? '');
    $shipping_pincode = trim($_POST['shipping_pincode'] ?? '');
    $notes            = trim($_POST['notes'] ?? '');

    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_pincode)) {
        $error = "Please fill in all shipping and contact details.";
    } elseif (!preg_match('/^[6-9]\d{9}$/', preg_replace('/[\s\-\+91]/', '', $shipping_phone))) {
        $error = "Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).";
    } elseif (!preg_match('/^[1-9]\d{5}$/', trim($shipping_pincode))) {
        $error = "Please enter a valid 6-digit Indian postal PIN code (e.g. 560038).";
    } else {
        try {
            $db->beginTransaction();

            // -------------------------------------------------------------
            // P1.3.1 VERIFY ORDER: Real-time stock verification
            // -------------------------------------------------------------
            foreach ($cartItems as $item) {
                // Lock row for update
                $vStmt = $db->prepare("SELECT stock_quantity, status, product_name FROM products WHERE product_id = ? FOR UPDATE");
                $vStmt->execute([$item['product_id']]);
                $currentStock = $vStmt->fetch();

                if (!$currentStock || $currentStock['status'] !== 'ACTIVE') {
                    throw new Exception("Product '{$item['product_name']}' is no longer active in our catalog.");
                }

                // Check active reservations
                $resStmt = $db->prepare("
                    SELECT COALESCE(SUM(quantity), 0) 
                    FROM stock_reservations 
                    WHERE product_id = ? AND status = 'ACTIVE' AND expiry_time > NOW()
                ");
                $resStmt->execute([$item['product_id']]);
                $alreadyReserved = (int)$resStmt->fetchColumn();

                $trulyAvailable = $currentStock['stock_quantity'] - $alreadyReserved;
                if ($item['quantity'] > $trulyAvailable) {
                    throw new Exception("Insufficient stock for '{$currentStock['product_name']}'. Only {$trulyAvailable} units currently available.");
                }
            }

            // -------------------------------------------------------------
            // P1.3.2 & P1.3.3: CREATE ORDER & RESERVE STOCK
            // -------------------------------------------------------------
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $insOrder = $db->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, subtotal, discount, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode, notes, created_at)
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'PENDING', 'PENDING_PAYMENT', ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insOrder->execute([
                $orderNumber,
                $userId,
                $rawSubtotal,
                $promoDiscount,
                $taxAmount,
                $shippingCharge,
                $grandTotal,
                $shipping_name,
                $shipping_phone,
                $shipping_address,
                $shipping_city,
                $shipping_state,
                $shipping_pincode,
                $notes
            ]);
            $orderId = $db->lastInsertId();

            // Insert Order Items and Stock Reservations (P1.3.3)
            $insItem = $db->prepare("
                INSERT INTO order_items (order_id, product_id, supplier_id, quantity, price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $nowStr = date('Y-m-d H:i:s');
            $expiryStr = date('Y-m-d H:i:s', strtotime("+" . STOCK_RESERVATION_MINUTES . " minutes"));
            $insRes = $db->prepare("
                INSERT INTO stock_reservations (order_id, product_id, quantity, reserved_at, expiry_time, status)
                VALUES (?, ?, ?, ?, ?, 'ACTIVE')
            ");

            foreach ($cartItems as $item) {
                $itemSubtotal = $item['final_price'] * $item['quantity'];
                $insItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['supplier_id'],
                    $item['quantity'],
                    $item['final_price'],
                    $itemSubtotal
                ]);

                // P1.3.3 Reserve stock with 15-minute hold
                $insRes->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $nowStr,
                    $expiryStr
                ]);
            }

            // Clear Customer Cart since items are now transferred to Pending Order
            $delCart = $db->prepare("
                DELETE FROM cart_items 
                WHERE cart_id IN (SELECT cart_id FROM cart WHERE user_id = ?)
            ");
            $delCart->execute([$userId]);

            // Initial Payment record
            $txnId = 'TXN-MOCK-' . date('Ymd') . '-' . rand(100000, 999999);
            $insPay = $db->prepare("
                INSERT INTO payments (order_id, customer_id, transaction_id, amount, payment_method, payment_status, created_at)
                VALUES (?, ?, ?, ?, 'UPI', 'PENDING', NOW())
            ");
            $insPay->execute([$orderId, $userId, $txnId, $grandTotal]);
            $paymentId = $db->lastInsertId();

            $db->commit();

            // DFD Audit
            DFDManager::logFlow('P1.3', 'ORDER_CREATED_AND_STOCK_RESERVED', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'reserved_items' => count($cartItems),
                'grand_total' => $grandTotal
            ]);

            // -------------------------------------------------------------
            // P1.4.1 GENERATE ENCRYPTED PAYMENT TOKEN
            // -------------------------------------------------------------
            $tokenPayload = [
                'order_id'    => $orderId,
                'payment_id'  => $paymentId,
                'customer_id' => $userId,
                'amount'      => $grandTotal,
                'timestamp'   => time()
            ];
            $tokenJson = json_encode($tokenPayload);
            $secretKey = 'MOCK_SECRET_KEY_ACADEMIC_2026';
            $signature = hash_hmac('sha256', $tokenJson, $secretKey);
            $paymentToken = base64_encode($tokenJson . '||' . $signature);

            // Update payment gateway_token in D4
            $upPay = $db->prepare("UPDATE payments SET gateway_token = ? WHERE payment_id = ?");
            $upPay->execute([$paymentToken, $paymentId]);

            // Redirect to Safe Mock Bank Gateway (DFD P1.4.2)
            header("Location: " . BASE_URL . "/payment/mock_gateway.php?token=" . urlencode($paymentToken));
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/cart.php">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clipboard-check text-primary me-2"></i> Order Verification &amp; Checkout</h4>
            <small class="text-muted">Secure checkout &amp; order confirmation</small>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>
            <div>
                <strong>Checkout Error:</strong><br>
                <?= htmlspecialchars($error) ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">
        <?= csrf_field() ?>
        
        <div class="row g-4">
            
            <!-- Left Column: Shipping Address & Coupon -->
            <div class="col-lg-7">
                
                <!-- Shipping Address Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> 1. Delivery &amp; Shipping Address</h6>
                        <span class="badge bg-light text-muted border">Step 1 of 2</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Recipient Full Name *</label>
                            <input type="text" name="shipping_name" class="form-control" value="<?= htmlspecialchars($_POST['shipping_name'] ?? $customer['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Contact Mobile Number *</label>
                            <input type="tel" name="shipping_phone" class="form-control" value="<?= htmlspecialchars($_POST['shipping_phone'] ?? $customer['phone']) ?>" pattern="[0-9]{10}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Street Address / Apartment / Landmark *</label>
                        <textarea name="shipping_address" class="form-control" rows="2" required><?= htmlspecialchars($_POST['shipping_address'] ?? $customer['address']) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">City / Town *</label>
                            <input type="text" name="shipping_city" class="form-control" value="<?= htmlspecialchars($_POST['shipping_city'] ?? $customer['city']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">State *</label>
                            <input type="text" name="shipping_state" class="form-control" value="<?= htmlspecialchars($_POST['shipping_state'] ?? $customer['state']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Postal Pincode *</label>
                            <input type="text" name="shipping_pincode" class="form-control" value="<?= htmlspecialchars($_POST['shipping_pincode'] ?? $customer['pincode']) ?>" pattern="[0-9]{6}" required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Special Delivery Instructions (Optional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional delivery instructions or landmark">
                    </div>
                </div>

                <!-- Order Items Review Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> 2. Review Items in Order</h6>

                    <div class="list-group list-group-flush">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="list-group-item px-0 py-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <img src="<?= product_image_url($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['ram']) ?> | <?= htmlspecialchars($item['storage']) ?> • Qty: <strong><?= $item['quantity'] ?></strong></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-dark"><?= format_inr($item['final_price'] * $item['quantity']) ?></div>
                                    <small class="text-muted">(<?= format_inr($item['final_price']) ?> each)</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Coupons & Financial Breakdown -->
            <div class="col-lg-5">
                
                <!-- Coupon Code Box -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                    <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-ticket text-warning me-2"></i> Apply Coupon Code</h6>
                    <div class="input-group mb-2">
                        <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="Enter coupon code" value="<?= htmlspecialchars($couponCode) ?>">
                        <button type="submit" class="btn btn-dark" name="apply_coupon" value="1">Apply</button>
                    </div>

                    <?php if ($promoMessage): ?>
                        <div class="small fw-bold <?= $promoSuccess ? 'text-success' : 'text-danger' ?>">
                            <i class="fa-solid <?= $promoSuccess ? 'fa-check-circle' : 'fa-circle-xmark' ?> me-1"></i> <?= htmlspecialchars($promoMessage) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-2 text-muted small">
                        <strong>Available Coupons:</strong><br>
                        <code>FESTIVE500</code> - ₹500 off (orders > ₹15k)<br>
                        <code>WELCOME10</code> - 10% off (up to ₹2500)
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0">Order Summary</h6>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Cart Subtotal</span>
                        <span class="text-dark fw-semibold"><?= format_inr($rawSubtotal) ?></span>
                    </div>

                    <?php if ($promoDiscount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>Promotional Coupon Discount</span>
                            <span class="fw-semibold">- <?= format_inr($promoDiscount) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Estimated GST (18% included)</span>
                        <span class="text-muted"><?= format_inr($taxAmount) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-secondary">Shipping &amp; Handling</span>
                        <?php if ($shippingCharge == 0): ?>
                            <span class="text-success fw-bold">FREE</span>
                        <?php else: ?>
                            <span class="text-dark fw-semibold"><?= format_inr($shippingCharge) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="border-top border-bottom py-3 my-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold fs-5 text-dark">Payable Grand Total</span>
                            <div class="small text-muted">Includes all taxes</div>
                        </div>
                        <span class="fw-bold fs-3 text-primary"><?= format_inr($grandTotal) ?></span>
                    </div>

                    <!-- Stock Reservation Notice -->
                    <div class="alert alert-info py-2 px-3 small rounded-3 my-3">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>
                        <strong>Reserved Items:</strong> Upon proceeding, these phones will be reserved for <strong>15 minutes</strong> in the warehouse to ensure stock availability while you complete payment.
                    </div>

                    <div class="d-grid mt-2">
                        <button type="submit" name="place_order" value="1" class="btn btn-fk-orange btn-lg shadow-sm fw-bold">
                            <i class="fa-solid fa-lock me-2"></i> Proceed to Payment
                        </button>
                    </div>

                    <div class="text-center mt-3 text-muted small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> 256-bit Encrypted &amp; Secure Payment Gateway
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
