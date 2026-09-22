<?php
/**
 * St. Monica Junior School CMS - Edit Gallery Image Details
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
$image = Database::fetchOne("SELECT * FROM `gallery` WHERE `id` = :id", ['id' => $id]);

if (!$image) {
    set_flash('danger', 'Media item not found.');
    redirect(admin_url('gallery/'));
}

$pageTitle = 'Edit Media: ' . $image['title'];
$activeMenu = 'gallery';
$categories = ['Campus Life', 'Academics', 'Co-curricular Activities', 'Special Events', 'Administration'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'Campus Life';
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    if (empty($title)) {
        set_flash('danger', 'Title / caption is required.');
    } else {
        try {
            Database::update('gallery', [
                'title'         => $title,
                'description'   => $description,
                'category'      => $category,
                'display_order' => $displayOrder,
                'status'        => $status
            ], 'id = :id', ['id' => $id]);

            log_activity('Updated Gallery Media', "Title: {$title} (ID: {$id})");
            set_flash('success', 'Media information updated successfully.');
            redirect(admin_url('gallery/?category=' . urlencode($category)));
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
            <a href="<?= admin_url('gallery/') ?>" class="hover:text-slate-800">Media Gallery</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit Media</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Media Item</h1>
    </div>
    <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to Gallery</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-2xl mx-auto mb-8">
    <div class="mb-6 pb-6 border-b border-slate-100 flex items-center gap-5">
        <img src="<?= public_url($image['file_path']) ?>" alt="<?= e($image['title']) ?>" class="w-32 h-24 object-cover rounded-lg border border-slate-200">
        <div>
            <span class="cms-badge badge-active text-xs"><?= e($image['category']) ?></span>
            <h3 class="font-bold text-slate-900 text-base mt-1"><?= e($image['title']) ?></h3>
            <p class="text-xs text-slate-400 mt-0.5"><?= e($image['file_path']) ?></p>
        </div>
    </div>

    <form method="POST" action="<?= admin_url('gallery/edit.php?id=' . $id) ?>" class="space-y-5">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Category *</label>
            <select name="category" required class="cms-select">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $image['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Title / Caption *</label>
            <input type="text" name="title" required value="<?= e($image['title']) ?>" class="cms-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Description</label>
            <textarea name="description" rows="3" class="cms-textarea"><?= e($image['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Display Order</label>
                <input type="number" name="display_order" value="<?= e($image['display_order']) ?>" min="0" class="cms-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                <select name="status" class="cms-select">
                    <option value="published" <?= $image['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $image['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
