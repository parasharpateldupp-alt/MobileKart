<?php
$pageTitle = "Contact Us - Online Mobile Purchasing & Distributing System";
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-5">
    <div class="row mb-5 text-center">
        <div class="col-lg-8 mx-auto">
            <span class="badge bg-primary px-3 py-2 text-uppercase mb-2">Get in Touch</span>
            <h1 class="display-5 fw-bold text-dark">Contact Customer Support & Distribution</h1>
            <p class="lead text-muted">Have inquiries regarding smartphone stock allotments, regional supplier distribution, or order fulfillment?</p>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h4 class="fw-bold mb-4">Distribution Headquarters</h4>
                <div class="d-flex mb-3">
                    <i class="fa-solid fa-location-dot text-primary fa-xl me-3 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Central Warehouse & Admin Hub</h6>
                        <p class="text-muted small mb-0">Dandi Daji Faliya, Taluka & Dist. Valsad, Gujarat - 396385</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <i class="fa-solid fa-phone text-success fa-xl me-3 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Administrator Hotline</h6>
                        <p class="text-muted small mb-0">+91 9974410030 (Mon-Sat, 9:00 AM - 6:00 PM)</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <i class="fa-solid fa-envelope text-warning fa-xl me-3 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Official Support Email</h6>
                        <p class="text-muted small mb-0">support@mobilekart-distribution.com</p>
                    </div>
                </div>
                <div class="d-flex mb-3">
                    <i class="fa-solid fa-building-columns text-info fa-xl me-3 mt-1"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Academic Affiliation</h6>
                        <p class="text-muted small mb-0">Dolat-Usha Institute of Applied Sciences & Dhiru-Sarla Institute of Management & Commerce, Valsad (VNSGU)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h4 class="fw-bold mb-4">Send a Support Message</h4>
                <form onsubmit="event.preventDefault(); alert('Thank you! Your academic demonstration inquiry has been recorded.');">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="Enter your name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" placeholder="10-digit mobile" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inquiry Subject</label>
                            <select class="form-select">
                                <option>Order & Shipment Tracking</option>
                                <option>Supplier Onboarding & Allotment</option>
                                <option>Warranty & Device Specifications</option>
                                <option>General Inquiries</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Detailed Message</label>
                            <textarea class="form-control" rows="4" placeholder="Type your query here..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4 py-2">Submit Inquiry</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
