<?php
/**
 * St. Monica Junior School CMS - Change Admin Password
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('profile/'));
}

require_csrf();

$adminId         = $_SESSION['admin_id'];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    set_flash('danger', 'Please complete all password fields.');
    redirect(admin_url('profile/'));
}

if (strlen($newPassword) < 8) {
    set_flash('danger', 'New password must be at least 8 characters long.');
    redirect(admin_url('profile/'));
}

if ($newPassword !== $confirmPassword) {
    set_flash('danger', 'New password and confirmation do not match.');
    redirect(admin_url('profile/'));
}

try {
    $admin = Database::fetchOne("SELECT `password` FROM `admins` WHERE `id` = :id", ['id' => $adminId]);

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        log_activity('Failed Password Change', 'Invalid current password entered', 'profile', $adminId);
        set_flash('danger', 'Your current password was entered incorrectly.');
        redirect(admin_url('profile/'));
    }

    // Hash new password securely
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    Database::update('admins', [
        'password' => $hashedPassword
    ], 'id = :id', ['id' => $adminId]);

    // Regenerate session ID upon credential change
    session_regenerate_id(true);

    log_activity('Changed Password', 'Administrator changed password successfully', 'profile', $adminId);
    set_flash('success', 'Your password has been changed successfully.');
} catch (Exception $e) {
    set_flash('danger', 'Database error changing password: ' . $e->getMessage());
}

redirect(admin_url('profile/'));
