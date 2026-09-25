<?php
/**
 * St. Monica Junior School CMS - CLI Backup Runner
 * Intended for scheduled execution (cron / Windows Task Scheduler), not web access.
 *
 * Usage: php ADMIN/backups/cli-backup.php
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/services/BackupService.php';

$filename = BackupService::create();

if ($filename) {
    try {
        require_once CMS_ROOT . '/includes/functions.php';
        require_once CMS_ROOT . '/includes/database.php';
        Database::insert('activity_logs', [
            'admin_id'   => null,
            'admin_name' => 'Scheduled Task',
            'action'     => 'Created Backup',
            'module'     => 'backups',
            'details'    => "Scheduled backup file created: {$filename}",
            'ip_address' => '127.0.0.1'
        ]);
    } catch (Exception $e) {
        // Logging failure shouldn't affect the backup's success status
    }
    echo "Backup created successfully: {$filename}\n";
    exit(0);
}

echo "Backup creation FAILED.\n";
exit(1);
