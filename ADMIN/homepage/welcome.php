<?php
/**
 * St. Monica Junior School CMS - Director's Welcome Message Editor
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('homepage');

$pageTitle = "Director's Welcome Message";
$activeMenu = 'homepage';

// Handle Save
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? 'Message from Our Director');
    $authorName = trim($_POST['author_name'] ?? 'Rev. Fr. Dr Denis Mpanga');
    $authorTitle = trim($_POST['author_title'] ?? 'Director');
    $content = trim($_POST['content'] ?? '');

    // Photo chosen from the Media Library (empty = keep current)
    $imagePath = null;
    if (!empty($_POST['image'])) {
        $imagePath = resolve_media_selection($_POST['image']);
        if (!$imagePath) {
            set_flash('danger', 'The selected photo is no longer available in the Media Library. Please choose another.');
            redirect(admin_url('homepage/welcome.php'));
        }
    }

    try {
        $existing = Database::fetchOne("SELECT * FROM `homepage_sections` WHERE `section_key` = 'director_message'");
        $updateData = [
            'title'        => $title,
            'author_name'  => $authorName,
            'author_title' => $authorTitle,
            'content'      => $content,
        ];
        if ($imagePath) {
            $updateData['image'] = $imagePath;
        }

        if ($existing) {
            Database::update('homepage_sections', $updateData, "section_key = 'director_message'");
        } else {
            $updateData['section_key'] = 'director_message';
            if (!$imagePath) $updateData['image'] = 'assets/imgz/Director-copy.jpg';
            Database::insert('homepage_sections', $updateData);
        }

        log_activity('Updated Welcome Message', "Director: {$authorName}");
        set_flash('success', "Director's welcome message updated successfully!");
        redirect(admin_url('homepage/welcome.php'));
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

// Fetch current content
$section = Database::fetchOne("SELECT * FROM `homepage_sections` WHERE `section_key` = 'director_message'");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('homepage/') ?>" class="hover:text-slate-800">Homepage</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Director's Message</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Director's Welcome Message</h1>
    </div>
    <a href="<?= public_url('index.html') ?>#director-section" target="_blank" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">visibility</span>
        <span>View on Public Website</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Area (8 cols) -->
    <div class="lg:col-span-8">
        <div class="cms-card p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">edit_note</span>
                Edit Welcome Message & Details
            </h2>

            <form method="POST" action="<?= admin_url('homepage/welcome.php') ?>" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Section Heading *</label>
                        <input type="text" name="title" required value="<?= e($section['title'] ?? 'Message from Our Director') ?>" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Director's Full Name *</label>
                        <input type="text" name="author_name" required value="<?= e($section['author_name'] ?? 'Rev. Fr. Dr Denis Mpanga') ?>" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Official Title *</label>
                        <input type="text" name="author_title" required value="<?= e($section['author_title'] ?? 'Director') ?>" class="cms-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Director Photo</label>
                        <?= render_media_picker('image', [
                            'current' => $section['image'] ?? '',
                            'shape'   => 'circle',
                            'hint'    => 'Use a high quality portrait photo.',
                        ]) ?>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Welcome Message Content (Paragraphs) *</label>
                        <textarea name="content" rows="10" required class="cms-textarea leading-relaxed text-sm"><?= e($section['content'] ?? '') ?></textarea>
                        <p class="text-xs text-slate-400 mt-1">Separate paragraphs with double enter (blank line). They will format automatically on the website.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" class="cms-btn cms-btn-accent">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span>Update Welcome Message</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Snapshot (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        <div class="cms-card p-6">
            <h3 class="text-sm font-bold text-slate-900 brand-font uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-600">preview</span>
                Current Live Display
            </h3>
            
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm mb-4">
                <img src="<?= public_url($section['image'] ?? 'assets/imgz/Director-copy.jpg') ?>" alt="Director" class="w-full h-64 object-cover">
                <div class="p-4 bg-[#1e2a4a] text-white">
                    <p class="font-bold text-base leading-tight"><?= e($section['author_name'] ?? 'Rev. Fr. Dr Denis Mpanga') ?></p>
                    <p class="text-xs text-slate-300"><?= e($section['author_title'] ?? 'Director') ?></p>
                </div>
            </div>

            <div class="text-xs text-slate-600 space-y-2">
                <p class="font-semibold text-slate-900"><?= e($section['title'] ?? 'Message from Our Director') ?></p>
                <p class="line-clamp-4 leading-relaxed text-slate-500">
                    <?= e(substr($section['content'] ?? '', 0, 180)) ?>...
                </p>
            </div>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
