<?php
/**
 * St. Monica Junior School CMS - Update Profile Details
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('profile/'));
}

require_csrf();

$adminId = $_SESSION['admin_id'];
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');

if (empty($name) || empty($email)) {
    set_flash('danger', 'Name and email address are required.');
    redirect(admin_url('profile/'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please provide a valid email address.');
    redirect(admin_url('profile/'));
}

try {
    // Check if email is already used by another admin
    $existing = Database::fetchOne("SELECT `id` FROM `admins` WHERE `email` = :email AND `id` != :id", [
        'email' => $email,
        'id'    => $adminId
    ]);

    if ($existing) {
        set_flash('danger', 'That email address is already assigned to another administrator.');
        redirect(admin_url('profile/'));
    }

    Database::update('admins', [
        'name'  => $name,
        'email' => $email,
        'phone' => !empty($phone) ? $phone : null
    ], 'id = :id', ['id' => $adminId]);

    // Update active session values
    $_SESSION['admin_name']  = $name;
    $_SESSION['admin_email'] = $email;

    log_activity('Updated Profile', "Administrator updated personal details", 'profile', $adminId);
    set_flash('success', 'Profile information updated successfully.');
} catch (Exception $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect(admin_url('profile/'));
