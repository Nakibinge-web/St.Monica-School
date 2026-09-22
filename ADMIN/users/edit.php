<?php
/**
 * St. Monica Junior School CMS - Edit Administrator User
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = Database::fetchOne("SELECT * FROM `admins` WHERE `id` = :id", ['id' => $id]);

if (!$user) {
    set_flash('danger', 'Administrator not found.');
    redirect(admin_url('users/'));
}

$pageTitle = 'Edit Administrator';
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

    // Safeguard: Check if this would remove the last active super_admin
    $isCurrentlySuperAdmin = in_array($user['role'], ['super_admin', 'administrator']);
    $willRemainSuperAdmin = ($role === 'super_admin' && $status === 'active');

    if ($isCurrentlySuperAdmin && !$willRemainSuperAdmin) {
        $otherSuperAdmins = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `admins` 
             WHERE (`role` = 'super_admin' OR `role` = 'administrator') 
               AND `status` = 'active' 
               AND `id` != :id",
            ['id' => $id]
        );

        if ($otherSuperAdmins === 0) {
            set_flash('danger', 'Action blocked: System must have at least one active Super Administrator.');
            redirect(admin_url('users/edit.php?id=' . $id));
        }
    }

    if (empty($name) || empty($email)) {
        set_flash('danger', 'Full name and email address are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address.');
    } elseif (!empty($password) && strlen($password) < 8) {
        set_flash('danger', 'New password must be at least 8 characters long.');
    } elseif (!empty($password) && $password !== $confirm) {
        set_flash('danger', 'New password and confirmation do not match.');
    } else {
        try {
            $existing = Database::fetchOne(
                "SELECT `id` FROM `admins` WHERE `email` = :email AND `id` != :id",
                ['email' => $email, 'id' => $id]
            );

            if ($existing) {
                set_flash('danger', 'Another administrator is already registered with this email address.');
            } else {
                $updateData = [
                    'name'   => $name,
                    'email'  => $email,
                    'phone'  => !empty($phone) ? $phone : null,
                    'role'   => $role,
                    'status' => $status
                ];

                if (!empty($password)) {
                    $updateData['password'] = password_hash($password, PASSWORD_BCRYPT);
                }

                Database::update('admins', $updateData, '`id` = :id', ['id' => $id]);

                // If updating current user's session
                if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_role'] = $role;
                }

                log_activity('Updated Admin User', "Updated administrator '{$name}' (Role: {$role}, Status: {$status})", 'users', $id);
                set_flash('success', "Administrator account for '{$name}' updated successfully.");
                redirect(admin_url('users/'));
            }
        } catch (Exception $e) {
            set_flash('danger', 'Database error updating administrator: ' . $e->getMessage());
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
            <span class="text-slate-800 font-semibold">Edit Account</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Administrator</h1>
        <p class="text-xs text-slate-500 mt-0.5">Editing credentials and permissions for <strong class="text-slate-700"><?= e($user['name']) ?></strong></p>
    </div>
    <a href="<?= admin_url('users/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('users/edit.php?id=' . $user['id']) ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="name">
                    Full Name <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="email">
                    Login Email Address <span class="text-red-600">*</span>
                </label>
                <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" class="cms-input" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="phone">
                    Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input type="tel" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="cms-input">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="role">
                    Assigned Role Permissions <span class="text-red-600">*</span>
                </label>
                <select id="role" name="role" class="cms-select font-semibold">
                    <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Content Editor (Website & Media)</option>
                    <option value="admissions_manager" <?= $user['role'] === 'admissions_manager' ? 'selected' : '' ?>>Admissions Manager (Pupil Applications)</option>
                    <option value="super_admin" <?= in_array($user['role'], ['super_admin', 'administrator']) ? 'selected' : '' ?>>Super Administrator (Full System Control)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="status">
                    Account Status
                </label>
                <select id="status" name="status" class="cms-select">
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Can log in)</option>
                    <option value="inactive" <?= ($user['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <h4 class="font-bold text-slate-900 text-sm mb-1">Reset Password</h4>
            <p class="text-xs text-slate-500 mb-4">Leave blank to keep current administrator password unchanged.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="password">
                        New Password
                    </label>
                    <input type="password" id="password" name="password" minlength="8" 
                           placeholder="Leave empty to retain current" class="cms-input">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="confirmPassword">
                        Confirm New Password
                    </label>
                    <input type="password" id="confirmPassword" name="confirm_password" minlength="8" 
                           placeholder="Re-type new password" class="cms-input">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="<?= admin_url('users/') ?>" class="cms-btn cms-btn-outline text-xs">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent text-xs">
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
