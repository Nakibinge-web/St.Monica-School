<?php
/**
 * St. Monica Junior School CMS - Administrator User Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'Administrator User Management';
$activeMenu = 'users';

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`name` LIKE :s OR `email` LIKE :s)";
    $params['s'] = "%{$search}%";
}
if (!empty($roleFilter)) {
    if ($roleFilter === 'super_admin') {
        $where[] = "(`role` = 'super_admin' OR `role` = 'administrator')";
    } else {
        $where[] = "`role` = :role";
        $params['role'] = $roleFilter;
    }
}

$whereSql = implode(' AND ', $where);
$users = Database::fetchAll("SELECT * FROM `admins` WHERE {$whereSql} ORDER BY `id` ASC", $params);

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Administrator Users</h1>
        <p class="text-sm text-slate-500 mt-1">Manage administrative staff, configure role permissions, and provision user accounts.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('users/create.php') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">person_add</span>
            <span>Create New Admin</span>
        </a>
    </div>
</div>

<!-- Role Legend Banner -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="cms-card p-4 border-l-4 border-l-purple-600">
        <h4 class="font-bold text-xs text-purple-900 uppercase tracking-wider">Super Administrator</h4>
        <p class="text-xs text-slate-500 mt-1">Full control over all CMS content, admissions, media, settings, user provisioning, and audit logs.</p>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-blue-600">
        <h4 class="font-bold text-xs text-blue-900 uppercase tracking-wider">Content Editor</h4>
        <p class="text-xs text-slate-500 mt-1">Can manage Homepage, About Us, Staff directory, News & Events, Gallery, Testimonials, Media Library, and SEO.</p>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-emerald-600">
        <h4 class="font-bold text-xs text-emerald-900 uppercase tracking-wider">Admissions Manager</h4>
        <p class="text-xs text-slate-500 mt-1">Dedicated access to process incoming pupil applications and manage admission guidelines & procedures.</p>
    </div>
</div>

<!-- Users List -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Registered Administrators (<?= count($users) ?>)</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Administrator</th>
                    <th>Assigned Role</th>
                    <th>Account Status</th>
                    <th>Last Active</th>
                    <th>Registered</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $roleLabel = match($u['role']) {
                        'super_admin', 'administrator' => 'Super Admin',
                        'editor' => 'Content Editor',
                        'admissions_manager' => 'Admissions Manager',
                        default => ucfirst($u['role'])
                    };

                    $roleColor = match($u['role']) {
                        'super_admin', 'administrator' => 'bg-purple-100 text-purple-800 border-purple-200',
                        'editor' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'admissions_manager' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        default => 'bg-slate-100 text-slate-800'
                    };

                    $isActive = ($u['status'] ?? 'active') === 'active';
                ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="font-mono text-xs text-slate-400">#<?= $u['id'] ?></td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#1e2a4a] text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                    <?= e(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">
                                        <?= e($u['name']) ?>
                                        <?php if ($u['id'] == $_SESSION['admin_id']): ?>
                                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-normal ml-1">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono"><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold border <?= $roleColor ?>">
                                <?= $roleLabel ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                    Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="text-xs text-slate-600">
                                <?= $u['last_login'] ? date('M j, Y g:i A', strtotime($u['last_login'])) : 'Never' ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-xs text-slate-500">
                                <?= date('M j, Y', strtotime($u['created_at'])) ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="<?= admin_url('users/edit.php?id=' . $u['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit Admin">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <?php if ($u['id'] != $_SESSION['admin_id']): ?>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('users/delete.php?id=' . $u['id']) ?>" data-name="Admin Account: <?= e($u['name']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete Admin">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
