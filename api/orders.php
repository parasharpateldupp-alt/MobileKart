<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Orders API Endpoint (DFD P1.3)
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = current_user();
$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $orderId = (int)($_GET['order_id'] ?? 0);
    $orderNumber = trim($_GET['order_number'] ?? '');

    if (!$orderId && !$orderNumber) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID or number is required']);
        exit;
    }

    $where = [];
    $params = [];

    if ($orderId > 0) {
        $where[] = "o.order_id = ?";
        $params[] = $orderId;
    } else {
        $where[] = "o.order_number = ?";
        $params[] = $orderNumber;
    }

    // Role security: customer can only view their own order
    if ($user['role'] === 'CUSTOMER') {
        $where[] = "o.customer_id = ?";
        $params[] = $user['id'];
    }

    $whereSql = implode(" AND ", $where);
    $stmt = $db->prepare("
        SELECT o.*, f.tracking_id, f.carrier, f.shipment_status, f.estimated_delivery
        FROM orders o
        LEFT JOIN fulfillments f ON o.order_id = f.order_id
        WHERE {$whereSql}
    ");
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
        exit;
    }

    // Get order items
    $itemStmt = $db->prepare("
        SELECT oi.*, p.name as product_name, p.model, p.image_url, b.name as brand_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN brands b ON p.brand_id = b.brand_id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$order['order_id']]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
