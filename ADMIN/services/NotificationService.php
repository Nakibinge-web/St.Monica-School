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
    public static function recent(int $adminId, int $limit = 3): array {
        try {
            return Database::fetchAll(
                "SELECT * FROM `notifications` WHERE (`admin_id` = :id OR `admin_id` IS NULL) ORDER BY `created_at` DESC LIMIT " . (int)$limit,
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Fetch all notifications with pagination for the notifications page.
     */
    public static function all(int $adminId, int $limit = 20, int $offset = 0): array {
        try {
            return Database::fetchAll(
                "SELECT * FROM `notifications` WHERE (`admin_id` = :id OR `admin_id` IS NULL) ORDER BY `created_at` DESC LIMIT " . (int)$offset . ", " . (int)$limit,
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Total count of all notifications visible to a given admin.
     */
    public static function totalCount(int $adminId): int {
        try {
            return (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `notifications` WHERE (`admin_id` = :id OR `admin_id` IS NULL)",
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return 0;
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

    /**
     * Find a single notification visible to a given admin.
     */
    public static function find(int $notificationId, int $adminId): ?array {
        try {
            return Database::fetchOne(
                "SELECT * FROM `notifications` WHERE `id` = :id AND (`admin_id` = :aid OR `admin_id` IS NULL)",
                ['id' => $notificationId, 'aid' => $adminId]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Delete a single notification visible to a given admin.
     */
    public static function delete(int $notificationId, int $adminId): bool {
        try {
            return Database::delete(
                'notifications',
                '`id` = :id AND (`admin_id` = :aid OR `admin_id` IS NULL)',
                ['id' => $notificationId, 'aid' => $adminId]
            ) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete multiple selected notifications visible to a given admin.
     */
    public static function deleteMultiple(array $ids, int $adminId): int {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (empty($ids)) {
            return 0;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM `notifications` WHERE `id` IN ($placeholders) AND (`admin_id` = ? OR `admin_id` IS NULL)";
            $params = array_merge($ids, [$adminId]);
            $stmt = Database::query($sql, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Delete all (or only read) notifications visible to a given admin.
     */
    public static function deleteAll(int $adminId, bool $onlyRead = false): int {
        try {
            $where = '(`admin_id` = :aid OR `admin_id` IS NULL)';
            if ($onlyRead) {
                $where .= ' AND `is_read` = 1';
            }
            return Database::delete('notifications', $where, ['aid' => $adminId]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Count read notifications visible to a given admin.
     */
    public static function readCount(int $adminId): int {
        try {
            return (int)Database::fetchColumn(
                "SELECT COUNT(*) FROM `notifications` WHERE `is_read` = 1 AND (`admin_id` = :id OR `admin_id` IS NULL)",
                ['id' => $adminId]
            );
        } catch (Exception $e) {
            return 0;
        }
    }
}
