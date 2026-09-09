<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Consignment Tracking API Endpoint (DFD P1.5)
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$db = get_db_connection();
$trackingId = trim($_GET['tracking_id'] ?? '');
$orderNumber = trim($_GET['order_number'] ?? '');

if (!$trackingId && !$orderNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'tracking_id or order_number required']);
    exit;
}

if ($trackingId) {
    $stmt = $db->prepare("
        SELECT f.*, o.order_number, o.order_date, o.shipping_name, o.shipping_city, o.shipping_state
        FROM fulfillments f
        JOIN orders o ON f.order_id = o.order_id
        WHERE f.tracking_id = ?
    ");
    $stmt->execute([$trackingId]);
} else {
    $stmt = $db->prepare("
        SELECT f.*, o.order_number, o.order_date, o.shipping_name, o.shipping_city, o.shipping_state
        FROM fulfillments f
        JOIN orders o ON f.order_id = o.order_id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
}

$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Tracking information not found']);
    exit;
}

// Fetch checkpoint events
$evStmt = $db->prepare("
    SELECT event_id, status, location, description, event_time
    FROM shipment_events
    WHERE fulfillment_id = ?
    ORDER BY event_time ASC
");
$evStmt->execute([$shipment['fulfillment_id']]);
$events = $evStmt->fetchAll(PDO::FETCH_ASSOC);

// Map status progress percentage
$statusSteps = [
    'ORDER_CONFIRMED' => 20,
    'PROCESSING' => 35,
    'PACKED' => 50,
    'SHIPPED' => 70,
    'IN_TRANSIT' => 80,
    'OUT_FOR_DELIVERY' => 90,
    'DELIVERED' => 100,
    'CANCELLED' => 0
];
$progress = $statusSteps[$shipment['shipment_status']] ?? 25;

echo json_encode([
    'success' => true,
    'fulfillment' => [
        'tracking_id' => $shipment['tracking_id'],
        'order_number' => $shipment['order_number'],
        'carrier' => $shipment['carrier'],
        'status' => $shipment['shipment_status'],
        'progress_pct' => $progress,
        'estimated_delivery' => $shipment['estimated_delivery'],
        'delivered_date' => $shipment['delivered_date'],
        'destination' => $shipment['shipping_city'] . ', ' . $shipment['shipping_state'],
        'events' => $events
    ]
]);
