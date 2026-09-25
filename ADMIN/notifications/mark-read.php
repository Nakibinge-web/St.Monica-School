<?php
/**
 * St. Monica Junior School CMS - Mark a Single Notification as Read
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('notifications');
require_once CMS_ROOT . '/services/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('notifications/'));
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);
$adminId = (int)($_SESSION['admin_id'] ?? 0);

if ($id > 0) {
    NotificationService::markRead($id, $adminId);
}

// Only allow redirecting back within the admin panel (avoid open-redirect)
$redirectTo = $_POST['redirect'] ?? '';
$adminBase = admin_url('');
if ($redirectTo === '' || strpos($redirectTo, $adminBase) !== 0) {
    $redirectTo = admin_url('notifications/');
}
redirect($redirectTo);
