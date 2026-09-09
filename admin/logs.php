<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Activity Audit Trail & Security Logs (System Governance)
 */

$pageTitle = "Activity Audit Trail";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_admin();
$db = get_db_connection();

// Handle Purge Old Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_old_logs'])) {
    require_csrf();
    $days = (int)($_POST['older_than_days'] ?? 30);
    if ($days >= 7) {
        $stmt = $db->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $deleted = $stmt->rowCount();
        set_flash('success', "Successfully purged {$deleted} log entries older than {$days} days.");
    } else {
        set_flash('danger', "Minimum log retention period is 7 days.");
    }
    header("Location: logs.php");
    exit;
}

// Filters
$actionFilter = trim($_GET['action'] ?? '');
$entityFilter = trim($_GET['entity'] ?? '');
$userFilter = (int)($_GET['user_id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Base query
$where = ["1=1"];
$params = [];

if ($actionFilter) {
    $where[] = "l.action = ?";
    $params[] = $actionFilter;
}
if ($entityFilter) {
    $where[] = "l.entity_type = ?";
    $params[] = $entityFilter;
}
if ($userFilter > 0) {
    $where[] = "l.user_id = ?";
    $params[] = $userFilter;
}
if ($search) {
    $where[] = "(l.action LIKE ? OR l.details LIKE ? OR l.entity_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSql = implode(" AND ", $where);

// Count Total
$countSql = "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE {$whereSql}";
$cStmt = $db->prepare($countSql);
$cStmt->execute($params);
$totalRows = (int)$cStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch logs
$sql = "SELECT l.*, u.name as user_name, u.email as user_email, u.role as user_role
        FROM activity_logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        WHERE {$whereSql}
        ORDER BY l.log_id DESC
        LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct actions & entities for filter dropdowns
$distinctActions = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
$distinctEntities = $db->query("SELECT DISTINCT entity_type FROM activity_logs WHERE entity_type IS NOT NULL ORDER BY entity_type ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                <li class="breadcrumb-item active">Audit Trail</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0">System Activity &amp; Security Logs</h3>
        <p class="text-muted small mb-0">
            Immutable audit record of authentication, catalog mutations, order states, payment callbacks, and fulfillment events
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#purgeModal">
            <i class="fa-solid fa-trash-can me-1"></i> Maintenance Purge
        </button>
        <a href="logs.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Reset Filters
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="logs.php" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Search Keywords</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Action, payload details, user..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Action Type</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($distinctActions as $act): ?>
                        <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Entity Domain</label>
                <select name="entity" class="form-select form-select-sm">
                    <option value="">All Entities</option>
                    <?php foreach ($distinctEntities as $ent): ?>
                        <option value="<?= htmlspecialchars($ent) ?>" <?= $entityFilter === $ent ? 'selected' : '' ?>><?= htmlspecialchars($ent) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-filter me-1"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0">Audit Ledger</h6>
            <small class="text-muted">Total: <?= number_format($totalRows) ?> recorded events</small>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">D1 - D5 Traceability</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead class="table-light font-sans-serif">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 160px;">Timestamp</th>
                        <th style="width: 180px;">User Account</th>
                        <th style="width: 220px;">Action</th>
                        <th style="width: 140px;">Entity</th>
                        <th>Details Payload</th>
                        <th style="width: 110px;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted font-sans-serif">No activity logs found matching the filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $actionClass = 'bg-secondary';
                            if (str_contains($log['action'], 'LOGIN')) $actionClass = 'bg-info text-dark';
                            elseif (str_contains($log['action'], 'ORDER')) $actionClass = 'bg-primary text-white';
                            elseif (str_contains($log['action'], 'PAYMENT')) $actionClass = 'bg-success text-white';
                            elseif (str_contains($log['action'], 'STOCK') || str_contains($log['action'], 'INVENTORY')) $actionClass = 'bg-warning text-dark';
                            elseif (str_contains($log['action'], 'DELETE') || str_contains($log['action'], 'CANCEL')) $actionClass = 'bg-danger text-white';
                            elseif (str_contains($log['action'], 'ADMIN')) $actionClass = 'bg-dark text-white';

                            // Format JSON details nicely if applicable
                            $detailsDisplay = $log['details'];
                            $decoded = json_decode($log['details'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $detailsDisplay = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }
                            ?>
                            <tr>
                                <td class="text-muted">#<?= $log['log_id'] ?></td>
                                <td class="text-nowrap text-secondary"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                <td class="font-sans-serif">
                                    <?php if ($log['user_id']): ?>
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($log['user_name']) ?>">
                                            <?= htmlspecialchars($log['user_name'] ?? 'User #' . $log['user_id']) ?>
                                        </div>
                                        <small class="badge bg-light text-secondary border"><?= htmlspecialchars($log['user_role'] ?? 'USER') ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">SYSTEM / GUEST</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $actionClass ?> px-2 py-1 text-wrap text-break">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($log['entity_type']) ?>
                                            <?php if ($log['entity_id']): ?>: <?= htmlspecialchars($log['entity_id']) ?><?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (strlen($detailsDisplay) > 100): ?>
                                        <div class="text-truncate" style="max-width: 350px;" title="<?= htmlspecialchars($detailsDisplay) ?>">
                                            <?= htmlspecialchars($detailsDisplay) ?>
                                        </div>
                                        <a href="javascript:void(0)" class="small text-primary text-decoration-none font-sans-serif" onclick="alert(<?= htmlspecialchars(json_encode($detailsDisplay)) ?>)">
                                            View Full Payload
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($detailsDisplay ?: '-') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing page <?= $page ?> of <?= $totalPages ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">Previous</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Purge Old Logs -->
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="logs.php">
                <?= csrf_field() ?>
                <input type="hidden" name="purge_old_logs" value="1">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Purge Historical Logs</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary">
                        Delete audit trail records older than a specific retention window. Recent activity will remain completely intact.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Retention Threshold</label>
                        <select name="older_than_days" class="form-select">
                            <option value="90">Older than 90 days (Recommended)</option>
                            <option value="60">Older than 60 days</option>
                            <option value="30">Older than 30 days</option>
                            <option value="7">Older than 7 days</option>
                        </select>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i> This operation cannot be reversed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm &amp; Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
