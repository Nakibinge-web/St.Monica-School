<?php
/**
 * St. Monica Junior School CMS - Delete Gallery Media
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('gallery/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $image = Database::fetchOne("SELECT * FROM `gallery` WHERE `id` = :id", ['id' => $id]);

        if ($image) {
            Database::delete('gallery', 'id = :id', ['id' => $id]);

            // Unlink if stored under ADMIN/uploads/gallery/
            if (!empty($image['file_path']) && str_contains($image['file_path'], 'ADMIN/uploads/gallery/')) {
                $fullPath = CMS_ROOT . '/uploads/gallery/' . basename($image['file_path']);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            log_activity('Deleted Gallery Media', "{$image['title']} (ID: {$id})");
            set_flash('success', "Media '{$image['title']}' was removed from gallery.");
        } else {
            set_flash('danger', 'Media item not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('gallery/'));
