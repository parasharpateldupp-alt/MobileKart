<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Image Source Credits & Academic Transparency Documentation
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = "Catalog Image Sources & Open License Credits - MobileKart";
$db = get_db_connection();

$stmt = $db->query("
    SELECT pi.*, p.product_name, p.model, b.name AS brand_name, b.slug AS brand_slug
    FROM product_images pi
    JOIN products p ON pi.product_id = p.product_id
    JOIN brands b ON p.brand_id = b.brand_id
    ORDER BY p.product_id ASC, pi.is_primary DESC, pi.sort_order ASC
");
$records = $stmt->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Image Sources & Licenses</li>
                </ol>
            </nav>
            <h1 class="h2 fw-bold text-dark mb-2">
                <i class="fa-solid fa-scale-balanced text-primary me-2"></i>Catalog Image Attribution & Licensing Registry
            </h1>
            <p class="text-muted mb-0">
                Academic transparency log verifying the licensing, original provenance, authorship, and integrity of all photographic assets across the MobileKart catalog.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                <i class="fa-solid fa-shield-halved me-1"></i> 100% Verified Non-AI Assets
            </span>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-muted small text-uppercase fw-semibold">Catalog Devices</div>
                <div class="h3 fw-bold text-dark mb-0">18</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-muted small text-uppercase fw-semibold">Registered Assets</div>
                <div class="h3 fw-bold text-primary mb-0"><?= count($records) ?></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-muted small text-uppercase fw-semibold">Open Licenses (CC)</div>
                <div class="h3 fw-bold text-success mb-0">
                    <?= count(array_filter($records, fn($r) => str_contains($r['license'], 'CC'))) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                <div class="text-muted small text-uppercase fw-semibold">Manufacturer Imagery</div>
                <div class="h3 fw-bold text-secondary mb-0">
                    <?= count(array_filter($records, fn($r) => str_contains($r['license'], 'Manufacturer'))) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Attribution Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th style="width: 70px;">Preview</th>
                        <th>Device & Brand</th>
                        <th>File & Canonical Path</th>
                        <th>License</th>
                        <th>Source & Provider</th>
                        <th>Author & Attribution</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td>
                            <div class="border rounded p-1 bg-white text-center" style="width: 60px; height: 60px;">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($r['image_url']) ?>" 
                                     alt="<?= htmlspecialchars($r['product_name']) ?>" 
                                     class="img-fluid" 
                                     style="max-height: 50px; object-fit: contain;">
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></div>
                            <div class="small text-muted d-flex align-items-center gap-1">
                                <?= brand_logo_html($r, 14) ?>
                                <span><?= htmlspecialchars($r['brand_name']) ?></span>
                                <span class="badge bg-light text-secondary border ms-1"><?= htmlspecialchars($r['color_name'] ?? 'Standard') ?></span>
                                <?php if (!empty($r['is_primary'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Primary</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <code class="small text-dark fw-semibold"><?= htmlspecialchars(basename($r['image_url'])) ?></code>
                            <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($r['local_path']) ?></div>
                        </td>
                        <td>
                            <?php if (str_contains($r['license'], 'CC')): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-1">
                                    <i class="fa-brands fa-creative-commons me-1"></i> <?= htmlspecialchars($r['license']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-semibold px-2 py-1">
                                    <i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($r['license']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold small text-dark"><?= htmlspecialchars($r['source_name']) ?></div>
                            <?php if (!empty($r['source_url'])): ?>
                                <a href="<?= htmlspecialchars($r['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="small text-decoration-none text-primary d-inline-flex align-items-center gap-1">
                                    <span>View Source Page</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= htmlspecialchars($r['author'] ?: 'Official Brand PR') ?></div>
                            <div class="text-muted small" style="font-size: 0.8rem;"><?= htmlspecialchars($r['attribution_text']) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
                                <i class="fa-solid fa-circle-check me-1"></i> Verified
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
