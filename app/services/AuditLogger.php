<?php
// app/services/AuditLogger.php

class AuditLogger {
    public static function log(string $action, string $module, ?string $recordId = null, ?array $details = null): void {
        try {
            $db = Database::getInstance();
            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown', 0, 255);
            $detailsJson = $details ? json_encode($details) : null;

            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, module, record_id, ip_address, user_agent, details)
                VALUES (:uid, :act, :mod, :rec, :ip, :ua, :det)
            ");
            $stmt->execute([
                'uid' => $userId,
                'act' => $action,
                'mod' => $module,
                'rec' => $recordId,
                'ip'  => $ip,
                'ua'  => $agent,
                'det' => $detailsJson
            ]);
        } catch (Exception $e) {
            // Fail silently on audit log error to not block application execution
            error_log("AuditLog Failure: " . $e->getMessage());
        }
    }
}
