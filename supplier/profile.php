<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Supplier Company Profile (DFD P1.1)
 */

$pageTitle = "Company Profile";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_supplier'])) {
    require_csrf();
    $companyName   = trim($_POST['company_name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $pincode       = trim($_POST['pincode'] ?? '');

    if (!empty($companyName) && !empty($phone)) {
        $db->beginTransaction();

        $upUser = $db->prepare("UPDATE users SET phone = ?, address = ?, city = ?, state = ?, pincode = ?, updated_at = NOW() WHERE user_id = ?");
        $upUser->execute([$phone, $address, $city, $state, $pincode, $user['id']]);

        $upSup = $db->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, updated_at = NOW() WHERE supplier_id = ?");
        $upSup->execute([$companyName, $contactPerson, $supplierId]);

        $db->commit();
        set_flash('success', 'Company profile details updated successfully.');
        header("Location: profile.php");
        exit;
    }
}

// Fetch supplier record
$stmt = $db->prepare("
    SELECT s.*, u.email, u.phone, u.address, u.city, u.state, u.pincode
    FROM suppliers s
    JOIN users u ON s.user_id = u.user_id
    WHERE s.supplier_id = ?
");
$stmt->execute([$supplierId]);
$supplier = $stmt->fetch();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-building text-primary me-2"></i> Supplier Company Profile</h4>
        <small class="text-muted">Entity: <strong>SUPPLIER</strong> • Data Store: <strong>D1 USER DB (suppliers table)</strong></small>
    </div>
    <span class="badge bg-success py-2 px-3"><i class="fa-solid fa-circle-check me-1"></i> <?= $supplier['approval_status'] ?></span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <form method="POST" action="profile.php">
                <?= csrf_field() ?>
                <input type="hidden" name="update_supplier" value="1">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Company / Firm Name *</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($supplier['company_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">GST Identification Number (GSTIN)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($supplier['gst_number']) ?>" readonly>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Contact Person *</label>
                        <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Primary Mobile Phone *</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone']) ?>" pattern="[0-9]{10}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Business Email (Read-only)</label>
                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($supplier['email']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Central Warehouse / Office Street Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($supplier['address']) ?></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">City</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($supplier['city']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">State</label>
                        <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($supplier['state']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($supplier['pincode']) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Distributor Credibility</h6>
            <div class="text-center py-3">
                <div class="display-5 fw-bold text-warning mb-1"><?= number_format($supplier['rating'], 1) ?> ★</div>
                <div class="text-muted small">Merchant Reliability Score</div>
            </div>
            <ul class="list-unstyled small text-secondary mb-0 border-top pt-3">
                <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Verified Indian Business Entity</li>
                <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Authorized OEM Mobile Distributor</li>
                <li><i class="fa-solid fa-check text-success me-2"></i> Direct Logistics Hub Connected</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
