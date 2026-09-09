<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Admin User Management (DFD P1.1 Manage User Accounts)
 */

$pageTitle = "Users & Access Control";
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/logger.php';
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$roleFilter = trim($_GET['role'] ?? '');
$search = trim($_GET['q'] ?? '');

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'create_user') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['ADMIN', 'SUPPLIER', 'CUSTOMER']) ? $_POST['role'] : 'CUSTOMER';
        $password = $_POST['password'] ?? 'User@123';

        if (!empty($name) && !empty($email)) {
            $chk = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                set_flash('danger', 'Email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (name, email, phone, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'ACTIVE', NOW())");
                $ins->execute([$name, $email, $phone, $hash, $role]);
                $newId = $db->lastInsertId();

                if ($role === 'SUPPLIER') {
                    $insS = $db->prepare("INSERT INTO suppliers (user_id, company_name, gst_number, contact_person, approval_status) VALUES (?, ?, 'PENDING_GST', ?, 'APPROVED')");
                    $insS->execute([$newId, $name . ' Trading', $name]);
                }

                log_activity($user['id'], 'ADMIN_CREATED_USER', 'USER', $newId, ['role' => $role]);
                set_flash('success', "User '{$name}' created successfully with role {$role}.");
            }
        }
        header("Location: users.php");
        exit;
    }

    if ($action === 'change_role' && $targetId > 0 && $targetId !== (int)$user['id']) {
        $newRole = $_POST['new_role'] ?? 'CUSTOMER';
        $up = $db->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $up->execute([$newRole, $targetId]);

        if ($newRole === 'SUPPLIER') {
            $chkS = $db->prepare("SELECT supplier_id FROM suppliers WHERE user_id = ?");
            $chkS->execute([$targetId]);
            if (!$chkS->fetch()) {
                $uInfo = $db->prepare("SELECT name FROM users WHERE user_id = ?");
                $uInfo->execute([$targetId]);
                $n = $uInfo->fetchColumn();
                $db->prepare("INSERT INTO suppliers (user_id, company_name, gst_number, contact_person, approval_status) VALUES (?, ?, '29AAAAA0000A1Z5', ?, 'APPROVED')")->execute([$targetId, $n . ' Enterprises', $n]);
            }
        }

        log_activity($user['id'], 'ADMIN_CHANGED_ROLE', 'USER', $targetId, ['new_role' => $newRole]);
        set_flash('success', 'User role updated.');
        header("Location: users.php");
        exit;
    }

    if ($action === 'toggle_status' && $targetId > 0 && $targetId !== (int)$user['id']) {
        $up = $db->prepare("UPDATE users SET status = IF(status='ACTIVE', 'INACTIVE', 'ACTIVE') WHERE user_id = ?");
        $up->execute([$targetId]);
        log_activity($user['id'], 'ADMIN_TOGGLED_STATUS', 'USER', $targetId);
        set_flash('success', 'User account status toggled.');
        header("Location: users.php");
        exit;
    }

    if ($action === 'delete_user' && $targetId > 0 && $targetId !== (int)$user['id']) {
        $del = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $del->execute([$targetId]);
        log_activity($user['id'], 'ADMIN_DELETED_USER', 'USER', $targetId);
        set_flash('info', 'User deleted.');
        header("Location: users.php");
        exit;
    }
}

// Build query
$where = ["1=1"];
$params = [];

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql = "SELECT * FROM users WHERE " . implode(" AND ", $where) . " ORDER BY user_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users-gear text-primary me-2"></i> User Accounts &amp; RBAC</h4>
        <small class="text-muted">Manage administrators, registered customers, and verified suppliers</small>
    </div>
    <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fa-solid fa-user-plus me-1"></i> Add New User
    </button>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <option value="ADMIN" <?= $roleFilter === 'ADMIN' ? 'selected' : '' ?>>Administrators</option>
                <option value="SUPPLIER" <?= $roleFilter === 'SUPPLIER' ? 'selected' : '' ?>>Suppliers / Distributors</option>
                <option value="CUSTOMER" <?= $roleFilter === 'CUSTOMER' ? 'selected' : '' ?>>Customers</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark w-100 fw-bold">Filter</button>
            <a href="users.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>#</th>
                        <th>User Identity</th>
                        <th>Contact</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td><?= $u['user_id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= htmlspecialchars($u['phone']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($u['city'] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= match($u['role']) { 'ADMIN' => 'danger', 'SUPPLIER' => 'warning text-dark', default => 'primary' } ?>">
                                    <?= $u['role'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= $u['last_login'] ? date('d M Y, h:i A', strtotime($u['last_login'])) : 'Never' ?>
                            </td>
                            <td class="text-end">
                                <?php if ($u['user_id'] !== (int)$user['id']): ?>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Role change dropdown -->
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Role
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li>
                                                <form method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <input type="hidden" name="new_role" value="ADMIN">
                                                    <button class="dropdown-item small text-danger" type="submit">Make Admin</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <input type="hidden" name="new_role" value="SUPPLIER">
                                                    <button class="dropdown-item small text-warning" type="submit">Make Supplier</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="change_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <input type="hidden" name="new_role" value="CUSTOMER">
                                                    <button class="dropdown-item small text-primary" type="submit">Make Customer</button>
                                                </form>
                                            </li>
                                        </ul>

                                        <!-- Toggle Status -->
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning text-dark" title="Toggle Status">
                                                <i class="fa-solid fa-power-off"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this user account?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete User">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Add New User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_user">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" placeholder="Enter 10-digit mobile number" pattern="[0-9]{10}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Assign Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="CUSTOMER" selected>CUSTOMER</option>
                            <option value="SUPPLIER">SUPPLIER</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter initial password (min. 6 characters)" minlength="6" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create User Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
