<?php
/**
 * St. Monica Junior School CMS - Clear / Bulk Delete Notifications
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('notifications');
require_once CMS_ROOT . '/services/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('notifications/'));
}

require_csrf();

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'clear_read') {
    $count = NotificationService::deleteAll($adminId, true);
    if ($count > 0) {
        log_activity('Cleared Read Notifications', "Deleted {$count} read notification(s)", 'notifications');
        set_flash('success', "Cleared {$count} read notification" . ($count === 1 ? '' : 's') . ".");
    } else {
        set_flash('info', 'There were no read notifications to clear.');
    }
} elseif ($action === 'clear_all') {
    $count = NotificationService::deleteAll($adminId, false);
    if ($count > 0) {
        log_activity('Cleared All Notifications', "Deleted all ({$count}) notifications", 'notifications');
        set_flash('success', "All {$count} notification" . ($count === 1 ? '' : 's') . " have been deleted.");
    } else {
        set_flash('info', 'There were no notifications to delete.');
    }
} elseif ($action === 'delete_selected') {
    $selectedIds = $_POST['ids'] ?? [];
    if (!is_array($selectedIds)) {
        $selectedIds = [];
    }
    $count = NotificationService::deleteMultiple($selectedIds, $adminId);
    if ($count > 0) {
        log_activity('Bulk Deleted Notifications', "Deleted {$count} selected notification(s)", 'notifications');
        set_flash('success', "Deleted {$count} selected notification" . ($count === 1 ? '' : 's') . ".");
    } else {
        set_flash('warning', 'No valid notifications were selected for deletion.');
    }
} else {
    set_flash('warning', 'Invalid notification action requested.');
}

redirect(admin_url('notifications/'));
