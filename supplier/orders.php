<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Purchase Orders (DFD P1.3)
 */

$pageTitle = "Purchase Orders";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

$stmt = $db->prepare("
    SELECT oi.*, o.order_number, o.order_date, o.order_status, o.payment_status, o.shipping_name, o.shipping_city, o.shipping_state,
           p.product_name, p.model, p.image
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.supplier_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$supplierId]);
$orders = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-flatbed text-primary me-2"></i> Purchase Orders</h4>
        <small class="text-muted">Customer demand and wholesale dispatch obligations</small>
    </div>
    <a href="shipments.php" class="btn btn-warning btn-sm text-dark fw-bold">
        <i class="fa-solid fa-truck-fast me-1"></i> Manage Dispatches
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-cart-shopping fa-3x mb-3 opacity-50"></i>
                <h5>No purchase orders received yet</h5>
                <p class="small">Orders containing your smartphones will appear here as soon as customers checkout.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Order # / Date</th>
                            <th>Item Details</th>
                            <th>Destination</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Item Total</th>
                            <th>Payment</th>
                            <th>Fulfillment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $ord): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><code><?= htmlspecialchars($ord['order_number']) ?></code></div>
                                    <small class="text-muted"><?= date('d M Y, h:i A', strtotime($ord['order_date'])) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                            <img src="<?= product_image_url($ord['image']) ?>" alt="<?= htmlspecialchars($ord['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($ord['product_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($ord['model']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($ord['shipping_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ord['shipping_city']) ?>, <?= htmlspecialchars($ord['shipping_state']) ?></small>
                                </td>
                                <td class="text-center fw-bold fs-6"><?= $ord['quantity'] ?></td>
                                <td class="text-end fw-bold text-primary"><?= format_inr($ord['subtotal']) ?></td>
                                <td><?= get_payment_status_badge($ord['payment_status']) ?></td>
                                <td><?= get_order_status_badge($ord['order_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
