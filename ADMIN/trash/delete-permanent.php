<?php
/**
 * St. Monica Junior School CMS - Permanently Delete Item from Trash
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('trash/'));
}

require_csrf();

// Whitelisted recoverable tables: table => [title column, file column, upload subdir]
$recoverableTables = [
    'news_events'  => ['title_col' => 'title', 'file_col' => 'featured_image', 'subdir' => 'news'],
    'staff'        => ['title_col' => 'name',  'file_col' => 'photo',          'subdir' => 'staff'],
    'gallery'      => ['title_col' => 'title', 'file_col' => 'file_path',      'subdir' => 'gallery'],
    'testimonials' => ['title_col' => 'name',  'file_col' => 'photo',          'subdir' => null],
];

$table = $_GET['table'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!isset($recoverableTables[$table]) || $id <= 0) {
    set_flash('danger', 'Invalid delete request.');
    redirect(admin_url('trash/'));
}

$meta = $recoverableTables[$table];

try {
    $item = Database::fetchOne("SELECT * FROM `{$table}` WHERE `id` = :id AND `deleted_at` IS NOT NULL", ['id' => $id]);

    if ($item) {
        Database::delete($table, 'id = :id', ['id' => $id]);

        // Images picked from the Media Library are shared, so only remove files nothing else uses
        $filePath = $item[$meta['file_col']] ?? null;
        if (!empty($filePath) && str_contains($filePath, 'ADMIN/uploads/') && !is_media_file_protected($filePath)) {
            $fullPath = dirname(CMS_ROOT) . '/' . ltrim($filePath, '/');
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        $title = $item[$meta['title_col']] ?? "#{$id}";
        log_activity('Permanently Deleted', "Permanently deleted {$table} record: {$title}", $table, $id);
        set_flash('success', "'{$title}' permanently deleted.");
    } else {
        set_flash('danger', 'Item not found in Trash.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Failed to permanently delete item.');
}

redirect(admin_url('trash/'));
