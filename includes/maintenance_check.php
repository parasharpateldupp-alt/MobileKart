<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * Scheduled Maintenance Mode Controller
 */

if (php_sapi_name() === 'cli') {
    return;
}

if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE) {
    $bypassKey = 'mk_admin_2026';
    
    // Check for preview bypass via GET, Cookie, or Header
    $providedKey = $_GET['preview_key'] ?? $_COOKIE['mk_preview_token'] ?? $_SERVER['HTTP_X_PREVIEW_KEY'] ?? '';
    if ($providedKey === $bypassKey) {
        if (isset($_GET['preview_key']) && !headers_sent()) {
            setcookie('mk_preview_token', $bypassKey, time() + 86400, '/');
        }
        return; // Bypass maintenance mode
    }

    // Allow static asset requests to pass through
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf)$/i', $uri)) {
        return;
    }

    // Send 503 Service Unavailable
    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance - MobileKart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b263b 50%, #415a77 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        .maintenance-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 48px 36px;
            max-width: 580px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #2874f0;
            color: #fff;
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 28px;
            box-shadow: 0 4px 14px rgba(40, 116, 240, 0.4);
        }
        .pulse-icon {
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            font-size: 2.4rem;
            margin: 0 auto 24px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.96); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { transform: scale(1.04); box-shadow: 0 0 0 16px rgba(255, 193, 7, 0); }
            100% { transform: scale(0.96); box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        h1 {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }
        p.lead-text {
            color: #e0e6ed;
            font-size: 1.05rem;
            line-height: 1.65;
            margin-bottom: 24px;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(40, 167, 69, 0.18);
            border: 1px solid rgba(40, 167, 69, 0.35);
            color: #51cf66;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #51cf66;
            animation: blink 1.2s infinite ease-in-out;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.12);
            margin: 28px 0 20px;
        }
        .footer-note {
            color: #94a3b8;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="brand-badge">
            <i class="fa-solid fa-bolt text-warning"></i> MobileKart
        </div>
        
        <div class="pulse-icon">
            <i class="fa-solid fa-server"></i>
        </div>

        <h1>Under Scheduled Maintenance</h1>
        
        <p class="lead-text">
            MobileKart is currently undergoing scheduled platform upgrades and routine system maintenance to improve performance and reliability.
        </p>

        <div class="mb-3">
            <span class="status-pill">
                <span class="status-dot"></span> System Upgrade In Progress
            </span>
        </div>

        <p class="text-white-50 small mb-0">
            We anticipate being back online shortly. Thank you for your patience and understanding.
        </p>

        <div class="divider"></div>

        <div class="footer-note">
            &copy; <?= date('Y') ?> MobileKart Distribution Platform • All Rights Reserved
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
