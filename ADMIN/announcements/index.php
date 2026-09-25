<?php
/**
 * St. Monica Junior School CMS - Announcements Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('announcements');

$pageTitle = 'Announcements';
$activeMenu = 'announcements';

$announcements = [];
try {
    $announcements = Database::fetchAll("SELECT * FROM `announcements` ORDER BY `display_order` ASC, `id` DESC");
} catch (Exception $e) {
    // ignore
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Announcements</h1>
        <p class="text-sm text-slate-500 mt-1">Short, urgent notices shown on the public website (e.g. closures, deadlines). Kept visually distinct from full news articles.</p>
    </div>
    <a href="<?= admin_url('announcements/create.php') ?>" class="cms-btn cms-btn-accent text-xs">
        <span class="material-symbols-outlined text-[16px]">campaign</span>
        <span>Add Announcement</span>
    </a>
</div>

<div class="cms-card overflow-hidden">
    <table class="cms-table">
        <thead>
            <tr>
                <th style="width: 70px;">Order</th>
                <th>Title & Message</th>
                <th>Active Window</th>
                <th>Status</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="5" class="text-center py-12 text-slate-400">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">campaign</span>
                        No announcements yet. Click "Add Announcement" to create one.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($announcements as $a):
                    $today = date('Y-m-d');
                    $isCurrentlyActive = $a['status'] === 'active'
                        && (empty($a['start_date']) || $a['start_date'] <= $today)
                        && (empty($a['end_date']) || $a['end_date'] >= $today);
                ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="font-bold text-slate-700">#<?= (int)$a['display_order'] ?></td>
                        <td>
                            <div class="font-bold text-slate-900 text-sm"><?= e($a['title']) ?></div>
                            <div class="text-xs text-slate-500 max-w-md truncate"><?= e($a['message']) ?></div>
                        </td>
                        <td class="text-xs text-slate-600">
                            <?= !empty($a['start_date']) ? date('M j, Y', strtotime($a['start_date'])) : 'Any time' ?>
                            &rarr;
                            <?= !empty($a['end_date']) ? date('M j, Y', strtotime($a['end_date'])) : 'No end date' ?>
                        </td>
                        <td>
                            <?php if ($isCurrentlyActive): ?>
                                <span class="cms-badge badge-published">Live Now</span>
                            <?php elseif ($a['status'] === 'active'): ?>
                                <span class="cms-badge bg-amber-100 text-amber-800">Scheduled / Expired</span>
                            <?php else: ?>
                                <span class="cms-badge badge-draft">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="<?= admin_url('announcements/edit.php?id=' . $a['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <button type="button" data-delete-btn data-action="<?= admin_url('announcements/delete.php?id=' . $a['id']) ?>" data-name="<?= e($a['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
