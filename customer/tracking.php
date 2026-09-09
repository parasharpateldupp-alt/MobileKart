<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Order Tracking & Fulfillment Timeline (DFD P1.5)
 */

$pageTitle = "Track Smartphone Consignment - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$trackingQuery = trim($_GET['tracking_id'] ?? '');

$db = get_db_connection();
$fulfillment = null;
$events = [];
$order = null;

if ($orderId > 0) {
    $stmt = $db->prepare("
        SELECT f.*, o.order_number, o.order_date, o.order_status, o.shipping_name, o.shipping_city, o.shipping_state, o.total_amount
        FROM fulfillments f
        JOIN orders o ON f.order_id = o.order_id
        WHERE f.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $fulfillment = $stmt->fetch();
} elseif (!empty($trackingQuery)) {
    $stmt = $db->prepare("
        SELECT f.*, o.order_number, o.order_date, o.order_status, o.shipping_name, o.shipping_city, o.shipping_state, o.total_amount
        FROM fulfillments f
        JOIN orders o ON f.order_id = o.order_id
        WHERE f.tracking_id = ? OR o.order_number = ?
    ");
    $stmt->execute([$trackingQuery, $trackingQuery]);
    $fulfillment = $stmt->fetch();
}

if ($fulfillment) {
    $eStmt = $db->prepare("SELECT * FROM shipment_events WHERE fulfillment_id = ? ORDER BY event_time ASC");
    $eStmt->execute([$fulfillment['fulfillment_id']]);
    $events = $eStmt->fetchAll();
}

// Stages definition
$stages = [
    'ORDER_CONFIRMED'  => ['label' => 'Order Confirmed', 'icon' => 'fa-check'],
    'PROCESSING'       => ['label' => 'Processing', 'icon' => 'fa-gears'],
    'PACKED'           => ['label' => 'Packed', 'icon' => 'fa-box'],
    'SHIPPED'          => ['label' => 'Shipped', 'icon' => 'fa-truck'],
    'IN_TRANSIT'       => ['label' => 'In Transit', 'icon' => 'fa-route'],
    'OUT_FOR_DELIVERY' => ['label' => 'Out for Delivery', 'icon' => 'fa-motorcycle'],
    'DELIVERED'        => ['label' => 'Delivered', 'icon' => 'fa-house-circle-check']
];

$currentStatus = $fulfillment['shipment_status'] ?? 'ORDER_CONFIRMED';
$stageKeys = array_keys($stages);
$currentIndex = array_search($currentStatus, $stageKeys);
if ($currentIndex === false) $currentIndex = 0;

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/customer/orders.php">Orders</a></li>
            <li class="breadcrumb-item active">Shipment Tracking</li>
        </ol>
    </nav>

    <!-- Search tracking form -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-truck-fast text-warning me-2"></i> Live Consignment Tracker</h4>
                <p class="text-muted small mb-0">Real-time delivery milestones &amp; courier tracking updates</p>
            </div>
            <div class="col-lg-6">
                <form action="tracking.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="tracking_id" class="form-control" placeholder="Enter your Tracking ID" value="<?= htmlspecialchars($trackingQuery ?: ($fulfillment['tracking_id'] ?? '')) ?>" required>
                    <button type="submit" class="btn btn-fk-primary text-nowrap px-4 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Track
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($fulfillment): ?>
        <!-- Tracking Summary Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between border-bottom pb-3 mb-4 gap-3">
                <div>
                    <span class="badge bg-light text-primary border mb-1"><?= htmlspecialchars($fulfillment['carrier']) ?></span>
                    <h5 class="fw-bold text-dark mb-0">Tracking ID: <code><?= htmlspecialchars($fulfillment['tracking_id']) ?></code></h5>
                    <small class="text-muted">Order Ref: <strong><?= htmlspecialchars($fulfillment['order_number']) ?></strong> • Destination: <?= htmlspecialchars($fulfillment['shipping_city']) ?>, <?= htmlspecialchars($fulfillment['shipping_state']) ?></small>
                </div>
                <div class="text-md-end">
                    <span class="badge bg-success py-2 px-3 fs-6">
                        <?= htmlspecialchars($stages[$currentStatus]['label'] ?? $currentStatus) ?>
                    </span>
                    <?php if ($fulfillment['estimated_delivery']): ?>
                        <div class="small text-muted mt-1">Est. Delivery: <strong><?= date('d M Y', strtotime($fulfillment['estimated_delivery'])) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Visual Stepper Progress Bar (P1.5 Workflow) -->
            <div class="tracking-stepper d-none d-md-flex">
                <?php foreach ($stages as $key => $data): 
                    $idx = array_search($key, $stageKeys);
                    $stepClass = '';
                    if ($idx < $currentIndex) {
                        $stepClass = 'completed';
                    } elseif ($idx === $currentIndex) {
                        $stepClass = 'active';
                    }
                ?>
                    <div class="tracking-step <?= $stepClass ?>">
                        <div class="tracking-icon">
                            <i class="fa-solid <?= $data['icon'] ?>"></i>
                        </div>
                        <div class="tracking-label"><?= $data['label'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mobile Stepper Indicator -->
            <div class="d-md-none alert alert-light border p-3 mb-4 text-center">
                <div class="fw-bold text-primary mb-1">Current Milestone:</div>
                <div class="fs-5 fw-bold text-dark"><i class="fa-solid <?= $stages[$currentStatus]['icon'] ?? 'fa-box' ?> me-2 text-primary"></i> <?= $stages[$currentStatus]['label'] ?? $currentStatus ?></div>
            </div>

            <!-- Shipment Activity History Timeline -->
            <h6 class="fw-bold text-dark mb-3 mt-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Shipment Event Milestones</h6>
            <?php if (empty($events)): ?>
                <p class="text-muted small">Package manifested; first dispatch scan pending.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Timestamp</th>
                                <th style="width: 20%;">Milestone Stage</th>
                                <th style="width: 25%;">Facility / Location</th>
                                <th style="width: 30%;">Operational Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($events) as $ev): ?>
                                <tr>
                                    <td><small class="fw-bold text-dark"><?= date('d M Y, h:i A', strtotime($ev['event_time'])) ?></small></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <?= htmlspecialchars($stages[$ev['status']]['label'] ?? $ev['status']) ?>
                                        </span>
                                    </td>
                                    <td><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($ev['location']) ?></td>
                                    <td class="small text-secondary"><?= htmlspecialchars($ev['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($fulfillment['manifest'])): ?>
                <div class="mt-4 p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block fw-bold text-uppercase">Logistics Manifest Remarks:</small>
                    <span class="small text-secondary"><?= htmlspecialchars($fulfillment['manifest']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($orderId > 0 || !empty($trackingQuery)): ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white">
            <i class="fa-solid fa-magnifying-glass-location fa-4x text-muted mb-3 opacity-50"></i>
            <h5 class="fw-bold text-dark">No shipment records found</h5>
            <p class="text-muted small">Please verify the Tracking ID or Order ID and try again.</p>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white">
            <i class="fa-solid fa-truck-ramp-box fa-4x text-primary mb-3 opacity-75"></i>
            <h5 class="fw-bold text-dark">Enter your consignment tracking number above</h5>
            <p class="text-muted small mb-0">You can find your tracking number on your Order Details page or receipt.</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
