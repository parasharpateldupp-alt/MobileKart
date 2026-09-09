<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Restock Alerts (DFD P1.2)
 */

$pageTitle = "Low Stock & Restock Alerts";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Batch Restock Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_restock'])) {
    require_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $addQty = (int)($_POST['add_qty'] ?? 10);

    if ($productId > 0 && $addQty > 0) {
        $db->beginTransaction();
        $up = $db->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ? AND supplier_id = ?");
        $up->execute([$addQty, $productId, $supplierId]);

        $q = $db->prepare("SELECT stock_quantity, product_name FROM products WHERE product_id = ?");
        $q->execute([$productId]);
        $prod = $q->fetch();

        $log = $db->prepare("
            INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
            VALUES (?, 'RESTOCK', ?, ?, 'ALERT_RESTOCK', 'Restocked from Low-Stock Alert trigger', ?, NOW())
        ");
        $log->execute([$productId, $addQty, $prod['stock_quantity'], $user['id']]);

        $db->commit();
        set_flash('success', "Added +{$addQty} units to '{$prod['product_name']}'. Current stock: {$prod['stock_quantity']}.");
        header("Location: restock.php");
        exit;
    }
}

// Query Low Stock Products
$stmt = $db->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.brand_id 
    JOIN categories c ON p.category_id = c.category_id 
    WHERE p.supplier_id = ? AND p.stock_quantity <= p.minimum_stock 
    ORDER BY p.stock_quantity ASC
");
$stmt->execute([$supplierId]);
$alertProducts = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Low-Stock &amp; Depletion Alerts</h4>
        <small class="text-muted">Proactively manage low inventory levels before stockouts occur</small>
    </div>
    <a href="inventory.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-boxes-stacked me-1"></i> Full Inventory
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">Active Alerts (<?= count($alertProducts) ?> items below minimum threshold)</span>
        <span class="badge bg-danger">Critical Warning</span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($alertProducts)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
                <h5 class="fw-bold text-dark">No Low-Stock Alerts</h5>
                <p class="small text-muted mb-0">All smartphone inventory items currently have stock levels above their minimum alert threshold.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Smartphone Model</th>
                            <th>Brand / Category</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Min Threshold</th>
                            <th class="text-center">Deficit</th>
                            <th class="text-end">Quick Restock Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertProducts as $p): 
                            $deficit = max(0, $p['minimum_stock'] - $p['stock_quantity']);
                            $recommended = max(10, $p['minimum_stock'] * 2);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <img src="<?= product_image_url($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></div>
                                            <small class="text-muted">Model: <code><?= htmlspecialchars($p['model']) ?></code> • <?= htmlspecialchars($p['ram']) ?> | <?= htmlspecialchars($p['storage']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($p['brand_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['category_name']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger fs-6 px-3 py-1"><?= $p['stock_quantity'] ?></span>
                                </td>
                                <td class="text-center fw-bold text-secondary"><?= $p['minimum_stock'] ?></td>
                                <td class="text-center text-danger fw-bold">-<?= $deficit ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline-flex align-items-center gap-2 justify-content-end">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="quick_restock" value="1">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <input type="number" name="add_qty" class="form-control form-control-sm text-center" style="width: 80px;" value="<?= $recommended ?>" min="1">
                                        <button type="submit" class="btn btn-success btn-sm text-nowrap fw-bold">
                                            <i class="fa-solid fa-plus me-1"></i> Add Stock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
