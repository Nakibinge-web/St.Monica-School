<?php
/**
 * St. Monica Junior School CMS - Restore Database from Backup
 * Extremely destructive - requires typed "RESTORE" confirmation and always
 * creates a pre-restore safety backup of the current database first.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/BackupService.php';

$filename = $_GET['file'] ?? $_POST['file'] ?? '';
$path = BackupService::resolvePath($filename);

if (!$path) {
    set_flash('danger', 'Backup file not found.');
    redirect(admin_url('backups/'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $confirmation = trim($_POST['confirmation'] ?? '');

    if ($confirmation !== 'RESTORE') {
        set_flash('danger', 'You must type RESTORE exactly to confirm this action.');
        redirect(admin_url('backups/restore.php?file=' . urlencode($filename)));
    }

    // Always snapshot the current database before overwriting it
    $safetyBackup = BackupService::create();
    if ($safetyBackup) {
        log_activity('Pre-Restore Safety Backup', "Created safety backup before restore: {$safetyBackup}", 'backups');
    }

    $success = BackupService::restore($filename);

    if ($success) {
        log_activity('Restored Database', "Database restored from backup: {$filename}", 'backups');
        set_flash('success', "Database successfully restored from '{$filename}'. A safety backup of the prior state was also created.");
    } else {
        log_activity('Restore Failed', "Failed to restore from backup: {$filename}", 'backups');
        set_flash('danger', 'Database restore failed. Your data has not been modified beyond the safety backup created.');
    }

    redirect(admin_url('backups/'));
}

$pageTitle = 'Restore Database';
$activeMenu = 'backups';

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('backups/') ?>" class="hover:text-slate-800">Backups</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Restore</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Restore Database</h1>
    </div>
</div>

<div class="cms-card max-w-2xl p-8 border-t-4 border-t-red-600">
    <div class="flex items-start gap-4 mb-6">
        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-[32px]">warning</span>
        </div>
        <div>
            <h2 class="text-lg font-bold text-red-700">WARNING: Destructive Operation</h2>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Restoring <strong class="font-mono"><?= e($filename) ?></strong> will <strong>replace the current database state</strong>.
                This may permanently overwrite recent changes made after this backup was created.
            </p>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                A safety backup of the <strong>current</strong> database will be created automatically before restoring, so you can undo this if needed.
            </p>
        </div>
    </div>

    <form method="POST" action="<?= admin_url('backups/restore.php') ?>" class="space-y-5 pt-5 border-t border-slate-100">
        <?= csrf_field() ?>
        <input type="hidden" name="file" value="<?= e($filename) ?>">

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">
                Type <span class="font-mono font-bold text-red-600">RESTORE</span> to continue:
            </label>
            <input type="text" name="confirmation" required autocomplete="off" placeholder="RESTORE" class="cms-input font-mono uppercase tracking-widest">
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= admin_url('backups/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-danger">
                <span class="material-symbols-outlined text-[18px]">restore</span>
                <span>Restore Database</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
