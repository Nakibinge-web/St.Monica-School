<?php
/**
 * St. Monica Junior School CMS - Homepage Management Hub
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'Homepage Content Management';
$activeMenu = 'homepage';

try {
    $heroCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `hero_slides`");
    $whyCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `why_choose_us_items`");
    $statsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `statistics`");
    $directorSection = Database::fetchOne("SELECT * FROM `homepage_sections` WHERE `section_key` = 'director_message' LIMIT 1");
} catch (Exception $e) {
    $heroCount = $whyCount = $statsCount = 0;
    $directorSection = null;
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Homepage Content Manager</h1>
        <p class="text-sm text-slate-500 mt-1">Manage slides, welcome message, reasons to choose St. Monica, and statistics.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= public_url('index.html') ?>" target="_blank" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">open_in_new</span>
            <span>Preview Homepage</span>
        </a>
    </div>
</div>

<!-- Homepage Sections Overview Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- 1. Hero Carousel Manager -->
    <div class="cms-card p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[28px]">view_carousel</span>
                </div>
                <span class="cms-badge badge-active"><?= $heroCount ?> Slides</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-2">Hero Slider</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                Add, edit, reorder, and activate the full-screen background slides shown at the top of the homepage.
            </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Carousel images & buttons</span>
            <a href="<?= admin_url('homepage/hero.php') ?>" class="cms-btn cms-btn-accent cms-btn-sm">
                Manage Slides &rarr;
            </a>
        </div>
    </div>

    <!-- 2. Director's Message / Welcome -->
    <div class="cms-card p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#1e2a4a] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[28px]">campaign</span>
                </div>
                <span class="cms-badge badge-published">Active Message</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-2">Director's Welcome Message</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                Update the official welcome message from Rev. Fr. Dr Denis Mpanga, including the photo, title, and speech.
            </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Holistic & UNEB introduction</span>
            <a href="<?= admin_url('homepage/welcome.php') ?>" class="cms-btn cms-btn-primary cms-btn-sm">
                Edit Message &rarr;
            </a>
        </div>
    </div>

    <!-- 3. Why Choose Us Items -->
    <div class="cms-card p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[28px]">verified_user</span>
                </div>
                <span class="cms-badge badge-active"><?= $whyCount ?> Highlights</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-2">Why Choose St. Monica</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                Manage key school highlights such as Children's Safety, Healthy Meals, Learning & Fun, and Campus Environment.
            </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Pillar benefits & icons</span>
            <a href="<?= admin_url('homepage/why-choose-us.php') ?>" class="cms-btn cms-btn-outline cms-btn-sm">
                Edit Highlights &rarr;
            </a>
        </div>
    </div>

    <!-- 4. Statistics / Counters -->
    <div class="cms-card p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[28px]">analytics</span>
                </div>
                <span class="cms-badge badge-active"><?= $statsCount ?> Counters</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-2">School Statistics Counters</h2>
            <p class="text-sm text-slate-600 leading-relaxed">
                Configure the milestone numbers displayed on the parallax counter banner (Years of Excellence, Happy Pupils, Classrooms).
            </p>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Animated counter values</span>
            <a href="<?= admin_url('homepage/statistics.php') ?>" class="cms-btn cms-btn-outline cms-btn-sm">
                Edit Counters &rarr;
            </a>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
