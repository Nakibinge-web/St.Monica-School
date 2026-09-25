<?php
/**
 * St. Monica Junior School CMS - Delete Enquiry
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('inquiries');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('inquiries/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $item = Database::fetchOne("SELECT * FROM `enquiries` WHERE `id` = :id", ['id' => $id]);

        if ($item) {
            Database::delete('enquiries', 'id = :id', ['id' => $id]);
            log_activity('Deleted Enquiry', "Deleted enquiry from {$item['name']} ({$item['email']})", 'enquiries', $id);
            set_flash('success', 'Enquiry deleted successfully.');
        } else {
            set_flash('danger', 'Enquiry not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Failed to delete enquiry.');
    }
}

redirect(admin_url('inquiries/'));
