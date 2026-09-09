<?php
/**
 * ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
 * System Activity & Audit Logger
 */

require_once dirname(__DIR__) . '/config/database.php';

function log_activity($userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $db = get_db_connection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            substr($action, 0, 100),
            $entityType ? substr($entityType, 0, 50) : null,
            $entityId ? substr((string)$entityId, 0, 50) : null,
            is_array($details) ? json_encode($details, JSON_UNESCAPED_SLASHES) : $details,
            $ip
        ]);
        return true;
    } catch (Exception $e) {
        // Silently log to PHP error log so system execution never fails due to logging
        error_log("Failed to write activity log: " . $e->getMessage());
        return false;
    }
}
