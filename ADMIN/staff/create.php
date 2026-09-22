<?php
/**
 * St. Monica Junior School CMS - Add New Staff Member
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'Add Staff Member';
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
        $photoPath = 'assets/imgz/headteacher.webp'; // fallback default
        $uploadError = null;

        if (!empty($_FILES['photo']['name'])) {
            $uploaded = handle_file_upload($_FILES['photo'], 'staff', $uploadError);
            if ($uploaded) {
                $photoPath = $uploaded;
            } else {
                set_flash('danger', 'Photo upload failed: ' . $uploadError);
                redirect(admin_url('staff/create.php'));
            }
        }

        try {
            $newId = Database::insert('staff', [
                'name'          => $name,
                'position'      => $position,
                'department'    => $department,
                'biography'     => $biography,
                'email'         => $email,
                'photo'         => $photoPath,
                'display_order' => $displayOrder,
                'is_featured'   => $isFeatured,
                'status'        => $status
            ]);

            log_activity('Added Staff Member', "{$name} ({$position})");
            set_flash('success', "Staff member {$name} registered successfully.");
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
            <span class="text-slate-800 font-semibold">New Profile</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Add Staff Member</h1>
    </div>
    <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to Staff List</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-3xl mx-auto mb-8">
    <form method="POST" action="<?= admin_url('staff/create.php') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Full Name -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Ms Jane Frances Luyiga" class="cms-input">
            </div>

            <!-- Position -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Position / Title *</label>
                <input type="text" name="position" required placeholder="e.g. Headteacher, Primary 3 Teacher" class="cms-input">
            </div>

            <!-- Department -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Department</label>
                <select name="department" class="cms-select">
                    <option value="Administration">Administration</option>
                    <option value="Early Childhood (Pre-Primary)">Early Childhood (Pre-Primary)</option>
                    <option value="Lower Primary">Lower Primary</option>
                    <option value="Upper Primary">Upper Primary</option>
                    <option value="Co-curricular & Sports">Co-curricular & Sports</option>
                    <option value="Support Staff">Support Staff</option>
                </select>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Contact Email</label>
                <input type="email" name="email" placeholder="staff@stmonicakasanje.ac.ug" class="cms-input">
            </div>

            <!-- Display Order -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Display Order</label>
                <input type="number" name="display_order" value="1" min="0" class="cms-input">
            </div>

            <!-- Biography / Intro -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Biography / Profile Description</label>
                <textarea name="biography" rows="4" placeholder="Brief statement about teaching philosophy, qualifications, or experience..." class="cms-textarea"></textarea>
            </div>

            <!-- Photo Upload -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Staff Photo</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview-target="staffPreviewImg" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">Portrait photo (JPG, PNG, or WEBP under 8MB).</p>

                <div id="staffPreviewImgContainer" class="hidden mt-4">
                    <p class="text-xs font-semibold text-slate-600 mb-1">Photo Preview:</p>
                    <img id="staffPreviewImg" src="#" alt="Preview" class="h-32 w-32 rounded-full object-cover object-top border border-slate-300 shadow-sm">
                </div>
            </div>

            <!-- Settings -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Publication Status</label>
                <select name="status" class="cms-select">
                    <option value="published">Published (Visible on site)</option>
                    <option value="draft">Draft (Hidden)</option>
                </select>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-3 cursor-pointer text-sm font-semibold text-slate-700 select-none">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span>Feature on Homepage ("Our Dedicated Team")</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">check</span>
                <span>Save Staff Member</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
