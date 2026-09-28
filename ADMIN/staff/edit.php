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
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'The contact email does not look valid. Correct it, or leave it empty since it is optional.');
    } else {
        // Photo chosen from the Media Library (empty = keep current)
        $photoPath = null;
        if (!empty($_POST['photo'])) {
            $photoPath = resolve_media_selection($_POST['photo']);
            if (!$photoPath) {
                set_flash('danger', 'The selected photo is no longer available in the Media Library. Please choose another.');
                redirect(admin_url('staff/edit.php?id=' . $id));
            }
        }

        try {
            $updateData = [
                'name'          => $name,
                'position'      => $position,
                'department'    => $department,
                'biography'     => $biography,
                'email'         => $email !== '' ? $email : null, // optional
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
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Contact Email <span class="normal-case tracking-normal font-normal text-slate-400">(Optional)</span></label>
                <input type="email" name="email" value="<?= e($staff['email'] ?? '') ?>" placeholder="staff@stmonicakasanje.ac.ug" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">Leave empty if this staff member has no public email.</p>
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
                
                <?= render_media_picker('photo', [
                    'current' => $staff['photo'] ?? '',
                    'shape'   => 'circle',
                    'hint'    => 'Portrait photo recommended.',
                ]) ?>
            </div>

            <!-- Settings -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Publication Status</label>
                <select name="status" class="cms-select">
                    <option value="published" <?= $staff['status'] === 'published' ? 'selected' : '' ?>>Published (Visible)</option>
                    <option value="draft" <?= $staff['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex flex-col gap-1 pt-2">
                <label class="inline-flex items-center gap-3 cursor-pointer text-sm font-semibold text-slate-700 select-none whitespace-nowrap">
                    <input type="checkbox" name="is_featured" value="1" <?= $staff['is_featured'] ? 'checked' : '' ?> class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span>Feature on Homepage ("Our Dedicated Team")</span>
                </label>
                <p class="text-xs text-slate-400 ml-7">Published staff always appear on the About Us page. Tick this to also show them on the homepage.</p>
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
