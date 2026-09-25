<?php
/**
 * St. Monica Junior School CMS - Delete Announcement
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('announcements');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('announcements/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `announcements` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            Database::delete('announcements', 'id = :id', ['id' => $id]);
            log_activity('Deleted Announcement', "Deleted announcement: {$item['title']}", 'announcements', $id);
            set_flash('success', "Announcement '{$item['title']}' deleted successfully.");
        } else {
            set_flash('danger', 'Announcement not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Failed to delete announcement.');
    }
}

redirect(admin_url('announcements/'));
