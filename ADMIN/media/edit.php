<?php
/**
 * St. Monica Junior School CMS - Edit Media Asset
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `media_library` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Media asset not found.');
    redirect(admin_url('media/'));
}

$pageTitle = 'Edit Media: ' . $item['title'];
$activeMenu = 'media';

$categories = ['Campus Life', 'Academics', 'Sports & MDD', 'Special Events', 'Facilities', 'Administration', 'General'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title       = trim($_POST['title'] ?? '');
    $altText     = trim($_POST['alt_text'] ?? '');
    $caption     = trim($_POST['caption'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'General');
    $status      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title)) {
        set_flash('danger', 'Media title is required.');
    } else {
        try {
            Database::update('media_library', [
                'title'       => $title,
                'alt_text'    => $altText,
                'caption'     => $caption,
                'description' => $description,
                'category'    => $category,
                'status'      => $status
            ], 'id = :id', ['id' => $id]);

            log_activity('Updated Media Asset', "Title: {$title} (ID: {$id})", 'media', $id);
            set_flash('success', "Media asset '{$title}' updated successfully.");
            redirect(admin_url('media/'));
        } catch (Exception $e) {
            set_flash('danger', 'Database error: ' . $e->getMessage());
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('media/') ?>" class="hover:text-slate-800">Media Library</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit Asset</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Media: <?= e($item['title']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Library</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Preview column (5 cols) -->
    <div class="lg:col-span-5 space-y-4">
        <div class="cms-card p-4 overflow-hidden">
            <img src="<?= public_url($item['file_path']) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" 
                 class="w-full h-auto rounded-lg object-contain bg-slate-900/5 max-h-80 mx-auto">
        </div>

        <div class="cms-card p-4 text-xs space-y-2">
            <h4 class="font-bold text-slate-900 uppercase tracking-wider mb-2">File Information</h4>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="text-slate-400">Path:</span>
                <span class="font-mono text-slate-700 truncate max-w-[200px]" title="<?= e($item['file_path']) ?>"><?= e($item['file_path']) ?></span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="text-slate-400">Dimensions:</span>
                <span class="font-medium text-slate-800"><?= e($item['dimensions'] ?: 'N/A') ?></span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
                <span class="text-slate-400">Uploaded:</span>
                <span class="font-medium text-slate-800"><?= date('M j, Y g:i A', strtotime($item['created_at'])) ?></span>
            </div>
            <div class="pt-2">
                <button type="button" onclick="navigator.clipboard.writeText('<?= public_url($item['file_path']) ?>'); alert('URL copied to clipboard!');" 
                        class="cms-btn cms-btn-outline w-full text-xs">
                    <span class="material-symbols-outlined text-[16px]">content_copy</span>
                    <span>Copy Web URL</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Metadata Form column (7 cols) -->
    <div class="lg:col-span-7">
        <div class="cms-card p-6 sm:p-8">
            <form method="POST" action="<?= admin_url('media/edit.php?id=' . $item['id']) ?>" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">
                        Media Title <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" 
                           class="cms-input" required>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="category">
                            Category
                        </label>
                        <select id="category" name="category" class="cms-select">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= $item['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">
                            Status
                        </label>
                        <select id="status" name="status" class="cms-select font-semibold">
                            <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="altText">
                        Image Alt Text <span class="text-emerald-700 font-semibold">(Crucial for SEO & Accessibility)</span>
                    </label>
                    <input type="text" id="altText" name="alt_text" value="<?= e($item['alt_text']) ?>" 
                           placeholder="Describe the image content accurately..." class="cms-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="caption">
                        Caption
                    </label>
                    <input type="text" id="caption" name="caption" value="<?= e($item['caption']) ?>" 
                           class="cms-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="description">
                        Description / Administrative Notes
                    </label>
                    <textarea id="description" name="description" rows="3" 
                              class="cms-textarea"><?= e($item['description']) ?></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                    <button type="submit" class="cms-btn cms-btn-primary">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span>Update Metadata</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
