<?php
/**
 * St. Monica Junior School CMS - Edit Staff Member
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('staff');

$id = (int)($_GET['id'] ?? 0);
$staff = Database::fetchOne("SELECT * FROM `staff` WHERE `id` = :id", ['id' => $id]);

if (!$staff) {
    set_flash('danger', 'Staff member not found.');
    redirect(admin_url('staff/'));
}

$pageTitle = 'Edit ' . $staff['name'];
$activeMenu = 'staff';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? 'Administration');
    $biography = trim($_POST['biography'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    if (empty($name) || empty($position)) {
        set_flash('danger', 'Staff member name and position are required.');
    } else {
        $photoPath = null;
        $uploadError = null;

        if (!empty($_FILES['photo']['name'])) {
            $uploaded = handle_file_upload($_FILES['photo'], 'staff', $uploadError);
            if ($uploaded) {
                $photoPath = $uploaded;
            } else {
                set_flash('danger', 'Photo upload failed: ' . $uploadError);
                redirect(admin_url('staff/edit.php?id=' . $id));
            }
        }

        try {
            $updateData = [
                'name'          => $name,
                'position'      => $position,
                'department'    => $department,
                'biography'     => $biography,
                'email'         => $email,
                'display_order' => $displayOrder,
                'is_featured'   => $isFeatured,
                'status'        => $status
            ];

            if ($photoPath) {
                $updateData['photo'] = $photoPath;
            }

            Database::update('staff', $updateData, 'id = :id', ['id' => $id]);

            log_activity('Updated Staff Member', "{$name} (ID: {$id})");
            set_flash('success', "Staff profile for {$name} updated successfully.");
            redirect(admin_url('staff/'));
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
            <a href="<?= admin_url('staff/') ?>" class="hover:text-slate-800">Staff Members</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit Profile</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Staff Member</h1>
    </div>
    <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to Staff List</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-3xl mx-auto mb-8">
    <form method="POST" action="<?= admin_url('staff/edit.php?id=' . $id) ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Full Name -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Full Name *</label>
                <input type="text" name="name" required value="<?= e($staff['name']) ?>" class="cms-input">
            </div>

            <!-- Position -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Position / Title *</label>
                <input type="text" name="position" required value="<?= e($staff['position']) ?>" class="cms-input">
            </div>

            <!-- Department -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Department</label>
                <select name="department" class="cms-select">
                    <?php
                    $depts = [
                        'Administration',
                        'Early Childhood (Pre-Primary)',
                        'Lower Primary',
                        'Upper Primary',
                        'Co-curricular & Sports',
                        'Support Staff'
                    ];
                    foreach ($depts as $dept): ?>
                        <option value="<?= e($dept) ?>" <?= $staff['department'] === $dept ? 'selected' : '' ?>>
                            <?= e($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Contact Email</label>
                <input type="email" name="email" value="<?= e($staff['email'] ?? '') ?>" class="cms-input">
            </div>

            <!-- Display Order -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Display Order</label>
                <input type="number" name="display_order" value="<?= e($staff['display_order']) ?>" min="0" class="cms-input">
            </div>

            <!-- Biography / Intro -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Biography / Profile Description</label>
                <textarea name="biography" rows="4" class="cms-textarea"><?= e($staff['biography'] ?? '') ?></textarea>
            </div>

            <!-- Photo Upload -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Staff Photo</label>
                
                <?php if (!empty($staff['photo'])): ?>
                    <div class="flex items-center gap-4 mb-3">
                        <img src="<?= public_url($staff['photo']) ?>" alt="<?= e($staff['name']) ?>" class="h-20 w-20 rounded-full object-cover object-top border border-slate-200">
                        <div class="text-xs text-slate-500">
                            <span class="font-semibold text-slate-700 block">Current Photo</span>
                            Upload a new file below only if you wish to change it.
                        </div>
                    </div>
                <?php endif; ?>

                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview-target="staffEditPreviewImg" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep current photo.</p>

                <div id="staffEditPreviewImgContainer" class="hidden mt-4">
                    <p class="text-xs font-semibold text-slate-600 mb-1">New Photo Preview:</p>
                    <img id="staffEditPreviewImg" src="#" alt="Preview" class="h-32 w-32 rounded-full object-cover object-top border border-slate-300 shadow-sm">
                </div>
            </div>

            <!-- Settings -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Publication Status</label>
                <select name="status" class="cms-select">
                    <option value="published" <?= $staff['status'] === 'published' ? 'selected' : '' ?>>Published (Visible)</option>
                    <option value="draft" <?= $staff['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-3 cursor-pointer text-sm font-semibold text-slate-700 select-none">
                    <input type="checkbox" name="is_featured" value="1" <?= $staff['is_featured'] ? 'checked' : '' ?> class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span>Feature on Homepage ("Our Dedicated Team")</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Update Staff Member</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
