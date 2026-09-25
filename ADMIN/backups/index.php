<?php
/**
 * St. Monica Junior School CMS - Database Backups
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/BackupService.php';

$pageTitle = 'Database Backups';
$activeMenu = 'backups';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create') {
    require_csrf();

    $filename = BackupService::create();
    if ($filename) {
        log_activity('Created Backup', "Backup file created: {$filename}", 'backups');
        set_flash('success', "Backup '{$filename}' created successfully.");
    } else {
        log_activity('Backup Failed', 'Backup creation attempt failed', 'backups');
        set_flash('danger', 'Failed to create backup. Check that the storage directory is writable.');
    }
    redirect(admin_url('backups/'));
}

$backups = BackupService::list();

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Database Backups</h1>
        <p class="text-sm text-slate-500 mt-1">Create and manage full database dumps. Files are protected from public access.</p>
    </div>
    <form method="POST" action="<?= admin_url('backups/') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <button type="submit" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">backup</span>
            <span>Create Backup Now</span>
        </button>
    </form>
</div>

<div class="cms-card p-4 mb-6 bg-blue-50 border border-blue-200">
    <p class="text-xs text-blue-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-0.5">info</span>
        <span>For automated daily backups, configure a scheduled task (Windows Task Scheduler or cron) to request this page's create action, or run <code class="bg-white px-1 rounded">php ADMIN/backups/cli-backup.php</code> on a schedule. See the README for setup instructions.</span>
    </p>
</div>

<div class="cms-card overflow-hidden">
    <table class="cms-table">
        <thead>
            <tr>
                <th>Backup File</th>
                <th>Size</th>
                <th>Created</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($backups)): ?>
                <tr>
                    <td colspan="4" class="text-center py-12 text-slate-400">
                        <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">backup</span>
                        No backups yet. Click "Create Backup Now" to generate your first one.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($backups as $b): ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="font-mono text-xs text-slate-800"><?= e($b['filename']) ?></td>
                        <td class="text-xs text-slate-500"><?= format_bytes($b['size']) ?></td>
                        <td class="text-xs text-slate-500"><?= date('M j, Y g:i A', $b['created']) ?></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="<?= admin_url('backups/download.php?file=' . urlencode($b['filename'])) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Download">
                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                </a>
                                <a href="<?= admin_url('backups/restore.php?file=' . urlencode($b['filename'])) ?>" class="p-1.5 text-amber-600 hover:text-amber-800 rounded hover:bg-amber-50" title="Restore">
                                    <span class="material-symbols-outlined text-[18px]">restore</span>
                                </a>
                                <button type="button" data-delete-btn data-action="<?= admin_url('backups/delete.php?file=' . urlencode($b['filename'])) ?>" data-name="Backup <?= e($b['filename']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
