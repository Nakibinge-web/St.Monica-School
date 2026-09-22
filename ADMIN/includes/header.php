<?php
/**
 * St. Monica Junior School CMS - Layout Header
 */

if (!defined('CMS_ROOT')) {
    if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';

// Enforce auth on all pages using the header (unless explicitly set $publicPage = true)
if (empty($publicPage)) {
    require_auth();
}

$currentAdmin = current_admin();
$pageTitle = $pageTitle ?? 'CMS Administration';
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Admin Bespoke CSS -->
    <link rel="stylesheet" href="<?= admin_url('assets/css/admin.css') ?>">
</head>
<body class="min-h-full flex flex-col antialiased">
    <!-- Top Bar Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="flex items-center justify-between h-16 px-4 lg:px-8">
            <!-- Left: Toggle & Brand -->
            <div class="flex items-center gap-4">
                <button id="sidebarToggleBtn" type="button" class="lg:hidden text-slate-600 hover:text-slate-900 p-2 rounded-lg hover:bg-slate-100 transition">
                    <span class="material-symbols-outlined text-[24px]">menu</span>
                </button>
                <a href="<?= admin_url('dashboard/') ?>" class="flex items-center gap-3">
                    <img src="<?= public_url('assets/imgz/logo2-cut.png') ?>" alt="Logo" class="h-9 w-auto object-contain">
                    <div class="hidden sm:block">
                        <span class="brand-font font-bold text-slate-900 text-base leading-tight block">St. Monica School</span>
                        <span class="text-[11px] font-semibold text-red-600 tracking-wider uppercase block">Admin CMS Panel</span>
                    </div>
                </a>
            </div>

            <!-- Right: Website preview & Admin Profile -->
            <div class="flex items-center gap-3 sm:gap-6">
                <!-- Public Website Preview Link -->
                <a href="<?= public_url('index.html') ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md transition duration-150">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    <span class="hidden md:inline">View Public Site</span>
                </a>

                <!-- Admin Profile dropdown/indicator -->
                <div class="flex items-center gap-3 pl-3 sm:pl-6 border-l border-slate-200">
                    <a href="<?= admin_url('profile/') ?>" class="flex items-center gap-2 hover:opacity-80 transition" title="Edit Profile">
                        <div class="w-8 h-8 rounded-full bg-[#1e2a4a] text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                            <?= e(substr($currentAdmin['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs font-semibold text-slate-800 leading-tight"><?= e($currentAdmin['name'] ?? 'Admin') ?></div>
                            <div class="text-[11px] text-slate-500 font-medium">
                                <?php
                                $rName = match($currentAdmin['role'] ?? '') {
                                    'super_admin', 'administrator' => 'Super Admin',
                                    'editor' => 'Editor',
                                    'admissions_manager' => 'Admissions Mgr',
                                    default => ucfirst($currentAdmin['role'] ?? 'Admin')
                                };
                                echo e($rName);
                                ?>
                            </div>
                        </div>
                    </a>
                    <a href="<?= admin_url('login/logout.php') ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Sign Out">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container: Sidebar + Content -->
    <div class="flex-1 flex relative">
        <!-- Sidebar Backdrop (Mobile) -->
        <div id="sidebarBackdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden"></div>

        <!-- Include Sidebar Component -->
        <?php include CMS_ROOT . '/includes/sidebar.php'; ?>

        <!-- Content Area -->
        <main class="admin-main flex-1 min-w-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Flash Alerts Notification -->
                <?= render_flash() ?>
