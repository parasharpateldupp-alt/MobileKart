<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Safe Mock Bank Gateway (DFD P1.4.2 Process Gateway Auth)
 * ACADEMIC DEMONSTRATION ONLY — NO REAL MONEY IS INVOLVED
 */

$pageTitle = "Secure Payment Checkout - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$tokenParam = $_GET['token'] ?? '';
if (empty($tokenParam)) {
    die("<h1>Payment Error</h1><p>Missing or invalid payment token.</p><a href='" . BASE_URL . "/customer/cart.php'>Return to Cart</a>");
}

// Decode & Verify Payment Token
$decodedRaw = base64_decode($tokenParam);
$parts = explode('||', $decodedRaw);
if (count($parts) !== 2) {
    die("<h1>Security Alert</h1><p>Malformed payment token structure.</p>");
}

[$tokenJson, $signature] = $parts;
$secretKey = 'MOCK_SECRET_KEY_ACADEMIC_2026';
$expectedSig = hash_hmac('sha256', $tokenJson, $secretKey);

if (!hash_equals($expectedSig, $signature)) {
    die("<h1>Security Alert</h1><p>Token signature verification failed (Tampering detected).</p>");
}

$payload = json_decode($tokenJson, true);
$orderId = (int)($payload['order_id'] ?? 0);
$paymentId = (int)($payload['payment_id'] ?? 0);
$amount = (float)($payload['amount'] ?? 0.0);

$db = get_db_connection();

// Fetch Order and Customer details
$stmt = $db->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("<h1>Order Error</h1><p>Order not found or access unauthorized.</p>");
}

if ($order['payment_status'] === 'PAID') {
    header("Location: " . BASE_URL . "/customer/order-details.php?id=" . $orderId);
    exit;
}

// Fetch order items
$itemStmt = $db->prepare("
    SELECT oi.*, p.product_name, p.model 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .gateway-card { max-width: 840px; margin: 30px auto; border-radius: 16px; box-shadow: 0 10px 35px rgba(0,0,0,0.08); border: none; overflow: hidden; }
        .bank-brand-header { background: linear-gradient(135deg, #0f172a, #1e3a8a); color: white; padding: 24px 30px; }
        .nav-pills .nav-link { color: #475569; font-weight: 600; padding: 12px 18px; border-radius: 10px; margin-bottom: 6px; }
        .nav-pills .nav-link.active { background-color: #1e40af; color: white; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="card gateway-card">
        
        <!-- Header -->
        <div class="bank-brand-header d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white bg-opacity-10 p-3 rounded-3 text-warning">
                    <i class="fa-solid fa-shield-halved fa-2x"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">MobileKart Secure Checkout</h4>
                    <p class="text-white-50 mb-0 small">256-Bit SSL Encrypted Payment Gateway</p>
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-success px-3 py-2 fw-bold text-uppercase">
                    <i class="fa-solid fa-lock me-1"></i> Verified &amp; Secure
                </span>
                <div class="text-white-50 small mt-1">PCI-DSS Compliant</div>
            </div>
        </div>

        <div class="card-body p-4 p-md-5 bg-white">
            
            <!-- Order Reference Bar -->
            <div class="bg-light p-3 rounded-4 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border">
                <div>
                    <div class="small text-muted">Merchant: <strong>MobileKart Distribution India</strong></div>
                    <div class="small text-muted">Order Ref: <code><?= htmlspecialchars($order['order_number']) ?></code> • Customer: <strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
                </div>
                <div class="text-md-end">
                    <div class="small text-muted">Total Payable Amount</div>
                    <div class="fs-3 fw-bold text-primary"><?= format_inr($order['total_amount']) ?></div>
                </div>
            </div>

            <!-- Payment Simulation Options Form -->
            <form action="callback.php" method="POST" id="gatewayForm">
                <input type="hidden" name="token" value="<?= htmlspecialchars($tokenParam) ?>">
                <input type="hidden" name="payment_id" value="<?= $paymentId ?>">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                <input type="hidden" name="payment_decision" id="paymentDecision" value="SUCCESS">
                <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="UPI">

                <div class="row g-4 mb-4">
                    <!-- Left: Payment Method Tabs -->
                    <div class="col-md-4 border-end">
                        <div class="nav flex-column nav-pills" id="methodTabs" role="tablist">
                            <button class="nav-link active text-start" id="upi-tab" data-bs-toggle="pill" data-bs-target="#upi-pane" type="button" role="tab" onclick="setMethod('UPI')">
                                <i class="fa-solid fa-mobile-screen me-2 text-primary"></i> UPI (Instant Pay)
                            </button>
                            <button class="nav-link text-start" id="card-tab" data-bs-toggle="pill" data-bs-target="#card-pane" type="button" role="tab" onclick="setMethod('DEBIT_CARD')">
                                <i class="fa-solid fa-credit-card me-2 text-success"></i> Credit / Debit Card
                            </button>
                            <button class="nav-link text-start" id="net-tab" data-bs-toggle="pill" data-bs-target="#net-pane" type="button" role="tab" onclick="setMethod('NET_BANKING')">
                                <i class="fa-solid fa-landmark me-2 text-warning"></i> Net Banking
                            </button>
                        </div>
                    </div>

                    <!-- Right: Method Input Simulator -->
                    <div class="col-md-8">
                        <div class="tab-content" id="methodPanes">
                            
                            <!-- 1. UPI Tab -->
                            <div class="tab-pane fade show active" id="upi-pane" role="tabpanel">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-qrcode text-primary me-2"></i> UPI Virtual Payment Address</h6>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold">Enter your UPI ID / VPA</label>
                                    <input type="text" class="form-control" placeholder="username@upi or mobile@upi">
                                    <small class="text-muted">Supports Google Pay, PhonePe, Paytm, and BHIM UPI.</small>
                                </div>
                                <div class="d-flex gap-2 mb-3">
                                    <span class="badge bg-light text-dark border p-2"><i class="fa-brands fa-google-pay fs-5 text-primary"></i> Google Pay</span>
                                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-wallet text-primary fs-5"></i> PhonePe</span>
                                    <span class="badge bg-light text-dark border p-2"><i class="fa-solid fa-mobile-button text-info fs-5"></i> Paytm</span>
                                </div>
                            </div>

                            <!-- 2. Card Tab -->
                            <div class="tab-pane fade" id="card-pane" role="tabpanel">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-credit-card text-success me-2"></i> Card Details</h6>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold">Card Number</label>
                                    <input type="text" class="form-control" placeholder="Card Number (16 digits)">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small text-secondary fw-semibold">Expiry Date</label>
                                        <input type="text" class="form-control" placeholder="MM/YY">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-secondary fw-semibold">CVV</label>
                                        <input type="password" class="form-control" placeholder="CVV" maxlength="3">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold">Cardholder Name</label>
                                    <input type="text" class="form-control" placeholder="Name on Card" value="<?= htmlspecialchars($order['customer_name']) ?>">
                                </div>
                            </div>

                            <!-- 3. Net Banking Tab -->
                            <div class="tab-pane fade" id="net-pane" role="tabpanel">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-landmark text-warning me-2"></i> Indian Net Banking</h6>
                                <div class="mb-3">
                                    <label class="form-label small text-secondary fw-semibold">Choose Your Bank</label>
                                    <select class="form-select">
                                        <option selected>State Bank of India (SBI)</option>
                                        <option>HDFC Bank</option>
                                        <option>ICICI Bank</option>
                                        <option>Axis Bank</option>
                                        <option>Punjab National Bank (PNB)</option>
                                        <option>Kotak Mahindra Bank</option>
                                    </select>
                                </div>
                                <p class="small text-muted">You will be securely redirected to your bank's authentication gateway.</p>
                            </div>

                        </div>

                        <!-- Pay Button -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-success btn-lg w-100 fw-bold py-3 shadow-sm mb-3" onclick="submitDecision('SUCCESS')">
                                <i class="fa-solid fa-shield-halved me-2"></i> Pay <?= format_inr($order['total_amount']) ?> Securely
                            </button>

                            <div class="text-center">
                                <button type="button" class="btn btn-link text-muted text-decoration-none small" onclick="submitDecision('CANCELLED')">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Cancel transaction and return to shopping cart
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </form>

        </div>
        <div class="card-footer bg-light text-center py-3 text-muted small border-top">
            <i class="fa-solid fa-lock text-success me-1"></i> 256-Bit SSL Encrypted • PCI-DSS Compliant • MobileKart Payments
        </div>
    </div>
</div>

<script>
function setMethod(method) {
    document.getElementById('selectedPaymentMethod').value = method;
}

function submitDecision(decision) {
    document.getElementById('paymentDecision').value = decision;
    document.getElementById('gatewayForm').submit();
}
</script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
