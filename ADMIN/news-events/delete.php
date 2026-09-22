<?php
/**
 * St. Monica Junior School CMS - Delete News or Event
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('news-events/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `news_events` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            Database::delete('news_events', 'id = :id', ['id' => $id]);

            // Clean up uploaded image if in ADMIN/uploads/news/
            if (!empty($item['featured_image']) && str_contains($item['featured_image'], 'ADMIN/uploads/news/')) {
                $fullPath = CMS_ROOT . '/uploads/news/' . basename($item['featured_image']);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            log_activity('Deleted News/Event', "{$item['title']} (ID: {$id})");
            set_flash('success', "Post '{$item['title']}' deleted successfully.");
        } else {
            set_flash('danger', 'Post not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('news-events/'));
