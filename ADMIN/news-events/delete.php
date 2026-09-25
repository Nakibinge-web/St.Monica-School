<?php
/**
 * St. Monica Junior School CMS - Delete News or Event
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('news-events');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('news-events/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `news_events` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            // Soft delete: the record and its image are kept so it can be restored from Trash.
            Database::update('news_events', ['deleted_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);

            log_activity('Deleted News/Event', "{$item['title']} (ID: {$id})", 'news_events', $id);
            set_flash('success', "Post '{$item['title']}' moved to Trash.");
        } else {
            set_flash('danger', 'Post not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('news-events/'));
