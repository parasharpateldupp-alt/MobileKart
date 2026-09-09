<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Payment Ledger & Gateway Audit (DFD P1.4)
 */

$pageTitle = "Payments & Financial Ledger";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$methodFilter = trim($_GET['method'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if ($methodFilter) {
    $where[] = "p.payment_method = ?";
    $params[] = $methodFilter;
}

if ($statusFilter) {
    $where[] = "p.payment_status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT p.*, o.order_number, u.name AS customer_name, u.email AS customer_email
    FROM payments p
    JOIN orders o ON p.order_id = o.order_id
    JOIN users u ON p.customer_id = u.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.payment_id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-credit-card text-primary me-2"></i> Payments Ledger &amp; Bank Authorizations</h4>
        <small class="text-muted">Real-time payment audit log, transaction IDs, and settlement status</small>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
            <select name="method" class="form-select">
                <option value="">All Payment Methods</option>
                <option value="UPI" <?= $methodFilter === 'UPI' ? 'selected' : '' ?>>UPI</option>
                <option value="CREDIT_CARD" <?= $methodFilter === 'CREDIT_CARD' ? 'selected' : '' ?>>Credit Card</option>
                <option value="DEBIT_CARD" <?= $methodFilter === 'DEBIT_CARD' ? 'selected' : '' ?>>Debit Card</option>
                <option value="NET_BANKING" <?= $methodFilter === 'NET_BANKING' ? 'selected' : '' ?>>Net Banking</option>
            </select>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select">
                <option value="">All Payment Statuses</option>
                <option value="SUCCESS" <?= $statusFilter === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS (Authorized)</option>
                <option value="FAILED" <?= $statusFilter === 'FAILED' ? 'selected' : '' ?>>FAILED (Declined)</option>
                <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                <option value="CANCELLED" <?= $statusFilter === 'CANCELLED' ? 'selected' : '' ?>>CANCELLED</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100 fw-bold">Filter Ledger</button>
            <a href="payments.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Ledger Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Txn Reference</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Authorization Status</th>
                        <th>Gateway Response</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($pay['transaction_id']) ?></code></td>
                            <td>
                                <a href="orders.php?q=<?= urlencode($pay['order_number']) ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= htmlspecialchars($pay['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($pay['customer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($pay['customer_email']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pay['payment_method']) ?></span></td>
                            <td class="text-end fw-bold text-primary"><?= format_inr($pay['amount']) ?></td>
                            <td><?= get_payment_status_badge($pay['payment_status']) ?></td>
                            <td class="small text-muted" style="max-width: 260px;">
                                <div class="text-truncate" title="<?= htmlspecialchars($pay['gateway_response'] ?? '') ?>">
                                    <?= htmlspecialchars($pay['gateway_response'] ?? 'Pending Gateway Hook') ?>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <?= $pay['payment_date'] ? date('d M Y, h:i A', strtotime($pay['payment_date'])) : date('d M Y, h:i A', strtotime($pay['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
