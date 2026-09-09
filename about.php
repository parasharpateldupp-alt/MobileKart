<?php
$pageTitle = "About Us - Online Mobile Purchasing & Distributing System";
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row mb-5 text-center">
        <div class="col-lg-8 mx-auto">
            <span class="badge bg-primary px-3 py-2 text-uppercase mb-2">Corporate Profile</span>
            <h1 class="display-5 fw-bold text-dark">About MobileKart Distribution</h1>
            <p class="lead text-muted">A Comprehensive 3-Tier Enterprise Smartphone Procurement, Inventory Control & Distribution Management Platform.</p>
        </div>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                <div class="feature-icon bg-primary text-white mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-mobile-screen-button fa-xl"></i>
                </div>
                <h4 class="fw-bold">100% Genuine Flagships</h4>
                <p class="text-muted">Direct manufacturer allotments with verified IMEI barcodes and comprehensive 1-year brand warranty on all devices.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                <div class="feature-icon bg-success text-white mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-truck-fast fa-xl"></i>
                </div>
                <h4 class="fw-bold">Fast Regional Distribution</h4>
                <p class="text-muted">Optimized multi-depot fulfillment network with Ekart and Blue Dart express logistics offering 7-stage consignment milestone tracking.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                <div class="feature-icon bg-warning text-dark mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-file-invoice-dollar fa-xl"></i>
                </div>
                <h4 class="fw-bold">Automated GST Compliance</h4>
                <p class="text-muted">Compliant with Indian taxation laws (HSN 85171300), auto-calculating 18% GST (9% CGST + 9% SGST) with instant downloadable tax invoices.</p>
            </div>
        </div>
    </div>
    <div class="card bg-light border-0 p-5 rounded-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h3 class="fw-bold text-dark mb-3">Academic Capstone Information</h3>
                <p class="text-muted">This project is developed as part of the 5th Semester Computer Application curriculum affiliated with <strong>Veer Narmad South Gujarat University (VNSGU)</strong> at <strong>Dolat-Usha Institute of Applied Sciences & Dhiru-Sarla Institute of Management & Commerce, Valsad</strong>.</p>
                <ul class="list-unstyled text-muted">
                    <li><i class="fa-solid fa-check text-success me-2"></i> Architecture: 3-Tier Multi-Actor Web Platform (Customer, Supplier, Administrator)</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Backend: Modular PHP 8.2 & MySQL 8.4 Relational Database</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Concurrency Control: 15-Minute Temporary Stock Lock (P1.3.3)</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Security: BCRYPT Password Hashing & HMAC-SHA256 Payment Signing</li>
                </ul>
            </div>
            <div class="col-lg-5 text-center">
                <div class="p-4 bg-white rounded-3 shadow-sm">
                    <i class="fa-solid fa-graduation-cap fa-4x text-primary mb-3"></i>
                    <h5 class="fw-bold">Academic Project 2025-2026</h5>
                    <p class="text-muted small mb-0">Project: Online Mobile Purchasing & Distributing System</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
