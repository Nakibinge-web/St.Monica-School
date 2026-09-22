<?php
/**
 * St. Monica Junior School CMS - Delete Staff Member
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('staff/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $staff = Database::fetchOne("SELECT * FROM `staff` WHERE `id` = :id", ['id' => $id]);

        if ($staff) {
            // Delete DB record
            Database::delete('staff', 'id = :id', ['id' => $id]);

            // Clean up uploaded image if inside ADMIN/uploads/staff/
            if (!empty($staff['photo']) && str_contains($staff['photo'], 'ADMIN/uploads/staff/')) {
                $fullPath = CMS_ROOT . '/uploads/staff/' . basename($staff['photo']);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            log_activity('Deleted Staff Member', "{$staff['name']} (ID: {$id})");
            set_flash('success', "Staff member '{$staff['name']}' has been deleted.");
        } else {
            set_flash('danger', 'Staff member not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('staff/'));
