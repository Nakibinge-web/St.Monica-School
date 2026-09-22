<?php
/**
 * St. Monica Junior School CMS - Testimonials Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('testimonials');

$pageTitle = 'Testimonials Management';
$activeMenu = 'testimonials';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`name` LIKE :s OR `content` LIKE :s OR `child_info` LIKE :s)";
    $params['s'] = "%{$search}%";
}
if (in_array($statusFilter, ['published', 'draft'])) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$testimonials = [];

try {
    $totalRows = (int)Database::fetchColumn("SELECT COUNT(*) FROM `testimonials` WHERE {$whereSql}", $params);
    $testimonials = Database::fetchAll("SELECT * FROM `testimonials` WHERE {$whereSql} ORDER BY `display_order` ASC, `id` DESC LIMIT {$perPage} OFFSET {$offset}", $params);
} catch (Exception $e) {
    // Error handling
}

$totalPages = ceil($totalRows / $perPage);

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Parent & Community Testimonials</h1>
        <p class="text-sm text-slate-500 mt-1">Manage reviews, quotes, and parent testimonials displayed on the homepage.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('testimonials/create.php') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">add_comment</span>
            <span>Add Testimonial</span>
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('testimonials/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-8 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by parent name or quote text..."
                   class="cms-input pl-10 text-sm">
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
            <?php if ($search || $statusFilter): ?>
                <a href="<?= admin_url('testimonials/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Testimonials Table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Testimonials List (<?= $totalRows ?>)</h2>
        <span class="text-xs text-slate-400">Ordered by display sequence</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th style="width: 70px;">Avatar</th>
                    <th>Author & Role</th>
                    <th>Quote / Testimonial</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($testimonials)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">format_quote</span>
                            No testimonials found. Click "Add Testimonial" to register a parent review.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $t): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="font-bold text-slate-700">#<?= (int)$t['display_order'] ?></td>
                            <td>
                                <?php if (!empty($t['photo'])): ?>
                                    <img src="<?= public_url($t['photo']) ?>" alt="<?= e($t['name']) ?>" class="w-10 h-10 rounded-full object-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-[#1e2a4a] text-white flex items-center justify-center font-bold text-xs">
                                        <?= e($t['initials'] ?: substr($t['name'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm"><?= e($t['name']) ?></div>
                                <div class="text-xs text-slate-500"><?= e($t['child_info'] ?: $t['role']) ?></div>
                            </td>
                            <td class="max-w-md">
                                <p class="text-xs text-slate-600 line-clamp-2 italic">
                                    "<?= e($t['content']) ?>"
                                </p>
                            </td>
                            <td>
                                <div class="flex items-center text-amber-500 text-xs">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <span class="material-symbols-outlined text-[16px] <?= $s <= $t['rating'] ? 'text-amber-500' : 'text-slate-300' ?>" style="font-variation-settings: 'FILL' 1;">star</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <span class="cms-badge <?= $t['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                                    <?= ucfirst($t['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= admin_url('testimonials/edit.php?id=' . $t['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('testimonials/delete.php?id=' . $t['id']) ?>" data-name="Testimonial by <?= e($t['name']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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

    <!-- Pagination -->
    <?= render_pagination($page, $totalPages, admin_url('testimonials/'), array_filter(['search' => $search, 'status' => $statusFilter])) ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
