<?php
/**
 * St. Monica Junior School CMS - Delete Staff Member
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('staff/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $staff = Database::fetchOne("SELECT * FROM `staff` WHERE `id` = :id", ['id' => $id]);

        if ($staff) {
            // Soft delete: the record and photo are kept so it can be restored from Trash.
            Database::update('staff', ['deleted_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);

            log_activity('Deleted Staff Member', "{$staff['name']} (ID: {$id})", 'staff', $id);
            set_flash('success', "Staff member '{$staff['name']}' moved to Trash.");
        } else {
            set_flash('danger', 'Staff member not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('staff/'));
