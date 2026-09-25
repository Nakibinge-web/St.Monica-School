<?php
/**
 * St. Monica Junior School CMS - Trash & Recovery
 * Lists soft-deleted content across recoverable modules. Super Admin only.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'Trash & Recovery';
$activeMenu = 'trash';

// Whitelisted recoverable tables: table => [label, title column, icon]
$recoverableTables = [
    'news_events'  => ['label' => 'News & Events', 'title_col' => 'title', 'icon' => 'article'],
    'staff'        => ['label' => 'Staff', 'title_col' => 'name', 'icon' => 'badge'],
    'gallery'      => ['label' => 'Gallery', 'title_col' => 'title', 'icon' => 'photo_library'],
    'testimonials' => ['label' => 'Testimonials', 'title_col' => 'name', 'icon' => 'format_quote'],
];

$trashedItems = [];
foreach ($recoverableTables as $table => $meta) {
    try {
        $rows = Database::fetchAll("SELECT `id`, `{$meta['title_col']}` AS title, `deleted_at` FROM `{$table}` WHERE `deleted_at` IS NOT NULL ORDER BY `deleted_at` DESC");
        foreach ($rows as $row) {
            $trashedItems[] = [
                'table' => $table,
                'label' => $meta['label'],
                'icon'  => $meta['icon'],
                'id'    => $row['id'],
                'title' => $row['title'],
                'deleted_at' => $row['deleted_at']
            ];
        }
    } catch (Exception $e) {
        // Table may not be migrated yet
    }
}

usort($trashedItems, fn($a, $b) => strtotime($b['deleted_at']) <=> strtotime($a['deleted_at']));

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Trash & Recovery</h1>
        <p class="text-sm text-slate-500 mt-1">Deleted News & Events, Staff, Gallery, and Testimonials are kept here until permanently removed.</p>
    </div>
</div>

<div class="cms-card overflow-hidden">
    <?php if (empty($trashedItems)): ?>
        <div class="text-center py-16 text-slate-400">
            <span class="material-symbols-outlined text-5xl text-slate-300 mb-3 block">delete</span>
            Trash is empty.
        </div>
    <?php else: ?>
        <table class="cms-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Deleted On</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trashedItems as $item): ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-slate-400"><?= $item['icon'] ?></span>
                                <span class="font-semibold text-slate-900 text-sm"><?= e($item['title']) ?></span>
                            </div>
                        </td>
                        <td><span class="cms-badge bg-slate-100 text-slate-700"><?= e($item['label']) ?></span></td>
                        <td class="text-xs text-slate-500"><?= date('M j, Y g:i A', strtotime($item['deleted_at'])) ?></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="<?= admin_url('trash/restore.php') ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="table" value="<?= e($item['table']) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="cms-btn cms-btn-outline text-xs">
                                        <span class="material-symbols-outlined text-[16px]">restore</span>
                                        <span>Restore</span>
                                    </button>
                                </form>
                                <button type="button" data-delete-btn
                                        data-action="<?= admin_url('trash/delete-permanent.php?table=' . urlencode($item['table']) . '&id=' . (int)$item['id']) ?>"
                                        data-name="<?= e($item['title']) ?> (permanently)"
                                        class="cms-btn cms-btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">
                                    <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                                    <span>Delete Forever</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
