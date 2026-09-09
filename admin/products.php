<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Products Master Catalog (DFD P1.2)
 */

$pageTitle = "Products Master Catalog";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$search = trim($_GET['q'] ?? '');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $prodId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'toggle_flag') {
        $flag = $_POST['flag'] === 'featured' ? 'is_featured' : 'is_trending';
        $up = $db->prepare("UPDATE products SET {$flag} = IF({$flag}=1, 0, 1) WHERE product_id = ?");
        $up->execute([$prodId]);
        log_activity($user['id'], 'ADMIN_TOGGLED_FLAG', 'PRODUCT', $prodId, ['flag' => $flag]);
        set_flash('success', 'Product spotlight flag updated.');
        header("Location: products.php");
        exit;
    } elseif ($action === 'delete_product') {
        $del = $db->prepare("DELETE FROM products WHERE product_id = ?");
        $del->execute([$prodId]);
        log_activity($user['id'], 'ADMIN_DELETED_PRODUCT', 'PRODUCT', $prodId);
        set_flash('info', 'Smartphone removed from master catalog.');
        header("Location: products.php");
        exit;
    }
}

// Fetch all products
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.product_name LIKE ? OR p.model LIKE ? OR b.name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT p.*, b.name AS brand_name, c.name AS category_name, s.company_name AS supplier_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN categories c ON p.category_id = c.category_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.product_id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-mobile-screen text-primary me-2"></i> Products Master Catalog</h4>
        <small class="text-muted">Manage active smartphone listings, stock status, and pricing</small>
    </div>
    <a href="../supplier/product_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add Mobile Phone
    </a>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search by smartphone name, model, brand..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary fw-bold px-4">Search</button>
        <?php if ($search): ?>
            <a href="products.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Products Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>Smartphone Details</th>
                        <th>Supplier</th>
                        <th>Pricing</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Spotlight</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
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
                                        <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $p['product_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                        </a>
                                        <div class="small text-muted"><?= htmlspecialchars($p['brand_name']) ?> • <code><?= htmlspecialchars($p['model']) ?></code></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="small text-secondary fw-semibold"><?= htmlspecialchars($p['supplier_name']) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= format_inr($p['final_price']) ?></div>
                                <?php if ($p['discount'] > 0): ?>
                                    <small class="text-muted text-decoration-line-through"><?= format_inr($p['price']) ?></small>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small"><?= $p['discount'] ?>% off</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold fs-6 <?= ($p['stock_quantity'] <= $p['minimum_stock']) ? 'text-danger' : 'text-dark' ?>">
                                    <?= $p['stock_quantity'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <!-- Featured toggle -->
                                    <form method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_flag">
                                        <input type="hidden" name="flag" value="featured">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $p['is_featured'] ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>" title="Toggle Featured">
                                            <i class="fa-solid fa-crown"></i>
                                        </button>
                                    </form>
                                    <!-- Trending toggle -->
                                    <form method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_flag">
                                        <input type="hidden" name="flag" value="trending">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $p['is_trending'] ? 'btn-danger' : 'btn-outline-secondary' ?>" title="Toggle Trending">
                                            <i class="fa-solid fa-fire"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                    <?= $p['status'] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="../customer/product-details.php?id=<?= $p['product_id'] ?>" target="_blank" class="btn btn-outline-secondary" title="View Public Page">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                    <a href="inventory.php?adjust_id=<?= $p['product_id'] ?>" class="btn btn-outline-warning text-dark" title="Adjust Stock">
                                        <i class="fa-solid fa-boxes-stacked"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this product?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_product">
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
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
