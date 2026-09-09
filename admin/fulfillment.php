<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin Fulfillment & Consignment Tracking (DFD P1.5)
 */

$pageTitle = "Fulfillment & Tracking";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Shipment Status Progression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['advance_shipment'])) {
    require_csrf();
    $fulfillmentId = (int)($_POST['fulfillment_id'] ?? 0);
    $newStatus     = trim($_POST['new_status'] ?? '');
    $location      = trim($_POST['location'] ?? 'Fulfillment Center');
    $description   = trim($_POST['description'] ?? 'Consignment updated');

    if ($fulfillmentId > 0 && !empty($newStatus)) {
        $db->beginTransaction();

        $upF = $db->prepare("UPDATE fulfillments SET shipment_status = ?, updated_at = NOW() WHERE fulfillment_id = ?");
        $upF->execute([$newStatus, $fulfillmentId]);

        // Get order_id
        $oId = $db->prepare("SELECT order_id FROM fulfillments WHERE fulfillment_id = ?");
        $oId->execute([$fulfillmentId]);
        $orderId = $oId->fetchColumn();

        if ($orderId) {
            $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?")->execute([$newStatus, $orderId]);
        }

        if ($newStatus === 'DELIVERED') {
            $db->prepare("UPDATE fulfillments SET delivered_date = NOW() WHERE fulfillment_id = ?")->execute([$fulfillmentId]);
        }

        // Append event
        $insEv = $db->prepare("
            INSERT INTO shipment_events (fulfillment_id, status, location, description, event_time)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insEv->execute([$fulfillmentId, $newStatus, $location, $description]);

        log_activity($user['id'], 'ADMIN_UPDATED_SHIPMENT', 'FULFILLMENT', $fulfillmentId, ['status' => $newStatus]);
        $db->commit();
        set_flash('success', "Consignment status advanced to {$newStatus}.");
        header("Location: fulfillment.php");
        exit;
    }
}

// Fetch all fulfillments
$sql = "
    SELECT f.*, o.order_number, o.order_date, o.shipping_name, o.shipping_city, o.shipping_state,
           (SELECT COUNT(*) FROM order_items WHERE order_id = f.order_id) AS item_count
    FROM fulfillments f
    JOIN orders o ON f.order_id = o.order_id
    ORDER BY f.fulfillment_id DESC
";
$fulfillments = $db->query($sql)->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Master Fulfillment &amp; Consignment Pipeline</h4>
        <small class="text-muted">Courier assignments, manifest dispatching, and delivery milestone tracking</small>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Consignment ID</th>
                        <th>Carrier &amp; Tracking</th>
                        <th>Order Ref</th>
                        <th>Destination</th>
                        <th>Current Milestone</th>
                        <th class="text-end">Advance Fulfillment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfillments as $f): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><code><?= htmlspecialchars($f['shipment_id']) ?></code></div>
                                <small class="text-muted"><?= date('d M Y', strtotime($f['created_at'])) ?></small>
                            </td>
                            <td>
                                <div><strong class="text-primary"><?= htmlspecialchars($f['carrier']) ?></strong></div>
                                <code><?= htmlspecialchars($f['tracking_id']) ?></code>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($f['order_number']) ?></code>
                                <div class="small text-muted"><?= $f['item_count'] ?> items</div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($f['shipping_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($f['shipping_city']) ?>, <?= htmlspecialchars($f['shipping_state']) ?></small>
                            </td>
                            <td>
                                <?= get_order_status_badge($f['shipment_status']) ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#advanceModal_<?= $f['fulfillment_id'] ?>">
                                    <i class="fa-solid fa-arrows-split-up-and-left me-1"></i> Update Stage
                                </button>
                                <a href="../customer/tracking.php?order_id=<?= $f['order_id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Live Timeline">
                                    <i class="fa-solid fa-route"></i>
                                </a>

                                <!-- Advance Stage Modal -->
                                <div class="modal fade text-start" id="advanceModal_<?= $f['fulfillment_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content border-0 shadow rounded-4">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title fw-bold">Advance Fulfillment Milestone</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="advance_shipment" value="1">
                                                <input type="hidden" name="fulfillment_id" value="<?= $f['fulfillment_id'] ?>">

                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Target Milestone *</label>
                                                        <select name="new_status" class="form-select" required>
                                                            <option value="PROCESSING" <?= $f['shipment_status'] === 'PROCESSING' ? 'selected' : '' ?>>PROCESSING (Picking items)</option>
                                                            <option value="PACKED" <?= $f['shipment_status'] === 'PACKED' ? 'selected' : '' ?>>PACKED (Package sealed)</option>
                                                            <option value="SHIPPED" <?= $f['shipment_status'] === 'SHIPPED' ? 'selected' : '' ?>>SHIPPED (Handed over)</option>
                                                            <option value="IN_TRANSIT" <?= $f['shipment_status'] === 'IN_TRANSIT' ? 'selected' : '' ?>>IN TRANSIT (Inter-hub transit)</option>
                                                            <option value="OUT_FOR_DELIVERY" <?= $f['shipment_status'] === 'OUT_FOR_DELIVERY' ? 'selected' : '' ?>>OUT FOR DELIVERY (Last mile)</option>
                                                            <option value="DELIVERED" <?= $f['shipment_status'] === 'DELIVERED' ? 'selected' : '' ?>>DELIVERED (Signed OTP)</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Facility / Hub Location *</label>
                                                        <input type="text" name="location" class="form-control" value="Bengaluru Sorting Hub" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Milestone Operational Description *</label>
                                                        <input type="text" name="description" class="form-control" value="Scanned and processed through automated sorter" required>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary fw-bold">Update Logistics Stage</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
