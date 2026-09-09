<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Customer Profile & Address Management (DFD P1.1)
 */

$pageTitle = "My Profile & Address - MobileKart";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

require_login();

$userId = $_SESSION['user_id'];
$db = get_db_connection();

$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    require_csrf();
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $state   = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = "Name and Phone number are required.";
    } else {
        $stmt = $db->prepare("
            UPDATE users 
            SET name = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$name, $phone, $address, $city, $state, $pincode, $userId]);
        $_SESSION['user_name'] = $name;
        $success = "Profile and shipping address updated successfully!";
        log_activity($userId, 'PROFILE_UPDATED', 'USER', $userId);
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    require_csrf();
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass)) {
        $error = "Please fill in all password fields.";
    } elseif (strlen($newPass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $error = "New password and confirmation do not match.";
    } else {
        $chk = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $chk->execute([$userId]);
        $storedHash = $chk->fetchColumn();

        if (password_verify($currentPass, $storedHash)) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $up = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
            $up->execute([$newHash, $userId]);
            $success = "Password changed successfully!";
            log_activity($userId, 'PASSWORD_CHANGED', 'USER', $userId);
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Fetch current user details
$uStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3 small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item active">Account Settings</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-id-card text-primary me-2"></i> Account Profile &amp; Address</h4>
            <small class="text-muted">Manage your personal details and delivery addresses</small>
        </div>
        <span class="badge bg-primary fs-6 px-3 py-2"><?= htmlspecialchars($user['role']) ?></span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4">
            <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Left: Profile & Address Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-user-pen text-primary me-2"></i> Personal &amp; Delivery Information
                </h5>

                <form method="POST" action="profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Contact Mobile Number *</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" pattern="[0-9]{10}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Default Street Address / Flat / Landmark</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">State</label>
                            <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-fk-primary py-2 px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Change Password Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-shield-halved text-warning me-2"></i> Change Password
                </h5>

                <form method="POST" action="profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">
                        <i class="fa-solid fa-key me-1"></i> Update Password
                    </button>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                <div class="small text-muted">
                    <strong>Account Information:</strong><br>
                    User ID: <code>#<?= $user['user_id'] ?></code><br>
                    Joined: <?= date('d M Y', strtotime($user['created_at'])) ?><br>
                    Last Login: <?= $user['last_login'] ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Initial' ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
