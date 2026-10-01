<?php
// app/services/NotificationService.php

class NotificationService {
    /**
     * Send a notification to a specific user (or system admins if userId is null)
     */
    public static function send(?int $userId, string $title, string $message, ?string $url = null): void {
        try {
            $db = Database::getInstance();

            if ($userId === null) {
                // Send to all Admin users (role_id = 1)
                $adminStmt = $db->query("SELECT id FROM users WHERE role_id = 1 AND status = 'Active'");
                $adminIds = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, url) VALUES (:uid, :t, :m, :u)");
                foreach ($adminIds as $aid) {
                    $stmt->execute(['uid' => $aid, 't' => $title, 'm' => $message, 'u' => $url]);
                }
                return;
            }

            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, url) VALUES (:uid, :t, :m, :u)");
            $stmt->execute(['uid' => $userId, 't' => $title, 'm' => $message, 'u' => $url]);
        } catch (Exception $e) {
            error_log("NotificationService Failure: " . $e->getMessage());
        }
    }

    /**
     * Fetch unread notifications for logged in user
     */
    public static function getUnread(?int $userId): array {
        if (!$userId) return [];
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = :uid AND is_read = 0 
                ORDER BY created_at DESC LIMIT 10
            ");
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(int $id, int $userId): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $userId]);
        } catch (Exception $e) {
            error_log("NotificationService markAsRead Failure: " . $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read for current user
     */
    public static function markAllAsRead(int $userId): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);
        } catch (Exception $e) {
            error_log("NotificationService markAllAsRead Failure: " . $e->getMessage());
        }
    }
}
