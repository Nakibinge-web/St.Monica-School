<?php
/**
 * St. Monica Junior School CMS - Create Announcement
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('announcements');

$pageTitle = 'Add Announcement';
$activeMenu = 'announcements';

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
            $newId = Database::insert('announcements', [
                'title'         => $title,
                'message'       => $message,
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'display_order' => $order,
                'status'        => $status
            ]);

            log_activity('Created Announcement', "Added announcement: {$title}", 'announcements', $newId);
            set_flash('success', "Announcement '{$title}' created successfully.");
            redirect(admin_url('announcements/'));
        } catch (Exception $e) {
            set_flash('danger', 'Failed to save announcement.');
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('announcements/') ?>" class="hover:text-slate-800">Announcements</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">New Announcement</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Add Announcement</h1>
    </div>
    <a href="<?= admin_url('announcements/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('announcements/create.php') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">Title <span class="text-red-600">*</span></label>
            <input type="text" id="title" name="title" placeholder="e.g. School Closure Notice" class="cms-input" required maxlength="200">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="message">Message <span class="text-red-600">*</span></label>
            <textarea id="message" name="message" rows="3" placeholder="Short, urgent message shown to website visitors..." class="cms-textarea" required></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="startDate">Start Date</label>
                <input type="date" id="startDate" name="start_date" class="cms-input">
                <span class="text-[11px] text-slate-400 mt-1 block">Leave blank to start immediately.</span>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="endDate">End Date</label>
                <input type="date" id="endDate" name="end_date" class="cms-input">
                <span class="text-[11px] text-slate-400 mt-1 block">Leave blank for no end date.</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="displayOrder">Display Priority Order</label>
                <input type="number" id="displayOrder" name="display_order" value="0" min="0" class="cms-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">Status</label>
                <select id="status" name="status" class="cms-select font-semibold">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('announcements/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">check</span>
                <span>Save Announcement</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
