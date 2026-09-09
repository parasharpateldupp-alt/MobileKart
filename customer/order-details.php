<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Order Details & Management (DFD P1.3)
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_login();

$orderId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$db = get_db_connection();

// Fetch order
$stmt = $db->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           (SELECT i.invoice_id FROM invoices i WHERE i.order_id = o.order_id LIMIT 1) AS invoice_id,
           (SELECT f.fulfillment_id FROM fulfillments f WHERE f.order_id = o.order_id LIMIT 1) AS fulfillment_id
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ? AND (o.customer_id = ? OR ? = 'ADMIN')
");
$stmt->execute([$orderId, $userId, $_SESSION['user_role']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: " . BASE_URL . "/customer/orders.php");
    exit;
}

$pageTitle = "Order " . htmlspecialchars($order['order_number']) . " - MobileKart";

// Fetch Order Items
$itemStmt = $db->prepare("
    SELECT oi.*, p.product_name, p.model, p.image, p.ram, p.storage, s.company_name AS supplier_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN suppliers s ON oi.supplier_id = s.supplier_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

// Fetch Payment Details
$pStmt = $db->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY payment_id DESC LIMIT 1");
$pStmt->execute([$orderId]);
$payment = $pStmt->fetch();

// Fetch Stock Reservation status (P1.3.3)
$resStmt = $db->prepare("SELECT * FROM stock_reservations WHERE order_id = ? LIMIT 1");
$resStmt->execute([$orderId]);
$reservation = $resStmt->fetch();

// Handle Customer Order Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    require_csrf();
    if (in_array($order['order_status'], ['PENDING_PAYMENT', 'ORDER_CONFIRMED', 'PROCESSING'])) {
        try {
            $db->beginTransaction();

            $upOrd = $db->prepare("UPDATE orders SET order_status = 'CANCELLED' WHERE order_id = ?");
            $upOrd->execute([$orderId]);

            // Release stock reservation
            $relRes = $db->prepare("UPDATE stock_reservations SET status = 'RELEASED' WHERE order_id = ?");
            $relRes->execute([$orderId]);

            // Return stock if already paid
            if ($order['payment_status'] === 'PAID') {
                $retStock = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
                $logInv = $db->prepare("
                    INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
                    VALUES (?, 'DAMAGE_RETURN', ?, (SELECT stock_quantity FROM products WHERE product_id = ?), ?, 'Customer Cancelled Order', ?, NOW())
                ");
                foreach ($orderItems as $it) {
                    $retStock->execute([$it['quantity'], $it['product_id']]);
                    $logInv->execute([$it['product_id'], $it['quantity'], $it['product_id'], $order['order_number'], $userId]);
                }
            }

            $db->commit();
            set_flash('info', 'Order has been successfully cancelled and warehouse inventory updated.');
            header("Location: " . BASE_URL . "/customer/order-details.php?id=" . $orderId);
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash('danger', 'Error cancelling order: ' . $e->getMessage());
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
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/orders.php">My Orders</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_number']) ?></li>
        </ol>
    </nav>

    <!-- Payment Feedback Alerts -->
    <?php if (($_GET['payment'] ?? '') === 'success'): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 p-4 mb-4" role="alert">
            <i class="fa-solid fa-circle-check fa-3x text-success me-4"></i>
            <div>
                <h5 class="fw-bold mb-1">Payment Successful &amp; Order Confirmed!</h5>
                <p class="mb-0 text-secondary">
                    Your payment was authorized successfully. Your order has been confirmed, warehouse packaging has been initiated, and an official GST tax invoice has been generated.
                </p>
            </div>
        </div>
    <?php elseif (($_GET['payment'] ?? '') === 'failed'): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 p-4 mb-4" role="alert">
            <i class="fa-solid fa-circle-xmark fa-3x text-danger me-4"></i>
            <div>
                <h5 class="fw-bold mb-1">Payment Authorization Declined</h5>
                <p class="mb-0 text-secondary">
                    Your payment transaction was not completed. Any temporary stock holds have been returned to available inventory. You can retry placing your order anytime.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Order Header Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-3 mb-3">
            <div>
                <span class="badge bg-light text-secondary border mb-1">Order #</span>
                <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($order['order_number']) ?></h4>
                <small class="text-muted">Placed on <?= date('l, d F Y at h:i A', strtotime($order['order_date'])) ?></small>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?= get_order_status_badge($order['order_status']) ?>
                <?= get_payment_status_badge($order['payment_status']) ?>

                <?php if (!empty($order['invoice_id'])): ?>
                    <a href="<?= BASE_URL ?>/customer/invoices.php?id=<?= $order['invoice_id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-file-invoice me-1"></i> Tax Invoice
                    </a>
                <?php endif; ?>

                <?php if ($order['order_status'] !== 'CANCELLED' && $order['payment_status'] === 'PAID'): ?>
                    <a href="<?= BASE_URL ?>/customer/tracking.php?order_id=<?= $order['order_id'] ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                        <i class="fa-solid fa-truck-fast me-1"></i> Track Shipment
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Ordered Mobiles</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product &amp; Specifications</th>
                        <th>Supplier</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <img src="<?= product_image_url($it['image']) ?>" alt="<?= htmlspecialchars($it['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($it['product_name']) ?></div>
                                        <small class="text-muted">Model: <?= htmlspecialchars($it['model']) ?> • <?= htmlspecialchars($it['ram']) ?> RAM | <?= htmlspecialchars($it['storage']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars($it['supplier_name']) ?></span></td>
                            <td class="text-center fw-bold"><?= $it['quantity'] ?></td>
                            <td class="text-end"><?= format_inr($it['price']) ?></td>
                            <td class="text-end fw-bold text-dark"><?= format_inr($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="row g-4">
            <!-- Shipping Information -->
            <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="fa-solid fa-location-dot text-danger me-2"></i> Delivery Address</h6>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($order['shipping_name']) ?></div>
                    <p class="small text-secondary mb-2">
                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                        <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> - <?= htmlspecialchars($order['shipping_pincode']) ?>
                    </p>
                    <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i> Phone: <strong><?= htmlspecialchars($order['shipping_phone']) ?></strong></div>
                    <?php if (!empty($order['notes'])): ?>
                        <div class="small text-info mt-2"><i class="fa-solid fa-info-circle me-1"></i> Note: <?= htmlspecialchars($order['notes']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2"><i class="fa-solid fa-credit-card text-success me-2"></i> Payment Information</h6>
                    <?php if ($payment): ?>
                        <div class="small mb-1">Method: <strong><?= htmlspecialchars($payment['payment_method']) ?></strong></div>
                        <div class="small mb-1">Status: <?= get_payment_status_badge($payment['payment_status']) ?></div>
                        <div class="small mb-1">Transaction ID: <code><?= htmlspecialchars($payment['transaction_id']) ?></code></div>
                        <?php if ($payment['payment_date']): ?>
                            <div class="small text-muted">Paid at: <?= date('d M Y, h:i A', strtotime($payment['payment_date'])) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="small text-muted">No payment record found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Financial Total Card -->
            <div class="col-md-4">
                <div class="card border rounded-3 p-3 bg-light h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">Cost Breakdown</h6>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-secondary">Subtotal</span>
                        <span class="fw-semibold"><?= format_inr($order['subtotal']) ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                        <div class="d-flex justify-content-between small mb-1 text-success">
                            <span>Coupon Discount</span>
                            <span class="fw-semibold">- <?= format_inr($order['discount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-secondary">GST Tax (18%)</span>
                        <span class="text-muted"><?= format_inr($order['tax']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-secondary">Shipping</span>
                        <span class="fw-semibold"><?= $order['shipping_charge'] > 0 ? format_inr($order['shipping_charge']) : 'FREE' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 fw-bold fs-5 text-primary">
                        <span>Total Paid</span>
                        <span><?= format_inr($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Section -->
        <?php if (in_array($order['order_status'], ['PENDING_PAYMENT', 'ORDER_CONFIRMED', 'PROCESSING'])): ?>
            <div class="border-top pt-3 mt-4 text-end">
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Any reserved or paid items will be returned to inventory.');">
                    <?= csrf_field() ?>
                    <button type="submit" name="cancel_order" value="1" class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-ban me-1"></i> Cancel Order
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
