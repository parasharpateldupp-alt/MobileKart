<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Coupon Promotions & Discounts Manager
 */

$pageTitle = "Promotions & Discount Coupons";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_promo') {
        $code      = strtoupper(trim($_POST['code'] ?? ''));
        $percent   = (int)($_POST['discount_percent'] ?? 0);
        $amount    = (float)($_POST['discount_amount'] ?? 0.0);
        $minOrder  = (float)($_POST['minimum_order'] ?? 0.0);
        $maxDisc   = (float)($_POST['max_discount'] ?? 0.0);
        $validFrom = $_POST['valid_from'] ?? date('Y-m-d');
        $validTo   = $_POST['valid_to'] ?? date('Y-m-d', strtotime('+1 year'));

        if (!empty($code)) {
            $ins = $db->prepare("
                INSERT INTO promotions (code, discount_percent, discount_amount, minimum_order, max_discount, valid_from, valid_to, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $ins->execute([$code, $percent, $amount, $minOrder, $maxDisc, $validFrom, $validTo]);
            log_activity($user['id'], 'ADMIN_CREATED_PROMO', 'PROMOTION', $db->lastInsertId(), ['code' => $code]);
            set_flash('success', "Coupon '{$code}' created.");
        }
        header("Location: promotions.php");
        exit;
    }

    if ($action === 'toggle_active') {
        $pId = (int)($_POST['promo_id'] ?? 0);
        $db->prepare("UPDATE promotions SET is_active = IF(is_active=1, 0, 1) WHERE promo_id = ?")->execute([$pId]);
        set_flash('success', 'Coupon active status toggled.');
        header("Location: promotions.php");
        exit;
    }

    if ($action === 'delete_promo') {
        $pId = (int)($_POST['promo_id'] ?? 0);
        $db->prepare("DELETE FROM promotions WHERE promo_id = ?")->execute([$pId]);
        set_flash('info', 'Coupon deleted.');
        header("Location: promotions.php");
        exit;
    }
}

$promos = $db->query("SELECT * FROM promotions ORDER BY promo_id DESC")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-ticket text-primary me-2"></i> Discount Coupons &amp; Promotions</h4>
        <small class="text-muted">Incentivize purchasing with targeted percentage or flat discount vouchers</small>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addPromoModal">
        <i class="fa-solid fa-plus me-1"></i> Create Coupon
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Coupon Code</th>
                        <th>Benefit Type</th>
                        <th>Min Order Value</th>
                        <th>Validity Period</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promos as $p): ?>
                        <tr>
                            <td><span class="badge bg-light text-primary border fs-6 px-3 py-1"><code><?= htmlspecialchars($p['code']) ?></code></span></td>
                            <td>
                                <?php if ($p['discount_percent'] > 0): ?>
                                    <span class="fw-bold text-success"><?= $p['discount_percent'] ?>% Instant Off</span>
                                    <?php if ($p['max_discount'] > 0): ?>
                                        <div class="small text-muted">Max: <?= format_inr($p['max_discount']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="fw-bold text-primary">Flat <?= format_inr($p['discount_amount']) ?> Off</span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_inr($p['minimum_order']) ?></td>
                            <td class="small text-muted">
                                <?= date('d M Y', strtotime($p['valid_from'])) ?> to <?= date('d M Y', strtotime($p['valid_to'])) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <form method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="promo_id" value="<?= $p['promo_id'] ?>">
                                        <button type="submit" class="btn btn-outline-warning text-dark" title="Toggle Status">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_promo">
                                        <input type="hidden" name="promo_id" value="<?= $p['promo_id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Promo Modal -->
<div class="modal fade" id="addPromoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-ticket me-2"></i> Create Coupon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_promo">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Coupon Code (Uppercase) *</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="Enter coupon code" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Discount (%)</label>
                            <input type="number" min="0" max="90" name="discount_percent" class="form-control" value="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Flat Amount (₹)</label>
                            <input type="number" min="0" name="discount_amount" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Minimum Order (₹)</label>
                            <input type="number" min="0" name="minimum_order" class="form-control" value="10000">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Max Discount Cap (₹)</label>
                            <input type="number" min="0" name="max_discount" class="form-control" value="2500">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Valid From</label>
                            <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Valid To</label>
                            <input type="date" name="valid_to" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Coupon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
