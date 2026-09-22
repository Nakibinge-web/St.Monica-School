<?php
/**
 * St. Monica Junior School CMS - About Us Content Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'About Us Content Management';
$activeMenu = 'about';

// Fetch current About sections
$sections = Database::fetchAll("SELECT * FROM `about_content`");
$aboutData = [];
foreach ($sections as $sec) {
    $aboutData[$sec['section_key']] = $sec;
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">About Us Content</h1>
        <p class="text-sm text-slate-500 mt-1">Manage school history, foundational vision & mission, motto, and core values.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= public_url('about.html') ?>" target="_blank" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">visibility</span>
            <span>View Public About Page</span>
        </a>
    </div>
</div>

<!-- Sub-navigation Tabs -->
<div class="flex items-center gap-2 border-b border-slate-200 mb-8 pb-3">
    <a href="<?= admin_url('about/') ?>" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#1e2a4a] text-white">
        History, Vision & Motto
    </a>
    <a href="<?= admin_url('about/core-values.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        Core Values
    </a>
    <a href="<?= admin_url('about/facilities.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        School Facilities
    </a>
</div>

<form method="POST" action="<?= admin_url('about/update.php') ?>" enctype="multipart/form-data" class="space-y-8">
    <?= csrf_field() ?>

    <!-- 1. School History -->
    <div class="cms-card p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6 pb-3 border-b border-slate-100">
            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">history_edu</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 brand-font">Our History</h2>
                <p class="text-xs text-slate-400">School founding story, milestones, and expansion since 1995.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Section Title</label>
                <input type="text" name="history_title" value="<?= e($aboutData['history']['title'] ?? 'Our History') ?>" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">History Narrative (Paragraphs)</label>
                <textarea name="history_content" rows="6" class="cms-textarea leading-relaxed text-sm"><?= e($aboutData['history']['content'] ?? '') ?></textarea>
                <p class="text-xs text-slate-400 mt-1">Separate paragraphs with double enter (blank lines).</p>
            </div>
        </div>
    </div>

    <!-- 2. Foundation: Vision, Mission & Motto -->
    <div class="cms-card p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6 pb-3 border-b border-slate-100">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-[#1e2a4a] flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">flag</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 brand-font">Our Foundation: Vision, Mission & Motto</h2>
                <p class="text-xs text-slate-400">The guiding philosophy and principles of St. Monica Junior School.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Our Vision</label>
                <textarea name="vision_content" rows="5" class="cms-textarea leading-relaxed text-sm"><?= e($aboutData['vision']['content'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Our Mission</label>
                <textarea name="mission_content" rows="5" class="cms-textarea leading-relaxed text-sm"><?= e($aboutData['mission']['content'] ?? '') ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Our Motto & Explanation</label>
                <textarea name="motto_content" rows="3" class="cms-textarea leading-relaxed text-sm"><?= e($aboutData['motto']['content'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- 3. Support / Donation CTA Banner -->
    <div class="cms-card p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6 pb-3 border-b border-slate-100">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">volunteer_activism</span>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-900 brand-font">Support St. Monica (Donations Banner)</h2>
                <p class="text-xs text-slate-400">Content displayed on the parallax Support St. Monica banner.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Banner Heading</label>
                <input type="text" name="support_title" value="<?= e($aboutData['support_cta']['title'] ?? 'Support St.Monica') ?>" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Call to Action Narrative</label>
                <textarea name="support_content" rows="3" class="cms-textarea leading-relaxed text-sm"><?= e($aboutData['support_cta']['content'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- Save Button Bar -->
    <div class="flex items-center justify-end gap-3 pt-4">
        <button type="submit" class="cms-btn cms-btn-accent text-base px-8 py-3">
            <span class="material-symbols-outlined text-[20px]">save</span>
            <span>Save About Changes</span>
        </button>
    </div>
</form>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
