<?php
/**
 * St. Monica Junior School CMS - Create Testimonial
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('testimonials');

$pageTitle = 'Add Testimonial';
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
        // Auto compute initials if empty
        if (empty($initials)) {
            $parts = explode(' ', $name);
            $initials = count($parts) >= 2 
                ? strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1))
                : strtoupper(substr($name, 0, 2));
        }

        // Photo chosen from the Media Library (optional)
        $photoPath = null;
        if (!empty($_POST['photo'])) {
            $photoPath = resolve_media_selection($_POST['photo']);
            if (!$photoPath) {
                set_flash('danger', 'The selected photo is no longer available in the Media Library. Please choose another.');
                redirect(admin_url('testimonials/create.php'));
            }
        }

        try {
            $newId = Database::insert('testimonials', [
                'name'          => $name,
                'role'          => $role,
                'child_info'    => $childInfo,
                'rating'        => $rating,
                'content'       => $content,
                'photo'         => $photoPath,
                'initials'      => $initials,
                'display_order' => $order,
                'status'        => $status
            ]);

            log_activity('Created Testimonial', "Added testimonial by {$name} ({$role})", 'testimonials', $newId);
            set_flash('success', "Testimonial by '{$name}' created successfully.");
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
            <span class="text-slate-800 font-semibold">New Testimonial</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Add Testimonial</h1>
    </div>
    <a href="<?= admin_url('testimonials/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('testimonials/create.php') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                    Author / Parent Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" name="name" placeholder="e.g. Mrs. Mary Kisakye" 
                       class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="role">
                    Author Role <span class="text-red-600">*</span>
                </label>
                <select id="role" name="role" class="cms-select">
                    <option value="Parent">Parent</option>
                    <option value="Former Parent">Former Parent</option>
                    <option value="Student">Student</option>
                    <option value="Alumni">Alumni</option>
                    <option value="Partner">Community Partner</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="childInfo">
                    Child Information / Designation
                </label>
                <input type="text" id="childInfo" name="child_info" placeholder="e.g. Parent, Primary 5" 
                       class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="rating">
                    Star Rating
                </label>
                <select id="rating" name="rating" class="cms-select">
                    <option value="5">★★★★★ (5 Stars - Excellent)</option>
                    <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                    <option value="3">★★★☆☆ (3 Stars - Average)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="content">
                Testimonial Quote / Feedback <span class="text-red-600">*</span>
            </label>
            <textarea id="content" name="content" rows="4" placeholder="Enter what the parent says about St. Monica Junior School..." 
                      class="cms-textarea" required></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="initials">
                    Initials (For Avatar fallback)
                </label>
                <input type="text" id="initials" name="initials" placeholder="e.g. MK" maxlength="4" 
                       class="cms-input uppercase">
                <span class="text-[11px] text-slate-400 mt-1 block">Leave empty to compute from name.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="displayOrder">
                    Display Priority Order
                </label>
                <input type="number" id="displayOrder" name="display_order" value="0" min="0" 
                       class="cms-input">
                <span class="text-[11px] text-slate-400 mt-1 block">Lower numbers appear first.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">
                    Publishing Status
                </label>
                <select id="status" name="status" class="cms-select font-semibold">
                    <option value="published">Published</option>
                    <option value="draft">Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Author Portrait Photo <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <?= render_media_picker('photo', [
                'shape' => 'circle',
                'hint'  => 'Square portrait recommended. If omitted, an initials circle will be displayed.',
            ]) ?>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('testimonials/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">check</span>
                <span>Save Testimonial</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
