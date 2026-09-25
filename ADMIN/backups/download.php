<?php
/**
 * St. Monica Junior School CMS - Download Backup File (auth-gated stream)
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/BackupService.php';

$filename = $_GET['file'] ?? '';
$path = BackupService::resolvePath($filename);

if (!$path) {
    set_flash('danger', 'Backup file not found.');
    redirect(admin_url('backups/'));
}

log_activity('Downloaded Backup', "Downloaded backup file: " . basename($path), 'backups');

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
