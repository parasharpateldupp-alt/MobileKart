<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Customer Reviews Moderation
 */

$pageTitle = "Customer Reviews Moderation";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $revId = (int)($_POST['review_id'] ?? 0);

    if ($action === 'approve') {
        $db->prepare("UPDATE reviews SET status = 'APPROVED' WHERE review_id = ?")->execute([$revId]);
        set_flash('success', 'Review approved and published.');
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE reviews SET status = 'REJECTED' WHERE review_id = ?")->execute([$revId]);
        set_flash('warning', 'Review marked as rejected.');
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([$revId]);
        set_flash('info', 'Review deleted.');
    }
    header("Location: reviews.php");
    exit;
}

$reviews = $db->query("
    SELECT r.*, p.product_name, p.model, u.name AS customer_name, u.email AS customer_email
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.customer_id = u.user_id
    ORDER BY r.review_id DESC
")->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-star-half-stroke text-warning me-2"></i> Reviews &amp; Ratings Moderation</h4>
        <small class="text-muted">Audit verified customer reviews before and after public publishing</small>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <?php if (empty($reviews)): ?>
            <div class="p-5 text-center text-muted">No reviews submitted yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>#</th>
                            <th>Smartphone</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Headline &amp; Comment</th>
                            <th>Status</th>
                            <th class="text-end">Moderation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td><?= $r['review_id'] ?></td>
                                <td>
                                    <a href="../customer/product-details.php?id=<?= $r['product_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none small">
                                        <?= htmlspecialchars($r['product_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($r['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['customer_email']) ?></small>
                                </td>
                                <td><span class="fk-rating-badge"><?= $r['rating'] ?> ★</span></td>
                                <td style="max-width: 320px;">
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($r['title']) ?></div>
                                    <div class="small text-secondary text-truncate"><?= htmlspecialchars($r['comment']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= match($r['status']) { 'APPROVED' => 'success', 'PENDING' => 'warning text-dark', default => 'danger' } ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($r['status'] !== 'APPROVED'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                                <button type="submit" class="btn btn-outline-success" title="Approve"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($r['status'] !== 'REJECTED'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                                <button type="submit" class="btn btn-outline-warning text-dark" title="Reject"><i class="fa-solid fa-ban"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete review?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
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
