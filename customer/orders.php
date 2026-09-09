<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Order History (DFD P1.3)
 */

$pageTitle = "My Orders - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$db = get_db_connection();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS items_count,
           (SELECT i.invoice_id FROM invoices i WHERE i.order_id = o.order_id LIMIT 1) AS invoice_id
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-box-archive text-primary me-2"></i> My Smartphone Orders</h4>
            <small class="text-muted">Track your mobile consignments, download tax invoices, and view order receipts</small>
        </div>
        <a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-fk-primary btn-sm">
            <i class="fa-solid fa-cart-plus me-1"></i> Browse More Mobiles
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white">
            <i class="fa-solid fa-boxes-stacked fa-4x text-muted mb-3 opacity-50"></i>
            <h5 class="fw-bold text-dark">You haven't placed any orders yet</h5>
            <p class="text-muted small">Explore our smartphone selection and place your first order today!</p>
            <div>
                <a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-primary px-4 py-2">Start Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="card-header bg-light py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold">Order Placed</div>
                                    <div class="fw-semibold text-dark"><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
                                </div>
                                <div class="border-start ps-3">
                                    <div class="small text-muted text-uppercase fw-bold">Total Amount</div>
                                    <div class="fw-bold text-primary"><?= format_inr($order['total_amount']) ?></div>
                                </div>
                                <div class="border-start ps-3 d-none d-md-block">
                                    <div class="small text-muted text-uppercase fw-bold">Ship To</div>
                                    <div class="small text-dark fw-semibold"><?= htmlspecialchars($order['shipping_name']) ?></div>
                                </div>
                            </div>
                            
                            <div class="text-md-end">
                                <div class="small text-muted">Order ID: <code><?= htmlspecialchars($order['order_number']) ?></code></div>
                                <div class="mt-1">
                                    <?= get_order_status_badge($order['order_status']) ?>
                                    <?= get_payment_status_badge($order['payment_status']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <?php
                            // Fetch items for this order
                            $itStmt = $db->prepare("
                                SELECT oi.*, p.product_name, p.image, p.ram, p.storage 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.product_id 
                                WHERE oi.order_id = ?
                            ");
                            $itStmt->execute([$order['order_id']]);
                            $items = $itStmt->fetchAll();
                            ?>

                            <div class="row g-3 align-items-center">
                                <div class="col-lg-8">
                                    <?php foreach ($items as $it): ?>
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                                <img src="<?= product_image_url($it['image']) ?>" alt="<?= htmlspecialchars($it['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                            </div>
                                            <div>
                                                <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $it['product_id'] ?>" class="fw-bold text-dark text-decoration-none small">
                                                    <?= htmlspecialchars($it['product_name']) ?>
                                                </a>
                                                <div class="text-muted small">Qty: <strong><?= $it['quantity'] ?></strong> • <?= format_inr($it['price']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="col-lg-4 text-lg-end d-flex flex-column gap-2">
                                    <a href="<?= BASE_URL ?>/customer/order-details.php?id=<?= $order['order_id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-file-lines me-1"></i> Order Details
                                    </a>

                                    <?php if ($order['order_status'] !== 'CANCELLED' && $order['payment_status'] === 'PAID'): ?>
                                        <a href="<?= BASE_URL ?>/customer/tracking.php?order_id=<?= $order['order_id'] ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                                            <i class="fa-solid fa-truck-fast me-1"></i> Track Shipment (P1.5)
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($order['invoice_id'])): ?>
                                        <a href="<?= BASE_URL ?>/customer/invoices.php?id=<?= $order['invoice_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-receipt me-1"></i> Download Invoice (P1.4.3)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
