<?php
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();

try {
    $db->beginTransaction();

    $customerId = 7; // Rahul Sharma
    $p1 = $db->query("SELECT product_id, supplier_id, product_name, model, final_price FROM products WHERE product_id = 1")->fetch(PDO::FETCH_ASSOC);

    $orderNum = 'ORD-' . date('Ymd') . '-PENDING';
    $subtotal = (float)$p1['final_price'];
    $tax = round($subtotal * 0.18, 2);
    $total = $subtotal + $tax;

    // Delete previous pending if exists
    $oldOrd = $db->query("SELECT order_id FROM orders WHERE order_number = '$orderNum'")->fetchColumn();
    if ($oldOrd) {
        $db->exec("DELETE FROM payments WHERE order_id = $oldOrd");
        $db->exec("DELETE FROM order_items WHERE order_id = $oldOrd");
        $db->exec("DELETE FROM orders WHERE order_id = $oldOrd");
    }

    $stmt = $db->prepare("
        INSERT INTO orders (order_number, customer_id, order_date, subtotal, discount, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode)
        VALUES (?, ?, NOW(), ?, 0.00, ?, 0.00, ?, 'PENDING', 'PENDING_PAYMENT', 'Rahul Sharma', '9876543210', 'Flat 402, Royal Residency, Station Road', 'Valsad', 'Gujarat', '396001')
    ");
    $stmt->execute([$orderNum, $customerId, $subtotal, $tax, $total]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, supplier_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $itemStmt->execute([$orderId, $p1['product_id'], $p1['supplier_id'], 1, $subtotal, $subtotal]);

    $payStmt = $db->prepare("INSERT INTO payments (order_id, customer_id, transaction_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, 'CREDIT_CARD', 'PENDING')");
    $payStmt->execute([$orderId, $customerId, 'TXN-PENDING-' . time(), $total]);
    $paymentId = (int)$db->lastInsertId();

    $db->commit();
    echo "PENDING_ORDER_ID: $orderId, PAYMENT_ID: $paymentId, TOTAL: $total
";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "
";
}
