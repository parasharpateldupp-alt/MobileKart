<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Sales Breakdown (DFD P1.4 & Analytics)
 */

$pageTitle = "Sales Breakdown & Revenue";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Top Selling Models for this Supplier
$topStmt = $db->prepare("
    SELECT p.product_name, p.model, p.image, 
           SUM(oi.quantity) AS total_units_sold, 
           SUM(oi.subtotal) AS total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.supplier_id = ? AND o.payment_status = 'PAID'
    GROUP BY p.product_id
    ORDER BY total_revenue DESC
");
$topStmt->execute([$supplierId]);
$topProducts = $topStmt->fetchAll();

// Monthly Sales summary
$monthStmt = $db->prepare("
    SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS sale_month,
           COUNT(DISTINCT o.order_id) AS total_orders,
           SUM(oi.quantity) AS units_sold,
           SUM(oi.subtotal) AS gross_sales
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE oi.supplier_id = ? AND o.payment_status = 'PAID'
    GROUP BY sale_month
    ORDER BY sale_month DESC
");
$monthStmt->execute([$supplierId]);
$monthlySales = $monthStmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Sales &amp; Revenue Analytics</h4>
        <small class="text-muted">Breakdown of smartphone units sold and wholesale receipts</small>
    </div>
</div>

<div class="row g-4">
    
    <!-- Top Selling Phones Table -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-fire text-danger me-2"></i> Product Revenue Performance</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                    <div class="p-4 text-center text-muted small">No verified sales recorded yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Smartphone</th>
                                    <th class="text-center">Units Sold</th>
                                    <th class="text-end">Gross Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $tp): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                    <img src="<?= product_image_url($tp['image']) ?>" alt="<?= htmlspecialchars($tp['product_name']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($tp['product_name']) ?></div>
                                                    <small class="text-muted"><code><?= htmlspecialchars($tp['model']) ?></code></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold fs-6 text-dark"><?= $tp['total_units_sold'] ?></td>
                                        <td class="text-end fw-bold text-primary"><?= format_inr($tp['total_revenue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Sales Summary -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Monthly Settlement Report</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($monthlySales)): ?>
                    <div class="p-4 text-center text-muted small">No monthly data available yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-center">Units</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlySales as $ms): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= date('F Y', strtotime($ms['sale_month'] . '-01')) ?></td>
                                        <td class="text-center"><?= $ms['total_orders'] ?></td>
                                        <td class="text-center fw-bold"><?= $ms['units_sold'] ?></td>
                                        <td class="text-end fw-bold text-success"><?= format_inr($ms['gross_sales']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
