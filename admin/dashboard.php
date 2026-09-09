<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Administrator Central Dashboard & Operations Hub
 */

$pageTitle = "Administrator Dashboard";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// 1. Core KPIs
$revStmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'PAID'");
$totalRevenue = (float)$revStmt->fetchColumn();

$ordStmt = $db->query("SELECT COUNT(*) FROM orders");
$totalOrders = (int)$ordStmt->fetchColumn();

$custStmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'");
$totalCustomers = (int)$custStmt->fetchColumn();

$prodStmt = $db->query("SELECT COUNT(*), COALESCE(SUM(stock_quantity), 0) FROM products");
[$totalProducts, $totalStockUnits] = $prodStmt->fetch(PDO::FETCH_NUM);

$lowStockStmt = $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= minimum_stock");
$lowStockCount = (int)$lowStockStmt->fetchColumn();

$penShipStmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('ORDER_CONFIRMED', 'PROCESSING', 'PACKED', 'SHIPPED')");
$pendingShipments = (int)$penShipStmt->fetchColumn();

// 2. Chart Data: Monthly Revenue (Last 6 Months)
$monthlyStmt = $db->query("
    SELECT DATE_FORMAT(MIN(order_date), '%b %Y') AS month_label,
           DATE_FORMAT(order_date, '%Y-%m') AS month_key,
           COALESCE(SUM(total_amount), 0) AS revenue
    FROM orders
    WHERE payment_status = 'PAID'
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month_key ASC
    LIMIT 6
");
$monthlyRows = $monthlyStmt->fetchAll();
$monthlyLabels = [];
$monthlyData = [];
foreach ($monthlyRows as $row) {
    $monthlyLabels[] = $row['month_label'];
    $monthlyData[] = (float)$row['revenue'];
}
// Fallback if low data
if (empty($monthlyLabels)) {
    $monthlyLabels = ['May 2026', 'Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026'];
    $monthlyData = [45000, 78000, 125000, 189000, $totalRevenue ?: 230000];
}

// 3. Chart Data: Orders by Status
$statusStmt = $db->query("
    SELECT order_status, COUNT(*) AS count
    FROM orders
    GROUP BY order_status
");
$statusRows = $statusStmt->fetchAll();
$statusLabels = [];
$statusData = [];
foreach ($statusRows as $row) {
    $statusLabels[] = ucwords(strtolower(str_replace('_', ' ', $row['order_status'])));
    $statusData[] = (int)$row['count'];
}

// 4. Chart Data: Top Brands
$brandStmt = $db->query("
    SELECT b.name AS brand_name, COALESCE(SUM(oi.quantity), 0) AS units_sold
    FROM brands b
    JOIN products p ON b.brand_id = p.brand_id
    JOIN order_items oi ON p.product_id = oi.product_id
    GROUP BY b.brand_id, b.name
    ORDER BY units_sold DESC
    LIMIT 5
");
$brandRows = $brandStmt->fetchAll();
$brandLabels = [];
$brandData = [];
foreach ($brandRows as $row) {
    $brandLabels[] = $row['brand_name'];
    $brandData[] = (int)$row['units_sold'];
}

// 5. Recent Orders (6 rows)
$recentOrders = $db->query("
    SELECT o.*, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 6
")->fetchAll();

// 6. Low Stock Warnings
$lowStockList = $db->query("
    SELECT p.*, b.name AS brand_name, s.company_name AS supplier_name
    FROM products p
    JOIN brands b ON p.brand_id = b.brand_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE p.stock_quantity <= p.minimum_stock
    ORDER BY p.stock_quantity ASC
    LIMIT 5
")->fetchAll();

// 7. Recent System Activity Logs
$recentLogs = $db->query("
    SELECT al.*, u.name AS user_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 6
")->fetchAll();
?>

<!-- KPI Metrics Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div class="metric-val fs-4"><?= format_inr($totalRevenue, false) ?></div>
            <div class="metric-label">Total Revenue</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="metric-val"><?= $totalOrders ?></div>
            <div class="metric-label">Total Orders</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="metric-val"><?= $totalCustomers ?></div>
            <div class="metric-label">Registered Customers</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <div class="metric-val"><?= $totalProducts ?></div>
            <div class="metric-label">Catalog Mobiles</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="metric-val text-danger"><?= $lowStockCount ?></div>
            <div class="metric-label">Low Stock Alerts</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
            <div class="metric-val"><?= $pendingShipments ?></div>
            <div class="metric-label">Pending Dispatches</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    
    <!-- Monthly Revenue Line Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-line text-primary me-2"></i> Monthly Revenue Trend (₹)</h6>
                    <small class="text-muted">Financial performance &amp; sales overview</small>
                </div>
                <span class="badge bg-light text-primary border">Chart.js Live</span>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Orders by Status Doughnut Chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-chart-pie text-success me-2"></i> Order Status Distribution</h6>
                    <small class="text-muted">Fulfillment Lifecycle</small>
                </div>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Recent Orders & Low Stock Section -->
<div class="row g-4 mb-4">
    
    <!-- Recent Orders Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Recent Customer Orders (P1.3)</h6>
                <a href="orders.php" class="small text-primary fw-bold text-decoration-none">Manage All Orders</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Fulfillment</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ord): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><code><?= htmlspecialchars($ord['order_number']) ?></code></div>
                                        <small class="text-muted"><?= date('d M, h:i A', strtotime($ord['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($ord['shipping_city']) ?></small>
                                    </td>
                                    <td class="fw-bold text-primary small"><?= format_inr($ord['total_amount']) ?></td>
                                    <td><?= get_payment_status_badge($ord['payment_status']) ?></td>
                                    <td><?= get_order_status_badge($ord['order_status']) ?></td>
                                    <td class="text-end">
                                        <a href="orders.php?order_id=<?= $ord['order_id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert Box -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> Low Inventory Alerts</h6>
                <a href="inventory.php" class="small text-danger fw-bold text-decoration-none">Stock Audit</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockList)): ?>
                    <div class="p-4 text-center text-muted small">All warehouse stocks are above threshold levels.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lowStockList as $lp): ?>
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($lp['product_name']) ?></div>
                                    <small class="text-muted">Supplier: <?= htmlspecialchars($lp['supplier_name']) ?></small>
                                    <div class="text-danger small fw-bold">Stock: <?= $lp['stock_quantity'] ?> units (Min: <?= $lp['minimum_stock'] ?>)</div>
                                </div>
                                <a href="inventory.php?adjust_id=<?= $lp['product_id'] ?>" class="btn btn-outline-danger btn-sm text-nowrap">
                                    Adjust
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Recent System Activity Audit Logs -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-shield-halved text-secondary me-2"></i> System Activity Audit Trail</h6>
        <a href="logs.php" class="small text-primary fw-bold text-decoration-none">View Full Audit Logs</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action Trigger</th>
                        <th>Entity</th>
                        <th>IP Address</th>
                        <th>Operational Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?= date('d M, H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($log['user_name'] ?? 'System Core') ?></strong></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td><code><?= htmlspecialchars($log['entity_type'] ?? 'GENERAL') ?></code></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></td>
                            <td class="text-secondary"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    initAdminCharts({
        monthlySales: {
            labels: <?= json_encode($monthlyLabels) ?>,
            data: <?= json_encode($monthlyData) ?>
        },
        orderStatus: {
            labels: <?= json_encode($statusLabels) ?>,
            data: <?= json_encode($statusData) ?>
        },
        brandSales: {
            labels: <?= json_encode($brandLabels) ?>,
            data: <?= json_encode($brandData) ?>
        }
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
