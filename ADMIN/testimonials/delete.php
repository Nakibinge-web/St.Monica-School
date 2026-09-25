<?php
/**
 * St. Monica Junior School CMS - Delete Testimonial
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('testimonials');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('testimonials/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `testimonials` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            // Soft delete: the record and photo are kept so it can be restored from Trash.
            Database::update('testimonials', ['deleted_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);

            log_activity('Deleted Testimonial', "Deleted testimonial by {$item['name']}", 'testimonials', $id);
            set_flash('success', "Testimonial by '{$item['name']}' moved to Trash.");
        } else {
            set_flash('danger', 'Testimonial not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('testimonials/'));
