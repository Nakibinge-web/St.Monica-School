<?php
/**
 * St. Monica Junior School CMS - Edit Announcement
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('announcements');

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `announcements` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Announcement not found.');
    redirect(admin_url('announcements/'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $order = (int)($_POST['display_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title) || empty($message)) {
        set_flash('danger', 'Please provide both a title and a message.');
    } else {
        try {
            Database::update('announcements', [
                'title'         => $title,
                'message'       => $message,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'display_order' => $order,
                'status'        => $status
            ], 'id = :id', ['id' => $id]);

            log_activity('Updated Announcement', "Updated announcement: {$title}", 'announcements', $id);
            set_flash('success', 'Announcement updated successfully.');
            redirect(admin_url('announcements/'));
        } catch (Exception $e) {
            set_flash('danger', 'Failed to update announcement.');
        }
    }
}

$pageTitle = 'Edit: ' . $item['title'];
$activeMenu = 'announcements';

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('announcements/') ?>" class="hover:text-slate-800">Announcements</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Announcement</h1>
    </div>
    <a href="<?= admin_url('announcements/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('announcements/edit.php?id=' . $item['id']) ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">Title <span class="text-red-600">*</span></label>
            <input type="text" id="title" name="title" value="<?= e($item['title']) ?>" class="cms-input" required maxlength="200">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="message">Message <span class="text-red-600">*</span></label>
            <textarea id="message" name="message" rows="3" class="cms-textarea" required><?= e($item['message']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="startDate">Start Date</label>
                <input type="date" id="startDate" name="start_date" value="<?= e($item['start_date'] ?? '') ?>" class="cms-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="endDate">End Date</label>
                <input type="date" id="endDate" name="end_date" value="<?= e($item['end_date'] ?? '') ?>" class="cms-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="displayOrder">Display Priority Order</label>
                <input type="number" id="displayOrder" name="display_order" value="<?= (int)$item['display_order'] ?>" min="0" class="cms-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">Status</label>
                <select id="status" name="status" class="cms-select font-semibold">
                    <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('announcements/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
