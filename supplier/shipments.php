<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Shipment & Dispatch Management (DFD P1.5)
 */

$pageTitle = "Shipment & Dispatch Management";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Shipment Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shipment'])) {
    require_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $carrier = trim($_POST['carrier'] ?? 'Ekart Logistics');
    $trackingId = trim($_POST['tracking_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'PACKED');
    $location = trim($_POST['location'] ?? 'Supplier Warehouse Hub');
    $description = trim($_POST['description'] ?? 'Package manifested and dispatched');

    if ($orderId > 0 && !empty($trackingId)) {
        try {
            $db->beginTransaction();

            // Check if fulfillment exists
            $fStmt = $db->prepare("SELECT fulfillment_id FROM fulfillments WHERE order_id = ?");
            $fStmt->execute([$orderId]);
            $fulfillmentId = $fStmt->fetchColumn();

            if (!$fulfillmentId) {
                $shipmentId = 'SHP-' . date('Ymd') . '-' . rand(100, 999);
                $insF = $db->prepare("
                    INSERT INTO fulfillments (order_id, shipment_id, tracking_id, carrier, shipment_status, delivery_address, manifest, created_at)
                    VALUES (?, ?, ?, ?, ?, (SELECT shipping_address FROM orders WHERE order_id = ?), ?, NOW())
                ");
                $insF->execute([$orderId, $shipmentId, $trackingId, $carrier, $newStatus, $orderId, $description]);
                $fulfillmentId = $db->lastInsertId();
            } else {
                $upF = $db->prepare("
                    UPDATE fulfillments 
                    SET carrier = ?, tracking_id = ?, shipment_status = ?, updated_at = NOW() 
                    WHERE fulfillment_id = ?
                ");
                $upF->execute([$carrier, $trackingId, $newStatus, $fulfillmentId]);
            }

            // Append shipment event
            $insEv = $db->prepare("
                INSERT INTO shipment_events (fulfillment_id, status, location, description, event_time)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insEv->execute([$fulfillmentId, $newStatus, $location, $description]);

            // Sync order status
            $upOrd = $db->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $upOrd->execute([$newStatus, $orderId]);

            $db->commit();
            set_flash('success', "Shipment details updated successfully for Order #{$orderId}. Status: {$newStatus}");
            header("Location: shipments.php");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash('danger', "Failed to update shipment: " . $e->getMessage());
        }
    }
}

// Fetch orders containing this supplier's products
$stmt = $db->prepare("
    SELECT o.order_id, o.order_number, o.order_date, o.order_status, o.payment_status, o.shipping_name, o.shipping_city, o.shipping_state,
           f.tracking_id, f.carrier, f.shipment_status,
           COUNT(oi.order_item_id) AS item_count
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN fulfillments f ON o.order_id = f.order_id
    WHERE oi.supplier_id = ? AND o.payment_status = 'PAID'
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$stmt->execute([$supplierId]);
$shipments = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Shipment &amp; Logistics Management</h4>
        <small class="text-muted">Order fulfillment, courier dispatch, and consignment milestone updates</small>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <?php if (empty($shipments)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-boxes-packing fa-3x mb-3 opacity-50"></i>
                <h5>No pending dispatches</h5>
                <p class="small">Paid customer orders requiring smartphone dispatch will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Order #</th>
                            <th>Recipient &amp; Destination</th>
                            <th>Carrier Logistics</th>
                            <th>Tracking Number</th>
                            <th>Fulfillment State</th>
                            <th class="text-end">Update Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shipments as $s): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><code><?= htmlspecialchars($s['order_number']) ?></code></div>
                                    <small class="text-muted"><?= date('d M Y', strtotime($s['order_date'])) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($s['shipping_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['shipping_city']) ?>, <?= htmlspecialchars($s['shipping_state']) ?></small>
                                </td>
                                <td>
                                    <span class="small fw-bold text-dark"><?= htmlspecialchars($s['carrier'] ?? 'Ekart Logistics') ?></span>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($s['tracking_id'] ?? 'Pending Assign') ?></code>
                                </td>
                                <td>
                                    <?= get_order_status_badge($s['shipment_status'] ?: $s['order_status']) ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#shipModal_<?= $s['order_id'] ?>">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Dispatch / Update
                                    </button>

                                    <!-- Update Modal -->
                                    <div class="modal fade text-start" id="shipModal_<?= $s['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0 shadow-lg rounded-4">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-truck-ramp-box me-2"></i> Update Order Consignment</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="shipments.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="update_shipment" value="1">
                                                    <input type="hidden" name="order_id" value="<?= $s['order_id'] ?>">

                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Logistics Carrier *</label>
                                                            <select name="carrier" class="form-select" required>
                                                                <option value="Ekart Logistics" <?= ($s['carrier'] ?? '') === 'Ekart Logistics' ? 'selected' : '' ?>>Ekart Logistics Express</option>
                                                                <option value="Blue Dart Express" <?= ($s['carrier'] ?? '') === 'Blue Dart Express' ? 'selected' : '' ?>>Blue Dart Express</option>
                                                                <option value="Delhivery Logistics" <?= ($s['carrier'] ?? '') === 'Delhivery Logistics' ? 'selected' : '' ?>>Delhivery Logistics</option>
                                                                <option value="DTDC India" <?= ($s['carrier'] ?? '') === 'DTDC India' ? 'selected' : '' ?>>DTDC India</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Tracking / Consignment ID *</label>
                                                            <input type="text" name="tracking_id" class="form-control" value="<?= htmlspecialchars($s['tracking_id'] ?: ('TRK-EKART-' . rand(10000000, 99999999))) ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Milestone Status *</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="PACKED">PACKED (Tamper-proof package sealed)</option>
                                                                <option value="SHIPPED">SHIPPED (Handed over to carrier)</option>
                                                                <option value="IN_TRANSIT">IN TRANSIT (Moving between hubs)</option>
                                                                <option value="OUT_FOR_DELIVERY">OUT FOR DELIVERY (With courier agent)</option>
                                                                <option value="DELIVERED">DELIVERED (Signed with recipient OTP)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Current Location Hub</label>
                                                            <input type="text" name="location" class="form-control" value="Supplier Fulfillment Hub, Bengaluru" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Operational Log Remark</label>
                                                            <input type="text" name="description" class="form-control" value="Consignment processed and scanned at sorting unit">
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary fw-bold">Save Tracking Event</button>
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
