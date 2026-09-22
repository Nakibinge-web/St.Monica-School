<?php
/**
 * St. Monica Junior School CMS - Edit Testimonial
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('testimonials');

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `testimonials` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Testimonial not found.');
    redirect(admin_url('testimonials/'));
}

$pageTitle = 'Edit Testimonial: ' . $item['name'];
$activeMenu = 'testimonials';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $name       = trim($_POST['name'] ?? '');
    $role       = trim($_POST['role'] ?? 'Parent');
    $childInfo  = trim($_POST['child_info'] ?? '');
    $rating     = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $content    = trim($_POST['content'] ?? '');
    $initials   = trim($_POST['initials'] ?? '');
    $order      = (int)($_POST['display_order'] ?? 0);
    $status     = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    if (empty($name) || empty($content)) {
        set_flash('danger', 'Please provide both author name and testimonial quote.');
    } else {
        if (empty($initials)) {
            $parts = explode(' ', $name);
            $initials = count($parts) >= 2 
                ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
                : strtoupper(substr($name, 0, 2));
        }

        $photoPath = $item['photo'];

        // Remove photo if requested
        if (!empty($_POST['remove_photo']) && $item['photo']) {
            $fullPath = CMS_ROOT . '/uploads/gallery/' . basename($item['photo']);
            if (file_exists($fullPath)) @unlink($fullPath);
            $photoPath = null;
        }

        // New photo upload
        if (!empty($_FILES['photo']['name'])) {
            $uploadError = null;
            $uploaded = handle_file_upload($_FILES['photo'], 'gallery', $uploadError);
            if ($uploaded) {
                // Delete old photo
                if ($item['photo'] && str_contains($item['photo'], 'ADMIN/uploads/')) {
                    $oldPath = CMS_ROOT . '/uploads/' . basename(dirname($item['photo'])) . '/' . basename($item['photo']);
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
                $photoPath = $uploaded;
            } else {
                set_flash('danger', 'Photo upload failed: ' . $uploadError);
                redirect(admin_url('testimonials/edit.php?id=' . $id));
            }
        }

        try {
            Database::update('testimonials', [
                'name'          => $name,
                'role'          => $role,
                'child_info'    => $childInfo,
                'rating'        => $rating,
                'content'       => $content,
                'photo'         => $photoPath,
                'initials'      => $initials,
                'display_order' => $order,
                'status'        => $status
            ], 'id = :id', ['id' => $id]);

            log_activity('Updated Testimonial', "Updated testimonial by {$name}", 'testimonials', $id);
            set_flash('success', "Testimonial updated successfully.");
            redirect(admin_url('testimonials/'));
        } catch (Exception $e) {
            set_flash('danger', 'Database error: ' . $e->getMessage());
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('testimonials/') ?>" class="hover:text-slate-800">Testimonials</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit Testimonial</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit: <?= e($item['name']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('testimonials/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to List</span>
        </a>
    </div>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('testimonials/edit.php?id=' . $item['id']) ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                    Author / Parent Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" name="name" value="<?= e($item['name']) ?>" 
                       class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="role">
                    Author Role <span class="text-red-600">*</span>
                </label>
                <select id="role" name="role" class="cms-select">
                    <?php foreach (['Parent', 'Former Parent', 'Student', 'Alumni', 'Partner'] as $r): ?>
                        <option value="<?= $r ?>" <?= $item['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="childInfo">
                    Child Information / Designation
                </label>
                <input type="text" id="childInfo" name="child_info" value="<?= e($item['child_info']) ?>" 
                       class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="rating">
                    Star Rating
                </label>
                <select id="rating" name="rating" class="cms-select">
                    <option value="5" <?= (int)$item['rating'] === 5 ? 'selected' : '' ?>>★★★★★ (5 Stars - Excellent)</option>
                    <option value="4" <?= (int)$item['rating'] === 4 ? 'selected' : '' ?>>★★★★☆ (4 Stars - Very Good)</option>
                    <option value="3" <?= (int)$item['rating'] === 3 ? 'selected' : '' ?>>★★★☆☆ (3 Stars - Average)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="content">
                Testimonial Quote / Feedback <span class="text-red-600">*</span>
            </label>
            <textarea id="content" name="content" rows="4" 
                      class="cms-textarea" required><?= e($item['content']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="initials">
                    Initials
                </label>
                <input type="text" id="initials" name="initials" value="<?= e($item['initials']) ?>" 
                       class="cms-input uppercase">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="displayOrder">
                    Display Priority Order
                </label>
                <input type="number" id="displayOrder" name="display_order" value="<?= (int)$item['display_order'] ?>" min="0" 
                       class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">
                    Publishing Status
                </label>
                <select id="status" name="status" class="cms-select font-semibold">
                    <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Author Portrait Photo
            </label>
            <?php if (!empty($item['photo'])): ?>
                <div class="flex items-center gap-4 mb-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <img src="<?= public_url($item['photo']) ?>" alt="Current Photo" class="w-12 h-12 rounded-full object-cover">
                    <div class="flex-1 text-xs">
                        <span class="font-semibold text-slate-800 block">Current Photo</span>
                        <span class="text-slate-400 font-mono text-[11px]"><?= e($item['photo']) ?></span>
                    </div>
                    <label class="flex items-center gap-1.5 text-xs text-red-600 cursor-pointer hover:underline">
                        <input type="checkbox" name="remove_photo" value="1">
                        <span>Remove</span>
                    </label>
                </div>
            <?php endif; ?>

            <input type="file" name="photo" accept="image/*" data-preview-target="photoPreview" 
                   class="cms-input py-2">

            <div id="photoPreviewContainer" class="hidden mt-3">
                <img id="photoPreview" src="" alt="Photo Preview" class="w-16 h-16 rounded-full object-cover border border-slate-200 shadow-sm">
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('testimonials/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Update Testimonial</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
