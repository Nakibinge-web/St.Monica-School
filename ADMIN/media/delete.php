<?php
/**
 * St. Monica Junior School CMS - Delete Media Asset
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('media/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `media_library` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            Database::delete('media_library', 'id = :id', ['id' => $id]);

            // Attempt to unlink file if in uploads directory
            if (!empty($item['file_path']) && str_contains($item['file_path'], 'ADMIN/uploads/')) {
                $fullPath = dirname(CMS_ROOT) . '/' . ltrim($item['file_path'], '/');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            log_activity('Deleted Media Asset', "Deleted '{$item['title']}' (Path: {$item['file_path']})", 'media', $id);
            set_flash('success', "Media asset '{$item['title']}' deleted successfully.");
        } else {
            set_flash('danger', 'Media asset not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('media/'));
