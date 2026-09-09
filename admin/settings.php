<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * System Governance & Global Configurations
 */

$pageTitle = "System Settings";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();
$db = get_db_connection();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    require_csrf();
    
    $editableKeys = [
        'site_name',
        'tax_rate_percent',
        'free_shipping_threshold',
        'standard_shipping_charge',
        'stock_reservation_minutes',
        'contact_email',
        'contact_phone',
        'currency_symbol'
    ];

    $updated = 0;
    $upStmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");

    foreach ($editableKeys as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            $upStmt->execute([$val, $key]);
            $updated++;
        }
    }

    log_activity($user['id'], 'ADMIN_UPDATED_SYSTEM_SETTINGS', 'SETTINGS', 'SYSTEM', ['keys_updated' => $editableKeys]);
    set_flash('success', "System settings updated successfully ({$updated} items).");
    header("Location: settings.php");
    exit;
}

// Fetch all system settings
$settingsStmt = $db->query("SELECT * FROM system_settings");
$settingsMap = [];
while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $settingsMap[$row['setting_key']] = $row;
}

// Helper to get setting value
function get_val($key, $default = '') {
    global $settingsMap;
    return $settingsMap[$key]['setting_value'] ?? $default;
}

// Server Environment Information
$serverInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP',
    'Database Engine' => $db->getAttribute(PDO::ATTR_DRIVER_NAME) . ' ' . $db->getAttribute(PDO::ATTR_SERVER_VERSION),
    'Memory Limit' => ini_get('memory_limit'),
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Post Max Size' => ini_get('post_max_size'),
    'Execution Time Limit' => ini_get('max_execution_time') . 's',
    'Session Save Path' => session_save_path() ?: sys_get_temp_dir()
];

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0">System Governance &amp; Configuration</h3>
        <p class="text-muted small mb-0">Manage global business rules, tax, logistics parameters, and runtime environment</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/config/setup.php" class="btn btn-outline-danger" onclick="return confirm('Database reset will restore default schema and catalog. Continue?')">
            <i class="fa-solid fa-rotate-left me-1"></i> Launch Database Auto-Installer / Reset Tool
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Settings Form -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i>E-Commerce Business Rules</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="settings.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="save_settings" value="1">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Application / Storefront Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars(get_val('site_name', 'Online Mobile Purchasing & Distributing System')) ?>" required>
                        <small class="text-muted">Displayed on storefront headers, customer invoices, and notification emails.</small>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">GST Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="tax_rate_percent" class="form-control" value="<?= htmlspecialchars(get_val('tax_rate_percent', '18')) ?>" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Standard Indian Goods &amp; Services Tax for electronics.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Stock Reservation Hold (Minutes)</label>
                            <div class="input-group">
                                <input type="number" name="stock_reservation_minutes" class="form-control" value="<?= htmlspecialchars(get_val('stock_reservation_minutes', '15')) ?>" required>
                                <span class="input-group-text">Mins</span>
                            </div>
                            <small class="text-muted">Time allowed for customer to complete bank authorization.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Free Express Delivery Threshold</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="free_shipping_threshold" class="form-control" value="<?= htmlspecialchars(get_val('free_shipping_threshold', '5000')) ?>" required>
                            </div>
                            <small class="text-muted">Orders with subtotal &ge; this value receive free shipping.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Standard Delivery Charge</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="standard_shipping_charge" class="form-control" value="<?= htmlspecialchars(get_val('standard_shipping_charge', '149')) ?>" required>
                            </div>
                            <small class="text-muted">Charged when subtotal is below threshold.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Support Email</label>
                            <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars(get_val('contact_email', 'support@mobiledistribution.com')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Helpline Phone</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars(get_val('contact_phone', '+91 99744 10030')) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">Currency Representation</label>
                        <input type="text" name="currency_symbol" class="form-control" style="max-width: 120px;" value="<?= htmlspecialchars(get_val('currency_symbol', '₹')) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Server Environment & DFD Summary -->
    <div class="col-lg-5">
        <!-- Runtime Telemetry -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-server text-secondary me-2"></i>Runtime Environment</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0 font-monospace small">
                    <tbody>
                        <?php foreach ($serverInfo as $label => $val): ?>
                            <tr>
                                <td class="ps-3 py-2 text-muted fw-bold"><?= $label ?></td>
                                <td class="pe-3 py-2 text-dark"><?= htmlspecialchars($val) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
