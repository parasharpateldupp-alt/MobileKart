<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Master Inventory Audit & Stock Adjustments (DFD P1.2 & D2)
 */

$pageTitle = "Inventory & Stock Audit";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$prefillId = (int)($_GET['adjust_id'] ?? 0);
$filter = trim($_GET['filter'] ?? '');

// Handle Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    require_csrf();
    $prodId = (int)($_POST['product_id'] ?? 0);
    $type   = $_POST['adj_type'] ?? 'RESTOCK'; // RESTOCK (increase) or ADJUSTMENT (decrease)
    $qty    = (int)($_POST['quantity'] ?? 0);
    $notes  = trim($_POST['notes'] ?? 'Admin warehouse adjustment');

    if ($prodId > 0 && $qty > 0) {
        $db->beginTransaction();

        $delta = ($type === 'RESTOCK') ? $qty : -$qty;

        $up = $db->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity + ?) WHERE product_id = ?");
        $up->execute([$delta, $prodId]);

        $q = $db->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
        $q->execute([$prodId]);
        $prod = $q->fetch();

        $insLog = $db->prepare("
            INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, 'ADMIN_ADJUST', ?, ?, NOW())
        ");
        $insLog->execute([$prodId, $type, $delta, $prod['stock_quantity'], $notes, $user['id']]);

        log_activity($user['id'], 'ADMIN_INVENTORY_ADJUSTMENT', 'PRODUCT', $prodId, [
            'delta' => $delta,
            'quantity_after' => $prod['stock_quantity'],
            'notes' => $notes
        ]);

        $db->commit();
        set_flash('success', "Stock updated for '{$prod['product_name']}'. Current stock: {$prod['stock_quantity']} units.");
        header("Location: inventory.php");
        exit;
    }
}

// Fetch products based on filter
$where = ["1=1"];
if ($filter === 'low') {
    $where[] = "p.stock_quantity <= p.minimum_stock AND p.stock_quantity > 0";
} elseif ($filter === 'out') {
    $where[] = "p.stock_quantity <= 0";
}

$pSql = "
    SELECT p.*, b.name AS brand_name, s.company_name AS supplier_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.stock_quantity ASC
";
$products = $db->query($pSql)->fetchAll();

// Fetch transactions history
$trans = $db->query("
    SELECT it.*, p.product_name, p.model, u.name AS operator_name
    FROM inventory_transactions it
    JOIN products p ON it.product_id = p.product_id
    LEFT JOIN users u ON it.created_by = u.user_id
    ORDER BY it.created_at DESC
    LIMIT 20
")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Master Inventory Control</h4>
        <small class="text-muted">Stock adjustments, supplier warehouse tracking, and reorder levels</small>
    </div>
</div>

<div class="row g-4 mb-4">
    
    <!-- Adjustment Tool Box -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="fa-solid fa-sliders text-primary me-2"></i> Stock Adjustment
            </h5>

            <form method="POST" action="inventory.php">
                <?= csrf_field() ?>
                <input type="hidden" name="adjust_stock" value="1">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Smartphone *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Select Smartphone --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['product_id'] ?>" <?= $prefillId === $p['product_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['stock_quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Adjustment Type *</label>
                    <select name="adj_type" class="form-select" required>
                        <option value="RESTOCK">Increase Stock (+ Inward Intake)</option>
                        <option value="ADJUSTMENT">Decrease Stock (- Damage / Correction)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Quantity Units *</label>
                    <input type="number" name="quantity" class="form-control" min="1" max="1000" placeholder="Enter quantity" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Operational Audit Note *</label>
                    <input type="text" name="notes" class="form-control" placeholder="Enter adjustment remarks or reference" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Commit Stock Adjustment
                </button>
            </form>
        </div>
    </div>

    <!-- Product Stock Levels Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Smartphone Stock Levels</h6>
                <div class="btn-group btn-group-sm">
                    <a href="inventory.php" class="btn btn-outline-secondary <?= empty($filter) ? 'active' : '' ?>">All</a>
                    <a href="inventory.php?filter=low" class="btn btn-outline-warning text-dark <?= $filter === 'low' ? 'active' : '' ?>">Low Stock</a>
                    <a href="inventory.php?filter=out" class="btn btn-outline-danger <?= $filter === 'out' ? 'active' : '' ?>">Out of Stock</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Smartphone Details</th>
                                <th>Supplier</th>
                                <th class="text-center">Min Threshold</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): 
                                $isLow = $p['stock_quantity'] <= $p['minimum_stock'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($p['product_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['brand_name']) ?> • <?= htmlspecialchars($p['ram']) ?> | <?= htmlspecialchars($p['storage']) ?></small>
                                    </td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($p['supplier_name']) ?></small></td>
                                    <td class="text-center small text-muted"><?= $p['minimum_stock'] ?></td>
                                    <td class="text-center">
                                        <span class="fw-bold fs-6 <?= $isLow ? 'text-danger' : 'text-dark' ?>">
                                            <?= $p['stock_quantity'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($p['stock_quantity'] <= 0): ?>
                                            <span class="badge bg-danger">OUT OF STOCK</span>
                                        <?php elseif ($isLow): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> LOW STOCK</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">NORMAL</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Movement History Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Inventory Transactions Audit Log (D2)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Action</th>
                                <th class="text-center">Delta</th>
                                <th class="text-center">Balance</th>
                                <th>Auditor</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trans as $t): ?>
                                <tr>
                                    <td><?= date('d M, H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($t['product_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= $t['transaction_type'] ?></span></td>
                                    <td class="text-center fw-bold <?= $t['quantity_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($t['quantity_change'] > 0 ? '+' : '') . $t['quantity_change'] ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark"><?= $t['quantity_after'] ?></td>
                                    <td><?= htmlspecialchars($t['operator_name'] ?? 'System') ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($t['notes'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
