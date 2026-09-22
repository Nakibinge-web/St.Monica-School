<?php
/**
 * St. Monica Junior School CMS - View Staff Member Profile
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
$staff = Database::fetchOne("SELECT * FROM `staff` WHERE `id` = :id", ['id' => $id]);

if (!$staff) {
    set_flash('danger', 'Staff member not found.');
    redirect(admin_url('staff/'));
}

$pageTitle = 'Profile: ' . $staff['name'];
$activeMenu = 'staff';

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('staff/') ?>" class="hover:text-slate-800">Staff Members</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold"><?= e($staff['name']) ?></span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Staff Profile</h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('staff/') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to List</span>
        </a>
        <a href="<?= admin_url('staff/edit.php?id=' . $staff['id']) ?>" class="cms-btn cms-btn-primary">
            <span class="material-symbols-outlined text-[18px]">edit</span>
            <span>Edit Profile</span>
        </a>
    </div>
</div>

<div class="cms-card p-6 sm:p-8 max-w-2xl mx-auto">
    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 pb-6 border-b border-slate-100 text-center sm:text-left">
        <div class="w-28 h-28 rounded-2xl overflow-hidden bg-slate-100 border-2 border-slate-200 shadow-md flex-shrink-0">
            <img src="<?= public_url($staff['photo'] ?? 'assets/imgz/headteacher.webp') ?>" alt="<?= e($staff['name']) ?>" class="w-full h-full object-cover object-top">
        </div>
        <div class="space-y-1">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                <span class="cms-badge <?= $staff['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                    <?= ucfirst($staff['status']) ?>
                </span>
                <?php if ($staff['is_featured']): ?>
                    <span class="cms-badge badge-sports">Featured on Homepage</span>
                <?php endif; ?>
            </div>
            <h2 class="text-xl font-bold text-slate-900 brand-font"><?= e($staff['name']) ?></h2>
            <p class="text-sm font-semibold text-red-600"><?= e($staff['position']) ?></p>
            <p class="text-xs text-slate-500"><?= e($staff['department'] ?? 'Department Unspecified') ?></p>
        </div>
    </div>

    <div class="py-6 space-y-4">
        <div>
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Email Address</h3>
            <p class="text-sm text-slate-800"><?= !empty($staff['email']) ? e($staff['email']) : '<span class="text-slate-400 italic">Not specified</span>' ?></p>
        </div>

        <div>
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Display Priority Order</h3>
            <p class="text-sm text-slate-800">#<?= e($staff['display_order']) ?></p>
        </div>

        <div>
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Biography / About</h3>
            <div class="text-sm text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-lg border border-slate-100">
                <?= !empty($staff['biography']) ? nl2br(e($staff['biography'])) : '<span class="text-slate-400 italic">No biography provided.</span>' ?>
            </div>
        </div>
    </div>

    <div class="pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
        <span>Created: <?= date('M j, Y', strtotime($staff['created_at'])) ?></span>
        <span>Last Updated: <?= date('M j, Y g:i A', strtotime($staff['updated_at'])) ?></span>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
