<?php
/**
 * St. Monica Junior School CMS - News & Events Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('news-events');

$pageTitle = 'News & Events Management';
$activeMenu = 'news-events';

$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = ['`deleted_at` IS NULL'];
$params = [];

if ($search !== '') {
    $where[] = "(`title` LIKE :search OR `excerpt` LIKE :search OR `event_location` LIKE :search)";
    $params['search'] = "%{$search}%";
}
if (in_array($typeFilter, ['news', 'event', 'sports'])) {
    $where[] = "`type` = :type";
    $params['type'] = $typeFilter;
}
if (in_array($statusFilter, ['published', 'draft'])) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);
$items = Database::fetchAll("SELECT * FROM `news_events` WHERE {$whereSql} ORDER BY `created_at` DESC", $params);

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">News & Events</h1>
        <p class="text-sm text-slate-500 mt-1">Publish news announcements, upcoming school events, and sports updates.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('news-events/create.php?type=event') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">add_alarm</span>
            <span>+ Event</span>
        </a>
        <a href="<?= admin_url('news-events/create.php?type=news') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">post_add</span>
            <span>+ News Post</span>
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('news-events/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-6 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search news or events by title, keyword, location..."
                   class="cms-input pl-10 text-sm">
        </div>

        <div class="sm:col-span-2">
            <select name="type" class="cms-select text-sm">
                <option value="">All Types</option>
                <option value="news" <?= $typeFilter === 'news' ? 'selected' : '' ?>>News</option>
                <option value="event" <?= $typeFilter === 'event' ? 'selected' : '' ?>>Events</option>
                <option value="sports" <?= $typeFilter === 'sports' ? 'selected' : '' ?>>Sports</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <select name="status" class="cms-select text-sm">
                <option value="">All Statuses</option>
                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="cms-btn cms-btn-primary flex-1 text-xs">Filter</button>
            <?php if ($search || $typeFilter || $statusFilter): ?>
                <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Articles & Events (<?= count($items) ?>)</h2>
        <span class="text-xs text-slate-400">Sorted by creation date</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Title & Excerpt</th>
                    <th>Category</th>
                    <th>Event Details</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-slate-400">
                            No news or events found. Click "+ News Post" or "+ Event" to publish one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="w-16 h-12 rounded overflow-hidden bg-slate-100 border border-slate-200">
                                    <img src="<?= public_url($item['featured_image'] ?? 'assets/imgz/3 graduants.webp') ?>" alt="Thumbnail" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm max-w-sm truncate"><?= e($item['title']) ?></div>
                                <div class="text-xs text-slate-400 max-w-sm truncate"><?= e($item['excerpt']) ?></div>
                            </td>
                            <td>
                                <span class="cms-badge badge-<?= e($item['type']) ?>">
                                    <?= e(ucfirst($item['type'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['event_date']): ?>
                                    <div class="text-xs font-semibold text-slate-800">
                                        <?= date('M j, Y', strtotime($item['event_date'])) ?>
                                    </div>
                                    <?php if ($item['event_location']): ?>
                                        <div class="text-[11px] text-slate-400 truncate max-w-[150px]"><?= e($item['event_location']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400"><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="cms-badge <?= $item['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                                <?php if (!empty($item['expires_at']) && strtotime($item['expires_at']) <= time()): ?>
                                    <span class="cms-badge bg-amber-100 text-amber-800 ml-1">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= admin_url('news-events/view.php?id=' . $item['id']) ?>" class="p-1.5 text-slate-500 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="View Preview">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <a href="<?= admin_url('news-events/edit.php?id=' . $item['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('news-events/delete.php?id=' . $item['id']) ?>" data-name="<?= e($item['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
