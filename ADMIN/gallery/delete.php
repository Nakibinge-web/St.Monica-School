<?php
/**
 * St. Monica Junior School CMS - Delete Gallery Media
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('gallery');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('gallery/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $image = Database::fetchOne("SELECT * FROM `gallery` WHERE `id` = :id", ['id' => $id]);

        if ($image) {
            // Soft delete: the record and file are kept so it can be restored from Trash.
            Database::update('gallery', ['deleted_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);

            log_activity('Deleted Gallery Media', "{$image['title']} (ID: {$id})", 'gallery', $id);
            set_flash('success', "Media '{$image['title']}' moved to Trash.");
        } else {
            set_flash('danger', 'Media item not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('gallery/'));
