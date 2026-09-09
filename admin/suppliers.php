<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Supplier Management (DFD P1.1 Manage User Accounts)
 */

$pageTitle = "Suppliers & Distributors";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $supplierId = (int)($_POST['supplier_id'] ?? 0);

    if ($action === 'approve') {
        $up = $db->prepare("UPDATE suppliers SET approval_status = 'APPROVED', updated_at = NOW() WHERE supplier_id = ?");
        $up->execute([$supplierId]);
        log_activity($user['id'], 'ADMIN_APPROVED_SUPPLIER', 'SUPPLIER', $supplierId);
        set_flash('success', "Supplier application approved successfully.");
        header("Location: suppliers.php");
        exit;
    } elseif ($action === 'reject') {
        $up = $db->prepare("UPDATE suppliers SET approval_status = 'REJECTED', updated_at = NOW() WHERE supplier_id = ?");
        $up->execute([$supplierId]);
        log_activity($user['id'], 'ADMIN_REJECTED_SUPPLIER', 'SUPPLIER', $supplierId);
        set_flash('warning', "Supplier application rejected.");
        header("Location: suppliers.php");
        exit;
    }
}

// Fetch suppliers with metrics
$stmt = $db->query("
    SELECT s.*, u.name AS owner_name, u.email, u.phone, u.city,
           (SELECT COUNT(*) FROM products WHERE supplier_id = s.supplier_id) AS products_count,
           (SELECT COALESCE(SUM(oi.subtotal), 0) 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.order_id 
            WHERE oi.supplier_id = s.supplier_id AND o.payment_status = 'PAID') AS gross_sales
    FROM suppliers s
    JOIN users u ON s.user_id = u.user_id
    ORDER BY s.supplier_id ASC
");
$suppliers = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-warehouse text-primary me-2"></i> Authorized Suppliers &amp; Distributors</h4>
        <small class="text-muted">Manage distributor partnerships, verify GSTINs, and audit product volume</small>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>Company &amp; GSTIN</th>
                        <th>Contact Representative</th>
                        <th class="text-center">Products Listed</th>
                        <th class="text-end">Gross Sales</th>
                        <th>Verification Status</th>
                        <th class="text-end">Approval Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><?= $s['supplier_id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['company_name']) ?></div>
                                <small class="text-muted">GSTIN: <code><?= htmlspecialchars($s['gst_number']) ?></code></small>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($s['contact_person']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($s['phone']) ?> • <?= htmlspecialchars($s['email']) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-primary border px-3 py-1 fs-6">
                                    <?= $s['products_count'] ?> mobiles
                                </span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?= format_inr($s['gross_sales']) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= match($s['approval_status']) { 'APPROVED' => 'success', 'PENDING' => 'warning text-dark', default => 'danger' } ?> px-2 py-1">
                                    <?= $s['approval_status'] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($s['approval_status'] !== 'APPROVED'): ?>
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="supplier_id" value="<?= $s['supplier_id'] ?>">
                                            <button type="submit" class="btn btn-success" title="Approve Supplier">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($s['approval_status'] !== 'REJECTED'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Reject this supplier?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="supplier_id" value="<?= $s['supplier_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Reject Supplier">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </form>
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
