<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Orders Management & Processing (DFD P1.3)
 */

$pageTitle = "Orders Management";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';

    if ($orderId > 0 && !empty($newStatus)) {
        $db->beginTransaction();
        $up = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
        $up->execute([$newStatus, $orderId]);

        // Sync fulfillment if exists
        $upF = $db->prepare("UPDATE fulfillments SET shipment_status = ?, updated_at = NOW() WHERE order_id = ?");
        $upF->execute([$newStatus, $orderId]);

        // If delivered, set delivered_date
        if ($newStatus === 'DELIVERED') {
            $db->prepare("UPDATE fulfillments SET delivered_date = NOW() WHERE order_id = ?")->execute([$orderId]);
        }

        log_activity($user['id'], 'ADMIN_UPDATED_ORDER_STATUS', 'ORDER', $orderId, ['new_status' => $newStatus]);
        $db->commit();
        set_flash('success', "Order #{$orderId} status changed to {$newStatus}.");
        header("Location: orders.php");
        exit;
    }
}

// Build Query
$where = ["1=1"];
$params = [];

if ($statusFilter) {
    $where[] = "o.order_status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.name LIKE ? OR o.shipping_phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS items_count,
           (SELECT i.invoice_id FROM invoices i WHERE i.order_id = o.order_id LIMIT 1) AS invoice_id
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY o.order_date DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-cart-shopping text-primary me-2"></i> Orders Pipeline Management</h4>
        <small class="text-muted">Track customer orders, payment confirmations, and fulfillment states</small>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search by Order #, Customer Name, Phone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">All Fulfillment Statuses</option>
                <option value="ORDER_CONFIRMED" <?= $statusFilter === 'ORDER_CONFIRMED' ? 'selected' : '' ?>>Order Confirmed</option>
                <option value="PROCESSING" <?= $statusFilter === 'PROCESSING' ? 'selected' : '' ?>>Processing</option>
                <option value="PACKED" <?= $statusFilter === 'PACKED' ? 'selected' : '' ?>>Packed</option>
                <option value="SHIPPED" <?= $statusFilter === 'SHIPPED' ? 'selected' : '' ?>>Shipped</option>
                <option value="IN_TRANSIT" <?= $statusFilter === 'IN_TRANSIT' ? 'selected' : '' ?>>In Transit</option>
                <option value="OUT_FOR_DELIVERY" <?= $statusFilter === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>Out for Delivery</option>
                <option value="DELIVERED" <?= $statusFilter === 'DELIVERED' ? 'selected' : '' ?>>Delivered</option>
                <option value="CANCELLED" <?= $statusFilter === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100 fw-bold">Filter</button>
            <a href="orders.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Order Ref</th>
                        <th>Customer</th>
                        <th>Dest City</th>
                        <th>Items</th>
                        <th class="text-end">Total Amount</th>
                        <th>Payment</th>
                        <th>Fulfillment State</th>
                        <th class="text-end">Actions</th>
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
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ord['shipping_phone']) ?></small>
                            </td>
                            <td><span class="small text-secondary"><?= htmlspecialchars($ord['shipping_city']) ?>, <?= htmlspecialchars($ord['shipping_state']) ?></span></td>
                            <td class="small fw-semibold"><?= $ord['items_count'] ?> units</td>
                            <td class="text-end fw-bold text-primary"><?= format_inr($ord['total_amount']) ?></td>
                            <td><?= get_payment_status_badge($ord['payment_status']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                                    <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit();" style="width: 145px; font-size: 0.8rem;">
                                        <option value="ORDER_CONFIRMED" <?= $ord['order_status'] === 'ORDER_CONFIRMED' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="PROCESSING" <?= $ord['order_status'] === 'PROCESSING' ? 'selected' : '' ?>>Processing</option>
                                        <option value="PACKED" <?= $ord['order_status'] === 'PACKED' ? 'selected' : '' ?>>Packed</option>
                                        <option value="SHIPPED" <?= $ord['order_status'] === 'SHIPPED' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="IN_TRANSIT" <?= $ord['order_status'] === 'IN_TRANSIT' ? 'selected' : '' ?>>In Transit</option>
                                        <option value="OUT_FOR_DELIVERY" <?= $ord['order_status'] === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>Out For Delivery</option>
                                        <option value="DELIVERED" <?= $ord['order_status'] === 'DELIVERED' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="CANCELLED" <?= $ord['order_status'] === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="../customer/order-details.php?id=<?= $ord['order_id'] ?>" target="_blank" class="btn btn-outline-primary" title="View Order Breakdown">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <?php if ($ord['invoice_id']): ?>
                                        <a href="../customer/invoices.php?id=<?= $ord['invoice_id'] ?>" target="_blank" class="btn btn-outline-secondary" title="View Tax Invoice">
                                            <i class="fa-solid fa-receipt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
