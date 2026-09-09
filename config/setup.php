<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Automated Database Setup & Reset Tool
 */

require_once __DIR__ . '/constants.php';

$message = '';
$messageType = '';
$isCli = (php_sapi_name() === 'cli');

if ($isCli || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install')) {
    try {
        // Step 1: Connect to MySQL server without selecting DB
        $dsn = sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Step 2: Read SQL file
        $sqlPath = dirname(__DIR__) . '/database/database.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("SQL database template file not found at: {$sqlPath}");
        }
        $sql = file_get_contents($sqlPath);

        // Step 3: Execute SQL batches
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // Split queries by semicolon and execute
        $pdo->exec($sql);

        // Step 4: Ensure Bcrypt password hash for PATANJALI admin account
        $adminHash = password_hash('PATU1522', PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE name = 'PATANJALI' OR email = 'patanjali@patu.com'");
        $updateStmt->execute([$adminHash]);

        $message = "Database `" . DB_NAME . "` initialized successfully! All tables created, relationships linked, and sample Indian smartphones pre-seeded.";
        $messageType = 'success';
        $isInstalled = true;
        if ($isCli) {
            echo "[SUCCESS] " . $message . "\n";
            exit(0);
        }
    } catch (Exception $e) {
        $message = "Installation Error: " . $e->getMessage();
        $messageType = 'danger';
        if ($isCli) {
            fwrite(STDERR, "[ERROR] " . $message . "\n");
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Auto-Installer - Mobile Distribution System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f3f6; font-family: 'Segoe UI', system-ui, sans-serif; }
        .setup-card { max-width: 720px; margin: 50px auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: none; }
        .brand-header { background: #2874f0; color: white; padding: 25px; border-radius: 12px 12px 0 0; }
        .cred-badge { font-family: monospace; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card setup-card">
            <div class="brand-header text-center">
                <i class="fa-solid fa-server fa-3x mb-2 text-warning"></i>
                <h3 class="fw-bold mb-1">Database Auto-Installer & Initializer</h3>
                <p class="text-white-50 mb-0">Online Mobile Purchasing & Distributing System</p>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> d-flex align-items-center mb-4" role="alert">
                        <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> fa-2x me-3"></i>
                        <div><?= $message ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($isInstalled): ?>
                    <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                        <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-circle-check me-2"></i> Administrator Account:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm bg-white mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Role</th>
                                        <th>Username / Email</th>
                                        <th>Password</th>
                                        <th>Access Scope</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-danger">ADMIN</span></td>
                                        <td><code>PATANJALI</code> (or <code>patanjali@patu.com</code>)</td>
                                        <td><span class="cred-badge">PATU1522</span></td>
                                        <td>Full Control, Catalog, Inventory, Orders, Users</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <a href="../index.php" class="btn btn-primary w-100 py-2">
                                <i class="fa-solid fa-store me-1"></i> Open Storefront
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="../login.php" class="btn btn-dark w-100 py-2">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Login Page
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-secondary">
                        Click the button below to initialize or reset the MySQL database. This operation will execute <code>database/database.sql</code>, creating all database tables, Indian mobile phone models, inventory records, and the PATANJALI admin account.
                    </p>

                    <div class="card bg-light p-3 mb-4 rounded-3">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-sliders me-2 text-primary"></i> Target Environment:</h6>
                        <ul class="mb-0 text-muted small">
                            <li>Database Host: <strong><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></strong></li>
                            <li>Database Name: <strong><?= htmlspecialchars(DB_NAME) ?></strong></li>
                            <li>Database User: <strong><?= htmlspecialchars(DB_USER) ?></strong></li>
                            <li>Storage Engine: <strong>InnoDB (UTF-8 mb4)</strong></li>
                        </ul>
                    </div>

                    <form method="POST" onsubmit="return confirm('This will initialize/reset the database schema and sample data. Proceed?');">
                        <input type="hidden" name="action" value="install">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="fa-solid fa-play me-2"></i> Initialize Database Now
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
