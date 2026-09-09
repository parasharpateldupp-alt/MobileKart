<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Unified Login Page (DFD P1.1 Manage User Accounts)
 */

$pageTitle = "Login - Online Mobile Purchasing & Distributing System";
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
    } elseif (is_supplier()) {
        header("Location: " . BASE_URL . "/supplier/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/index.php");
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } else {
        $authResult = authenticate_user($email, $password);
        if ($authResult['success']) {
            login_user($authResult['user']);
            
            // Redirect logic
            $intended = $_SESSION['intended_redirect'] ?? null;
            unset($_SESSION['intended_redirect']);

            if ($intended && !str_contains($intended, 'login.php') && !str_contains($intended, 'logout.php')) {
                header("Location: " . $intended);
            } elseif ($authResult['user']['role'] === ROLE_ADMIN) {
                header("Location: " . BASE_URL . "/admin/dashboard.php");
            } elseif ($authResult['user']['role'] === ROLE_SUPPLIER) {
                header("Location: " . BASE_URL . "/supplier/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "/index.php");
            }
            exit;
        } else {
            $error = $authResult['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            
            <!-- Login Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #2874f0, #1b5cbd);">
                    <i class="fa-solid fa-mobile-screen-button fa-3x mb-2 text-warning"></i>
                    <h4 class="fw-bold mb-1">Sign In to MobileKart</h4>
                    <p class="text-white-50 mb-0 small">Access your orders, wishlist, and recommendations</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small">Email or Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" id="loginEmail" name="email" class="form-control border-start-0" placeholder="Enter email or username" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-secondary small mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" id="loginPassword" name="password" class="form-control border-start-0" placeholder="Enter password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-fk-primary w-100 py-2 fw-bold shadow-sm mb-3">
                            <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted small">New to MobileKart?</span>
                        <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-decoration-none ms-1">Create an account</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
