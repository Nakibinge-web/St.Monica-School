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
            Database::delete('testimonials', 'id = :id', ['id' => $id]);

            // Clean up uploaded image if present
            if (!empty($item['photo']) && str_contains($item['photo'], 'ADMIN/uploads/')) {
                $fullPath = CMS_ROOT . '/uploads/' . basename(dirname($item['photo'])) . '/' . basename($item['photo']);
                if (file_exists($fullPath)) @unlink($fullPath);
            }

            log_activity('Deleted Testimonial', "Deleted testimonial by {$item['name']}", 'testimonials', $id);
            set_flash('success', "{$item['name']} deleted successfully.");
        } else {
            set_flash('danger', 'Testimonial not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('testimonials/'));
