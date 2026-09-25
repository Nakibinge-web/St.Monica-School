<?php
/**
 * St. Monica Junior School CMS - Reset Password
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';

if (is_logged_in()) {
    redirect(admin_url('dashboard/'));
}

$rawToken = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenValid = false;
$resetRecord = null;

if (!empty($rawToken)) {
    try {
        $resetRecord = Database::fetchOne(
            "SELECT * FROM `password_resets` WHERE `token_hash` = :h AND `used_at` IS NULL AND `expires_at` > NOW() LIMIT 1",
            ['h' => hash('sha256', $rawToken)]
        );
        $tokenValid = (bool)$resetRecord;
    } catch (Exception $e) {
        $tokenValid = false;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $tokenValid) {
    require_csrf();

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) {
        set_flash('danger', 'Password must be at least 8 characters long.');
    } elseif ($password !== $confirm) {
        set_flash('danger', 'Passwords do not match.');
    } else {
        try {
            Database::update('admins', [
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ], 'id = :id', ['id' => $resetRecord['admin_id']]);

            Database::update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $resetRecord['id']]);

            // Invalidate any other outstanding reset tokens and remember-me sessions for this account
            Database::delete('password_resets', 'admin_id = :id AND used_at IS NULL', ['id' => $resetRecord['admin_id']]);
            Database::delete('remember_tokens', 'admin_id = :id', ['id' => $resetRecord['admin_id']]);

            log_activity('Password Reset Completed', 'Password reset via secure link', 'auth', $resetRecord['admin_id']);

            set_flash('success', 'Your password has been reset successfully. Please sign in with your new password.');
            redirect(admin_url('login/login.php'));
        } catch (Exception $e) {
            set_flash('danger', 'Failed to reset password. Please try again.');
        }
    }
}

$pageTitle = 'Reset Password';
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
            <?= render_flash() ?>

            <?php if (!$tokenValid): ?>
                <div class="text-center py-4">
                    <span class="material-symbols-outlined text-5xl text-red-400 mb-3 block">error</span>
                    <h2 class="text-lg font-bold text-slate-900">Invalid or Expired Link</h2>
                    <p class="text-sm text-slate-500 mt-2">This password reset link is invalid or has expired. Please request a new one.</p>
                    <a href="<?= admin_url('login/forgot-password.php') ?>" class="cms-btn cms-btn-accent mt-5 inline-flex">
                        Request New Link
                    </a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-900 brand-font">Choose a New Password</h2>
                    <p class="text-sm text-slate-500 mt-1">Minimum 8 characters.</p>
                </div>

                <form method="POST" action="<?= admin_url('login/reset-password.php?token=' . e($rawToken)) ?>" class="space-y-5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($rawToken) ?>">

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">New Password</label>
                        <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••••••"
                               class="cms-input py-3 text-sm focus:border-[#1e2a4a]">
                    </div>
                    <div>
                        <label for="password_confirm" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Confirm New Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" placeholder="••••••••••••"
                               class="cms-input py-3 text-sm focus:border-[#1e2a4a]">
                    </div>

                    <button type="submit" class="cms-btn cms-btn-accent w-full py-3 text-sm shadow-md font-bold mt-2">
                        <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                        <span>Reset Password</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
