<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Stock Management & Restock (DFD P1.2)
 */

$pageTitle = "Inventory & Stock Management";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$prefillId = (int)($_GET['restock_id'] ?? 0);

// Handle Restock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_action'])) {
    require_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $addQty    = (int)($_POST['quantity'] ?? 0);
    $notes     = trim($_POST['notes'] ?? 'Supplier warehouse restock consignment');

    if ($productId > 0 && $addQty > 0) {
        try {
            $db->beginTransaction();

            $up = $db->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?, updated_at = NOW() 
                WHERE product_id = ? AND supplier_id = ?
            ");
            $up->execute([$addQty, $productId, $supplierId]);

            // Query new quantity
            $qStmt = $db->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
            $qStmt->execute([$productId]);
            $pData = $qStmt->fetch();

            // Insert inventory transaction in D2
            $log = $db->prepare("
                INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
                VALUES (?, 'RESTOCK', ?, ?, 'RESTOCK_BATCH', ?, ?, NOW())
            ");
            $log->execute([$productId, $addQty, $pData['stock_quantity'], $notes, $user['id']]);

            $db->commit();
            set_flash('success', "Successfully added +{$addQty} units to '{$pData['product_name']}'. Current stock: {$pData['stock_quantity']}.");
            header("Location: inventory.php");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash('danger', "Error restocking product: " . $e->getMessage());
        }
    }
}

// Fetch products for this supplier
$pStmt = $db->prepare("
    SELECT p.*, b.name AS brand_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.brand_id 
    WHERE p.supplier_id = ? 
    ORDER BY p.stock_quantity ASC
");
$pStmt->execute([$supplierId]);
$products = $pStmt->fetchAll();

// Fetch Recent Inventory Transactions for Supplier's Products
$tStmt = $db->prepare("
    SELECT it.*, p.product_name, p.model 
    FROM inventory_transactions it 
    JOIN products p ON it.product_id = p.product_id 
    WHERE p.supplier_id = ? 
    ORDER BY it.created_at DESC 
    LIMIT 10
");
$tStmt->execute([$supplierId]);
$transactions = $tStmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Inventory &amp; Stock Audit</h4>
        <small class="text-muted">Real-time stock adjustments, reorder quantities, and movement history</small>
    </div>
    <a href="restock.php" class="btn btn-outline-danger btn-sm fw-bold">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> View Low-Stock Alerts
    </a>
</div>

<div class="row g-4 mb-4">
    
    <!-- Restock Form Box -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 80px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="fa-solid fa-dolly text-primary me-2"></i> Quick Restock Tool
            </h5>

            <form method="POST" action="inventory.php">
                <?= csrf_field() ?>
                <input type="hidden" name="restock_action" value="1">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Select Smartphone *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Choose Smartphone --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['product_id'] ?>" <?= $prefillId === $p['product_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['stock_quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Add Quantity (Units) *</label>
                    <input type="number" name="quantity" class="form-control" min="1" max="1000" placeholder="Enter quantity" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Inward Remarks / PO Ref</label>
                    <input type="text" name="notes" class="form-control" placeholder="Enter inward remarks or PO reference">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-plus-circle me-1"></i> Add Stock Units
                </button>
            </form>
        </div>
    </div>

    <!-- Product Stock Levels Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Current Smartphone Stock Allocations</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Product Details</th>
                                <th>RAM / Storage</th>
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
                                        <small class="text-muted"><?= htmlspecialchars($p['brand_name']) ?> • <?= htmlspecialchars($p['model']) ?></small>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($p['ram']) ?> | <?= htmlspecialchars($p['storage']) ?></td>
                                    <td class="text-center small text-muted"><?= $p['minimum_stock'] ?></td>
                                    <td class="text-center">
                                        <span class="fw-bold fs-6 <?= $isLow ? 'text-danger' : 'text-success' ?>">
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

        <!-- Recent Stock Movement History -->
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
                                <th>Type</th>
                                <th class="text-center">Delta</th>
                                <th class="text-center">Balance</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?= date('d M, H:i', strtotime($t['created_at'])) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($t['product_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= $t['transaction_type'] ?></span></td>
                                    <td class="text-center fw-bold <?= $t['quantity_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($t['quantity_change'] > 0 ? '+' : '') . $t['quantity_change'] ?>
                                    </td>
                                    <td class="text-center fw-bold text-dark"><?= $t['quantity_after'] ?></td>
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
