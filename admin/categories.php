<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Categories & Brands Manager (DFD P1.2)
 */

$pageTitle = "Categories & Brands";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $type = $_POST['type'] ?? '';

    if ($type === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $icon = trim($_POST['icon'] ?? 'fa-mobile-alt');
        if (!empty($name)) {
            $ins = $db->prepare("INSERT INTO categories (name, slug, icon, status) VALUES (?, ?, ?, 'ACTIVE')");
            $ins->execute([$name, $slug, $icon]);
            log_activity($user['id'], 'ADMIN_ADDED_CATEGORY', 'CATEGORY', $db->lastInsertId());
            set_flash('success', "Category '{$name}' created.");
        }
        header("Location: categories.php");
        exit;
    }

    if ($type === 'add_brand') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? strtolower(str_replace(' ', '-', $name)));
        $logo = trim($_POST['logo_icon'] ?? 'fa-mobile');
        if (!empty($name)) {
            $ins = $db->prepare("INSERT INTO brands (name, slug, logo_icon, status) VALUES (?, ?, ?, 'ACTIVE')");
            $ins->execute([$name, $slug, $logo]);
            log_activity($user['id'], 'ADMIN_ADDED_BRAND', 'BRAND', $db->lastInsertId());
            set_flash('success', "Brand '{$name}' created.");
        }
        header("Location: categories.php");
        exit;
    }

    if ($type === 'delete_category') {
        $id = (int)($_POST['category_id'] ?? 0);
        $db->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$id]);
        set_flash('info', 'Category deleted.');
        header("Location: categories.php");
        exit;
    }

    if ($type === 'delete_brand') {
        $id = (int)($_POST['brand_id'] ?? 0);
        $db->prepare("DELETE FROM brands WHERE brand_id = ?")->execute([$id]);
        set_flash('info', 'Brand deleted.');
        header("Location: categories.php");
        exit;
    }
}

// Fetch categories & brands with counts
$categories = $db->query("
    SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) AS products_count
    FROM categories c
    ORDER BY c.name ASC
")->fetchAll();

$brands = $db->query("
    SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.brand_id) AS products_count
    FROM brands b
    ORDER BY b.name ASC
")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-tags text-primary me-2"></i> Categories &amp; Brand Taxonomy</h4>
        <small class="text-muted">Manage product classifications, brand partners, and catalog hierarchies</small>
    </div>
</div>

<div class="row g-4">
    
    <!-- Left: Categories -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list text-primary me-2"></i> Smartphone Categories</h6>
                <button class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCatModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Category
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Category Name</th>
                                <th>Slug</th>
                                <th class="text-center">Products</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td>
                                        <i class="fa-solid <?= htmlspecialchars($c['icon']) ?> text-primary me-2"></i>
                                        <strong><?= htmlspecialchars($c['name']) ?></strong>
                                    </td>
                                    <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
                                    <td class="text-center fw-bold"><?= $c['products_count'] ?></td>
                                    <td class="text-end">
                                        <?php if ($c['products_count'] == 0): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="type" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">In Use</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Brands -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-mobile-screen text-primary me-2"></i> Mobile Manufacturers &amp; Brands</h6>
                <button class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Brand
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Brand Name</th>
                                <th>Slug</th>
                                <th class="text-center">Phones Listed</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $b): ?>
                                <tr>
                                    <td>
                                        <i class="fa-brands <?= htmlspecialchars($b['logo_icon']) ?> text-primary me-2"></i>
                                        <strong><?= htmlspecialchars($b['name']) ?></strong>
                                    </td>
                                    <td><code><?= htmlspecialchars($b['slug']) ?></code></td>
                                    <td class="text-center fw-bold"><?= $b['products_count'] ?></td>
                                    <td class="text-end">
                                        <?php if ($b['products_count'] == 0): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this brand?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="type" value="delete_brand">
                                                <input type="hidden" name="brand_id" value="<?= $b['brand_id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">In Use</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Add Category -->
<div class="modal fade" id="addCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="add_category">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Category Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Slug (Optional)</label>
                        <input type="text" name="slug" class="form-control" placeholder="category-slug">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">FontAwesome Icon Class</label>
                        <input type="text" name="icon" class="form-control" value="fa-mobile-alt">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Brand -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Add New Mobile Brand</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="add_brand">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Brand Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter brand name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Slug (Optional)</label>
                        <input type="text" name="slug" class="form-control" placeholder="brand-slug">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Logo Icon Class</label>
                        <input type="text" name="logo_icon" class="form-control" value="fa-mobile">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
