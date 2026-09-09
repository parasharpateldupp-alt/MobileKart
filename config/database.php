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

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // Ensure sql_mode does not break complex reporting/analytical queries across various MySQL/MariaDB setups
        $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        return $pdo;
    } catch (PDOException $e) {
        // If script is an API or CLI, return false or error message
        if (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            throw $e;
        }

        // Professional diagnostic screen for academic demo & cloud setup
        $isVercel = (!empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || str_contains($_SERVER['HTTP_HOST'] ?? '', 'vercel.app'));
        $setupUrl = (defined('BASE_URL') ? BASE_URL : '') . '/config/setup.php';
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Required - MobileKart</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body { background-color: #f1f3f6; font-family: "Segoe UI", system-ui, sans-serif; }
                .setup-card { max-width: 720px; margin: 40px auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); border: none; }
                .brand-header { background: #2874f0; color: white; padding: 24px; border-radius: 12px 12px 0 0; }
            </style>
        </head>
        <body>
            <div class="container">
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
                                <strong>Database Connection Alert:</strong><br>
                                Unable to connect to MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . ':' . htmlspecialchars(DB_PORT) . '</code>.
                            </div>
                        </div>';

        if ($isVercel) {
            echo '<div class="alert alert-info border-0 shadow-sm">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-cloud me-2"></i> Running on Vercel Serverless:</h6>
                <p class="small mb-2">Vercel serverless functions do not host a local MySQL database. Connect any free cloud MySQL database (e.g. <strong>TiDB Cloud Serverless</strong> or <strong>Aiven</strong>) in 3 steps:</p>
                <ol class="small mb-0 ps-3">
                    <li class="mb-1">Create a free database at <a href="https://tidbcloud.com" target="_blank" class="fw-bold text-decoration-underline">TiDB Cloud</a> (Free 5GB forever).</li>
                    <li class="mb-1">Import <code>database/database.sql</code> using the TiDB Web Console.</li>
                    <li>In your <strong>Vercel Dashboard &rarr; Project Settings &rarr; Environment Variables</strong>, add:
                        <ul class="mt-1 font-monospace text-dark">
                            <li><code>DB_HOST</code> &mdash; your cloud database hostname</li>
                            <li><code>DB_PORT</code> &mdash; 4000 (or 3306)</li>
                            <li><code>DB_NAME</code> &mdash; test (or your database name)</li>
                            <li><code>DB_USER</code> &mdash; your cloud username</li>
                            <li><code>DB_PASS</code> &mdash; your cloud password</li>
                        </ul>
                    </li>
                </ol>
            </div>';
        } else {
            echo '<h6 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2 text-primary"></i> Quick Setup Instructions for XAMPP:</h6>
            <ol class="list-group list-group-numbered mb-4">
                <li class="list-group-item">Ensure <strong>Apache</strong> and <strong>MySQL</strong> services are running in your XAMPP Control Panel.</li>
                <li class="list-group-item">Open <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>).</li>
                <li class="list-group-item">Create database <code>online_mobile_distribution</code> and import <code>database/database.sql</code>.</li>
                <li class="list-group-item">Or click the 1-click installer below.</li>
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
            <strong>Technical Detail:</strong> ' . htmlspecialchars($e->getMessage()) . '
        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}
