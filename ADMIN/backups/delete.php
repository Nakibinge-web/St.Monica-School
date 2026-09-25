<?php
/**
 * St. Monica Junior School CMS - Delete Backup File
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/BackupService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('backups/'));
}

require_csrf();

$filename = $_GET['file'] ?? '';

if (BackupService::delete($filename)) {
    log_activity('Deleted Backup', "Deleted backup file: {$filename}", 'backups');
    set_flash('success', "Backup '{$filename}' deleted.");
} else {
    set_flash('danger', 'Backup file not found or could not be deleted.');
}

redirect(admin_url('backups/'));
