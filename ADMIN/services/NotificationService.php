<?php
/**
 * St. Monica Junior School CMS
 * Admin Notification Center Service
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/database.php';

class NotificationService {
    /**
     * Create a notification. Pass $adminId = null to broadcast to every
     * administrator (surfaced to all in the bell dropdown / notifications list).
     */
    public static function create(string $title, ?string $message = null, ?string $link = null, string $type = 'general', ?int $adminId = null): void {
        try {
            Database::insert('notifications', [
                'admin_id' => $adminId,
                'type'     => $type,
                'title'    => $title,
                'message'  => $message,
                'link'     => $link,
                'is_read'  => 0
            ]);
        } catch (Exception $e) {
            // Notifications are a convenience layer; never break the calling action
        }
    }

    /**
     * Count unread notifications visible to a given admin (their own + broadcasts).
     */
    public static function unreadCount(int $adminId): int {
        try {
            return (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `notifications` WHERE `is_read` = 0 AND (`admin_id` = :id OR `admin_id` IS NULL)",
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Fetch the most recent notifications visible to a given admin.
     */
    public static function recent(int $adminId, int $limit = 8): array {
        try {
            return Database::fetchAll(
                "SELECT * FROM `notifications` WHERE (`admin_id` = :id OR `admin_id` IS NULL) ORDER BY `created_at` DESC LIMIT " . (int)$limit,
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return [];
        }
    }

    public static function markRead(int $notificationId, int $adminId): void {
        try {
            Database::update('notifications', ['is_read' => 1], '`id` = :id AND (`admin_id` = :aid OR `admin_id` IS NULL)', ['id' => $notificationId, 'aid' => $adminId]);
        } catch (Exception $e) {
            // ignore
        }
    }

    public static function markAllRead(int $adminId): void {
        try {
            Database::update('notifications', ['is_read' => 1], '`admin_id` = :aid OR `admin_id` IS NULL', ['aid' => $adminId]);
        } catch (Exception $e) {
            // ignore
        }
    }
}
