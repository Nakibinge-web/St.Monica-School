<?php
/**
 * St. Monica Junior School CMS - Delete a Single Notification
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('notifications');
require_once CMS_ROOT . '/services/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('notifications/'));
}

require_csrf();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$adminId = (int)($_SESSION['admin_id'] ?? 0);

if ($id > 0) {
    $notif = NotificationService::find($id, $adminId);
    if ($notif) {
        $deleted = NotificationService::delete($id, $adminId);
        if ($deleted) {
            log_activity('Deleted Notification', "Deleted notification: {$notif['title']}", 'notifications', $id);
            set_flash('success', 'Notification deleted successfully.');
        } else {
            set_flash('danger', 'Failed to delete notification.');
        }
    } else {
        set_flash('danger', 'Notification not found or already deleted.');
    }
} else {
    set_flash('warning', 'Invalid notification identifier specified.');
}

// Only allow redirecting back within the admin panel (avoid open-redirect)
$redirectTo = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
$adminBase = admin_url('');
if ($redirectTo === '' || strpos($redirectTo, $adminBase) !== 0) {
    $redirectTo = admin_url('notifications/');
}
redirect($redirectTo);
