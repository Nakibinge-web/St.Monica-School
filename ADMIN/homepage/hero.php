<?php
/**
 * St. Monica Junior School CMS - Hero Slides Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('homepage');

$pageTitle = 'Manage Hero Slides';
$activeMenu = 'homepage';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$editSlide = null;

// Handle Delete Request
if ($action === 'delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $slide = Database::fetchOne("SELECT * FROM `hero_slides` WHERE `id` = :id", ['id' => $editId]);
    if ($slide) {
        Database::delete('hero_slides', 'id = :id', ['id' => $editId]);
        log_activity('Deleted Hero Slide', 'Slide Title: ' . $slide['title']);
        set_flash('success', 'Hero slide deleted successfully.');
    } else {
        set_flash('danger', 'Hero slide not found.');
    }
    redirect(admin_url('homepage/hero.php'));
}

// Handle Toggle Status Request
if ($action === 'toggle' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $slide = Database::fetchOne("SELECT * FROM `hero_slides` WHERE `id` = :id", ['id' => $editId]);
    if ($slide) {
        $newStatus = ($slide['status'] === 'active') ? 'inactive' : 'active';
        Database::update('hero_slides', ['status' => $newStatus], 'id = :id', ['id' => $editId]);
        log_activity('Toggled Hero Slide Status', "Slide: {$slide['title']} -> {$newStatus}");
        set_flash('success', "Slide status changed to {$newStatus}.");
    }
    redirect(admin_url('homepage/hero.php'));
}

// Handle Save (Create or Update)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($action, ['create', 'update'])) {
    require_csrf();
    
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $buttonText = trim($_POST['button_text'] ?? 'Know More');
    $buttonUrl = trim($_POST['button_url'] ?? 'about.html');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title)) {
        set_flash('danger', 'Slide title is required.');
    } else {
        $imagePath = null;

        // Image chosen from the Media Library (empty = keep current)
        if (!empty($_POST['image'])) {
            $imagePath = resolve_media_selection($_POST['image']);
            if (!$imagePath) {
                set_flash('danger', 'The selected image is no longer available in the Media Library. Please choose another.');
                redirect($action === 'update' ? admin_url("homepage/hero.php?action=edit&id={$editId}") : admin_url("homepage/hero.php?action=create"));
            }
        }

        if ($action === 'create') {
            if (!$imagePath) {
                // If no new image, default fallback or require image
                $imagePath = 'assets/imgz/hero1-light.webp';
            }
            Database::insert('hero_slides', [
                'title'         => $title,
                'subtitle'      => $subtitle,
                'description'   => $description,
                'image'         => $imagePath,
                'button_text'   => $buttonText,
                'button_url'    => $buttonUrl,
                'display_order' => $displayOrder,
                'status'        => $status
            ]);
            log_activity('Created Hero Slide', "Title: {$title}");
            set_flash('success', 'Hero slide created successfully.');
            redirect(admin_url('homepage/hero.php'));
        } elseif ($action === 'update') {
            $existing = Database::fetchOne("SELECT * FROM `hero_slides` WHERE `id` = :id", ['id' => $editId]);
            if ($existing) {
                $dataToUpdate = [
                    'title'         => $title,
                    'subtitle'      => $subtitle,
                    'description'   => $description,
                    'button_text'   => $buttonText,
                    'button_url'    => $buttonUrl,
                    'display_order' => $displayOrder,
                    'status'        => $status
                ];
                if ($imagePath) {
                    $dataToUpdate['image'] = $imagePath;
                }
                Database::update('hero_slides', $dataToUpdate, 'id = :id', ['id' => $editId]);
                log_activity('Updated Hero Slide', "Slide ID: {$editId}");
                set_flash('success', 'Hero slide updated successfully.');
                redirect(admin_url('homepage/hero.php'));
            } else {
                set_flash('danger', 'Hero slide not found.');
            }
        }
    }
}

// Fetch slide for editing
if ($action === 'edit' && $editId > 0) {
    $editSlide = Database::fetchOne("SELECT * FROM `hero_slides` WHERE `id` = :id", ['id' => $editId]);
    if (!$editSlide) {
        set_flash('danger', 'Slide not found.');
        redirect(admin_url('homepage/hero.php'));
    }
}

// Fetch all slides
$slides = Database::fetchAll("SELECT * FROM `hero_slides` ORDER BY `display_order` ASC, `id` ASC");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('homepage/') ?>" class="hover:text-slate-800">Homepage</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Hero Slider</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Hero Carousel Slides</h1>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="<?= admin_url('homepage/hero.php?action=create') ?>" class="cms-btn cms-btn-accent">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span>Add Slide</span>
        </a>
    <?php else: ?>
        <a href="<?= admin_url('homepage/hero.php') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Slides List</span>
        </a>
    <?php endif; ?>
</div>

<?php if (in_array($action, ['create', 'edit'])): ?>
    <!-- Slide Create / Edit Form -->
    <div class="cms-card p-6 sm:p-8 max-w-3xl mx-auto mb-8">
        <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-red-600"><?= $action === 'edit' ? 'edit' : 'add_photo_alternate' ?></span>
            <?= $action === 'edit' ? 'Edit Hero Slide' : 'Add New Hero Slide' ?>
        </h2>

        <form method="POST" action="<?= admin_url('homepage/hero.php?action=' . ($action === 'edit' ? 'update&id=' . $editId : 'create')) ?>" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Slide Title *</label>
                    <input type="text" name="title" required value="<?= e($editSlide['title'] ?? '') ?>" placeholder="e.g. Welcome to St.Monica Junior School"
                           class="cms-input">
                </div>

                <!-- Subtitle -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Subtitle / Topic</label>
                    <input type="text" name="subtitle" value="<?= e($editSlide['subtitle'] ?? '') ?>" placeholder="e.g. Quality Education"
                           class="cms-input">
                </div>

                <!-- Display Order -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Display Order</label>
                    <input type="number" name="display_order" value="<?= e($editSlide['display_order'] ?? 1) ?>" min="0"
                           class="cms-input">
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Description / Excerpt</label>
                    <textarea name="description" rows="3" placeholder="Brief welcome or introductory sentence..." class="cms-textarea"><?= e($editSlide['description'] ?? '') ?></textarea>
                </div>

                <!-- Button Text -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Call To Action Button Text</label>
                    <input type="text" name="button_text" value="<?= e($editSlide['button_text'] ?? 'Know More') ?>" class="cms-input">
                </div>

                <!-- Button URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Button Link (URL / Page)</label>
                    <input type="text" name="button_url" value="<?= e($editSlide['button_url'] ?? 'about.html') ?>" class="cms-input">
                </div>

                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="status" value="active" <?= ($editSlide['status'] ?? 'active') === 'active' ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>Active (Visible on public site)</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="radio" name="status" value="inactive" <?= ($editSlide['status'] ?? '') === 'inactive' ? 'checked' : '' ?> class="text-red-600 focus:ring-red-500">
                            <span>Inactive (Hidden)</span>
                        </label>
                    </div>
                </div>

                <!-- Slide Image (from Media Library) -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Slide Background Image</label>
                    <?= render_media_picker('image', [
                        'current' => $editSlide['image'] ?? '',
                        'hint'    => 'Recommended: 1920x1080px landscape photo.',
                    ]) ?>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="<?= admin_url('homepage/hero.php') ?>" class="cms-btn cms-btn-outline">Cancel</a>
                <button type="submit" class="cms-btn cms-btn-accent">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span><?= $action === 'edit' ? 'Save Changes' : 'Publish Slide' ?></span>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Slides Table Listing -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Current Slides (<?= count($slides) ?>)</h2>
        <span class="text-xs text-slate-400">Ordered by display order</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Order</th>
                    <th style="width: 110px;">Image</th>
                    <th>Slide Content</th>
                    <th>Button</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slides)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-slate-400">
                            No hero slides found. Click "Add Slide" to create the first slide.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td class="font-bold text-slate-700">
                                #<?= e($slide['display_order']) ?>
                            </td>
                            <td>
                                <div class="w-20 h-12 rounded overflow-hidden bg-slate-100 border border-slate-200">
                                    <img src="<?= public_url($slide['image']) ?>" alt="Thumbnail" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900"><?= e($slide['title']) ?></div>
                                <?php if ($slide['subtitle']): ?>
                                    <div class="text-xs text-red-600 font-semibold"><?= e($slide['subtitle']) ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-slate-500 max-w-md truncate mt-0.5"><?= e($slide['description']) ?></div>
                            </td>
                            <td>
                                <span class="text-xs font-semibold text-slate-700 block"><?= e($slide['button_text']) ?></span>
                                <span class="text-[11px] text-slate-400 block"><?= e($slide['button_url']) ?></span>
                            </td>
                            <td>
                                <form method="POST" action="<?= admin_url('homepage/hero.php?action=toggle&id=' . $slide['id']) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="cms-badge <?= $slide['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?> hover:opacity-80 transition cursor-pointer" title="Click to toggle status">
                                        <?= ucfirst($slide['status']) ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="<?= admin_url('homepage/hero.php?action=edit&id=' . $slide['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit Slide">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('homepage/hero.php?action=delete&id=' . $slide['id']) ?>" data-name="<?= e($slide['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete Slide">
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
