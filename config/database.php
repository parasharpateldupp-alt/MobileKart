<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Database Connection Module (PDO)
 */

require_once __DIR__ . '/constants.php';

function get_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    // Resolve PHP 8.5+ compatible driver attribute constants
    $initCmdKey = defined('Pdo\\Mysql::ATTR_INIT_COMMAND') 
        ? constant('Pdo\\Mysql::ATTR_INIT_COMMAND') 
        : (defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : 1002);

    $sslVerifyKey = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') 
        ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') 
        : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014);

    $sslCaKey = defined('Pdo\\Mysql::ATTR_SSL_CA') 
        ? constant('Pdo\\Mysql::ATTR_SSL_CA') 
        : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007);

    // SSL CA certificate path for secure cloud transport (TiDB Cloud)
    $caPath = __DIR__ . '/cacert.pem';
    if (!file_exists($caPath)) {
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $caPath = '/etc/ssl/certs/ca-certificates.crt';
        } elseif (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
            $caPath = '/etc/pki/tls/certs/ca-bundle.crt';
        }
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        $initCmdKey                  => "SET NAMES " . DB_CHARSET,
        $sslVerifyKey                => false,
        PDO::ATTR_TIMEOUT            => 2
    ];

    if (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') {
        if ($caPath && file_exists($caPath)) {
            $options[$sslCaKey] = $caPath;
        }
    }

    $lastErr = null;

    // Fast local SQLite path: instant execution without socket latency
    if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
        $sqlitePath = dirname(__DIR__) . '/database/mobilekart.sqlite';
        if (file_exists($sqlitePath) && !getenv('FORCE_MYSQL')) {
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                register_sqlite_functions($pdo);
                return $pdo;
            } catch (Exception $sqErr) {
                $lastErr = $sqErr;
            }
        }
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // Ensure sql_mode does not break complex reporting/analytical queries across various MySQL/MariaDB setups
        $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        return $pdo;
    } catch (PDOException $e) {
        $lastErr = $e;
    }

    // Fallback: Try TiDB Cloud if localhost failed
    if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
        try {
            $cloudHost = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
            $cloudPort = '4000';
            $cloudDb = 'test';
            $cloudUser = '4AVQkGYiwq2zQBT.root';
            $cloudPass = 'BNdSSFFOMEjJYR1K';
            $cloudDsn = "mysql:host={$cloudHost};port={$cloudPort};dbname={$cloudDb};charset=" . DB_CHARSET;
            $cloudOptions = $options;
            if ($caPath && file_exists($caPath)) {
                $cloudOptions[$sslCaKey] = $caPath;
            }
            $pdo = new PDO($cloudDsn, $cloudUser, $cloudPass, $cloudOptions);
            $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            return $pdo;
        } catch (Exception $cloudErr) {
            if (!$lastErr) $lastErr = $cloudErr;
        }
    }

    // If script is an API or CLI, return false or error message
    if (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
        throw ($lastErr ?: new Exception("Database connection failed"));
    }

        // Professional diagnostic screen for academic demo & cloud setup
        $isVercel = (!empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || str_contains($_SERVER['HTTP_HOST'] ?? '', 'vercel.app'));
        $setupUrl = (defined('BASE_URL') ? BASE_URL : '') . '/config/setup.php';
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Setup - MobileKart Distribution</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body { background-color: #f1f3f6; font-family: "Segoe UI", system-ui, sans-serif; }
                .setup-card { max-width: 760px; margin: 40px auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: none; }
                .brand-header { background: #2874f0; color: white; padding: 24px; border-radius: 12px 12px 0 0; }
            </style>
        </head>
        <body>
            <div class="container py-4">
                <div class="card setup-card">
                    <div class="brand-header text-center">
                        <i class="fa-solid fa-mobile-screen-button fa-3x mb-2 text-warning"></i>
                        <h4 class="mb-1">Online Mobile Purchasing & Distributing System</h4>
                        <p class="mb-0 text-white-50">' . ($isVercel ? 'Vercel Cloud Deployment Setup' : 'Database Setup & Diagnostics') . '</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation fa-2x me-3 text-warning"></i>
                            <div>
                                <strong>Database Connection Required:</strong><br>
                                Unable to connect to MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . ':' . htmlspecialchars(DB_PORT) . '</code>.
                            </div>
                        </div>';

        if ($isVercel) {
            echo '<div class="card bg-light border-0 p-3 mb-4 rounded-3">
                <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-cloud me-2"></i> Connect Free Cloud MySQL on Vercel:</h6>
                <p class="small text-muted mb-3">Vercel serverless runs your PHP code, but requires an external MySQL database to persist mobile orders and user accounts. You can create a free cloud MySQL database in 1 minute:</p>
                <ol class="small mb-3 ps-3">
                    <li class="mb-2"><strong>Get Free Database:</strong> Open <a href="https://tidbcloud.com" target="_blank" class="fw-bold text-primary text-decoration-underline">TiDB Cloud Serverless</a> (Free 5GB forever, 0 dollars, no credit card required) or <a href="https://aiven.io" target="_blank" class="fw-bold text-primary text-decoration-underline">Aiven MySQL</a>.</li>
                    <li class="mb-2"><strong>Import Tables:</strong> Copy and run the contents of <code>database/database.sql</code> into your cloud database console.</li>
                    <li class="mb-2"><strong>Add Environment Variables in Vercel:</strong> Go to your <strong>Vercel Project &rarr; Settings &rarr; Environment Variables</strong> and add:
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered bg-white">
                                <thead class="table-light"><tr><th>Variable Key</th><th>Value (from your cloud provider)</th></tr></thead>
                                <tbody>
                                    <tr><td><code>DB_HOST</code></td><td>e.g. <code>gateway01.ap-south-1.prod.aws.tidbcloud.com</code></td></tr>
                                    <tr><td><code>DB_PORT</code></td><td><code>4000</code> (or <code>3306</code>)</td></tr>
                                    <tr><td><code>DB_NAME</code></td><td><code>test</code> (or your DB name)</td></tr>
                                    <tr><td><code>DB_USER</code></td><td>your cloud username</td></tr>
                                    <tr><td><code>DB_PASS</code></td><td>your cloud password</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </li>
                </ol>
            </div>';
        } else {
            echo '<h6 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2 text-primary"></i> Quick Setup Instructions for XAMPP:</h6>
            <ol class="list-group list-group-numbered mb-4">
                <li class="list-group-item">Ensure <strong>Apache</strong> and <strong>MySQL</strong> services are running in your XAMPP Control Panel.</li>
                <li class="list-group-item">Open <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>).</li>
                <li class="list-group-item">Create database <code>online_mobile_distribution</code> and import <code>database/database.sql</code>.</li>
            </ol>';
        }

        echo '<div class="d-grid gap-2">
            <a href="' . htmlspecialchars($setupUrl) . '" class="btn btn-primary btn-lg shadow-sm">
                <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Run 1-Click Database Auto-Installer
            </a>
            <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                <i class="fa-solid fa-rotate me-2"></i> Retry Connection
            </button>
        </div>

        <div class="mt-4 pt-3 border-top text-muted small">
            <strong>Technical Detail:</strong> ' . htmlspecialchars($lastErr ? $lastErr->getMessage() : 'Database offline') . '
        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        exit;
}


/**
 * Register MySQL compatibility functions in SQLite PDO
 */
function register_sqlite_functions(PDO $pdo): void {
    $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
    $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'));
    $pdo->sqliteCreateFunction('CURTIME', fn() => date('H:i:s'));
    $pdo->sqliteCreateFunction('CONCAT', fn(...$args) => implode('', $args));
    $pdo->sqliteCreateFunction('CONCAT_WS', fn($sep, ...$args) => implode($sep, $args));
    $pdo->sqliteCreateFunction('IF', fn($cond, $t, $f) => $cond ? $t : $f);
    $pdo->sqliteCreateFunction('IFNULL', fn($v, $f) => $v !== null ? $v : $f);
    $pdo->sqliteCreateFunction('YEAR', fn($d) => $d ? date('Y', strtotime($d)) : null);
    $pdo->sqliteCreateFunction('MONTH', fn($d) => $d ? date('m', strtotime($d)) : null);
    $pdo->sqliteCreateFunction('DAY', fn($d) => $d ? date('d', strtotime($d)) : null);
    $pdo->sqliteCreateFunction('DATEDIFF', fn($d1, $d2) => (int)round((strtotime($d1) - strtotime($d2)) / 86400));
    $pdo->sqliteCreateFunction('GREATEST', fn(...$args) => count($args) > 0 ? max($args) : null);
    $pdo->sqliteCreateFunction('LEAST', fn(...$args) => count($args) > 0 ? min($args) : null);
    $pdo->sqliteCreateFunction('DATE_FORMAT', function($date, $format) {
        if (!$date) return null;
        $ts = strtotime($date);
        if ($ts === false) return null;
        $map = [
            '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n',
            '%d' => 'd', '%e' => 'j', '%H' => 'H', '%h' => 'h',
            '%i' => 'i', '%s' => 's', '%M' => 'F', '%b' => 'M',
            '%W' => 'l', '%a' => 'D', '%p' => 'A'
        ];
        return date(strtr($format, $map), $ts);
    });
}
