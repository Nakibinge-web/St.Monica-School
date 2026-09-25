<?php
/**
 * St. Monica Junior School CMS - Authentication Handler
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/includes/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('login/login.php'));
}

// Enforce CSRF protection
require_csrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    set_flash('danger', 'Please provide both your email address and password.');
    redirect(admin_url('login/login.php'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please enter a valid email address format.');
    redirect(admin_url('login/login.php'));
}

if (is_login_locked($email)) {
    log_activity('Blocked Login Attempt (Rate Limited)', 'Too many recent failed attempts for: ' . $email, 'auth');
    set_flash('danger', 'Too many failed sign-in attempts for this account. Please wait 15 minutes and try again.');
    redirect(admin_url('login/login.php'));
}

$remember = !empty($_POST['remember']);

try {
    $admin = Database::fetchOne("SELECT * FROM `admins` WHERE `email` = :email LIMIT 1", ['email' => $email]);

    if ($admin && password_verify($password, $admin['password'])) {
        // Check if account is active
        if (isset($admin['status']) && $admin['status'] === 'inactive') {
            log_activity('Deactivated Login Attempt', 'Attempt by deactivated account: ' . $email, 'auth');
            set_flash('danger', 'Your administrator account has been deactivated. Please contact an administrator.');
            redirect(admin_url('login/login.php'));
        }

        // Successful login
        login_admin($admin, $remember);

        $redirectTo = $_SESSION['redirect_to'] ?? admin_url('dashboard/');
        unset($_SESSION['redirect_to']);

        set_flash('success', 'Welcome back, ' . $admin['name'] . '!');
        redirect($redirectTo);
    } else {
        // Slow down brute-force attempts
        usleep(300000); // 300ms delay
        log_activity('Failed Login Attempt', 'Attempted email: ' . $email . ' from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
        set_flash('danger', 'Invalid email address or password. Please check your credentials.');
        redirect(admin_url('login/login.php'));
    }
} catch (Exception $e) {
    set_flash('danger', 'A system error occurred during sign in. Please try again shortly.');
    redirect(admin_url('login/login.php'));
}
