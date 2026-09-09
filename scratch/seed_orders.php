<?php
require_once dirname(__DIR__) . '/config/database.php';
$db = get_db_connection();

try {
    $db->beginTransaction();

    // Ensure customer user exists
    $user = $db->query("SELECT user_id FROM users WHERE role = 'CUSTOMER' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $db->exec("INSERT INTO users (name, email, password_hash, role, phone, address, city, state, pincode, is_active)
                   VALUES ('Rahul Sharma', 'customer@gmail.com', '" . password_hash('Customer@123', PASSWORD_BCRYPT) . "', 'CUSTOMER', '9876543210', 'Flat 402, Royal Residency, City Center', 'Valsad', 'Gujarat', '396001', 1)");
        $customerId = $db->lastInsertId();
    } else {
        $customerId = $user['user_id'];
    }

    // Get products
    $prod1 = $db->query("SELECT product_id, name, price FROM products WHERE product_id = 1")->fetch(PDO::FETCH_ASSOC);
    $prod3 = $db->query("SELECT product_id, name, price FROM products WHERE product_id = 3")->fetch(PDO::FETCH_ASSOC);

    if (!$prod1) {
        $prod1 = ['product_id' => 1, 'name' => 'Samsung Galaxy S24 Ultra 5G', 'price' => 121499.00];
    }
    if (!$prod3) {
        $prod3 = ['product_id' => 3, 'name' => 'OnePlus 12 5G', 'price' => 64399.00];
    }

    // Insert Order 1 (Delivered)
    $orderNum1 = 'ORD-' . date('Ymd') . '-1001';
    $subtotal1 = 121499.00;
    $tax1 = round($subtotal1 * 0.18, 2);
    $total1 = $subtotal1 + $tax1;

    $stmt = $db->prepare("
        INSERT INTO orders (order_number, customer_id, total_amount, tax_amount, shipping_charge, discount_amount, order_status, payment_status, payment_method, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode, order_date)
        VALUES (?, ?, ?, ?, 0.00, 0.00, 'DELIVERED', 'PAID', 'CREDIT_CARD', 'Rahul Sharma', '9876543210', 'Flat 402, Royal Residency, Station Road', 'Valsad', 'Gujarat', '396001', DATE_SUB(NOW(), INTERVAL 5 DAY))
    ");
    $stmt->execute([$orderNum1, $customerId, $total1, $tax1]);
    $orderId1 = $db->lastInsertId();

    // Order items
    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    $itemStmt->execute([$orderId1, $prod1['product_id'], $prod1['name'], 1, $subtotal1, $subtotal1]);

    // Payment
    $jsonResp = json_encode(['status' => 'SUCCESS', 'auth_code' => 'AUTH872619']);
    $payStmt = $db->prepare("INSERT INTO payments (order_id, transaction_id, payment_method, amount, payment_status, gateway_response, payment_date) VALUES (?, ?, 'CREDIT_CARD', ?, 'SUCCESS', ?, DATE_SUB(NOW(), INTERVAL 5 DAY))");
    $payStmt->execute([$orderId1, 'TXN-98234710', $total1, $jsonResp]);
    $paymentId1 = $db->lastInsertId();

    // Invoice
    $invNum1 = 'INV-' . date('Y') . '-0001';
    $invStmt = $db->prepare("INSERT INTO invoices (invoice_number, order_id, customer_id, payment_id, total_amount, tax_amount, shipping_charge, invoice_date) VALUES (?, ?, ?, ?, ?, ?, 0.00, DATE_SUB(NOW(), INTERVAL 5 DAY))");
    $invStmt->execute([$invNum1, $orderId1, $customerId, $paymentId1, $total1, $tax1]);
    $invoiceId1 = $db->lastInsertId();

    $invItemStmt = $db->prepare("INSERT INTO invoice_items (invoice_id, product_id, description, hsn_code, quantity, unit_price, tax_rate, tax_amount, total_price) VALUES (?, ?, ?, '85171300', 1, ?, 18.00, ?, ?)");
    $invItemStmt->execute([$invoiceId1, $prod1['product_id'], $prod1['name'], $subtotal1, $tax1, $total1]);

    // Tracking for Order 1
    $trackStmt = $db->prepare("INSERT INTO order_tracking (order_id, status, description, location, created_at) VALUES (?, ?, ?, ?, ?)");
    $trackStmt->execute([$orderId1, 'ORDER_PLACED', 'Order placed successfully by customer', 'Valsad Hub', date('Y-m-d H:i:s', strtotime('-5 days'))]);
    $trackStmt->execute([$orderId1, 'PAYMENT_VERIFIED', 'Payment authenticated via Mock Bank Gateway', 'Valsad Gateway', date('Y-m-d H:i:s', strtotime('-5 days + 2 minutes'))]);
    $trackStmt->execute([$orderId1, 'PROCESSING', 'Stock reserved and invoice generated', 'Central Warehouse Surat', date('Y-m-d H:i:s', strtotime('-4 days'))]);
    $trackStmt->execute([$orderId1, 'PACKED', 'Item packed in tamper-proof security box with IMEI barcode', 'Central Warehouse Surat', date('Y-m-d H:i:s', strtotime('-3 days'))]);
    $trackStmt->execute([$orderId1, 'SHIPPED', 'Dispatched via Blue Dart Express (AWB: BLUEDART-984214)', 'Surat Logistics Hub', date('Y-m-d H:i:s', strtotime('-2 days'))]);
    $trackStmt->execute([$orderId1, 'OUT_FOR_DELIVERY', 'Courier out for delivery with executive Rajesh', 'Valsad Local Delivery Center', date('Y-m-d H:i:s', strtotime('-1 days'))]);
    $trackStmt->execute([$orderId1, 'DELIVERED', 'Delivered to customer with OTP verification', 'Customer Residence Valsad', date('Y-m-d H:i:s', strtotime('-1 days + 3 hours'))]);

    // Insert Order 2 (In Transit / Shipped)
    $orderNum2 = 'ORD-' . date('Ymd') . '-1002';
    $subtotal2 = 64399.00;
    $tax2 = round($subtotal2 * 0.18, 2);
    $total2 = $subtotal2 + $tax2;

    $stmt->execute([$orderNum2, $customerId, $total2, $tax2]);
    $orderId2 = $db->lastInsertId();
    $itemStmt->execute([$orderId2, $prod3['product_id'], $prod3['name'], 1, $subtotal2, $subtotal2]);
    $payStmt->execute([$orderId2, 'TXN-98234789', $total2, $jsonResp]);
    $paymentId2 = $db->lastInsertId();

    $invNum2 = 'INV-' . date('Y') . '-0002';
    $invStmt->execute([$invNum2, $orderId2, $customerId, $paymentId2, $total2, $tax2]);
    $invoiceId2 = $db->lastInsertId();
    $invItemStmt->execute([$invoiceId2, $prod3['product_id'], $prod3['name'], $subtotal2, $tax2, $total2]);

    $trackStmt->execute([$orderId2, 'ORDER_PLACED', 'Order placed successfully', 'Valsad Hub', date('Y-m-d H:i:s', strtotime('-1 days'))]);
    $trackStmt->execute([$orderId2, 'PAYMENT_VERIFIED', 'Payment authenticated via UPI Gateway', 'UPI Gateway', date('Y-m-d H:i:s', strtotime('-1 days + 1 minute'))]);
    $trackStmt->execute([$orderId2, 'PROCESSING', 'Stock reserved at Supplier Depot', 'Ahmedabad Hub', date('Y-m-d H:i:s', strtotime('-18 hours'))]);
    $trackStmt->execute([$orderId2, 'SHIPPED', 'Dispatched via Express Delivery (AWB: EXP-482910)', 'Ahmedabad Logistics Hub', date('Y-m-d H:i:s', strtotime('-8 hours'))]);

    $db->commit();
    echo "SUCCESS: Seeded Orders ($orderId1, $orderId2), Invoices ($invoiceId1, $invoiceId2), Tracking, Payments!
";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "
";
}
