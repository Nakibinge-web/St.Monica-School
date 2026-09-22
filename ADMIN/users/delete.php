<?php
/**
 * St. Monica Junior School CMS - Delete Administrator User
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('users/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
        set_flash('danger', 'You cannot delete your own active administrator account.');
        redirect(admin_url('users/'));
    }

    try {
        $user = Database::fetchOne("SELECT * FROM `admins` WHERE `id` = :id", ['id' => $id]);

        if ($user) {
            // Check if user is a super admin and if they are the last active super admin
            if (in_array($user['role'], ['super_admin', 'administrator']) && $user['status'] === 'active') {
                $otherSuperAdmins = (int)Database::fetchColumn(
                    "SELECT COUNT(*) FROM `admins` 
                     WHERE (`role` = 'super_admin' OR `role` = 'administrator') 
                       AND `status` = 'active' 
                       AND `id` != :id",
                    ['id' => $id]
                );

                if ($otherSuperAdmins === 0) {
                    set_flash('danger', 'Action blocked: Cannot delete the last active Super Administrator account.');
                    redirect(admin_url('users/'));
                }
            }

            Database::delete('admins', '`id` = :id', ['id' => $id]);

            log_activity(
                'Deleted Admin User',
                "Deleted administrator account for '{$user['name']}' ({$user['email']})",
                'users',
                $id
            );

            set_flash('success', "Administrator account for '{$user['name']}' has been permanently deleted.");
        } else {
            set_flash('danger', 'Administrator account not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('users/'));
