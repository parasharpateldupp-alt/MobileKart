<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Public Storefront Footer Component
 */
require_once __DIR__ . '/../config/constants.php';
?>
</main>

<footer class="fk-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About -->
            <div class="col-6 col-lg-3">
                <div class="fk-footer-heading">ABOUT MOBILEKART</div>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/index.php">About Us</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/products.php">Smartphones Catalog</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/products.php?filter=trending">Trending 5G Devices</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/register.php?role=supplier">Sell on MobileKart</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/products.php?sort=discount_desc">Top Discount Offers</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/documentation/image-sources.php"><i class="fa-solid fa-scale-balanced me-1 text-warning"></i> Image Sources &amp; Licenses</a></li>
                </ul>
            </div>

            <!-- Help -->
            <div class="col-6 col-lg-3">
                <div class="fk-footer-heading">CUSTOMER CARE</div>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/orders.php">Track Your Order</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/invoices.php">Download Tax Invoices</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/cart.php">Shopping Cart</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/customer/profile.php">Account Settings</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/login.php">Customer Sign In</a></li>
                </ul>
            </div>

            <!-- Policies -->
            <div class="col-6 col-lg-3">
                <div class="fk-footer-heading">CONSUMER POLICY</div>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="javascript:void(0)">7-Day Replacement Guarantee</a></li>
                    <li class="mb-2"><a href="javascript:void(0)">Free Shipping Terms</a></li>
                    <li class="mb-2"><a href="javascript:void(0)">Security &amp; Privacy Policy</a></li>
                    <li class="mb-2"><a href="javascript:void(0)">GST Billing &amp; Tax Compliance</a></li>
                    <li class="mb-2"><a href="javascript:void(0)">Terms &amp; Conditions</a></li>
                </ul>
            </div>

            <!-- Registered Office & Contact -->
            <div class="col-12 col-lg-3">
                <div class="fk-footer-heading">REGISTERED OFFICE &amp; HELPLINE</div>
                <p class="text-white-50 small mb-2">
                    MobileKart India Distribution,<br>
                    Dandi Daji Faliya, Ta. Dist. Valsad,<br>
                    Gujarat, India - 396385
                </p>
                <div class="text-white small">
                    <i class="fa-solid fa-headset text-warning me-2"></i>Helpline: <strong>+91 99744 10030</strong>
                </div>
                <div class="text-white small mt-1">
                    <i class="fa-solid fa-envelope text-primary me-2"></i>support@mobiledistribution.com
                </div>
            </div>
        </div>

        <div class="fk-footer-bottom d-flex flex-column align-items-center justify-content-between gap-3 text-center">
            <div class="text-white-50 small" style="max-width: 900px;">
                <i class="fa-solid fa-graduation-cap text-warning me-1"></i> <strong>Academic Demonstration Project</strong>: MobileKart is an educational mobile purchasing and distribution system developed for academic showcase (DFD Architecture Levels 0-2). All smartphone product designs, logos, and brand trademarks belong to their respective corporate copyright holders.
            </div>
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between w-100 gap-3 border-top border-secondary pt-3">
                <div>
                    <span class="text-warning fw-bold">MobileKart</span> &copy; <?= date('Y') ?> Online Mobile Purchasing &amp; Distributing System. Academic Showcase.
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small"><i class="fa-solid fa-graduation-cap text-warning me-1"></i> Academic Project</span>
                    <span class="text-white-50 small"><i class="fa-solid fa-mobile-screen text-success me-1"></i> Demo Catalog</span>
                    <span class="text-white-50 small"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Mock Payment System</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main Site JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<!-- Cart AJAX JS -->
<script src="<?= BASE_URL ?>/assets/js/cart.js"></script>

</body>
</html>
