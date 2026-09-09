<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Edit Existing Smartphone (DFD P1.2)
 */

$pageTitle = "Edit Smartphone";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$productId = (int)($_GET['id'] ?? 0);
$db = get_db_connection();

$stmt = $db->prepare("SELECT * FROM products WHERE product_id = ? AND supplier_id = ?");
$stmt->execute([$productId, $supplierId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Product not found or access unauthorized.');
    header("Location: products.php");
    exit;
}

$brands = $db->query("SELECT * FROM brands WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();
$categories = $db->query("SELECT * FROM categories WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $brandId        = (int)($_POST['brand_id'] ?? 0);
    $categoryId     = (int)($_POST['category_id'] ?? 0);
    $productName    = trim($_POST['product_name'] ?? '');
    $model          = trim($_POST['model'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $price          = (float)($_POST['price'] ?? 0.0);
    $discount       = (int)($_POST['discount'] ?? 0);
    $stockQuantity  = (int)($_POST['stock_quantity'] ?? 0);
    $minimumStock   = (int)($_POST['minimum_stock'] ?? 5);
    $ram            = trim($_POST['ram'] ?? '');
    $storage        = trim($_POST['storage'] ?? '');
    $processor      = trim($_POST['processor'] ?? '');
    $display        = trim($_POST['display'] ?? '');
    $camera         = trim($_POST['camera'] ?? '');
    $battery        = trim($_POST['battery'] ?? '');
    $os             = trim($_POST['operating_system'] ?? '');
    $color          = trim($_POST['color'] ?? '');
    $warranty       = trim($_POST['warranty'] ?? '');
    $status         = in_array($_POST['status'] ?? '', ['ACTIVE', 'INACTIVE', 'OUT_OF_STOCK']) ? $_POST['status'] : 'ACTIVE';

    $finalPrice = $price - (($price * $discount) / 100);

    if (empty($productName) || empty($model) || $price <= 0) {
        $error = "Please fill in all mandatory fields.";
    } else {
        try {
            $db->beginTransaction();

            $oldStock = $product['stock_quantity'];
            $stockDiff = $stockQuantity - $oldStock;

            $up = $db->prepare("
                UPDATE products 
                SET brand_id = ?, category_id = ?, product_name = ?, model = ?, description = ?,
                    price = ?, discount = ?, final_price = ?, stock_quantity = ?, minimum_stock = ?,
                    ram = ?, storage = ?, processor = ?, display = ?, camera = ?, battery = ?,
                    operating_system = ?, color = ?, warranty = ?, status = ?, updated_at = NOW()
                WHERE product_id = ? AND supplier_id = ?
            ");
            $up->execute([
                $brandId, $categoryId, $productName, $model, $description,
                $price, $discount, $finalPrice, $stockQuantity, $minimumStock,
                $ram, $storage, $processor, $display, $camera, $battery,
                $os, $color, $warranty, $status,
                $productId, $supplierId
            ]);

            // If stock changed, record transaction in D2
            if ($stockDiff !== 0) {
                $inv = $db->prepare("
                    INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
                    VALUES (?, 'ADJUSTMENT', ?, ?, 'MANUAL_EDIT', 'Stock updated via supplier product editor', ?, NOW())
                ");
                $inv->execute([$productId, $stockDiff, $stockQuantity, $user['id']]);
            }

            $db->commit();
            set_flash('success', "Product '{$productName}' updated successfully!");
            header("Location: products.php");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Error updating product: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Smartphone</h4>
        <small class="text-muted">Product ID: #<?= $product['product_id'] ?> • <?= htmlspecialchars($product['product_name']) ?></small>
    </div>
    <a href="products.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
    <form method="POST">
        <?= csrf_field() ?>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. General Information</h6>
        
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-secondary">Smartphone Name *</label>
                <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Model Number *</label>
                <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($product['model']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Status</label>
                <select name="status" class="form-select">
                    <option value="ACTIVE" <?= $product['status'] === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE (Live)</option>
                    <option value="INACTIVE" <?= $product['status'] === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE (Hidden)</option>
                    <option value="OUT_OF_STOCK" <?= $product['status'] === 'OUT_OF_STOCK' ? 'selected' : '' ?>>OUT OF STOCK</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Brand</label>
                <select name="brand_id" class="form-select">
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['brand_id'] ?>" <?= $product['brand_id'] == $b['brand_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Category</label>
                <select name="category_id" class="form-select">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>" <?= $product['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">2. Pricing &amp; Warehouse Inventory</h6>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Original MRP (₹)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Discount (%)</label>
                <input type="number" min="0" max="90" name="discount" class="form-control" value="<?= htmlspecialchars($product['discount']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Current Stock Quantity</label>
                <input type="number" min="0" name="stock_quantity" class="form-control" value="<?= htmlspecialchars($product['stock_quantity']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Min Stock Alert Level</label>
                <input type="number" min="1" name="minimum_stock" class="form-control" value="<?= htmlspecialchars($product['minimum_stock']) ?>" required>
            </div>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">3. Technical Specifications</h6>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">RAM</label>
                <input type="text" name="ram" class="form-control" value="<?= htmlspecialchars($product['ram']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Storage</label>
                <input type="text" name="storage" class="form-control" value="<?= htmlspecialchars($product['storage']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Color</label>
                <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($product['color']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">OS</label>
                <input type="text" name="operating_system" class="form-control" value="<?= htmlspecialchars($product['operating_system']) ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Processor</label>
                <input type="text" name="processor" class="form-control" value="<?= htmlspecialchars($product['processor']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Display</label>
                <input type="text" name="display" class="form-control" value="<?= htmlspecialchars($product['display']) ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Camera</label>
                <input type="text" name="camera" class="form-control" value="<?= htmlspecialchars($product['camera']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Battery &amp; Charging</label>
                <input type="text" name="battery" class="form-control" value="<?= htmlspecialchars($product['battery']) ?>" required>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                <i class="fa-solid fa-floppy-disk me-1"></i> Update Smartphone
            </button>
            <a href="products.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
