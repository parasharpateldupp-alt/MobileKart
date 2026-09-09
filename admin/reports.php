<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Sales & Financial Reports Engine (DFD External Entity: Analytics & Reports)
 */

$pageTitle = "Sales & Financial Reports";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();
$db = get_db_connection();

// Date Range presets & filters
$preset = trim($_GET['preset'] ?? 'month');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');
$orderStatus = trim($_GET['order_status'] ?? '');

$today = date('Y-m-d');

if ($preset === 'today') {
    $startDate = $today;
    $endDate = $today;
} elseif ($preset === 'last7') {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = $today;
} elseif ($preset === 'month') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($preset === 'year') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
} elseif ($preset === 'all') {
    $startDate = '2020-01-01';
    $endDate = date('Y-m-d');
} else {
    // Custom range
    if (!$startDate) $startDate = date('Y-m-01');
    if (!$endDate) $endDate = $today;
}

// Base WHERE conditions
$where = ["DATE(o.order_date) >= ? AND DATE(o.order_date) <= ?"];
$params = [$startDate, $endDate];

if ($paymentStatus) {
    $where[] = "o.payment_status = ?";
    $params[] = $paymentStatus;
}
if ($orderStatus) {
    $where[] = "o.order_status = ?";
    $params[] = $orderStatus;
}

$whereSql = implode(" AND ", $where);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_report_' . date('Ymd_His') . '.csv');
    
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8 compatibility
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($out, [
        'Order Number', 'Date', 'Customer Name', 'Customer Email', 'Phone',
        'Items Count', 'Subtotal (INR)', 'Discount (INR)', 'GST Tax (INR)', 'Shipping (INR)',
        'Total Amount (INR)', 'Payment Status', 'Order Status', 'Delivery City', 'Delivery State'
    ]);
    
    $exportSql = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                         (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as items_count
                  FROM orders o
                  JOIN users u ON o.customer_id = u.user_id
                  WHERE {$whereSql}
                  ORDER BY o.order_date DESC";
    $stmt = $db->prepare($exportSql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['order_number'],
            $row['order_date'],
            $row['customer_name'],
            $row['customer_email'],
            $row['shipping_phone'],
            $row['items_count'],
            $row['subtotal'],
            $row['discount'],
            $row['tax'],
            $row['shipping_charge'],
            $row['total_amount'],
            $row['payment_status'],
            $row['order_status'],
            $row['shipping_city'],
            $row['shipping_state']
        ]);
    }
    fclose($out);
    exit;
}

// 1. KPI Aggregates
$kpiSql = "SELECT 
            COUNT(o.order_id) as total_orders,
            COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.total_amount ELSE 0 END), 0) as paid_revenue,
            COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.tax ELSE 0 END), 0) as total_tax,
            COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.discount ELSE 0 END), 0) as total_discounts,
            COALESCE(AVG(CASE WHEN o.payment_status = 'PAID' THEN o.total_amount ELSE NULL END), 0) as avg_order_val
           FROM orders o
           WHERE {$whereSql}";
$kpiStmt = $db->prepare($kpiSql);
$kpiStmt->execute($params);
$kpi = $kpiStmt->fetch(PDO::FETCH_ASSOC);

// Total items sold in period
$itemsSoldSql = "SELECT COALESCE(SUM(oi.quantity), 0) as total_items
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.order_id
                 WHERE {$whereSql} AND o.payment_status = 'PAID'";
$itemsSoldStmt = $db->prepare($itemsSoldSql);
$itemsSoldStmt->execute($params);
$totalItemsSold = (int)$itemsSoldStmt->fetchColumn();

// 2. Brand Performance Breakdown
$brandSql = "SELECT b.name as brand_name, 
                    COUNT(DISTINCT o.order_id) as orders_count,
                    SUM(oi.quantity) as units_sold,
                    SUM(oi.subtotal) as gross_brand_sales
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             JOIN brands b ON p.brand_id = b.brand_id
             JOIN orders o ON oi.order_id = o.order_id
             WHERE {$whereSql} AND o.payment_status = 'PAID'
             GROUP BY b.brand_id, b.name
             ORDER BY gross_brand_sales DESC";
$brandStmt = $db->prepare($brandSql);
$brandStmt->execute($params);
$brandReport = $brandStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Payment Method Breakdown
$paymentMethodSql = "SELECT p.payment_method, 
                            COUNT(p.payment_id) as tx_count,
                            SUM(p.amount) as tx_total
                     FROM payments p
                     JOIN orders o ON p.order_id = o.order_id
                     WHERE {$whereSql} AND p.payment_status = 'SUCCESS'
                     GROUP BY p.payment_method";
$pmStmt = $db->prepare($paymentMethodSql);
$pmStmt->execute($params);
$paymentMethods = $pmStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Detailed Orders Report
$ordersSql = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                     (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as items_count
              FROM orders o
              JOIN users u ON o.customer_id = u.user_id
              WHERE {$whereSql}
              ORDER BY o.order_date DESC
              LIMIT 100";
$ordersStmt = $db->prepare($ordersSql);
$ordersStmt->execute($params);
$ordersList = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                <li class="breadcrumb-item active">Financial Reports</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0">Sales &amp; Revenue Reports</h3>
        <p class="text-muted small mb-0">
            <i class="fa-solid fa-calendar-days text-primary me-1"></i>
            Generated for period <?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="reports.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success">
            <i class="fa-solid fa-file-csv me-1"></i> Export to CSV
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="reports.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Date Preset</label>
                <select name="preset" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" <?= $preset === 'today' ? 'selected' : '' ?>>Today (<?= date('d M') ?>)</option>
                    <option value="last7" <?= $preset === 'last7' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= $preset === 'month' ? 'selected' : '' ?>>This Month (<?= date('M Y') ?>)</option>
                    <option value="year" <?= $preset === 'year' ? 'selected' : '' ?>>This Year (<?= date('Y') ?>)</option>
                    <option value="all" <?= $preset === 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="custom" <?= $preset === 'custom' ? 'selected' : '' ?>>Custom Range...</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Payment Status</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Payments</option>
                    <option value="PAID" <?= $paymentStatus === 'PAID' ? 'selected' : '' ?>>Paid Only</option>
                    <option value="PENDING" <?= $paymentStatus === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="FAILED" <?= $paymentStatus === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Order Status</label>
                <select name="order_status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="DELIVERED" <?= $orderStatus === 'DELIVERED' ? 'selected' : '' ?>>Delivered</option>
                    <option value="SHIPPED" <?= $orderStatus === 'SHIPPED' ? 'selected' : '' ?>>Shipped</option>
                    <option value="PROCESSING" <?= $orderStatus === 'PROCESSING' ? 'selected' : '' ?>>Processing</option>
                    <option value="CANCELLED" <?= $orderStatus === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Net Paid Revenue</div>
                        <h4 class="fw-bold text-success mb-0 mt-1"><?= format_inr($kpi['paid_revenue']) ?></h4>
                        <small class="text-muted">Confirmed Collections</small>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-indian-rupee-sign fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Orders</div>
                        <h4 class="fw-bold text-primary mb-0 mt-1"><?= number_format($kpi['total_orders']) ?></h4>
                        <small class="text-muted">Processed in Filter</small>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-cart-shopping fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Units Dispatched</div>
                        <h4 class="fw-bold text-warning-emphasis mb-0 mt-1"><?= number_format($totalItemsSold) ?></h4>
                        <small class="text-muted">Smartphones Sold</small>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis p-3 rounded-circle">
                        <i class="fa-solid fa-mobile-screen fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Avg Order Value</div>
                        <h4 class="fw-bold text-info mb-0 mt-1"><?= format_inr($kpi['avg_order_val']) ?></h4>
                        <small class="text-muted">GST Collected: <?= format_inr($kpi['total_tax']) ?></small>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-receipt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Brand Revenue Breakdown -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0">Brand Performance Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Brand</th>
                                <th class="text-center">Orders</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Gross Revenue</th>
                                <th class="text-end">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brandReport)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No sales recorded for this date range.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $grandBrandTotal = array_sum(array_column($brandReport, 'gross_brand_sales'));
                                foreach ($brandReport as $br): 
                                    $pct = $grandBrandTotal > 0 ? round(($br['gross_brand_sales'] / $grandBrandTotal) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <i class="fa-solid fa-tag text-primary me-2"></i><?= htmlspecialchars($br['brand_name']) ?>
                                        </td>
                                        <td class="text-center"><?= number_format($br['orders_count']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                                <?= number_format($br['units_sold']) ?> units
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-success"><?= format_inr($br['gross_brand_sales']) ?></td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; max-width: 60px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%"></div>
                                                </div>
                                                <small class="text-muted fw-bold"><?= $pct ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Channel Breakdown -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0">Payment Gateway Distribution (D4)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($paymentMethods)): ?>
                    <p class="text-muted text-center py-4">No payment transactions found in selected filter.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $totalTxVal = array_sum(array_column($paymentMethods, 'tx_total'));
                        foreach ($paymentMethods as $pm): 
                            $pmPct = $totalTxVal > 0 ? round(($pm['tx_total'] / $totalTxVal) * 100, 1) : 0;
                            $icon = match($pm['payment_method']) {
                                'UPI' => 'fa-qrcode text-success',
                                'DEBIT_CARD' => 'fa-credit-card text-primary',
                                'CREDIT_CARD' => 'fa-id-card text-warning',
                                'NET_BANKING' => 'fa-building-columns text-info',
                                default => 'fa-wallet text-secondary'
                            };
                        ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold"><i class="fa-solid <?= $icon ?> me-2"></i><?= htmlspecialchars($pm['payment_method']) ?></span>
                                    <span class="fw-bold text-dark"><?= format_inr($pm['tx_total']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                                    <span><?= number_format($pm['tx_count']) ?> successful transactions</span>
                                    <span><?= $pmPct ?>% of total</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pmPct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Orders Log -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Transactions Ledger (Showing latest <?= count($ordersList) ?> records)</h6>
        <span class="badge bg-light text-dark border">Verified Transactions</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th class="text-center">Items</th>
                        <th>Subtotal</th>
                        <th>GST (18%)</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ordersList)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No orders match the selected criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ordersList as $row): ?>
                            <tr>
                                <td class="fw-bold text-primary font-monospace">
                                    <a href="<?= BASE_URL ?>/customer/order-details.php?id=<?= $row['order_id'] ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars($row['order_number']) ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?= date('d M Y, H:i', strtotime($row['order_date'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['shipping_city'] . ', ' . $row['shipping_state']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $row['items_count'] ?></span>
                                </td>
                                <td class="small"><?= format_inr($row['subtotal']) ?></td>
                                <td class="small text-muted"><?= format_inr($row['tax']) ?></td>
                                <td class="fw-bold text-success"><?= format_inr($row['total_amount']) ?></td>
                                <td>
                                    <?php if ($row['payment_status'] === 'PAID'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">PAID</span>
                                    <?php elseif ($row['payment_status'] === 'FAILED'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">FAILED</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= $row['payment_status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= render_status_badge($row['order_status']) ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/customer/invoices.php?order_id=<?= $row['order_id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Tax Invoice">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
