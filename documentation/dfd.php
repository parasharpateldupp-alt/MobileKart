<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Interactive Data Flow Diagram (DFD) Architecture & Technical Specification
 */

$pageTitle = "DFD Architecture & Technical Specification - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<!-- Mermaid.js for Dynamic Interactive DFD Diagrams -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.9.0/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({
        startOnLoad: true,
        theme: 'neutral',
        flowchart: { useMaxWidth: true, htmlLabels: true, curve: 'basis' }
    });
</script>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #1e40af, #2874f0);">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark fw-bold mb-2"><i class="fa-solid fa-graduation-cap me-1"></i> Academic Project DFD Specification</span>
                    <h2 class="fw-bold mb-2">Data Flow Diagram (DFD) Implementation</h2>
                    <p class="mb-0 text-white-50 lead fs-6">
                        Complete architectural decomposition of the <strong>Online Mobile Purchasing & Distributing System</strong> across Level 0 (Context Diagram), Level 1 (Functional Decomposition), and Level 2 (Order Processing & Payment Subprocesses) mapped directly to PHP 8+ modules and MySQL InnoDB tables.
                    </p>
                </div>
                <div class="col-lg-4 text-center mt-3 mt-lg-0">
                    <div class="bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-25">
                        <div class="h4 fw-bold mb-0 text-warning">5 Processes • 5 Data Stores</div>
                        <div class="small text-white-50">P1.1 – P1.5 & D1 – D5 Verified</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills nav-fill mb-4 p-2 bg-white shadow-sm rounded-3 border" id="dfdTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="l0-tab" data-bs-toggle="pill" data-bs-target="#l0-pane" type="button" role="tab">
                <i class="fa-solid fa-circle-nodes me-1"></i> Level 0: Context Diagram
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="l1-tab" data-bs-toggle="pill" data-bs-target="#l1-pane" type="button" role="tab">
                <i class="fa-solid fa-sitemap me-1"></i> Level 1: Functional Decomposition
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="l2-order-tab" data-bs-toggle="pill" data-bs-target="#l2-order-pane" type="button" role="tab">
                <i class="fa-solid fa-cart-shopping me-1"></i> Level 2: Order Processing (P1.3)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="l2-pay-tab" data-bs-toggle="pill" data-bs-target="#l2-pay-pane" type="button" role="tab">
                <i class="fa-solid fa-credit-card me-1"></i> Level 2: Payment & Billing (P1.4)
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="matrix-tab" data-bs-toggle="pill" data-bs-target="#matrix-pane" type="button" role="tab">
                <i class="fa-solid fa-table me-1"></i> Code & DB Traceability Matrix
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="dfdTabContent">
        
        <!-- =========================================================================
             LEVEL 0: CONTEXT DIAGRAM
        ========================================================================== -->
        <div class="tab-pane fade show active" id="l0-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-circle-nodes me-2"></i> Level 0: System Context Diagram</h5>
                    <span class="badge bg-primary">High-Level Boundary</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary">
                        The <strong>Context Diagram (Level 0)</strong> models the system boundary. The central entity is the <strong>Online Mobile Purchasing & Distributing System</strong>, interacting with 5 primary external entities: <strong>ADMIN</strong>, <strong>SUPPLIER</strong>, <strong>CUSTOMER</strong>, <strong>BANK GATEWAY</strong>, and <strong>ANALYTICS & REPORTS</strong>.
                    </p>

                    <div class="p-3 bg-light rounded-4 border text-center overflow-auto my-3">
                        <div class="mermaid">
flowchart TD
    subgraph ExternalEntities["External Entities"]
        ADMIN["1. ADMIN"]
        SUPPLIER["2. SUPPLIER"]
        CUSTOMER["3. CUSTOMER"]
        BANK["4. BANK GATEWAY (Mock)"]
        ANALYTICS["5. ANALYTICS & REPORTS"]
    end

    SYSTEM(("0. ONLINE MOBILE<br/>PURCHASING &amp; DISTRIBUTING<br/>SYSTEM"))

    ADMIN -->|Login, Config, Stock Updates, Promos| SYSTEM
    SYSTEM -->|Dashboard, Reports, Order Status, User Logs| ADMIN

    SUPPLIER -->|Registration, Product Data, Restock, Shipment| SYSTEM
    SYSTEM -->|Purchase Orders, Sales Forecast, Portal Info| SUPPLIER

    CUSTOMER -->|Registration, Search, Orders, Reviews| SYSTEM
    SYSTEM -->|Order Confirmation, Catalog, Invoices, Delivery Status| CUSTOMER

    SYSTEM -->|Payment Auth Request / Cancel| BANK
    BANK -->|Auth / Cancel Confirmation Token| SYSTEM

    SYSTEM -->|Transactional &amp; Financial Ledger Data| ANALYTICS
    ANALYTICS -->|Performance Metrics &amp; Visual Reports| ADMIN

    style SYSTEM fill:#2874f0,stroke:#1e40af,stroke-width:3px,color:#fff
    style ADMIN fill:#fee2e2,stroke:#ef4444,stroke-width:2px,color:#991b1b
    style SUPPLIER fill:#fef3c7,stroke:#f59e0b,stroke-width:2px,color:#92400e
    style CUSTOMER fill:#dbeafe,stroke:#3b82f6,stroke-width:2px,color:#1e40af
    style BANK fill:#e0e7ff,stroke:#6366f1,stroke-width:2px,color:#3730a3
    style ANALYTICS fill:#f3e8ff,stroke:#a855f7,stroke-width:2px,color:#6b21a8
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                <h6 class="fw-bold text-danger"><i class="fa-solid fa-user-shield me-2"></i> 1. ADMIN Entity</h6>
                                <p class="small text-muted mb-0">System configuration, inventory controls, order oversight, payment reconciliation, fulfillment management, support tickets, and activity audit logs.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                <h6 class="fw-bold text-warning"><i class="fa-solid fa-warehouse me-2"></i> 2. SUPPLIER Entity</h6>
                                <p class="small text-muted mb-0">Mobile catalog onboarding, real-time stock updates, restock threshold triggers, purchase orders, sales revenue reports, and shipment manifests.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                <h6 class="fw-bold text-primary"><i class="fa-solid fa-user me-2"></i> 3. CUSTOMER Entity</h6>
                                <p class="small text-muted mb-0">Account registration, mobile phone faceted search, cart management, checkout with stock reservation, payments, order tracking, and reviews.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                <h6 class="fw-bold text-indigo"><i class="fa-solid fa-building-columns me-2"></i> 4. BANK GATEWAY Entity (Mock)</h6>
                                <p class="small text-muted mb-0">Simulates academic UPI, Credit/Debit card OTP authentication, netbanking, returns signed transaction authorization responses or cancellation codes.</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white shadow-sm">
                                <h6 class="fw-bold text-purple"><i class="fa-solid fa-chart-line me-2"></i> 5. ANALYTICS & REPORTS Entity</h6>
                                <p class="small text-muted mb-0">Aggregates orders, revenue trends, top smartphone brands, stock levels, and generates interactive Chart.js visualizations for the executive administrator.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             LEVEL 1: FUNCTIONAL DECOMPOSITION
        ========================================================================== -->
        <div class="tab-pane fade" id="l1-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-sitemap me-2"></i> Level 1: Functional Decomposition</h5>
                    <span class="badge bg-success">5 Core Processes &amp; 5 Data Stores</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary">
                        Level 1 breaks the central system into five interconnected sub-processes (<code>P1.1</code> to <code>P1.5</code>) and five designated relational data stores (<code>D1</code> to <code>D5</code>).
                    </p>

                    <div class="p-3 bg-light rounded-4 border text-center overflow-auto my-3">
                        <div class="mermaid">
flowchart TB
    C[Customer]
    S[Supplier]
    A[Admin]
    BG[Bank Gateway]
    AR[Analytics Engine]

    subgraph Processes ["Level 1 Functional Processes"]
        P1["P1.1 Manage User Accounts"]
        P2["P1.2 Catalog &amp; Inventory"]
        P3["P1.3 Order Processing"]
        P4["P1.4 Payment &amp; Billing"]
        P5["P1.5 Fulfillment &amp; Tracking"]
    end

    subgraph DataStores ["Relational Data Stores"]
        D1[("D1: USER DB")]
        D2[("D2: PRODUCT / INVENTORY DB")]
        D3[("D3: ORDERS DB")]
        D4[("D4: BILLING DB")]
        D5[("D5: FULFILLMENT DB")]
    end

    C -->|Credentials / Address| P1
    P1 <-->|Read / Write Users &amp; Profiles| D1
    P1 -->|User Logs| A

    S -->|Product Specs / Restock| P2
    P2 <-->|Products / Inventory Stock| D2
    P2 -.->|Stock Level Alert Check| P3

    C -->|Product Search / Catalog| P2
    C -->|Cart Items / Checkout| P3
    P3 <-->|Orders / Order Items / Held Stock| D3
    P3 -->|Order for Payment| P4

    P4 <-->|Auth Token / Response| BG
    P4 <-->|Payments / Invoices / Ledger| D4
    P4 -->|Paid Confirmation| P5

    P5 <-->|Shipment &amp; Tracking Events| D5
    P5 -->|Live Tracking Status| C
    S -->|Dispatch Courier Details| P5
    A -->|Manage Fulfillment Pipeline| P5

    D3 -.-> AR
    D4 -.-> AR
    AR -->|Revenue &amp; Status Reports| A
                        </div>
                    </div>

                    <!-- Process Descriptions -->
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Process</th>
                                    <th style="width: 20%;">Functionality</th>
                                    <th style="width: 25%;">Primary Data Store</th>
                                    <th style="width: 40%;">DFD Inputs &amp; Outputs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="dfd-tag dfd-p1 fs-6">P1.1</span><br><strong>Manage User Accounts</strong></td>
                                    <td>Authentication, customer &amp; supplier onboarding, role verification, profile updates.</td>
                                    <td><code>D1: USER DB</code><br><small class="text-muted">tables: users, suppliers</small></td>
                                    <td>
                                        <strong>Inputs:</strong> Registration form, Login credentials, Address changes.<br>
                                        <strong>Outputs:</strong> Session cookies, Profile confirmation, Audit logs.
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p2 fs-6">P1.2</span><br><strong>Catalog &amp; Inventory</strong></td>
                                    <td>Mobile phone catalog, brand filters, specs tables, stock quantity management, restock alerts.</td>
                                    <td><code>D2: PRODUCT / INVENTORY DB</code><br><small class="text-muted">tables: products, categories, brands, inventory_transactions</small></td>
                                    <td>
                                        <strong>Inputs:</strong> Supplier specs, manual adjustments, search queries.<br>
                                        <strong>Outputs:</strong> Product catalog, stock alerts, low-stock warnings.
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p3 fs-6">P1.3</span><br><strong>Order Processing</strong></td>
                                    <td>Cart management, order verification, subtotal/tax calculation, atomic stock reservations.</td>
                                    <td><code>D3: ORDERS DB</code><br><small class="text-muted">tables: cart, cart_items, orders, order_items, stock_reservations</small></td>
                                    <td>
                                        <strong>Inputs:</strong> Verified cart items, shipping address.<br>
                                        <strong>Outputs:</strong> Order confirmation, stock hold, order for payment.
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p4 fs-6">P1.4</span><br><strong>Payment &amp; Billing</strong></td>
                                    <td>Customer authentication verification, mock bank authorization, GST tax invoice generation.</td>
                                    <td><code>D4: BILLING DB</code><br><small class="text-muted">tables: payments, invoices, invoice_items</small></td>
                                    <td>
                                        <strong>Inputs:</strong> Signed payment token, bank auth code.<br>
                                        <strong>Outputs:</strong> Payment status, itemized invoice, ledger update.
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p5 fs-6">P1.5</span><br><strong>Fulfillment &amp; Tracking</strong></td>
                                    <td>Order packing, carrier assignment, tracking ID, real-time shipment milestone timeline.</td>
                                    <td><code>D5: FULFILLMENT DB</code><br><small class="text-muted">tables: fulfillments, shipment_events</small></td>
                                    <td>
                                        <strong>Inputs:</strong> Confirmed order, dispatch manifest, courier tracking ID.<br>
                                        <strong>Outputs:</strong> Tracking timeline, estimated delivery date, delivery confirmation.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             LEVEL 2: ORDER PROCESSING DETAIL (P1.3)
        ========================================================================== -->
        <div class="tab-pane fade" id="l2-order-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-warning text-dark"><i class="fa-solid fa-cart-shopping me-2"></i> Level 2: Subprocesses of P1.3 Order Processing</h5>
                    <span class="badge bg-warning text-dark">P1.3.1, P1.3.2, P1.3.3</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary">
                        In accordance with the project DFD requirements, <strong>P1.3 Order Processing</strong> is decomposed into three distinct, atomic subprocesses to guarantee stock integrity and prevent overselling during high-traffic flash sales:
                    </p>

                    <div class="p-3 bg-light rounded-4 border text-center overflow-auto my-3">
                        <div class="mermaid">
flowchart LR
    Cart["Verified Cart"] --> P131["P1.3.1<br/>VERIFY ORDER"]
    D2[("D2: Product &amp; Stock")] <-->|Check Availability| P131
    P131 -->|Validated Items &amp; Qty| P132["P1.3.2<br/>CALCULATE SUB-TOTAL"]
    P132 -->|Subtotal + 18% GST + Shipping| P133["P1.3.3<br/>RESERVE STOCK"]
    P133 <-->|Lock Stock with 15-min TTL| D3Res[("D3: stock_reservations")]
    P133 -->|Reserve Request Ready| PayFlow["To P1.4 Payment Gateway"]

    style P131 fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#92400e
    style P132 fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#92400e
    style P133 fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#92400e
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold text-warning text-dark"><span class="badge bg-warning text-dark me-1">P1.3.1</span> Verify Order</h6>
                                <p class="small text-muted">
                                    Verifies that customer is authenticated, cart is not empty, all products are active, requested quantity does not exceed available stock, and shipping address is valid.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>customer/checkout.php</code><br>
                                    <strong>Validation:</strong> <code>stock_quantity >= cart_qty</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold text-warning text-dark"><span class="badge bg-warning text-dark me-1">P1.3.2</span> Calculate Sub-total</h6>
                                <p class="small text-muted">
                                    Computes <code>unit_price × quantity</code>, cart subtotal, applies promo discount, calculates 18% GST, checks free shipping threshold (₹5,000), and outputs grand total.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>customer/checkout.php</code><br>
                                    <strong>Formula:</strong> <code>Total = Subtotal - Discount + Tax + Shipping</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold text-warning text-dark"><span class="badge bg-warning text-dark me-1">P1.3.3</span> Reserve Stock</h6>
                                <p class="small text-muted">
                                    Inserts into <code>stock_reservations</code> with status <code>ACTIVE</code> and 15-minute expiry. Prevents overselling. Released if payment fails; committed permanently on payment success.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>customer/checkout.php</code><br>
                                    <strong>Data Store:</strong> <code>stock_reservations</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             LEVEL 2: PAYMENT & BILLING DETAIL (P1.4)
        ========================================================================== -->
        <div class="tab-pane fade" id="l2-pay-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-purple"><i class="fa-solid fa-credit-card me-2"></i> Level 2: Subprocesses of P1.4 Payment & Billing</h5>
                    <span class="badge bg-purple text-white" style="background-color: #9333ea;">P1.4.1, P1.4.2, P1.4.3</span>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary">
                        <strong>P1.4 Payment & Billing</strong> guarantees financial security through customer verification, simulation via the safe mock bank gateway, and automated tax invoice creation:
                    </p>

                    <div class="p-3 bg-light rounded-4 border text-center overflow-auto my-3">
                        <div class="mermaid">
flowchart LR
    OrderInfo["Order for Payment"] --> P141["P1.4.1<br/>VERIFY CUSTOMER AUTH"]
    P141 -->|Encrypted Payment Token| P142["P1.4.2<br/>PROCESS GATEWAY AUTH"]
    BG["Safe Mock Bank Gateway"] <-->|Auth Success / Decline / Cancel| P142
    P142 -->|On Success| P143["P1.4.3<br/>GENERATE INVOICE"]
    P143 -->|Store Invoice Record| D4[("D4: invoices &amp; ledger")]
    P142 -->|On Failure| Release["Release Stock Reservation<br/>(P1.3.3)"]

    style P141 fill:#fae8ff,stroke:#a21caf,stroke-width:2px,color:#701a75
    style P142 fill:#fae8ff,stroke:#a21caf,stroke-width:2px,color:#701a75
    style P143 fill:#fae8ff,stroke:#a21caf,stroke-width:2px,color:#701a75
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold" style="color: #9333ea;"><span class="badge bg-secondary me-1">P1.4.1</span> Verify Customer Auth</h6>
                                <p class="small text-muted">
                                    Validates customer session, verifies order ownership, builds an encrypted/HMAC-signed transaction token containing order ID, amount, timestamp, and customer ID.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>payment/checkout_process.php</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold" style="color: #9333ea;"><span class="badge bg-secondary me-1">P1.4.2</span> Process Gateway Auth</h6>
                                <p class="small text-muted">
                                    Academic Safe Mock Gateway simulating Indian banking (UPI, Cards, Netbanking). Supports interactive Success, Decline, and Cancel simulation without real funds.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>payment/mock_gateway.php</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border h-100 p-3 bg-white shadow-sm">
                                <h6 class="fw-bold" style="color: #9333ea;"><span class="badge bg-secondary me-1">P1.4.3</span> Generate Invoice</h6>
                                <p class="small text-muted">
                                    Generates tax-compliant GST invoice number (e.g. <code>INV-2026-00081</code>), persists snapshot in <code>invoices</code> and <code>invoice_items</code>, provides print layout.
                                </p>
                                <div class="small bg-light p-2 rounded">
                                    <strong>Source File:</strong> <code>customer/invoices.php</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             CODE & DB TRACEABILITY MATRIX
        ========================================================================== -->
        <div class="tab-pane fade" id="matrix-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-table me-2"></i> DFD to Code &amp; Database Traceability Matrix</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary">
                        Every process and data store in the Data Flow Diagram corresponds to explicit, modular PHP scripts and MySQL database tables:
                    </p>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>DFD Element</th>
                                    <th>Title</th>
                                    <th>Concrete Source Code File(s)</th>
                                    <th>Database Table(s)</th>
                                    <th>Live URL / Endpoint</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="dfd-tag dfd-p1">P1.1</span></td>
                                    <td><strong>Manage User Accounts</strong></td>
                                    <td><code>includes/auth.php</code>, <code>login.php</code>, <code>register.php</code></td>
                                    <td><code>users</code>, <code>suppliers</code></td>
                                    <td><a href="<?= BASE_URL ?>/login.php" class="btn btn-sm btn-outline-primary">Open Login</a></td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p2">P1.2</span></td>
                                    <td><strong>Catalog &amp; Inventory</strong></td>
                                    <td><code>customer/products.php</code>, <code>customer/product-details.php</code>, <code>admin/products.php</code>, <code>supplier/inventory.php</code></td>
                                    <td><code>products</code>, <code>categories</code>, <code>brands</code>, <code>inventory_transactions</code></td>
                                    <td><a href="<?= BASE_URL ?>/customer/products.php" class="btn btn-sm btn-outline-primary">Open Catalog</a></td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p3">P1.3</span></td>
                                    <td><strong>Order Processing</strong></td>
                                    <td><code>customer/cart.php</code>, <code>customer/checkout.php</code>, <code>api/cart.php</code></td>
                                    <td><code>cart</code>, <code>cart_items</code>, <code>orders</code>, <code>order_items</code>, <code>stock_reservations</code></td>
                                    <td><a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-sm btn-outline-primary">Open Cart</a></td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p4">P1.4</span></td>
                                    <td><strong>Payment &amp; Billing</strong></td>
                                    <td><code>payment/checkout_process.php</code>, <code>payment/mock_gateway.php</code>, <code>customer/invoices.php</code></td>
                                    <td><code>payments</code>, <code>invoices</code>, <code>invoice_items</code></td>
                                    <td><a href="<?= BASE_URL ?>/payment/mock_gateway.php" class="btn btn-sm btn-outline-primary">Open Gateway</a></td>
                                </tr>
                                <tr>
                                    <td><span class="dfd-tag dfd-p5">P1.5</span></td>
                                    <td><strong>Fulfillment &amp; Tracking</strong></td>
                                    <td><code>customer/tracking.php</code>, <code>admin/fulfillment.php</code>, <code>supplier/shipments.php</code></td>
                                    <td><code>fulfillments</code>, <code>shipment_events</code></td>
                                    <td><a href="<?= BASE_URL ?>/customer/tracking.php" class="btn btn-sm btn-outline-primary">Open Tracking</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
