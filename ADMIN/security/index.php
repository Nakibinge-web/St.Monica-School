<?php
/**
 * St. Monica Junior School CMS - Security Center
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/BackupService.php';

$pageTitle = 'Security Center';
$activeMenu = 'security';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

$activeSessions = [];
$recentFailedLogins = [];
$activeAdminCount = 0;
try {
    $activeSessions = Database::fetchAll(
        "SELECT s.*, a.name AS admin_name, a.email AS admin_email
         FROM `admin_sessions` s
         JOIN `admins` a ON a.id = s.admin_id
         WHERE s.revoked_at IS NULL
         ORDER BY s.last_activity DESC LIMIT 25"
    );
    $recentFailedLogins = Database::fetchAll(
        "SELECT * FROM `activity_logs` WHERE `action` IN ('Failed Login Attempt', 'Blocked Login Attempt (Rate Limited)', 'Deactivated Login Attempt') ORDER BY `created_at` DESC LIMIT 10"
    );
    $activeAdminCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admins` WHERE `status` = 'active'");
} catch (Exception $e) {
    // ignore
}

$lastBackup = null;
try {
    $backups = BackupService::list();
    $lastBackup = $backups[0] ?? null;
} catch (Exception $e) {
    // ignore
}

require_once CMS_ROOT . '/services/EmailService.php';
$emailConfigured = EmailService::isConfigured();

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Security Center</h1>
        <p class="text-sm text-slate-500 mt-1">Overview of authentication, session, and backup security posture.</p>
    </div>
</div>

<!-- Overview Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">HTTPS</p>
        <?php if ($isHttps): ?>
            <p class="text-sm font-bold text-emerald-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">verified_user</span> Connected Securely</p>
        <?php else: ?>
            <p class="text-sm font-bold text-amber-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">warning</span> Not Using HTTPS</p>
            <p class="text-[11px] text-slate-400 mt-1">Enable HTTPS in production to protect login credentials in transit.</p>
        <?php endif; ?>
    </div>

    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Session Security</p>
        <p class="text-sm font-bold text-emerald-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">verified_user</span> HttpOnly + SameSite=Strict</p>
        <p class="text-[11px] text-slate-400 mt-1">2-hour inactivity timeout; session ID regenerated on login.</p>
    </div>

    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Last Database Backup</p>
        <?php if ($lastBackup): ?>
            <p class="text-sm font-bold text-slate-800"><?= date('M j, Y g:i A', $lastBackup['created']) ?></p>
        <?php else: ?>
            <p class="text-sm font-bold text-amber-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">warning</span> No Backups Yet</p>
        <?php endif; ?>
        <a href="<?= admin_url('backups/') ?>" class="text-[11px] font-semibold text-red-600 hover:text-red-700">Manage Backups &rarr;</a>
    </div>

    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Password Policy</p>
        <p class="text-sm font-bold text-slate-800">Minimum 8 characters, bcrypt hashed</p>
        <p class="text-[11px] text-slate-400 mt-1"><?= $activeAdminCount ?> active administrator account(s).</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Login Rate Limiting</p>
        <p class="text-sm font-bold text-emerald-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">verified_user</span> Active</p>
        <p class="text-[11px] text-slate-400 mt-1">Accounts are temporarily locked for 15 minutes after 5 failed attempts.</p>
    </div>
    <div class="cms-card p-5">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Outgoing Email (SMTP)</p>
        <?php if ($emailConfigured): ?>
            <p class="text-sm font-bold text-emerald-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">verified_user</span> Configured</p>
        <?php else: ?>
            <p class="text-sm font-bold text-amber-600 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">warning</span> Not Configured</p>
            <p class="text-[11px] text-slate-400 mt-1">Notifications, password resets and application emails are logged only until SMTP is set up.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Active Sessions -->
<div class="cms-card overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-base font-bold text-slate-900 brand-font">Active Administrator Sessions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>Administrator</th>
                    <th>IP Address</th>
                    <th>Signed In</th>
                    <th>Last Activity</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeSessions)): ?>
                    <tr><td colspan="5" class="text-center py-8 text-slate-400 text-xs">No active sessions recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($activeSessions as $s): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td>
                                <div class="text-sm font-semibold text-slate-900"><?= e($s['admin_name']) ?></div>
                                <div class="text-[11px] text-slate-400"><?= e($s['admin_email']) ?></div>
                            </td>
                            <td class="text-xs font-mono text-slate-600"><?= e($s['ip_address'] ?? 'Unknown') ?></td>
                            <td class="text-xs text-slate-500"><?= date('M j, g:i A', strtotime($s['login_at'])) ?></td>
                            <td class="text-xs text-slate-500"><?= date('M j, g:i A', strtotime($s['last_activity'])) ?></td>
                            <td class="text-right">
                                <?php if ((int)$s['admin_id'] !== (int)$_SESSION['admin_id']): ?>
                                    <form method="POST" action="<?= admin_url('security/revoke-session.php') ?>" class="inline" onsubmit="return confirm('Revoke this session? The administrator will be signed out immediately.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <button type="submit" class="cms-btn cms-btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">
                                            <span class="material-symbols-outlined text-[16px]">block</span>
                                            <span>Revoke</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[11px] text-slate-400 italic">This session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Failed Logins -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-base font-bold text-slate-900 brand-font">Recent Failed / Blocked Sign-In Attempts</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr><th>Event</th><th>Details</th><th>When</th></tr>
            </thead>
            <tbody>
                <?php if (empty($recentFailedLogins)): ?>
                    <tr><td colspan="3" class="text-center py-8 text-slate-400 text-xs">No recent failed sign-in attempts.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentFailedLogins as $log): ?>
                        <tr>
                            <td class="text-xs font-semibold text-slate-800"><?= e($log['action']) ?></td>
                            <td class="text-xs text-slate-600"><?= e($log['details']) ?></td>
                            <td class="text-xs text-slate-500"><?= date('M j, g:i A', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
