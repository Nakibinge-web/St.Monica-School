<?php
/**
 * St. Monica Junior School CMS - Core Values Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('about');

$pageTitle = 'Manage Core Values';
$activeMenu = 'about';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$editVal = null;

// Handle Delete
if ($action === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $val = Database::fetchOne("SELECT * FROM `core_values` WHERE `id` = :id", ['id' => $editId]);
    if ($val) {
        Database::delete('core_values', 'id = :id', ['id' => $editId]);
        log_activity('Deleted Core Value', "Value: {$val['title']}");
        set_flash('success', 'Core value deleted.');
    }
    redirect(admin_url('about/core-values.php'));
}

// Handle Save
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($action, ['create', 'update'])) {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title)) {
        set_flash('danger', 'Core value title is required.');
    } else {
        if ($action === 'create') {
            Database::insert('core_values', [
                'title'         => $title,
                'display_order' => $displayOrder,
                'status'        => $status
            ]);
            log_activity('Added Core Value', "Value: {$title}");
            set_flash('success', 'New core value added.');
        } elseif ($action === 'update') {
            Database::update('core_values', [
                'title'         => $title,
                'display_order' => $displayOrder,
                'status'        => $status
            ], 'id = :id', ['id' => $editId]);
            log_activity('Updated Core Value', "ID: {$editId}");
            set_flash('success', 'Core value updated.');
        }
        redirect(admin_url('about/core-values.php'));
    }
}

if ($action === 'edit' && $editId > 0) {
    $editVal = Database::fetchOne("SELECT * FROM `core_values` WHERE `id` = :id", ['id' => $editId]);
}

$values = Database::fetchAll("SELECT * FROM `core_values` ORDER BY `display_order` ASC, `id` ASC");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('about/') ?>" class="hover:text-slate-800">About Us</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Core Values</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">School Core Values</h1>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= admin_url('about/core-values.php?action=create') ?>" class="cms-btn cms-btn-accent">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Core Value</span>
        </a>
    <?php else: ?>
        <a href="<?= admin_url('about/core-values.php') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Values</span>
        </a>
    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="flex items-center gap-2 border-b border-slate-200 mb-8 pb-3">
    <a href="<?= admin_url('about/') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        History, Vision & Motto
    </a>
    <a href="<?= admin_url('about/core-values.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#1e2a4a] text-white">
        Core Values
    </a>
    <a href="<?= admin_url('about/facilities.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        School Facilities
    </a>
</div>

<?php if (in_array($action, ['create', 'edit'])): ?>
    <div class="cms-card p-6 sm:p-8 max-w-lg mx-auto mb-8">
        <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3">
            <?= $action === 'edit' ? 'Edit Core Value' : 'Add Core Value' ?>
        </h2>
        <form method="POST" action="<?= admin_url('about/core-values.php?action=' . ($action === 'edit' ? 'update&id=' . $editId : 'create')) ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Value Title *</label>
                <input type="text" name="title" required value="<?= e($editVal['title'] ?? '') ?>" placeholder="e.g. Fearing God, Integrity" class="cms-input">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Display Order</label>
                    <input type="number" name="display_order" value="<?= e($editVal['display_order'] ?? 1) ?>" min="1" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Status</label>
                    <select name="status" class="cms-select">
                        <option value="active" <?= ($editVal['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editVal['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="<?= admin_url('about/core-values.php') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                <button type="submit" class="cms-btn cms-btn-accent">Save Value</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Core Values List -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Active Values (<?= count($values) ?>)</h2>
        <span class="text-xs text-slate-400">Rendered on About page foundation cards</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th>Core Value Principle</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($values)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-8 text-slate-400">No core values found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($values as $val): ?>
                        <tr>
                            <td class="font-bold text-slate-700">#<?= e($val['display_order']) ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600 text-[20px]">check_circle</span>
                                    <span class="font-bold text-slate-900 text-sm"><?= e($val['title']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="cms-badge <?= $val['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= ucfirst($val['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= admin_url('about/core-values.php?action=edit&id=' . $val['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('about/core-values.php?action=delete&id=' . $val['id']) ?>" data-name="<?= e($val['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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
