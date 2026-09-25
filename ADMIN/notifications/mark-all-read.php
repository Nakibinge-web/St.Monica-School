<?php
/**
 * St. Monica Junior School CMS - Mark All Notifications as Read
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
NotificationService::markAllRead($adminId);

set_flash('success', 'All notifications marked as read.');
redirect(admin_url('notifications/'));
