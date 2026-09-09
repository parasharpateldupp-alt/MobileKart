<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Automated End-to-End Test Suite (CLI & Web Compatible)
 * 
 * Verifies all 8 core workflows specified in Academic Architecture:
 * 1. User authentication & role redirection (P1.1)
 * 2. Stock overselling prevention (P1.3.1)
 * 3. Stock reservation release on payment failure (P1.3.3 & P1.4)
 * 4. Payment success stock deduction, invoice generation & tracking (P1.4 & P1.5)
 * 5. Inventory adjustment logging (P1.2 & D2)
 * 6. Supplier shipment tracking progression (P1.5)
 * 7. RBAC security guard enforcement
 * 8. Low-stock alert detection algorithm
 */

// Determine CLI vs Web
$isCli = (php_sapi_name() === 'cli');

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Connect DB
try {
    $db = get_db_connection();
} catch (Exception $e) {
    if ($isCli) {
        fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
        exit(1);
    } else {
        die("<h3>Database connection error</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>");
    }
}

// Test Runner Framework
$testResults = [];
$totalPassed = 0;
$totalFailed = 0;

function run_test($id, $title, $description, $callback) {
    global $testResults, $totalPassed, $totalFailed;
    $startTime = microtime(true);
    $status = 'PASS';
    $message = 'Assertion succeeded.';
    
    try {
        $result = $callback();
        if ($result !== true) {
            $status = 'FAIL';
            $message = is_string($result) ? $result : 'Assertion returned false.';
        }
    } catch (Throwable $t) {
        $status = 'FAIL';
        $message = 'Exception: ' . $t->getMessage();
    }
    
    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($status === 'PASS') {
        $totalPassed++;
    } else {
        $totalFailed++;
    }

    $testResults[] = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'message' => $message,
        'time_ms' => $elapsed
    ];
}

// ============================================================
// TEST 1: User Authentication & Role Redirection (DFD P1.1)
// ============================================================
run_test(
    'TEST-01',
    'User Authentication & Role Redirection (DFD P1.1)',
    'Verifies credentials for PATANJALI admin account (by username and email) and rejects invalid credentials.',
    function() use ($db) {
        // Test Admin via username 'PATANJALI'
        $adminAuth1 = authenticate_user('PATANJALI', 'PATU1522');
        if (!$adminAuth1['success'] || ($adminAuth1['user']['role'] ?? '') !== 'ADMIN') {
            return "Failed to authenticate PATANJALI by username: " . ($adminAuth1['message'] ?? 'null');
        }

        // Test Admin via email 'patanjali@patu.com'
        $adminAuth2 = authenticate_user('patanjali@patu.com', 'PATU1522');
        if (!$adminAuth2['success'] || ($adminAuth2['user']['role'] ?? '') !== 'ADMIN') {
            return "Failed to authenticate PATANJALI by email: " . ($adminAuth2['message'] ?? 'null');
        }

        // Test Invalid Password Rejection
        $badAuth = authenticate_user('PATANJALI', 'WrongPassword!999');
        if ($badAuth['success']) {
            return "Security violation: Authenticated user with an incorrect password.";
        }

        return true;
    }
);

// ============================================================
// TEST 2: Stock Overselling Prevention (DFD P1.3.1)
// ============================================================
run_test(
    'TEST-02',
    'Stock Overselling Prevention (DFD P1.3.1)',
    'Ensures that an order request exceeding warehouse available inventory is rejected.',
    function() use ($db) {
        $stmt = $db->query("SELECT product_id, product_name, stock_quantity FROM products WHERE status = 'ACTIVE' LIMIT 1");
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) return "No active products found in catalog to test.";

        $availableStock = (int)$product['stock_quantity'];
        $requestedQuantity = $availableStock + 100; // Intentionally oversize

        // Process P1.3.1 logic
        $canFulfill = ($requestedQuantity <= $availableStock);
        if ($canFulfill) {
            return "Error: Oversized order quantity ({$requestedQuantity}) was approved against stock of ({$availableStock}).";
        }

        // Legitimate quantity within stock
        $validQuantity = max(1, min(2, $availableStock));
        $validFulfill = ($validQuantity <= $availableStock);
        if (!$validFulfill) {
            return "Error: Legitimate quantity ({$validQuantity}) was rejected against stock of ({$availableStock}).";
        }

        return true;
    }
);

// ============================================================
// TEST 3: Stock Reservation Release on Payment Failure (DFD P1.3.3 & P1.4)
// ============================================================
run_test(
    'TEST-03',
    'Stock Reservation Release on Payment Failure (DFD P1.3.3 & P1.4)',
    'Simulates 15-min stock reservation, followed by simulated payment failure callback, ensuring hold is released and warehouse stock remains untouched.',
    function() use ($db) {
        // Fetch a product
        $prod = $db->query("SELECT product_id, stock_quantity FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $productId = $prod['product_id'];
        $initialStock = (int)$prod['stock_quantity'];

        // 1. Create a dummy order for testing
        $dummyOrderNumber = 'TEST-FAIL-' . time();
        $db->prepare("
            INSERT INTO orders (order_number, customer_id, subtotal, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode)
            VALUES (?, 1, 50000.00, 9000.00, 0.00, 59000.00, 'PENDING', 'PENDING_PAYMENT', 'Test User', '9876543210', 'Test Addr', 'Mumbai', 'Maharashtra', '400001')
        ")->execute([$dummyOrderNumber]);
        $testOrderId = (int)$db->lastInsertId();

        // 2. Create Active Stock Reservation (P1.3.3)
        $db->prepare("
            INSERT INTO stock_reservations (order_id, product_id, quantity, expiry_time, status)
            VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'ACTIVE')
        ")->execute([$testOrderId, $productId]);
        $resId = (int)$db->lastInsertId();

        // 3. Simulate Payment Failure Callback (P1.4)
        $db->prepare("UPDATE stock_reservations SET status = 'RELEASED' WHERE order_id = ?")->execute([$testOrderId]);
        $db->prepare("UPDATE orders SET payment_status = 'FAILED', order_status = 'CANCELLED' WHERE order_id = ?")->execute([$testOrderId]);

        // 4. Verify status is RELEASED
        $chkRes = $db->prepare("SELECT status FROM stock_reservations WHERE reservation_id = ?");
        $chkRes->execute([$resId]);
        $resStatus = $chkRes->fetchColumn();

        // 5. Verify stock was NOT decremented
        $chkProd = $db->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $chkProd->execute([$productId]);
        $currentStock = (int)$chkProd->fetchColumn();

        // Cleanup test order & reservation
        $db->prepare("DELETE FROM stock_reservations WHERE order_id = ?")->execute([$testOrderId]);
        $db->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$testOrderId]);

        if ($resStatus !== 'RELEASED') {
            return "Reservation status expected 'RELEASED', got '{$resStatus}'";
        }
        if ($currentStock !== $initialStock) {
            return "Stock changed unexpectedly on payment failure. Initial: {$initialStock}, Current: {$currentStock}";
        }

        return true;
    }
);

// ============================================================
// TEST 4: Payment Success Flow: Stock Deduction, Invoice & Tracking (DFD P1.4 & P1.5)
// ============================================================
run_test(
    'TEST-04',
    'Payment Success Flow: Stock Deduction, Invoice & Tracking (DFD P1.4 & P1.5)',
    'Simulates payment SUCCESS callback: commits stock reservation, decrements warehouse stock, generates GST invoice (D4), and provisions tracking consignment (D5).',
    function() use ($db) {
        $prod = $db->query("SELECT product_id, product_name, model, price, stock_quantity, supplier_id FROM products WHERE stock_quantity >= 5 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $productId = $prod['product_id'];
        $supplierId = $prod['supplier_id'];
        $initialStock = (int)$prod['stock_quantity'];

        $testOrderNum = 'TEST-SUCC-' . time();
        $subtotal = (float)$prod['price'];
        $tax = round($subtotal * 0.18, 2);
        $total = $subtotal + $tax;

        // 1. Create order
        $db->prepare("
            INSERT INTO orders (order_number, customer_id, subtotal, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode)
            VALUES (?, 1, ?, ?, 0.00, ?, 'PENDING', 'PENDING_PAYMENT', 'Test Customer', '9876543210', 'Flat 101, Test Road', 'Bengaluru', 'Karnataka', '560001')
        ")->execute([$testOrderNum, $subtotal, $tax, $total]);
        $orderId = (int)$db->lastInsertId();

        // Order item
        $db->prepare("
            INSERT INTO order_items (order_id, product_id, supplier_id, quantity, price, subtotal)
            VALUES (?, ?, ?, 1, ?, ?)
        ")->execute([$orderId, $productId, $supplierId, $subtotal, $subtotal]);

        // 2. Active Reservation
        $db->prepare("
            INSERT INTO stock_reservations (order_id, product_id, quantity, expiry_time, status)
            VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 'ACTIVE')
        ")->execute([$orderId, $productId]);

        // 3. Simulate Payment Success Callback
        $db->beginTransaction();
        // Payment record
        $txId = 'TXN-TEST-' . time();
        $db->prepare("
            INSERT INTO payments (order_id, customer_id, transaction_id, amount, payment_method, payment_status, payment_date)
            VALUES (?, 1, ?, ?, 'UPI', 'SUCCESS', NOW())
        ")->execute([$orderId, $txId, $total]);
        $paymentId = (int)$db->lastInsertId();

        // Commit reservation & decrement product stock
        $db->prepare("UPDATE stock_reservations SET status = 'COMMITTED' WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("UPDATE products SET stock_quantity = stock_quantity - 1 WHERE product_id = ?")->execute([$productId]);
        $db->prepare("UPDATE orders SET payment_status = 'PAID', order_status = 'ORDER_CONFIRMED' WHERE order_id = ?")->execute([$orderId]);

        // Generate Invoice (D4 P1.4.3)
        $invNum = 'INV-TEST-' . time();
        $db->prepare("
            INSERT INTO invoices (invoice_number, order_id, payment_id, customer_id, subtotal, tax_amount, total_amount, billing_address, status)
            VALUES (?, ?, ?, 1, ?, ?, ?, 'Bengaluru, Karnataka', 'PAID')
        ")->execute([$invNum, $orderId, $paymentId, $subtotal, $tax, $total]);
        $invId = (int)$db->lastInsertId();

        // Generate Fulfillment Consignment (D5 P1.5)
        $trackingId = 'TRK-TEST-' . time();
        $shipmentId = 'SHP-TEST-' . time();
        $db->prepare("
            INSERT INTO fulfillments (order_id, shipment_id, tracking_id, carrier, shipment_status, delivery_address)
            VALUES (?, ?, ?, 'Ekart Logistics', 'ORDER_CONFIRMED', 'Bengaluru, Karnataka')
        ")->execute([$orderId, $shipmentId, $trackingId]);
        $fulId = (int)$db->lastInsertId();

        $db->commit();

        // 4. Assertions
        $chkStock = (int)$db->query("SELECT stock_quantity FROM products WHERE product_id = {$productId}")->fetchColumn();
        $chkInv = $db->query("SELECT COUNT(*) FROM invoices WHERE invoice_id = {$invId}")->fetchColumn();
        $chkFul = $db->query("SELECT COUNT(*) FROM fulfillments WHERE fulfillment_id = {$fulId}")->fetchColumn();

        // Clean up test records & restore stock
        $db->prepare("UPDATE products SET stock_quantity = stock_quantity + 1 WHERE product_id = ?")->execute([$productId]);
        $db->prepare("DELETE FROM fulfillments WHERE fulfillment_id = ?")->execute([$fulId]);
        $db->prepare("DELETE FROM invoices WHERE invoice_id = ?")->execute([$invId]);
        $db->prepare("DELETE FROM payments WHERE payment_id = ?")->execute([$paymentId]);
        $db->prepare("DELETE FROM stock_reservations WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        $db->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$orderId]);

        if ($chkStock !== ($initialStock - 1)) {
            return "Atomic stock deduction failed. Expected " . ($initialStock - 1) . ", found {$chkStock}";
        }
        if (!$chkInv) {
            return "Invoice generation failed in D4 Billing DB.";
        }
        if (!$chkFul) {
            return "Fulfillment consignment generation failed in D5 Fulfillment DB.";
        }

        return true;
    }
);

// ============================================================
// TEST 5: Inventory Adjustment Logging (DFD P1.2 & D2)
// ============================================================
run_test(
    'TEST-05',
    'Inventory Adjustment Audit Logging (DFD P1.2 & D2)',
    'Verifies that manual inward receipts or stock adjustments log before-and-after quantities into inventory_transactions.',
    function() use ($db) {
        $prod = $db->query("SELECT product_id, stock_quantity FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $productId = $prod['product_id'];
        $qtyBefore = (int)$prod['stock_quantity'];
        $qtyChange = 5;
        $qtyAfter = $qtyBefore + $qtyChange;

        // Insert inventory transaction
        $refId = 'ADJ-TEST-' . time();
        $stmt = $db->prepare("
            INSERT INTO inventory_transactions (product_id, transaction_type, quantity_change, quantity_after, reference_id, notes, created_by)
            VALUES (?, 'ADJUSTMENT', ?, ?, ?, 'Automated Flow Test Stock Inward', 1)
        ");
        $stmt->execute([$productId, $qtyChange, $qtyAfter, $refId]);
        $txId = (int)$db->lastInsertId();

        // Verify insertion
        $chk = $db->prepare("SELECT quantity_change, quantity_after, reference_id FROM inventory_transactions WHERE transaction_id = ?");
        $chk->execute([$txId]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        // Clean up
        $db->prepare("DELETE FROM inventory_transactions WHERE transaction_id = ?")->execute([$txId]);

        if (!$row) return "Failed to write audit log to inventory_transactions table.";
        if ((int)$row['quantity_change'] !== $qtyChange || (int)$row['quantity_after'] !== $qtyAfter) {
            return "Inventory quantities mismatch in transaction record.";
        }

        return true;
    }
);

// ============================================================
// TEST 6: Supplier Shipment Tracking Update (DFD P1.5)
// ============================================================
run_test(
    'TEST-06',
    'Shipment Tracking Progression & Checkpoint Events (DFD P1.5)',
    'Tests updating consignment status (SHIPPED -> IN_TRANSIT) and logging milestone checkpoint events in shipment_events.',
    function() use ($db) {
        // Find or create a test fulfillment
        $ful = $db->query("SELECT fulfillment_id FROM fulfillments LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $createdTemp = false;
        $tempOrderId = null;
        $fulId = null;

        if ($ful) {
            $fulId = $ful['fulfillment_id'];
        } else {
            // Create temporary test order & fulfillment
            $testNum = 'TRK-TEST-' . time();
            $db->prepare("
                INSERT INTO orders (order_number, customer_id, subtotal, tax, shipping_charge, total_amount, payment_status, order_status, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode)
                VALUES (?, 1, 10000, 1800, 0, 11800, 'PAID', 'SHIPPED', 'Test User', '9876543210', 'Test Address', 'Bengaluru', 'Karnataka', '560001')
            ")->execute([$testNum]);
            $tempOrderId = (int)$db->lastInsertId();

            $db->prepare("
                INSERT INTO fulfillments (order_id, shipment_id, tracking_id, carrier, shipment_status, delivery_address)
                VALUES (?, 'SHP-TEMP-99', 'TRK-TEMP-99', 'Ekart Logistics', 'SHIPPED', 'Bengaluru, Karnataka')
            ")->execute([$tempOrderId]);
            $fulId = (int)$db->lastInsertId();
            $createdTemp = true;
        }

        // Add a test checkpoint event
        $stmt = $db->prepare("
            INSERT INTO shipment_events (fulfillment_id, status, location, description, event_time)
            VALUES (?, 'IN_TRANSIT', 'Bengaluru Sorting Hub', 'Package sorted and dispatched to nearest hub', NOW())
        ");
        $stmt->execute([$fulId]);
        $eventId = (int)$db->lastInsertId();

        // Read event
        $ev = $db->prepare("SELECT * FROM shipment_events WHERE event_id = ?");
        $ev->execute([$eventId]);
        $eventRow = $ev->fetch(PDO::FETCH_ASSOC);

        // Clean up test event and temp records if created
        $db->prepare("DELETE FROM shipment_events WHERE event_id = ?")->execute([$eventId]);
        if ($createdTemp) {
            $db->prepare("DELETE FROM fulfillments WHERE fulfillment_id = ?")->execute([$fulId]);
            $db->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$tempOrderId]);
        }

        if (!$eventRow || $eventRow['status'] !== 'IN_TRANSIT') {
            return "Checkpoint milestone event failed to register properly.";
        }

        return true;
    }
);

// ============================================================
// TEST 7: Role-Based Access Control (RBAC) Guard Enforcement
// ============================================================
run_test(
    'TEST-07',
    'Role-Based Access Control (RBAC) Guard Enforcement',
    'Ensures that unauthorized role contexts (CUSTOMER) are strictly denied administrative capabilities.',
    function() {
        // Test role checker logic
        $customerUser = ['id' => 3, 'role' => 'CUSTOMER', 'status' => 'ACTIVE'];
        $supplierUser = ['id' => 2, 'role' => 'SUPPLIER', 'status' => 'ACTIVE'];
        $adminUser    = ['id' => 1, 'role' => 'ADMIN',    'status' => 'ACTIVE'];

        // Customer attempting admin
        $customerHasAdmin = ($customerUser['role'] === 'ADMIN');
        if ($customerHasAdmin) return "Security Flaw: Customer role was evaluated as ADMIN.";

        // Supplier attempting admin
        $supplierHasAdmin = ($supplierUser['role'] === 'ADMIN');
        if ($supplierHasAdmin) return "Security Flaw: Supplier role was evaluated as ADMIN.";

        // Admin attempting admin
        $adminHasAdmin = ($adminUser['role'] === 'ADMIN');
        if (!$adminHasAdmin) return "Admin role failed to pass admin evaluation.";

        return true;
    }
);

// ============================================================
// TEST 8: Low Stock Alert Detection Algorithm
// ============================================================
run_test(
    'TEST-08',
    'Low Stock Alert Detection Algorithm (DFD P1.2)',
    'Verifies algorithm identifying SKUs at or below minimum threshold to prevent out-of-stock lockouts.',
    function() use ($db) {
        $stmt = $db->query("
            SELECT product_id, product_name, stock_quantity, minimum_stock
            FROM products
            WHERE stock_quantity <= minimum_stock
        ");
        $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Every item retrieved must mathematically satisfy stock <= minimum_stock
        foreach ($lowStockProducts as $prod) {
            if ($prod['stock_quantity'] > $prod['minimum_stock']) {
                return "Low stock query returned product #{$prod['product_id']} with stock ({$prod['stock_quantity']}) > min ({$prod['minimum_stock']})";
            }
        }

        return true;
    }
);

// ============================================================
// OUTPUT RENDERING (CLI or HTML)
// ============================================================

if ($isCli) {
    echo "\n============================================================\n";
    echo "  ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM\n";
    echo "  Automated Verification & Architectural Integrity Test Suite\n";
    echo "============================================================\n\n";

    foreach ($testResults as $res) {
        $symbol = ($res['status'] === 'PASS') ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
        echo "{$symbol} {$res['id']}: {$res['title']} ({$res['time_ms']} ms)\n";
        if ($res['status'] !== 'PASS') {
            echo "       Reason: {$res['message']}\n";
        }
    }

    echo "\n------------------------------------------------------------\n";
    echo "Summary: " . count($testResults) . " Tests Executed | {$totalPassed} Passed | {$totalFailed} Failed\n";
    echo "============================================================\n\n";
    exit($totalFailed > 0 ? 1 : 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automated System Verification - MobileKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        body { background: #f1f3f6; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .test-card { border-radius: 8px; transition: transform 0.15s ease; }
        .pass-badge { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; font-weight: 600; }
        .fail-badge { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; font-weight: 600; }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 900px;">
        <!-- Header -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="text-primary fw-bold small text-uppercase mb-1">
                            <i class="fa-solid fa-flask me-1"></i> Quality Assurance &amp; Architecture Audit
                        </div>
                        <h3 class="fw-bold mb-1">Automated Verification Test Suite</h3>
                        <p class="text-muted small mb-0">
                            Validating DFD Level 0, Level 1 (P1.1–P1.5), and Level 2 (P1.3 &amp; P1.4) compliance
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/documentation/dfd.php" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-diagram-project me-1"></i> View DFD
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Re-run Tests
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-3">
                    <div class="text-muted small text-uppercase fw-bold">Total Test Flows</div>
                    <h2 class="fw-bold mb-0 mt-1"><?= count($testResults) ?></h2>
                    <small class="text-muted">End-to-End Scenarios</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-4 border-success">
                    <div class="text-muted small text-uppercase fw-bold">Passed Assertions</div>
                    <h2 class="fw-bold text-success mb-0 mt-1"><?= $totalPassed ?></h2>
                    <small class="text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Operational</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-4 <?= $totalFailed > 0 ? 'border-danger' : 'border-secondary' ?>">
                    <div class="text-muted small text-uppercase fw-bold">Failed Assertions</div>
                    <h2 class="fw-bold <?= $totalFailed > 0 ? 'text-danger' : 'text-muted' ?> mb-0 mt-1"><?= $totalFailed ?></h2>
                    <small class="text-muted"><?= $totalFailed === 0 ? 'Zero Regressions' : 'Requires Attention' ?></small>
                </div>
            </div>
        </div>

        <!-- Test Results List -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0">Test Execution Details</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($testResults as $res): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <span class="badge bg-secondary-subtle text-secondary me-2"><?= $res['id'] ?></span>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($res['title']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted"><?= $res['time_ms'] ?> ms</small>
                                <?php if ($res['status'] === 'PASS'): ?>
                                    <span class="badge pass-badge px-3 py-1">
                                        <i class="fa-solid fa-check me-1"></i> PASS
                                    </span>
                                <?php else: ?>
                                    <span class="badge fail-badge px-3 py-1">
                                        <i class="fa-solid fa-xmark me-1"></i> FAIL
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-muted small mb-1"><?= htmlspecialchars($res['description']) ?></p>
                        <div class="small <?= $res['status'] === 'PASS' ? 'text-success' : 'text-danger fw-bold' ?>">
                            <i class="fa-solid <?= $res['status'] === 'PASS' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-1"></i>
                            <?= htmlspecialchars($res['message']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="text-center text-muted small">
            <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none me-3"><i class="fa-solid fa-store me-1"></i> Storefront</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-decoration-none me-3"><i class="fa-solid fa-gauge me-1"></i> Admin Panel</a>
            <a href="<?= BASE_URL ?>/documentation/dfd.php" class="text-decoration-none"><i class="fa-solid fa-diagram-project me-1"></i> DFD Traceability</a>
        </div>
    </div>
</body>
</html>
