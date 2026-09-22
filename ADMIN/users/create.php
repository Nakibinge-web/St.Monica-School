<?php
/**
 * St. Monica Junior School CMS - Create Administrator User
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'Create Administrator';
$activeMenu = 'users';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $role     = trim($_POST['role'] ?? 'editor');
    $status   = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $allowedRoles = ['super_admin', 'editor', 'admissions_manager'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'editor';
    }

    if (empty($name) || empty($email) || empty($password)) {
        set_flash('danger', 'Name, email address, and initial password are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address format.');
    } elseif (strlen($password) < 8) {
        set_flash('danger', 'Password must be at least 8 characters long.');
    } elseif ($password !== $confirm) {
        set_flash('danger', 'Password and confirmation do not match.');
    } else {
        try {
            $existing = Database::fetchOne("SELECT `id` FROM `admins` WHERE `email` = :email", ['email' => $email]);
            if ($existing) {
                set_flash('danger', 'An administrator account with that email already exists.');
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                $newId = Database::insert('admins', [
                    'name'     => $name,
                    'email'    => $email,
                    'phone'    => !empty($phone) ? $phone : null,
                    'role'     => $role,
                    'status'   => $status,
                    'password' => $hashed
                ]);

                log_activity('Created Admin User', "Created administrator '{$name}' with role '{$role}'", 'users', $newId);
                set_flash('success', "Administrator account for '{$name}' has been created.");
                redirect(admin_url('users/'));
            }
        } catch (Exception $e) {
            set_flash('danger', 'Database error creating administrator: ' . $e->getMessage());
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('users/') ?>" class="hover:text-slate-800">Admin Users</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">New Account</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Create Administrator</h1>
    </div>
    <a href="<?= admin_url('users/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('users/create.php') ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" name="name" placeholder="e.g. Isaac Omuge" class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="email">
                    Login Email Address <span class="text-red-600">*</span>
                </label>
                <input type="email" id="email" name="email" placeholder="e.g. i.omuge@stmonicakasanje.ac.ug" class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="phone">
                    Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input type="tel" id="phone" name="phone" placeholder="+256 7XX XXXXXX" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="role">
                    Assigned Role Permissions <span class="text-red-600">*</span>
                </label>
                <select id="role" name="role" class="cms-select font-semibold">
                    <option value="editor">Content Editor (Website & Media)</option>
                    <option value="admissions_manager">Admissions Manager (Pupil Applications)</option>
                    <option value="super_admin">Super Administrator (Full System Control)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">
                    Initial Account Status
                </label>
                <select id="status" name="status" class="cms-select">
                    <option value="active">Active (Can log in)</option>
                    <option value="inactive">Inactive (Suspended)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <h4 class="font-bold text-slate-900 text-sm mb-3">Security Credentials</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="password">
                        Initial Password <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="password" name="password" required minlength="8" 
                           placeholder="Minimum 8 characters" class="cms-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="confirmPassword">
                        Confirm Password <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="confirmPassword" name="confirm_password" required minlength="8" 
                           placeholder="Re-type password" class="cms-input">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('users/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                <span>Create Administrator</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
