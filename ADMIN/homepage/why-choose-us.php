<?php
/**
 * St. Monica Junior School CMS - Why Choose Us Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('homepage');

$pageTitle = 'Why Choose St. Monica Highlights';
$activeMenu = 'homepage';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$editItem = null;

// Handle Delete Item
if ($action === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $item = Database::fetchOne("SELECT * FROM `why_choose_us_items` WHERE `id` = :id", ['id' => $editId]);
    if ($item) {
        Database::delete('why_choose_us_items', 'id = :id', ['id' => $editId]);
        log_activity('Deleted Why Choose Us Item', "Title: {$item['title']}");
        set_flash('success', 'Highlight deleted successfully.');
    }
    redirect(admin_url('homepage/why-choose-us.php'));
}

// Handle Intro Save
if ($action === 'update_intro' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $title = trim($_POST['title'] ?? 'Why Choose St Monica?');
    $content = trim($_POST['content'] ?? '');

    Database::update('homepage_sections', [
        'title'   => $title,
        'content' => $content
    ], "section_key = 'why_choose_intro'");

    log_activity('Updated Why Choose Us Intro');
    set_flash('success', 'Section header updated successfully.');
    redirect(admin_url('homepage/why-choose-us.php'));
}

// Handle Item Save (Create or Update)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($action, ['create', 'update'])) {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-solid fa-star');
    $colorTheme = in_array($_POST['color_theme'] ?? '', ['navy', 'red']) ? $_POST['color_theme'] : 'navy';
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title) || empty($description)) {
        set_flash('danger', 'Title and description are required.');
    } else {
        if ($action === 'create') {
            Database::insert('why_choose_us_items', [
                'title'         => $title,
                'description'   => $description,
                'icon'          => $icon,
                'color_theme'   => $colorTheme,
                'display_order' => $displayOrder,
                'status'        => $status
            ]);
            log_activity('Added Why Choose Us Item', "Title: {$title}");
            set_flash('success', 'New highlight added.');
        } elseif ($action === 'update') {
            Database::update('why_choose_us_items', [
                'title'         => $title,
                'description'   => $description,
                'icon'          => $icon,
                'color_theme'   => $colorTheme,
                'display_order' => $displayOrder,
                'status'        => $status
            ], 'id = :id', ['id' => $editId]);
            log_activity('Updated Why Choose Us Item', "ID: {$editId}");
            set_flash('success', 'Highlight updated successfully.');
        }
        redirect(admin_url('homepage/why-choose-us.php'));
    }
}

if ($action === 'edit' && $editId > 0) {
    $editItem = Database::fetchOne("SELECT * FROM `why_choose_us_items` WHERE `id` = :id", ['id' => $editId]);
}

$items = Database::fetchAll("SELECT * FROM `why_choose_us_items` ORDER BY `display_order` ASC, `id` ASC");
$intro = Database::fetchOne("SELECT * FROM `homepage_sections` WHERE `section_key` = 'why_choose_intro'");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('homepage/') ?>" class="hover:text-slate-800">Homepage</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Why Choose Us</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Why Choose St. Monica Section</h1>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= admin_url('homepage/why-choose-us.php?action=create') ?>" class="cms-btn cms-btn-accent">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Highlight</span>
        </a>
    <?php else: ?>
        <a href="<?= admin_url('homepage/why-choose-us.php') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to List</span>
        </a>
    <?php endif; ?>
</div>

<!-- Section Intro Header Editor -->
<div class="cms-card p-6 mb-8">
    <h2 class="text-base font-bold text-slate-900 brand-font mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-slate-600">title</span>
        Section Header & Subtitle
    </h2>
    <form method="POST" action="<?= admin_url('homepage/why-choose-us.php?action=update_intro') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
        <?= csrf_field() ?>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Section Title</label>
            <input type="text" name="title" required value="<?= e($intro['title'] ?? 'Why Choose St Monica?') ?>" class="cms-input">
        </div>
        <div class="sm:col-span-6">
            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Intro Description</label>
            <input type="text" name="content" required value="<?= e($intro['content'] ?? '') ?>" class="cms-input">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="cms-btn cms-btn-primary w-full">Save Header</button>
        </div>
    </form>
</div>

<?php if (in_array($action, ['create', 'edit'])): ?>
    <!-- Add / Edit Item Form -->
    <div class="cms-card p-6 sm:p-8 max-w-2xl mx-auto mb-8">
        <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3">
            <?= $action === 'edit' ? 'Edit Highlight' : 'Add New Highlight' ?>
        </h2>
        <form method="POST" action="<?= admin_url('homepage/why-choose-us.php?action=' . ($action === 'edit' ? 'update&id=' . $editId : 'create')) ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Title *</label>
                <input type="text" name="title" required value="<?= e($editItem['title'] ?? '') ?>" placeholder="e.g. Children's Safety" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Description *</label>
                <textarea name="description" rows="3" required placeholder="Short explanation of this benefit..." class="cms-textarea"><?= e($editItem['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Font Awesome Icon</label>
                    <input type="text" name="icon" required value="<?= e($editItem['icon'] ?? 'fa-solid fa-children') ?>" placeholder="e.g. fa-solid fa-children" class="cms-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Color Accent</label>
                    <select name="color_theme" class="cms-select">
                        <option value="navy" <?= ($editItem['color_theme'] ?? 'navy') === 'navy' ? 'selected' : '' ?>>Navy Blue (#1e2a4a)</option>
                        <option value="red" <?= ($editItem['color_theme'] ?? '') === 'red' ? 'selected' : '' ?>>Red (#d93633)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-2">Display Order</label>
                    <input type="number" name="display_order" value="<?= e($editItem['display_order'] ?? 1) ?>" class="cms-input">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="<?= admin_url('homepage/why-choose-us.php') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                <button type="submit" class="cms-btn cms-btn-accent">Save Highlight</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Highlights Table Listing -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Highlight Items (<?= count($items) ?>)</h2>
        <span class="text-xs text-slate-400">Displayed on Why Choose Us section</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th style="width: 80px;">Icon</th>
                    <th>Highlight Title</th>
                    <th>Description</th>
                    <th>Color</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-400">No highlight items found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="font-bold text-slate-700">#<?= e($item['display_order']) ?></td>
                            <td>
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white <?= $item['color_theme'] === 'red' ? 'bg-[#d93633]' : 'bg-[#1e2a4a]' ?>">
                                    <i class="<?= e($item['icon']) ?> text-[18px]"></i>
                                </div>
                            </td>
                            <td class="font-bold text-slate-900"><?= e($item['title']) ?></td>
                            <td class="text-xs text-slate-600 max-w-md"><?= e($item['description']) ?></td>
                            <td>
                                <span class="cms-badge <?= $item['color_theme'] === 'red' ? 'badge-event' : 'badge-news' ?>">
                                    <?= ucfirst($item['color_theme']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= admin_url('homepage/why-choose-us.php?action=edit&id=' . $item['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('homepage/why-choose-us.php?action=delete&id=' . $item['id']) ?>" data-name="<?= e($item['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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
