<?php
/**
 * St. Monica Junior School CMS - Statistics / Counters Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('homepage');

$pageTitle = 'Homepage Statistics & Counters';
$activeMenu = 'homepage';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$editStat = null;

// Handle Delete
if ($action === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $stat = Database::fetchOne("SELECT * FROM `statistics` WHERE `id` = :id", ['id' => $editId]);
    if ($stat) {
        Database::delete('statistics', 'id = :id', ['id' => $editId]);
        log_activity('Deleted Statistic Counter', "Label: {$stat['label']}");
        set_flash('success', 'Counter deleted successfully.');
    }
    redirect(admin_url('homepage/statistics.php'));
}

// Handle Save
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($action, ['create', 'update'])) {
    require_csrf();

    $numberValue = (int)($_POST['number_value'] ?? 0);
    $suffix = trim($_POST['suffix'] ?? '+');
    $label = trim($_POST['label'] ?? '');
    $icon = trim($_POST['icon'] ?? 'verified');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($label)) {
        set_flash('danger', 'Counter label is required.');
    } else {
        if ($action === 'create') {
            Database::insert('statistics', [
                'number_value'  => $numberValue,
                'suffix'        => $suffix,
                'label'         => $label,
                'icon'          => $icon,
                'display_order' => $displayOrder,
                'status'        => $status
            ]);
            log_activity('Added Statistic Counter', "Label: {$label}");
            set_flash('success', 'Counter added successfully.');
        } elseif ($action === 'update') {
            Database::update('statistics', [
                'number_value'  => $numberValue,
                'suffix'        => $suffix,
                'label'         => $label,
                'icon'          => $icon,
                'display_order' => $displayOrder,
                'status'        => $status
            ], 'id = :id', ['id' => $editId]);
            log_activity('Updated Statistic Counter', "ID: {$editId}");
            set_flash('success', 'Counter updated successfully.');
        }
        redirect(admin_url('homepage/statistics.php'));
    }
}

if ($action === 'edit' && $editId > 0) {
    $editStat = Database::fetchOne("SELECT * FROM `statistics` WHERE `id` = :id", ['id' => $editId]);
}

$stats = Database::fetchAll("SELECT * FROM `statistics` ORDER BY `display_order` ASC, `id` ASC");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('homepage/') ?>" class="hover:text-slate-800">Homepage</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Statistics</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Milestone Statistics & Counters</h1>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= admin_url('homepage/statistics.php?action=create') ?>" class="cms-btn cms-btn-accent">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Counter</span>
        </a>
    <?php else: ?>
        <a href="<?= admin_url('homepage/statistics.php') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Counters</span>
        </a>
    <?php endif; ?>
</div>

<?php if (in_array($action, ['create', 'edit'])): ?>
    <div class="cms-card p-6 sm:p-8 max-w-xl mx-auto mb-8">
        <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3">
            <?= $action === 'edit' ? 'Edit Counter' : 'Add New Counter' ?>
        </h2>
        <form method="POST" action="<?= admin_url('homepage/statistics.php?action=' . ($action === 'edit' ? 'update&id=' . $editId : 'create')) ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Number Value *</label>
                    <input type="number" name="number_value" required value="<?= e($editStat['number_value'] ?? 10) ?>" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Suffix (e.g. +, %)</label>
                    <input type="text" name="suffix" value="<?= e($editStat['suffix'] ?? '+') ?>" placeholder="+" class="cms-input">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Counter Label *</label>
                <input type="text" name="label" required value="<?= e($editStat['label'] ?? '') ?>" placeholder="e.g. Happy Pupils" class="cms-input">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Material Icon</label>
                    <input type="text" name="icon" required value="<?= e($editStat['icon'] ?? 'school') ?>" placeholder="e.g. school, verified" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Display Order</label>
                    <input type="number" name="display_order" value="<?= e($editStat['display_order'] ?? 1) ?>" class="cms-input">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="<?= admin_url('homepage/statistics.php') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                <button type="submit" class="cms-btn cms-btn-accent">Save Counter</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Counters Cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <?php foreach ($stats as $stat): ?>
        <div class="cms-card p-6 text-center relative flex flex-col justify-between">
            <div>
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-50 text-red-600 mb-4 shadow-sm">
                    <span class="material-symbols-outlined text-[32px]"><?= e($stat['icon']) ?></span>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 brand-font">
                    <?= e($stat['number_value']) ?><?= e($stat['suffix']) ?>
                </h3>
                <p class="text-sm font-semibold text-slate-600 mt-1"><?= e($stat['label']) ?></p>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">Order: #<?= e($stat['display_order']) ?></span>
                <div class="flex items-center gap-1">
                    <a href="<?= admin_url('homepage/statistics.php?action=edit&id=' . $stat['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                    <button type="button" data-delete-btn data-action="<?= admin_url('homepage/statistics.php?action=delete&id=' . $stat['id']) ?>" data-name="<?= e($stat['label']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
