<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Payment Callback & Settlement Handler
 * DFD Implementation:
 *   - P1.4.2: Process Gateway Auth
 *   - P1.4.3: Generate Invoice
 *   - P1.3.3: Stock Commit / Release
 *   - P1.5: Initiate Fulfillment & Tracking
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once dirname(__DIR__) . '/includes/dfd_helper.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/customer/orders.php");
    exit;
}

$tokenParam = $_POST['token'] ?? '';
$paymentDecision = $_POST['payment_decision'] ?? 'FAILED';
$paymentMethod = $_POST['payment_method'] ?? 'UPI';

// Decode & Verify Token
$decodedRaw = base64_decode($tokenParam);
$parts = explode('||', $decodedRaw);
if (count($parts) !== 2) {
    die("<h1>Security Alert</h1><p>Invalid payment token.</p>");
}

[$tokenJson, $signature] = $parts;
$secretKey = 'MOCK_SECRET_KEY_ACADEMIC_2026';
if (!hash_equals(hash_hmac('sha256', $tokenJson, $secretKey), $signature)) {
    die("<h1>Security Alert</h1><p>Token signature verification failed.</p>");
}

$payload = json_decode($tokenJson, true);
$orderId = (int)($payload['order_id'] ?? 0);
$paymentId = (int)($payload['payment_id'] ?? 0);
$userId = $_SESSION['user_id'];

$db = get_db_connection();

// Verify order ownership
$stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    die("<h1>Error</h1><p>Order not found.</p>");
}

try {
    $db->beginTransaction();

    if ($paymentDecision === 'SUCCESS') {
        // -------------------------------------------------------------
        // 1. UPDATE PAYMENT & LEDGER (P1.4)
        // -------------------------------------------------------------
        $authCode = 'AUTH' . rand(100000, 999999);
        $gatewayResponse = json_encode([
            'status'         => 'AUTHORIZED',
            'auth_code'      => $authCode,
            'gateway'        => 'MobileKart Payments',
            'payment_method' => $paymentMethod,
            'timestamp'      => date('Y-m-d H:i:s')
        ]);

        $upPay = $db->prepare("
            UPDATE payments 
            SET payment_status = 'SUCCESS', payment_method = ?, gateway_response = ?, payment_date = NOW()
            WHERE payment_id = ?
        ");
        $upPay->execute([$paymentMethod, $gatewayResponse, $paymentId]);

        // -------------------------------------------------------------
        // 2. CONFIRM ORDER (P1.3)
        // -------------------------------------------------------------
        $upOrd = $db->prepare("
            UPDATE orders 
            SET payment_status = 'PAID', order_status = 'ORDER_CONFIRMED', updated_at = NOW()
            WHERE order_id = ?
        ");
        $upOrd->execute([$orderId]);

        // -------------------------------------------------------------
        // 3. COMMIT STOCK RESERVATION & DECREMENT INVENTORY (P1.3.3)
        // -------------------------------------------------------------
        $upRes = $db->prepare("
            UPDATE stock_reservations 
            SET status = 'COMMITTED' 
            WHERE order_id = ? AND status = 'ACTIVE'
        ");
        $upRes->execute([$orderId]);

        // Fetch items and atomically deduct stock
        $itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        $deductStock = $db->prepare("
            UPDATE products 
            SET stock_quantity = GREATEST(0, stock_quantity - ?) 
            WHERE product_id = ?
        ");

        $logInv = $db->prepare("
            INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by, created_at)
            VALUES (?, 'PURCHASE', ?, (SELECT stock_quantity FROM products WHERE product_id = ?), ?, ?, ?, NOW())
        ");

        foreach ($items as $it) {
            // Deduct stock
            $deductStock->execute([$it['quantity'], $it['product_id']]);
            // Log inventory transaction
            $logInv->execute([
                $it['product_id'],
                -$it['quantity'],
                $it['product_id'],
                $order['order_number'],
                "Purchased in Order {$order['order_number']} via {$paymentMethod}",
                $userId
            ]);
        }

        // -------------------------------------------------------------
        // 4. GENERATE GST INVOICE (P1.4.3)
        // -------------------------------------------------------------
        $invoiceNumber = 'INV-' . date('Y') . '-' . sprintf('%05d', $orderId);
        $fullAddress = "{$order['shipping_name']}, {$order['shipping_address']}, {$order['shipping_city']}, {$order['shipping_state']} - {$order['shipping_pincode']}";

        $insInv = $db->prepare("
            INSERT INTO invoices (invoice_number, order_id, payment_id, customer_id, invoice_date, subtotal, discount, tax_amount, shipping_charge, total_amount, billing_address, status)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'PAID')
        ");
        $insInv->execute([
            $invoiceNumber,
            $orderId,
            $paymentId,
            $userId,
            $order['subtotal'],
            $order['discount'],
            $order['tax'],
            $order['shipping_charge'],
            $order['total_amount'],
            $fullAddress
        ]);
        $invoiceId = $db->lastInsertId();

        // Insert invoice itemized lines
        $insInvItem = $db->prepare("
            INSERT INTO invoice_items (invoice_id, product_name, model, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($items as $it) {
            $pDetails = $db->prepare("SELECT product_name, model FROM products WHERE product_id = ?");
            $pDetails->execute([$it['product_id']]);
            $pd = $pDetails->fetch();
            $insInvItem->execute([
                $invoiceId,
                $pd['product_name'] ?? 'Smartphone',
                $pd['model'] ?? 'Standard',
                $it['quantity'],
                $it['price'],
                $it['subtotal']
            ]);
        }

        // -------------------------------------------------------------
        // 5. INITIATE FULFILLMENT & TRACKING (P1.5)
        // -------------------------------------------------------------
        $shipmentId = 'SHP-' . date('Ymd') . '-' . rand(100, 999);
        $trackingId = 'TRK-EKART-' . rand(10000000, 99999999);

        $insFul = $db->prepare("
            INSERT INTO fulfillments (order_id, shipment_id, tracking_id, carrier, shipment_status, delivery_address, manifest, created_at)
            VALUES (?, ?, ?, 'Ekart Logistics', 'ORDER_CONFIRMED', ?, 'Initial packaging and manifest generated by fulfillment center.', NOW())
        ");
        $insFul->execute([$orderId, $shipmentId, $trackingId, $fullAddress]);
        $fulfillmentId = $db->lastInsertId();

        $insEvent = $db->prepare("
            INSERT INTO shipment_events (fulfillment_id, status, location, description, event_time)
            VALUES (?, 'ORDER_CONFIRMED', 'Bengaluru Sorting Hub', 'Order placed & payment verified successfully', NOW())
        ");
        $insEvent->execute([$fulfillmentId]);

        $db->commit();

        // Audit Logging
        DFDManager::logFlow('P1.4', 'PAYMENT_SUCCESS_AND_INVOICE_GENERATED', [
            'order_id' => $orderId,
            'invoice_number' => $invoiceNumber,
            'amount' => $order['total_amount']
        ]);
        log_activity($userId, 'PAYMENT_SUCCESS', 'PAYMENT', $paymentId, ['amount' => $order['total_amount']]);

        set_flash('success', "Payment authorized successfully! Order {$order['order_number']} confirmed.");
        header("Location: " . BASE_URL . "/customer/order-details.php?id={$orderId}&payment=success");
        exit;

    } elseif ($paymentDecision === 'FAILED') {
        // -------------------------------------------------------------
        // PAYMENT DECLINED: RELEASE RESERVED STOCK (P1.3.3)
        // -------------------------------------------------------------
        $upPay = $db->prepare("
            UPDATE payments 
            SET payment_status = 'FAILED', gateway_response = 'Declined: Simulated card decline or insufficient funds' 
            WHERE payment_id = ?
        ");
        $upPay->execute([$paymentId]);

        $upOrd = $db->prepare("
            UPDATE orders 
            SET payment_status = 'FAILED', order_status = 'CANCELLED', updated_at = NOW() 
            WHERE order_id = ?
        ");
        $upOrd->execute([$orderId]);

        // Release stock reservation hold
        $relRes = $db->prepare("
            UPDATE stock_reservations 
            SET status = 'RELEASED' 
            WHERE order_id = ? AND status = 'ACTIVE'
        ");
        $relRes->execute([$orderId]);

        $db->commit();

        DFDManager::logFlow('P1.3', 'RESERVATION_RELEASED_ON_PAYMENT_FAILURE', ['order_id' => $orderId]);
        log_activity($userId, 'PAYMENT_FAILED', 'PAYMENT', $paymentId);

        set_flash('danger', "Payment was declined by the bank. Any temporary stock hold has been released.");
        header("Location: " . BASE_URL . "/customer/order-details.php?id={$orderId}&payment=failed");
        exit;

    } else { // CANCELLED
        // Release reservation hold
        $relRes = $db->prepare("
            UPDATE stock_reservations 
            SET status = 'RELEASED' 
            WHERE order_id = ? AND status = 'ACTIVE'
        ");
        $relRes->execute([$orderId]);

        $upPay = $db->prepare("UPDATE payments SET payment_status = 'CANCELLED' WHERE payment_id = ?");
        $upPay->execute([$paymentId]);

        $upOrd = $db->prepare("UPDATE orders SET order_status = 'CANCELLED' WHERE order_id = ?");
        $upOrd->execute([$orderId]);

        $db->commit();

        set_flash('info', "Transaction cancelled. Your cart is preserved.");
        header("Location: " . BASE_URL . "/customer/cart.php");
        exit;
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash('danger', "Transaction processing error: " . $e->getMessage());
    header("Location: " . BASE_URL . "/customer/checkout.php");
    exit;
}
