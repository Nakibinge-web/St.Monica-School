<?php
/**
 * St. Monica Junior School CMS - Restore Item from Trash
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('trash/'));
}

require_csrf();

$recoverableTables = ['news_events', 'staff', 'gallery', 'testimonials'];
$table = $_POST['table'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if (!in_array($table, $recoverableTables, true) || $id <= 0) {
    set_flash('danger', 'Invalid restore request.');
    redirect(admin_url('trash/'));
}

try {
    $rows = Database::update("{$table}", ['deleted_at' => null], 'id = :id', ['id' => $id]);
    if ($rows > 0) {
        log_activity('Restored from Trash', "Restored record #{$id} in {$table}", $table, $id);
        set_flash('success', 'Item restored successfully.');
    } else {
        set_flash('warning', 'Item was not found in Trash.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Failed to restore item.');
}

redirect(admin_url('trash/'));
