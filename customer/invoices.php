<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * GST Tax Invoice Viewer & Print Layout (DFD P1.4.3)
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$invoiceId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$db = get_db_connection();

// Fetch invoice
$stmt = $db->prepare("
    SELECT i.*, o.order_number, o.order_date, o.shipping_phone, o.shipping_name,
           u.name AS customer_name, u.email AS customer_email,
           p.transaction_id, p.payment_method, p.payment_date
    FROM invoices i
    JOIN orders o ON i.order_id = o.order_id
    JOIN users u ON i.customer_id = u.user_id
    LEFT JOIN payments p ON i.payment_id = p.payment_id
    WHERE i.invoice_id = ? AND (i.customer_id = ? OR ? = 'ADMIN')
");
$stmt->execute([$invoiceId, $userId, $_SESSION['user_role']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: " . BASE_URL . "/customer/orders.php");
    exit;
}

$pageTitle = "Invoice " . htmlspecialchars($invoice['invoice_number']) . " - MobileKart";

// Fetch Invoice Items
$itemStmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$itemStmt->execute([$invoiceId]);
$invoiceItems = $itemStmt->fetchAll();

// Split Tax into CGST (9%) and SGST (9%)
$cgst = round($invoice['tax_amount'] / 2, 2);
$sgst = round($invoice['tax_amount'] / 2, 2);
$taxableValue = $invoice['total_amount'] - $invoice['tax_amount'] - $invoice['shipping_charge'];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<style>
@media print {
    body { background: #ffffff !important; padding: 0 !important; }
    .no-print, .fk-navbar, .fk-footer, .breadcrumb, nav { display: none !important; }
    .invoice-card { box-shadow: none !important; border: 1px solid #000 !important; width: 100% !important; margin: 0 !important; }
}
.invoice-card { max-width: 900px; margin: 30px auto; border-radius: 12px; }
</style>

<div class="container py-4">
    <!-- Controls Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-primary me-2"></i> Tax Invoice</h4>
            <small class="text-muted">Official GST compliant tax invoice</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/customer/orders.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Orders
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold">
                <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Printable Invoice Card -->
    <div class="card border-0 shadow-sm invoice-card p-4 p-md-5 bg-white">
        
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-start border-bottom pb-4 mb-4 gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-mobile-screen-button text-primary fs-3"></i>
                    <h3 class="fw-bold mb-0 text-dark">Mobile<span class="text-primary">Kart</span></h3>
                </div>
                <div class="small text-secondary">
                    <strong>MobileKart Distribution India Pvt Ltd</strong><br>
                    Plot 45, Electronics City Phase 1, Hosur Road<br>
                    Bengaluru, Karnataka - 560100<br>
                    <strong>GSTIN:</strong> 29AAAAA0000A1Z5 | <strong>State Code:</strong> 29 (Karnataka)<br>
                    <strong>CIN:</strong> U72200KA2026PTC088219
                </div>
            </div>

            <div class="text-md-end">
                <span class="badge bg-primary text-uppercase px-3 py-2 fw-bold mb-2">Tax Invoice</span>
                <div class="fs-5 fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['invoice_number']) ?></div>
                <div class="small text-muted">Invoice Date: <strong><?= date('d M Y, h:i A', strtotime($invoice['invoice_date'])) ?></strong></div>
                <div class="small text-muted">Order Ref: <code><?= htmlspecialchars($invoice['order_number']) ?></code></div>
                <div class="small text-muted">Payment: <strong><?= htmlspecialchars($invoice['payment_method'] ?? 'ONLINE') ?></strong> (<?= htmlspecialchars($invoice['transaction_id'] ?? 'PAID') ?>)</div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 border-end">
                <div class="text-uppercase fw-bold text-secondary small mb-1">Billed &amp; Shipped To:</div>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['customer_name']) ?></h6>
                <div class="small text-secondary mb-1"><?= nl2br(htmlspecialchars($invoice['billing_address'])) ?></div>
                <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($invoice['shipping_phone']) ?></div>
                <div class="small text-muted"><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($invoice['customer_email']) ?></div>
            </div>
            <div class="col-md-6 ps-md-4">
                <div class="text-uppercase fw-bold text-secondary small mb-1">Supply Information:</div>
                <div class="small text-secondary mb-1">Place of Supply: <strong>Karnataka (State Code 29)</strong></div>
                <div class="small text-secondary mb-1">Nature of Transaction: <strong>Intra-State / B2C Supply</strong></div>
                <div class="small text-secondary mb-1">Invoice Status: <span class="badge bg-success">PAID / SETTLED</span></div>
                <div class="small text-secondary">Reverse Charge: <strong>No</strong></div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Description of Smartphone Goods</th>
                        <th style="width: 12%;">HSN Code</th>
                        <th class="text-center" style="width: 8%;">Qty</th>
                        <th class="text-end" style="width: 15%;">Unit Rate</th>
                        <th class="text-end" style="width: 15%;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceItems as $idx => $it): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($it['product_name']) ?></strong>
                                <div class="small text-muted">Model: <?= htmlspecialchars($it['model']) ?></div>
                            </td>
                            <td><code>8517 12 00</code></td>
                            <td class="text-center fw-bold"><?= $it['quantity'] ?></td>
                            <td class="text-end"><?= format_inr($it['unit_price'], false) ?></td>
                            <td class="text-end fw-bold"><?= format_inr($it['total_price'], false) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Financial Summary Breakdown -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless small mb-0">
                    <tr>
                        <td class="text-secondary">Subtotal (Item Amount):</td>
                        <td class="text-end fw-semibold"><?= format_inr($invoice['subtotal']) ?></td>
                    </tr>
                    <?php if ($invoice['discount'] > 0): ?>
                        <tr class="text-success">
                            <td>Coupon Discount:</td>
                            <td class="text-end fw-semibold">- <?= format_inr($invoice['discount']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-secondary">CGST (9.0%):</td>
                        <td class="text-end"><?= format_inr($cgst) ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">SGST (9.0%):</td>
                        <td class="text-end"><?= format_inr($sgst) ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Shipping &amp; Logistics:</td>
                        <td class="text-end"><?= $invoice['shipping_charge'] > 0 ? format_inr($invoice['shipping_charge']) : 'FREE' ?></td>
                    </tr>
                    <tr class="border-top border-dark fs-6 fw-bold">
                        <td class="pt-2 text-dark">Net Invoice Total:</td>
                        <td class="pt-2 text-end text-primary"><?= format_inr($invoice['total_amount']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer / Declaration -->
        <div class="border-top pt-3 text-muted small mt-4">
            <div class="row align-items-end">
                <div class="col-8">
                    <strong>Declaration:</strong><br>
                    We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct. Goods once sold are covered under respective manufacturer warranty terms.
                </div>
                <div class="col-4 text-end">
                    <div class="fw-bold text-dark">For MobileKart Distribution</div>
                    <div class="mt-4 pt-3 border-top border-secondary small text-secondary">Authorized Signatory</div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
