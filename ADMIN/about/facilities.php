<?php
/**
 * St. Monica Junior School CMS - Facilities Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('about');

$pageTitle = 'Manage School Facilities';
$activeMenu = 'about';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$editFac = null;

// Handle Delete
if ($action === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $fac = Database::fetchOne("SELECT * FROM `facilities` WHERE `id` = :id", ['id' => $editId]);
    if ($fac) {
        Database::delete('facilities', 'id = :id', ['id' => $editId]);
        log_activity('Deleted Facility', "Title: {$fac['title']}");
        set_flash('success', 'Facility deleted.');
    }
    redirect(admin_url('about/facilities.php'));
}

// Handle Save
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($action, ['create', 'update'])) {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title) || empty($description)) {
        set_flash('danger', 'Title and description are required.');
    } else {
        // Photo chosen from the Media Library (empty = keep current)
        $imagePath = null;
        if (!empty($_POST['image'])) {
            $imagePath = resolve_media_selection($_POST['image']);
            if (!$imagePath) {
                set_flash('danger', 'The selected photo is no longer available in the Media Library. Please choose another.');
                redirect(admin_url('about/facilities.php' . ($action === 'update' ? "?action=edit&id={$editId}" : '?action=create')));
            }
        }

        if ($action === 'create') {
            if (!$imagePath) $imagePath = 'assets/imgz/school building.webp';
            Database::insert('facilities', [
                'title'         => $title,
                'description'   => $description,
                'image'         => $imagePath,
                'display_order' => $displayOrder,
                'status'        => $status
            ]);
            log_activity('Added Facility', "Facility: {$title}");
            set_flash('success', 'Facility added successfully.');
        } elseif ($action === 'update') {
            $updateData = [
                'title'         => $title,
                'description'   => $description,
                'display_order' => $displayOrder,
                'status'        => $status
            ];
            if ($imagePath) {
                $updateData['image'] = $imagePath;
            }
            Database::update('facilities', $updateData, 'id = :id', ['id' => $editId]);
            log_activity('Updated Facility', "Facility ID: {$editId}");
            set_flash('success', 'Facility updated successfully.');
        }
        redirect(admin_url('about/facilities.php'));
    }
}

if ($action === 'edit' && $editId > 0) {
    $editFac = Database::fetchOne("SELECT * FROM `facilities` WHERE `id` = :id", ['id' => $editId]);
}

$facilities = Database::fetchAll("SELECT * FROM `facilities` ORDER BY `display_order` ASC, `id` ASC");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('about/') ?>" class="hover:text-slate-800">About Us</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Facilities</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">School Facilities ("What We Provide")</h1>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= admin_url('about/facilities.php?action=create') ?>" class="cms-btn cms-btn-accent">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Facility</span>
        </a>
    <?php else: ?>
        <a href="<?= admin_url('about/facilities.php') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Facilities</span>
        </a>
    <?php endif; ?>
</div>

<!-- Tabs -->
<div class="flex items-center gap-2 border-b border-slate-200 mb-8 pb-3">
    <a href="<?= admin_url('about/') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        History, Vision & Motto
    </a>
    <a href="<?= admin_url('about/core-values.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
        Core Values
    </a>
    <a href="<?= admin_url('about/facilities.php') ?>" class="px-4 py-2 rounded-lg text-xs font-bold bg-[#1e2a4a] text-white">
        School Facilities
    </a>
</div>

<?php if (in_array($action, ['create', 'edit'])): ?>
    <div class="cms-card p-6 sm:p-8 max-w-2xl mx-auto mb-8">
        <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3">
            <?= $action === 'edit' ? 'Edit Facility' : 'Add Facility' ?>
        </h2>
        <form method="POST" action="<?= admin_url('about/facilities.php?action=' . ($action === 'edit' ? 'update&id=' . $editId : 'create')) ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Facility Title *</label>
                <input type="text" name="title" required value="<?= e($editFac['title'] ?? '') ?>" placeholder="e.g. Modern Library" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Description *</label>
                <textarea name="description" rows="3" required class="cms-textarea"><?= e($editFac['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Display Order</label>
                    <input type="number" name="display_order" value="<?= e($editFac['display_order'] ?? 1) ?>" min="1" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Status</label>
                    <select name="status" class="cms-select">
                        <option value="active" <?= ($editFac['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editFac['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Facility Photo</label>
                <?= render_media_picker('image', ['current' => $editFac['image'] ?? '']) ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="<?= admin_url('about/facilities.php') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                <button type="submit" class="cms-btn cms-btn-accent">Save Facility</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Facilities Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    <?php foreach ($facilities as $fac): ?>
        <div class="cms-card overflow-hidden flex flex-col justify-between">
            <div>
                <div class="h-40 overflow-hidden bg-slate-100 border-b border-slate-100">
                    <img src="<?= public_url($fac['image'] ?? 'assets/imgz/school building.webp') ?>" alt="<?= e($fac['title']) ?>" class="w-full h-full object-cover">
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-slate-900 text-base mb-1"><?= e($fac['title']) ?></h3>
                    <p class="text-xs text-slate-500 leading-relaxed"><?= e($fac['description']) ?></p>
                </div>
            </div>
            <div class="p-4 pt-0 flex items-center justify-between text-xs text-slate-400 border-t border-slate-50">
                <span>Order: #<?= e($fac['display_order']) ?></span>
                <div class="flex items-center gap-1">
                    <a href="<?= admin_url('about/facilities.php?action=edit&id=' . $fac['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                    <button type="button" data-delete-btn data-action="<?= admin_url('about/facilities.php?action=delete&id=' . $fac['id']) ?>" data-name="<?= e($fac['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
