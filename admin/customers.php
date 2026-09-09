<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Customers Directory (DFD P1.1)
 */

$pageTitle = "Customers Directory";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$search = trim($_GET['q'] ?? '');

$where = ["u.role = 'CUSTOMER'"];
$params = [];

if ($search) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.city LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "
    SELECT u.*,
           COUNT(o.order_id) AS total_orders,
           COALESCE(SUM(o.total_amount), 0) AS total_spent,
           MAX(o.order_date) AS last_order_date
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.customer_id AND o.payment_status = 'PAID'
    WHERE " . implode(" AND ", $where) . "
    GROUP BY u.user_id
    ORDER BY total_spent DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-user-tag text-primary me-2"></i> Registered Customers Directory</h4>
        <small class="text-muted">Customer spending patterns, order counts, and delivery addresses</small>
    </div>
</div>

<!-- Search Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control" placeholder="Search customer by name, email, phone, city..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary fw-bold px-4">Search</button>
        <?php if ($search): ?>
            <a href="customers.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <?php if (empty($customers)): ?>
            <div class="p-5 text-center text-muted">No customers match your search query.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>#</th>
                            <th>Customer Name &amp; Email</th>
                            <th>Contact Phone</th>
                            <th>Shipping Address</th>
                            <th class="text-center">Orders</th>
                            <th class="text-end">Total Spent</th>
                            <th>Last Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= $c['user_id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['email']) ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($c['phone']) ?></code></td>
                                <td class="small text-secondary">
                                    <?php if ($c['address']): ?>
                                        <?= htmlspecialchars($c['address']) ?>, <?= htmlspecialchars($c['city'] ?? '') ?> - <?= htmlspecialchars($c['pincode'] ?? '') ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not provided</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold fs-6"><?= $c['total_orders'] ?></td>
                                <td class="text-end fw-bold text-primary"><?= format_inr($c['total_spent']) ?></td>
                                <td class="small text-muted">
                                    <?= $c['last_order_date'] ? date('d M Y', strtotime($c['last_order_date'])) : 'No purchases yet' ?>
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
