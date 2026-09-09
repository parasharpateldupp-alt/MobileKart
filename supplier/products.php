<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier My Products List (DFD P1.2)
 */

$pageTitle = "My Products Catalog";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Status Toggle or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $prodId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'toggle_status') {
        $up = $db->prepare("UPDATE products SET status = IF(status='ACTIVE', 'INACTIVE', 'ACTIVE') WHERE product_id = ? AND supplier_id = ?");
        $up->execute([$prodId, $supplierId]);
        set_flash('success', 'Product visibility updated.');
        header("Location: products.php");
        exit;
    } elseif ($action === 'delete') {
        $del = $db->prepare("DELETE FROM products WHERE product_id = ? AND supplier_id = ?");
        $del->execute([$prodId, $supplierId]);
        set_flash('info', 'Product removed from catalog.');
        header("Location: products.php");
        exit;
    }
}

// Fetch products
$stmt = $db->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE p.supplier_id = ?
    ORDER BY p.product_id DESC
");
$stmt->execute([$supplierId]);
$products = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-mobile-screen-button text-primary me-2"></i> My Smartphone Catalog</h4>
        <small class="text-muted">Manage product specifications, pricing, discounts, and online status</small>
    </div>
    <a href="product_add.php" class="btn btn-primary fw-bold shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> Add New Smartphone
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-mobile-screen fa-3x mb-3 opacity-50"></i>
                <h5>No smartphones listed yet</h5>
                <a href="product_add.php" class="btn btn-primary mt-2">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Smartphone Details</th>
                            <th style="width: 15%;">Pricing &amp; Discount</th>
                            <th style="width: 12%;">Inventory</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 21%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= $p['product_id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                            <img src="<?= product_image_url($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['brand_name']) ?> • <?= htmlspecialchars($p['ram']) ?> RAM | <?= htmlspecialchars($p['storage']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= format_inr($p['final_price']) ?></div>
                                    <?php if ($p['discount'] > 0): ?>
                                        <small class="text-muted text-decoration-line-through"><?= format_inr($p['price']) ?></small>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small ms-1"><?= $p['discount'] ?>% off</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold <?= ($p['stock_quantity'] <= $p['minimum_stock']) ? 'text-danger' : 'text-dark' ?>">
                                        <?= $p['stock_quantity'] ?> units
                                    </div>
                                    <small class="text-muted">Min: <?= $p['minimum_stock'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'secondary' ?> px-2 py-1">
                                        <?= $p['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="product_edit.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-primary" title="Edit Product">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="Toggle Active/Inactive">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                        </form>
                                        <a href="inventory.php?restock_id=<?= $p['product_id'] ?>" class="btn btn-outline-warning text-dark" title="Quick Restock">
                                            <i class="fa-solid fa-boxes-stacked"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product permanently?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Product">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
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
