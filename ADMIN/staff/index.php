<?php
/**
 * St. Monica Junior School CMS - Staff Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('staff');

$pageTitle = 'Staff Members Management';
$activeMenu = 'staff';

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$deptFilter = $_GET['dept'] ?? '';

// Build prepared query
$where = ['`deleted_at` IS NULL'];
$params = [];

if ($search !== '') {
    $where[] = "(`name` LIKE :s1 OR `position` LIKE :s2 OR `email` LIKE :s3)";
    $params += ['s1' => "%{$search}%", 's2' => "%{$search}%", 's3' => "%{$search}%"];
}
if (in_array($statusFilter, ['published', 'draft'])) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}
if (!empty($deptFilter)) {
    $where[] = "`department` = :dept";
    $params['dept'] = $deptFilter;
}

$whereSql = implode(' AND ', $where);
$staffList = Database::fetchAll("SELECT * FROM `staff` WHERE {$whereSql} ORDER BY `display_order` ASC, `id` ASC", $params);

// Get unique departments for filter dropdown
$departments = Database::fetchAll("SELECT DISTINCT `department` FROM `staff` WHERE `department` IS NOT NULL AND `department` != ''");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Staff Members</h1>
        <p class="text-sm text-slate-500 mt-1">Manage school administrators, teachers, and staff members.</p>
    </div>
    <a href="<?= admin_url('staff/create.php') ?>" class="cms-btn cms-btn-accent">
        <span class="material-symbols-outlined text-[18px]">person_add</span>
        <span>Add Staff Member</span>
    </a>
</div>

<!-- Search & Filters -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('staff/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-5 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, position or email..."
                   class="cms-input pl-10 text-sm">
        </div>

        <div class="sm:col-span-3">
            <select name="status" class="cms-select text-sm">
                <option value="">All Statuses</option>
                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <select name="dept" class="cms-select text-sm">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= e($d['department']) ?>" <?= $deptFilter === $d['department'] ? 'selected' : '' ?>>
                        <?= e($d['department']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="cms-btn cms-btn-primary flex-1 text-xs">Filter</button>
            <?php if ($search || $statusFilter || $deptFilter): ?>
                <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Staff Table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Team Members (<?= count($staffList) ?>)</h2>
        <span class="text-xs text-slate-400">Order by display priority</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th style="width: 80px;">Photo</th>
                    <th>Name & Department</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Homepage</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-slate-400">
                            No staff members match your criteria. Click "Add Staff Member" to register one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staffList as $person): ?>
                        <tr>
                            <td class="font-bold text-slate-700">#<?= e($person['display_order']) ?></td>
                            <td>
                                <div class="w-11 h-11 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shadow-sm">
                                    <img src="<?= public_url($person['photo'] ?? 'assets/imgz/headteacher.webp') ?>" alt="<?= e($person['name']) ?>" class="w-full h-full object-cover object-top">
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900"><?= e($person['name']) ?></div>
                                <div class="text-xs text-slate-500"><?= e($person['department'] ?? 'General Staff') ?> &bull; <?= e($person['email'] ?? 'No email') ?></div>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-700 text-sm"><?= e($person['position']) ?></span>
                            </td>
                            <td>
                                <span class="cms-badge <?= $person['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                                    <?= ucfirst($person['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($person['is_featured']): ?>
                                    <span class="cms-badge badge-sports text-[11px]">Featured</span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= admin_url('staff/view.php?id=' . $person['id']) ?>" class="p-1.5 text-slate-500 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="View Profile">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <a href="<?= admin_url('staff/edit.php?id=' . $person['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('staff/delete.php?id=' . $person['id']) ?>" data-name="<?= e($person['name']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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
