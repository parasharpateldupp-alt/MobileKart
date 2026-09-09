<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Reviews History
 */

$pageTitle = "My Reviews - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$userId = $_SESSION['user_id'];
$db = get_db_connection();

$stmt = $db->prepare("
    SELECT r.*, p.product_name, p.model, p.image, p.product_id
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item active">My Reviews</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i> My Ratings &amp; Reviews</h4>
            <small class="text-muted">Feedback you have shared on delivered smartphone orders</small>
        </div>
        <a href="<?= BASE_URL ?>/customer/orders.php" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-box-archive me-1"></i> View Orders
        </a>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white">
            <i class="fa-regular fa-comment-dots fa-4x text-muted mb-3 opacity-50"></i>
            <h5 class="fw-bold text-dark">You haven't written any product reviews yet</h5>
            <p class="text-muted small">Reviews can be submitted on the product page after your smartphone order is delivered.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($reviews as $rev): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="row g-3 align-items-center">
                            <div class="col-3 col-md-1 text-center">
                                <img src="<?= product_image_url($rev['image']) ?>" alt="<?= htmlspecialchars($rev['product_name']) ?>" style="max-height: 60px; max-width: 100%; object-fit: contain;">
                            </div>
                            <div class="col-9 col-md-11">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0">
                                        <a href="<?= BASE_URL ?>/customer/product-details.php?id=<?= $rev['product_id'] ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($rev['product_name']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted"><?= date('d M Y', strtotime($rev['created_at'])) ?></small>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="fk-rating-badge"><?= $rev['rating'] ?> ★</span>
                                    <span class="fw-bold text-dark small"><?= htmlspecialchars($rev['title']) ?></span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="fa-solid fa-check-circle me-1"></i> <?= $rev['status'] ?></span>
                                </div>
                                <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
