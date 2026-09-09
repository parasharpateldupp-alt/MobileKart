<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * User Registration (DFD P1.1 Manage User Accounts)
 */

$pageTitle = "Register - Online Mobile Purchasing & Distributing System";
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $error = "Password confirmation does not match.";
    } else {
        try {
            $db = get_db_connection();

            // Check if email already exists
            $checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = "An account with this email address is already registered. Please log in.";
            } else {
                $db->beginTransaction();

                // Hash password securely
                $hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert into users table
                $insUser = $db->prepare("
                    INSERT INTO users (name, email, phone, password_hash, role, status, created_at)
                    VALUES (?, ?, ?, ?, 'CUSTOMER', 'ACTIVE', NOW())
                ");
                $insUser->execute([$name, $email, $phone, $hash]);
                $userId = $db->lastInsertId();

                $db->commit();

                log_activity($userId, 'USER_REGISTERED', 'USER', $userId, ['role' => 'CUSTOMER']);

                // Auto login user
                $authResult = authenticate_user($email, $password);
                if ($authResult['success']) {
                    login_user($authResult['user']);
                    header("Location: " . BASE_URL . "/index.php");
                    exit;
                } else {
                    set_flash('success', 'Registration successful! Please log in.');
                    header("Location: " . BASE_URL . "/login.php");
                    exit;
                }
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $error = "Registration could not be completed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #2874f0, #1b5cbd);">
                    <i class="fa-solid fa-user-plus fa-3x mb-2 text-warning"></i>
                    <h4 class="fw-bold mb-1">Create an Account</h4>
                    <p class="text-white-50 mb-0 small">Join MobileKart to purchase smartphones with exclusive offers</p>
                </div>

                <div class="card-body p-4 p-md-5">

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="name" class="form-control border-start-0" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Mobile Number (10 Digits)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" name="phone" class="form-control border-start-0" placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control border-start-0" placeholder="Enter email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password" class="form-control border-start-0" placeholder="Create password (min. 6 characters)" minlength="6" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password_confirm" class="form-control border-start-0" placeholder="Re-enter password" minlength="6" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-fk-primary w-100 py-2 fw-bold shadow-sm mb-3">
                            <i class="fa-solid fa-user-check me-2"></i> Create Account
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-decoration-none ms-1">Log In</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
