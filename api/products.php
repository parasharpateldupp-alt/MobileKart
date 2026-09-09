<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Products Search Autocomplete API
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';

$action = $_GET['action'] ?? 'search';
$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->prepare("
        SELECT p.product_id, p.product_name, p.model, p.final_price, p.price, p.ram, p.storage, p.image, b.name as brand_name
        FROM products p
        JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.status = 'ACTIVE' AND (p.product_name LIKE ? OR p.model LIKE ? OR b.name LIKE ?)
        LIMIT 6
    ");
    $term = "%{$query}%";
    $stmt->execute([$term, $term, $term]);
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
