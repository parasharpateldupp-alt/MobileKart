<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Add New Smartphone to Catalog (DFD P1.2)
 */

$pageTitle = "Add New Smartphone";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$error = '';

$brands = $db->query("SELECT * FROM brands WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();
$categories = $db->query("SELECT * FROM categories WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();

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
    $warranty       = trim($_POST['warranty'] ?? '1 Year Manufacturer Warranty');
    $image          = trim($_POST['image'] ?? 'assets/images/products/s24_ultra.svg');

    // Calculate final price
    $finalPrice = $price - (($price * $discount) / 100);

    if (empty($productName) || empty($model) || $price <= 0 || empty($ram) || empty($storage)) {
        $error = "Please fill in all mandatory product and specification fields.";
    } else {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO products (supplier_id, brand_id, category_id, product_name, model, description, price, discount, final_price, stock_quantity, minimum_stock, ram, storage, processor, display, camera, battery, operating_system, color, warranty, image, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())
            ");
            $stmt->execute([
                $supplierId, $brandId, $categoryId, $productName, $model, $description,
                $price, $discount, $finalPrice, $stockQuantity, $minimumStock,
                $ram, $storage, $processor, $display, $camera, $battery, $os, $color, $warranty, $image
            ]);
            $newProdId = $db->lastInsertId();

            // Log Initial Stock in D2 Inventory DB
            if ($stockQuantity > 0) {
                $invStmt = $db->prepare("
                    INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
                    VALUES (?, 'INITIAL', ?, ?, 'INITIAL_ONBOARDING', 'Initial product catalog stock addition', ?, NOW())
                ");
                $invStmt->execute([$newProdId, $stockQuantity, $stockQuantity, $user['id']]);
            }

            $db->commit();
            set_flash('success', "Smartphone '{$productName}' added to distribution catalog successfully!");
            header("Location: products.php");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-square-plus text-primary me-2"></i> Onboard New Smartphone</h4>
        <small class="text-muted">List a new mobile handset with pricing, technical specifications, and initial warehouse stock</small>
    </div>
    <a href="products.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
    <form method="POST" action="product_add.php">
        <?= csrf_field() ?>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-circle-info me-2"></i> 1. General Product Details</h6>
        
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Smartphone Name *</label>
                <input type="text" name="product_name" class="form-control" placeholder="Enter smartphone name" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Model Code / Number *</label>
                <input type="text" name="model" class="form-control" placeholder="Enter model code/number" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Brand *</label>
                <select name="brand_id" class="form-select" required>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['brand_id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Category *</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Marketing Description *</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Enter product description and key features..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-indian-rupee-sign me-2"></i> 2. Pricing &amp; Inventory Allocation (D2)</h6>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Original MRP (₹) *</label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price in ₹" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Discount Percentage (%)</label>
                <input type="number" min="0" max="90" name="discount" class="form-control" placeholder="0" value="<?= htmlspecialchars($_POST['discount'] ?? '0') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Initial Stock Units *</label>
                <input type="number" min="0" name="stock_quantity" class="form-control" placeholder="Initial stock" value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '20') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Low Stock Alert Level *</label>
                <input type="number" min="1" name="minimum_stock" class="form-control" placeholder="Alert threshold" value="<?= htmlspecialchars($_POST['minimum_stock'] ?? '5') ?>" required>
            </div>
        </div>

        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="fa-solid fa-microchip me-2"></i> 3. Technical Specifications</h6>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">RAM Capacity *</label>
                <select name="ram" class="form-select" required>
                    <option>8 GB</option>
                    <option selected>12 GB</option>
                    <option>16 GB</option>
                    <option>6 GB</option>
                    <option>4 GB</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Internal Storage *</label>
                <select name="storage" class="form-select" required>
                    <option>128 GB</option>
                    <option selected>256 GB</option>
                    <option>512 GB</option>
                    <option>1 TB</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Color Option *</label>
                <input type="text" name="color" class="form-control" placeholder="Color variant" value="<?= htmlspecialchars($_POST['color'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Operating System *</label>
                <input type="text" name="operating_system" class="form-control" placeholder="Operating system" value="<?= htmlspecialchars($_POST['operating_system'] ?? 'Android 14') ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Processor Chipset *</label>
                <input type="text" name="processor" class="form-control" placeholder="Processor name / specs" value="<?= htmlspecialchars($_POST['processor'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Display Specifications *</label>
                <input type="text" name="display" class="form-control" placeholder="Display type, size, refresh rate" value="<?= htmlspecialchars($_POST['display'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Camera Specifications *</label>
                <input type="text" name="camera" class="form-control" placeholder="Rear & front camera specs" value="<?= htmlspecialchars($_POST['camera'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Battery &amp; Charging *</label>
                <input type="text" name="battery" class="form-control" placeholder="Battery capacity & charging speed" value="<?= htmlspecialchars($_POST['battery'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Warranty Details</label>
                <input type="text" name="warranty" class="form-control" value="<?= htmlspecialchars($_POST['warranty'] ?? '1 Year Brand Manufacturer Warranty') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Product Preview SVG / Image Path</label>
                <select name="image" class="form-select">
                    <option value="assets/images/products/s24_ultra.svg">assets/images/products/s24_ultra.svg</option>
                    <option value="assets/images/products/iphone15_promax.svg">assets/images/products/iphone15_promax.svg</option>
                    <option value="assets/images/products/oneplus12.svg" selected>assets/images/products/oneplus12.svg</option>
                    <option value="assets/images/products/xiaomi14_ultra.svg">assets/images/products/xiaomi14_ultra.svg</option>
                    <option value="assets/images/products/nothing2.svg">assets/images/products/nothing2.svg</option>
                    <option value="assets/images/products/vivo_x100.svg">assets/images/products/vivo_x100.svg</option>
                    <option value="assets/images/products/realme12_pro.svg">assets/images/products/realme12_pro.svg</option>
                    <option value="assets/images/products/moto_edge50.svg">assets/images/products/moto_edge50.svg</option>
                    <option value="assets/images/products/pixel8_pro.svg">assets/images/products/pixel8_pro.svg</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                <i class="fa-solid fa-check me-1"></i> Save &amp; Publish Smartphone
            </button>
            <a href="products.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
