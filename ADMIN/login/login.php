<?php
/**
 * St. Monica Junior School CMS - Admin Login Page
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';

// If already logged in, go straight to dashboard
if (is_logged_in()) {
    redirect(admin_url('dashboard/'));
}

$pageTitle = 'Sign In to Administration';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — St. Monica CMS</title>
    <link rel="icon" type="image/png" href="<?= public_url('assets/imgz/logo2-cut.png') ?>">
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" />
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= admin_url('assets/css/admin.css') ?>">
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-950 via-[#081534] to-slate-900">
    <div class="w-full max-w-md">
        <!-- School Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-3 shadow-xl mb-4">
                <img src="<?= public_url('assets/imgz/logo2-cut.png') ?>" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-bold text-white brand-font tracking-tight">St. Monica Junior School</h1>
            <p class="text-xs font-semibold text-red-400 uppercase tracking-widest mt-1">Website Administration CMS</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl p-8 shadow-2xl border border-slate-200/80">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-slate-900 brand-font">Sign In</h2>
                <p class="text-sm text-slate-500 mt-1">Enter your credentials to manage school website content.</p>
            </div>

            <!-- Flash Alert (if any) -->
            <?= render_flash() ?>

            <!-- Login Form -->
            <form method="POST" action="<?= admin_url('login/authenticate.php') ?>" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input type="email" id="email" name="email" required autofocus placeholder="admin@stmonicakasanje.ac.ug"
                               class="cms-input pl-11 py-3 text-sm focus:border-[#1e2a4a]">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock</span>
                        <input type="password" id="password" name="password" required placeholder="••••••••••••"
                               class="cms-input pl-11 py-3 text-sm focus:border-[#1e2a4a]">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 select-none">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                        <span>Remember me for 30 days</span>
                    </label>
                    <a href="<?= admin_url('login/forgot-password.php') ?>" class="text-red-600 hover:text-red-700 font-semibold transition">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="cms-btn cms-btn-accent w-full py-3 text-sm shadow-md font-bold mt-2">
                    <span class="material-symbols-outlined text-[18px]">login</span>
                    <span>Sign In to Dashboard</span>
                </button>
            </form>

            <?php
            $loginConfig = require CMS_ROOT . '/includes/config.php';
            if (!empty($loginConfig['app']['debug'])):
            ?>
            <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400">
                    Default Dev Login: <code class="text-slate-700 bg-slate-100 px-1 py-0.5 rounded">admin@stmonicakasanje.ac.ug</code> / <code class="text-slate-700 bg-slate-100 px-1 py-0.5 rounded">Admin@2026!</code>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer Notice -->
        <p class="text-center text-xs text-slate-500 mt-6">
            <a href="<?= public_url('index.html') ?>" class="text-slate-400 hover:text-white transition">&larr; Back to Website</a>
            &bull; &copy; <?= date('Y') ?> St. Monica Junior School Kasanje. All rights reserved.
        </p>
    </div>
</body>
</html>
