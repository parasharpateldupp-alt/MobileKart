<?php
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();

try {
    $db->beginTransaction();

    // 1. Get or create customer
    $user = $db->query("SELECT user_id FROM users WHERE role = 'CUSTOMER' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $db->exec("INSERT INTO users (name, email, phone, password_hash, role, status, address, city, state, pincode)
                   VALUES ('Rahul Sharma', 'customer@gmail.com', '9876543210', '" . password_hash('Customer@123', PASSWORD_BCRYPT) . "', 'CUSTOMER', 'ACTIVE', 'Flat 402, Royal Residency, Station Road', 'Valsad', 'Gujarat', '396001')");
        $customerId = (int)$db->lastInsertId();
    } else {
        $customerId = (int)$user['user_id'];
    }

    // 2. Get Products & Suppliers
    $p1 = $db->query("SELECT product_id, supplier_id, product_name, model, final_price FROM products WHERE product_id = 1")->fetch(PDO::FETCH_ASSOC);
    $p3 = $db->query("SELECT product_id, supplier_id, product_name, model, final_price FROM products WHERE product_id = 3")->fetch(PDO::FETCH_ASSOC);

    // 3. Order 1 (Delivered)
    $orderNum1 = 'ORD-' . date('Ymd') . '-1001';
    $subtotal1 = (float)$p1['final_price'];
    $tax1 = round($subtotal1 * 0.18, 2);
    $total1 = $subtotal1 + $tax1;

    $stmt = $db->prepare("
        INSERT INTO orders (order_number, customer_id, order_date, subtotal, discount, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode)
        VALUES (?, ?, DATE_SUB(NOW(), INTERVAL 5 DAY), ?, 0.00, ?, 0.00, ?, 'PAID', 'DELIVERED', 'Rahul Sharma', '9876543210', 'Flat 402, Royal Residency, Station Road', 'Valsad', 'Gujarat', '396001')
    ");
    $stmt->execute([$orderNum1, $customerId, $subtotal1, $tax1, $total1]);
    $orderId1 = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, supplier_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $itemStmt->execute([$orderId1, $p1['product_id'], $p1['supplier_id'], 1, $subtotal1, $subtotal1]);

    $payStmt = $db->prepare("INSERT INTO payments (order_id, customer_id, transaction_id, amount, payment_method, payment_status, gateway_response, payment_date) VALUES (?, ?, ?, ?, 'CREDIT_CARD', 'SUCCESS', ?, DATE_SUB(NOW(), INTERVAL 5 DAY))");
    $payStmt->execute([$orderId1, $customerId, 'TXN-98234710', $total1, '{"status":"SUCCESS","auth":"AUTH99281"}']);
    $paymentId1 = (int)$db->lastInsertId();

    $invNum1 = 'INV-' . date('Y') . '-0001';
    $invStmt = $db->prepare("INSERT INTO invoices (invoice_number, order_id, payment_id, customer_id, invoice_date, subtotal, discount, tax_amount, shipping_charge, total_amount, billing_address, status) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 5 DAY), ?, 0.00, ?, 0.00, ?, 'Flat 402, Royal Residency, Station Road, Valsad 396001', 'PAID')");
    $invStmt->execute([$invNum1, $orderId1, $paymentId1, $customerId, $subtotal1, $tax1, $total1]);
    $invoiceId1 = (int)$db->lastInsertId();

    $invItem = $db->prepare("INSERT INTO invoice_items (invoice_id, product_name, model, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    $invItem->execute([$invoiceId1, $p1['product_name'], $p1['model'], 1, $subtotal1, $subtotal1]);

    // Fulfillment 1
    $fulStmt = $db->prepare("INSERT INTO fulfillments (order_id, shipment_id, tracking_id, carrier, shipment_date, estimated_delivery, delivered_date, shipment_status, delivery_address) VALUES (?, ?, ?, 'Blue Dart Express', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'DELIVERED', 'Flat 402, Royal Residency, Station Road, Valsad 396001')");
    $fulStmt->execute([$orderId1, 'SHP-908123', 'BD-VAL-789421']);
    $fulId1 = (int)$db->lastInsertId();

    $evtStmt = $db->prepare("INSERT INTO shipment_events (fulfillment_id, status, location, description, event_time) VALUES (?, ?, ?, ?, ?)");
    $evtStmt->execute([$fulId1, 'ORDER_CONFIRMED', 'Valsad Hub', 'Order confirmed & invoice generated', date('Y-m-d H:i:s', strtotime('-5 days'))]);
    $evtStmt->execute([$fulId1, 'PROCESSING', 'Central Warehouse Surat', 'Inventory allocated & security packing', date('Y-m-d H:i:s', strtotime('-4 days'))]);
    $evtStmt->execute([$fulId1, 'SHIPPED', 'Surat Sorting Hub', 'Dispatched via Blue Dart Air Express', date('Y-m-d H:i:s', strtotime('-3 days'))]);
    $evtStmt->execute([$fulId1, 'OUT_FOR_DELIVERY', 'Valsad Local Center', 'Out for delivery with executive Rajesh', date('Y-m-d H:i:s', strtotime('-1 days'))]);
    $evtStmt->execute([$fulId1, 'DELIVERED', 'Customer Doorstep', 'Delivered successfully with OTP verification', date('Y-m-d H:i:s', strtotime('-1 days + 3 hours'))]);

    // 4. Order 2 (Shipped / In Transit)
    $orderNum2 = 'ORD-' . date('Ymd') . '-1002';
    $subtotal2 = (float)$p3['final_price'];
    $tax2 = round($subtotal2 * 0.18, 2);
    $total2 = $subtotal2 + $tax2;

    $stmt->execute([$orderNum2, $customerId, $subtotal2, $tax2, $total2]);
    $orderId2 = (int)$db->lastInsertId();

    $itemStmt->execute([$orderId2, $p3['product_id'], $p3['supplier_id'], 1, $subtotal2, $subtotal2]);
    $payStmt->execute([$orderId2, $customerId, 'TXN-98234789', $total2, '{"status":"SUCCESS","auth":"AUTH55123"}']);
    $paymentId2 = (int)$db->lastInsertId();

    $invNum2 = 'INV-' . date('Y') . '-0002';
    $invStmt->execute([$invNum2, $orderId2, $paymentId2, $customerId, $subtotal2, $tax2, $total2]);
    $invoiceId2 = (int)$db->lastInsertId();
    $invItem->execute([$invoiceId2, $p3['product_name'], $p3['model'], 1, $subtotal2, $subtotal2]);

    $fulStmt->execute([$orderId2, 'SHP-908124', 'BD-VAL-789422']);
    $fulId2 = (int)$db->lastInsertId();
    $evtStmt->execute([$fulId2, 'ORDER_CONFIRMED', 'Valsad Hub', 'Order confirmed & invoice generated', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $evtStmt->execute([$fulId2, 'PROCESSING', 'Central Warehouse Surat', 'Inventory allocated & security packing', date('Y-m-d H:i:s', strtotime('-1 days'))]);
    $evtStmt->execute([$fulId2, 'SHIPPED', 'Surat Sorting Hub', 'In transit to Valsad Delivery Hub', date('Y-m-d H:i:s', strtotime('-8 hours'))]);

    $db->commit();
    echo "SUCCESS: Seeded Orders ($orderId1, $orderId2), Invoices ($invoiceId1, $invoiceId2), Fulfillments ($fulId1, $fulId2)
";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "
";
}
