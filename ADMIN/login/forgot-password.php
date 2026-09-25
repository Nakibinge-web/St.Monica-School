<?php
/**
 * St. Monica Junior School CMS - Forgot Password Request
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/services/EmailService.php';

if (is_logged_in()) {
    redirect(admin_url('dashboard/'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $admin = Database::fetchOne("SELECT * FROM `admins` WHERE `email` = :email AND `status` = 'active' LIMIT 1", ['email' => $email]);

            if ($admin) {
                $rawToken = bin2hex(random_bytes(32));
                Database::insert('password_resets', [
                    'admin_id'   => $admin['id'],
                    'token_hash' => hash('sha256', $rawToken),
                    'expires_at' => date('Y-m-d H:i:s', time() + 3600)
                ]);

                $resetUrl = admin_url('login/reset-password.php?token=' . $rawToken);
                EmailService::sendTemplate('password_reset', $admin['email'], [
                    'admin_name'  => $admin['name'],
                    'reset_url'   => $resetUrl,
                    'school_name' => 'St. Monica Junior School Kasanje'
                ], $admin['name']);

                log_activity('Password Reset Requested', 'Reset link requested for: ' . $email, 'auth');
            }
            // Deliberately identical outcome whether or not the account exists,
            // to avoid revealing which email addresses have admin accounts.
        } catch (Exception $e) {
            // Fall through to the same generic message
        }
    }

    set_flash('success', 'If that email address is registered, a password reset link has been sent.');
    redirect(admin_url('login/login.php'));
}

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — St. Monica CMS</title>
    <link rel="icon" type="image/png" href="<?= public_url('assets/imgz/logo2-cut.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= admin_url('assets/css/admin.css') ?>">
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-950 via-[#081534] to-slate-900">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-3 shadow-xl mb-4">
                <img src="<?= public_url('assets/imgz/logo2-cut.png') ?>" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-bold text-white brand-font tracking-tight">St. Monica Junior School</h1>
            <p class="text-xs font-semibold text-red-400 uppercase tracking-widest mt-1">Website Administration CMS</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-2xl border border-slate-200/80">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900 brand-font">Forgot Password</h2>
                <p class="text-sm text-slate-500 mt-1">Enter your administrator email address and we'll send you a secure link to reset your password.</p>
            </div>

            <?= render_flash() ?>

            <form method="POST" action="<?= admin_url('login/forgot-password.php') ?>" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input type="email" id="email" name="email" required autofocus placeholder="admin@stmonicakasanje.ac.ug"
                               class="cms-input pl-11 py-3 text-sm focus:border-[#1e2a4a]">
                    </div>
                </div>

                <button type="submit" class="cms-btn cms-btn-accent w-full py-3 text-sm shadow-md font-bold mt-2">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Send Reset Link</span>
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <a href="<?= admin_url('login/login.php') ?>" class="text-xs font-semibold text-red-600 hover:text-red-700">&larr; Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
