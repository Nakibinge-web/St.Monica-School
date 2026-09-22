<?php
/**
 * St. Monica Junior School CMS - Administrator Profile Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$adminId = $_SESSION['admin_id'];
$admin = Database::fetchOne("SELECT * FROM `admins` WHERE `id` = :id", ['id' => $adminId]);

if (!$admin) {
    set_flash('danger', 'Administrator account not found.');
    redirect(admin_url('login/logout.php'));
}

$pageTitle = 'Administrator Profile';
$activeMenu = 'profile';

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">My Profile & Security</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your administrator account details and update your login password.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-5xl">
    <!-- Left Column: User Summary Card (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        <div class="cms-card p-6 text-center">
            <div class="w-20 h-20 rounded-full bg-[#1e2a4a] text-white flex items-center justify-center font-bold text-2xl uppercase shadow-md mx-auto mb-4 border-4 border-slate-100">
                <?= e(substr($admin['name'], 0, 1)) ?>
            </div>
            <h2 class="text-lg font-bold text-slate-900"><?= e($admin['name']) ?></h2>
            <p class="text-xs text-slate-500 mb-3"><?= e($admin['email']) ?></p>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                <span class="material-symbols-outlined text-[14px]">shield</span>
                <span>
                    <?= match($admin['role']) {
                        'super_admin', 'administrator' => 'Super Administrator',
                        'editor' => 'Content Editor',
                        'admissions_manager' => 'Admissions Manager',
                        default => ucfirst($admin['role'])
                    } ?>
                </span>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-100 text-left text-xs space-y-2.5 text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-400">Account Status:</span>
                    <span class="font-bold text-emerald-600">Active</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Last Login:</span>
                    <span class="font-medium text-slate-700">
                        <?= $admin['last_login'] ? date('M j, Y g:i A', strtotime($admin['last_login'])) : 'First Session' ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Registered:</span>
                    <span class="font-medium text-slate-700"><?= date('M j, Y', strtotime($admin['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Forms (8 cols) -->
    <div class="lg:col-span-8 space-y-6">
        <!-- Form 1: Account Information -->
        <div class="cms-card p-6 sm:p-8">
            <div class="flex items-center gap-2.5 pb-4 mb-6 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#1e2a4a] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">badge</span>
                </span>
                <div>
                    <h3 class="font-bold text-slate-900 text-base brand-font">Account Information</h3>
                    <p class="text-xs text-slate-500">Update your name and primary login email address.</p>
                </div>
            </div>

            <form method="POST" action="<?= admin_url('profile/update.php') ?>" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="adminName">
                            Full Name <span class="text-red-600">*</span>
                        </label>
                        <input type="text" id="adminName" name="name" value="<?= e($admin['name']) ?>" 
                               class="cms-input" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="adminPhone">
                            Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <input type="tel" id="adminPhone" name="phone" value="<?= e($admin['phone'] ?? '') ?>" 
                               placeholder="+256 7XX XXXXXX" class="cms-input">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="adminEmail">
                        Email Address <span class="text-red-600">*</span>
                    </label>
                    <input type="email" id="adminEmail" name="email" value="<?= e($admin['email']) ?>" 
                           class="cms-input" required>
                    <span class="text-[11px] text-slate-400 mt-1 block">Used for signing into the St. Monica CMS panel.</span>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="cms-btn cms-btn-primary">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span>Save Profile</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Form 2: Change Password -->
        <div class="cms-card p-6 sm:p-8 border-t-4 border-t-red-600">
            <div class="flex items-center gap-2.5 pb-4 mb-6 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">lock_reset</span>
                </span>
                <div>
                    <h3 class="font-bold text-slate-900 text-base brand-font">Change Password</h3>
                    <p class="text-xs text-slate-500">Ensure your account uses a strong, secure passphrase.</p>
                </div>
            </div>

            <form method="POST" action="<?= admin_url('profile/change-password.php') ?>" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="currentPassword">
                        Current Password <span class="text-red-600">*</span>
                    </label>
                    <input type="password" id="currentPassword" name="current_password" required 
                           placeholder="Enter your existing password" class="cms-input">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="newPassword">
                            New Password <span class="text-red-600">*</span>
                        </label>
                        <input type="password" id="newPassword" name="new_password" required minlength="8" 
                               placeholder="Minimum 8 characters" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="confirmPassword">
                            Confirm New Password <span class="text-red-600">*</span>
                        </label>
                        <input type="password" id="confirmPassword" name="confirm_password" required minlength="8" 
                               placeholder="Re-type new password" class="cms-input">
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="cms-btn cms-btn-accent">
                        <span class="material-symbols-outlined text-[18px]">key</span>
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
