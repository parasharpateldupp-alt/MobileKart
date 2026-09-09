<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Tax Invoices Repository (DFD P1.4.3)
 */

$pageTitle = "Invoices Repository";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$search = trim($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(i.invoice_number LIKE ? OR o.order_number LIKE ? OR u.name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT i.*, o.order_number, u.name AS customer_name, u.email AS customer_email
    FROM invoices i
    JOIN orders o ON i.order_id = o.order_id
    JOIN users u ON i.customer_id = u.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY i.invoice_id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Tax Invoices Repository</h4>
        <small class="text-muted">GST compliant tax receipts and downloadable records</small>
    </div>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by Invoice #, Order #, or Customer Name..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary fw-bold px-4">Search</button>
        <?php if ($search): ?>
            <a href="invoices.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Invoices Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Invoice Number</th>
                        <th>Order Ref</th>
                        <th>Customer</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">GST (18%)</th>
                        <th class="text-end">Total Amount</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark"><code><?= htmlspecialchars($inv['invoice_number']) ?></code></span>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($inv['order_number']) ?></code>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($inv['customer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($inv['customer_email']) ?></small>
                            </td>
                            <td class="text-end small"><?= format_inr($inv['subtotal']) ?></td>
                            <td class="text-end small text-muted"><?= format_inr($inv['tax_amount']) ?></td>
                            <td class="text-end fw-bold text-primary"><?= format_inr($inv['total_amount']) ?></td>
                            <td class="small text-muted"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                            <td class="text-end">
                                <a href="../customer/invoices.php?id=<?= $inv['invoice_id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                                    <i class="fa-solid fa-print me-1"></i> View / Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
