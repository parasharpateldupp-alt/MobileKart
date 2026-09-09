<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Dashboard (DFD P1.2 & P1.5)
 */

$pageTitle = "Supplier Dashboard";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Supplier KPIs
$pCount = $db->prepare("SELECT COUNT(*), COALESCE(SUM(stock_quantity), 0) FROM products WHERE supplier_id = ?");
$pCount->execute([$supplierId]);
[$totalProducts, $totalStock] = $pCount->fetch(PDO::FETCH_NUM);

$lowStockStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ? AND stock_quantity <= minimum_stock");
$lowStockStmt->execute([$supplierId]);
$lowStockCount = (int)$lowStockStmt->fetchColumn();

$salesStmt = $db->prepare("
    SELECT COUNT(DISTINCT oi.order_id), COALESCE(SUM(oi.subtotal), 0) 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE oi.supplier_id = ? AND o.payment_status = 'PAID'
");
$salesStmt->execute([$supplierId]);
[$ordersCount, $grossSales] = $salesStmt->fetch(PDO::FETCH_NUM);

$shipStmt = $db->prepare("
    SELECT COUNT(DISTINCT oi.order_id) 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.order_id 
    WHERE oi.supplier_id = ? AND o.order_status IN ('ORDER_CONFIRMED', 'PROCESSING', 'PACKED')
");
$shipStmt->execute([$supplierId]);
$pendingShipments = (int)$shipStmt->fetchColumn();

// Fetch Low Stock Mobiles
$lsList = $db->prepare("
    SELECT p.*, b.name AS brand_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.brand_id 
    WHERE p.supplier_id = ? AND p.stock_quantity <= p.minimum_stock 
    ORDER BY p.stock_quantity ASC 
    LIMIT 5
");
$lsList->execute([$supplierId]);
$lowStockProducts = $lsList->fetchAll();

// Fetch Recent Purchase Orders for Supplier
$recentOrders = $db->prepare("
    SELECT oi.order_id, oi.quantity, oi.subtotal, o.order_number, o.order_date, o.order_status, o.shipping_name, p.product_name
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.supplier_id = ?
    ORDER BY o.order_date DESC
    LIMIT 6
");
$recentOrders->execute([$supplierId]);
$orders = $recentOrders->fetchAll();
?>

<!-- Metric Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <div class="metric-val"><?= $totalProducts ?></div>
            <div class="metric-label">My Products</div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="metric-val"><?= number_format($totalStock) ?></div>
            <div class="metric-label">Warehouse Units</div>
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
            <div class="metric-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="metric-val"><?= $ordersCount ?></div>
            <div class="metric-label">Purchase Orders</div>
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

    <div class="col-6 col-md-4 col-xl-2">
        <div class="metric-card">
            <div class="metric-icon bg-purple bg-opacity-10 text-purple" style="color: #9333ea;">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div class="metric-val fs-4"><?= format_inr($grossSales, false) ?></div>
            <div class="metric-label">Gross Revenue</div>
        </div>
    </div>
</div>

<!-- Low Stock Warnings & Recent Orders -->
<div class="row g-4">
    
    <!-- Low Stock Alerts Section -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-bell me-2"></i> Low-Stock Inventory Alerts</h6>
                <a href="restock.php" class="small text-danger fw-bold text-decoration-none">Manage All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                        <p class="mb-0">All smartphone inventory levels are healthy!</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lowStockProducts as $p): ?>
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="badge bg-light text-secondary border small mb-1"><?= htmlspecialchars($p['brand_name']) ?></span>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($p['product_name']) ?></div>
                                    <div class="text-danger small fw-bold">Stock: <?= $p['stock_quantity'] ?> units (Min: <?= $p['minimum_stock'] ?>)</div>
                                </div>
                                <a href="inventory.php?restock_id=<?= $p['product_id'] ?>" class="btn btn-outline-danger btn-sm text-nowrap">
                                    <i class="fa-solid fa-plus me-1"></i> Restock
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Purchase Orders Section -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> Recent Purchase Orders</h6>
                <a href="orders.php" class="small text-primary fw-bold text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="p-4 text-center text-muted small">No orders recorded yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Order #</th>
                                    <th>Phone Model</th>
                                    <th>Customer</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($ord['order_number']) ?></code></td>
                                        <td class="small fw-semibold"><?= htmlspecialchars($ord['product_name']) ?></td>
                                        <td class="small"><?= htmlspecialchars($ord['shipping_name']) ?></td>
                                        <td class="text-center fw-bold"><?= $ord['quantity'] ?></td>
                                        <td class="text-end fw-bold text-dark small"><?= format_inr($ord['subtotal']) ?></td>
                                        <td><?= get_order_status_badge($ord['order_status']) ?></td>
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
