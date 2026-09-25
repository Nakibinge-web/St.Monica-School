<?php
/**
 * St. Monica Junior School CMS - Notification Center
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('notifications');
require_once CMS_ROOT . '/services/NotificationService.php';

$pageTitle = 'Notifications';
$activeMenu = 'notifications';

$adminId = (int)($_SESSION['admin_id'] ?? 0);
$notifications = NotificationService::recent($adminId, 50);

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Notifications</h1>
        <p class="text-sm text-slate-500 mt-1">System and content alerts for your account.</p>
    </div>
    <form method="POST" action="<?= admin_url('notifications/mark-all-read.php') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">done_all</span>
            <span>Mark All as Read</span>
        </button>
    </form>
</div>

<div class="cms-card overflow-hidden">
    <?php if (empty($notifications)): ?>
        <div class="text-center py-16 text-slate-400">
            <span class="material-symbols-outlined text-5xl text-slate-300 mb-3 block">notifications_off</span>
            No notifications yet.
        </div>
    <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($notifications as $n):
                $icon = match($n['type']) {
                    'admission' => 'how_to_reg',
                    'enquiry'   => 'mail',
                    'content'   => 'article',
                    default     => 'notifications'
                };
            ?>
                <div class="flex items-start gap-4 p-5 <?= !$n['is_read'] ? 'bg-red-50/40' : '' ?>">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-[#1e2a4a] flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-900 text-sm"><?= e($n['title']) ?></p>
                            <?php if (!$n['is_read']): ?>
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($n['message'])): ?>
                            <p class="text-sm text-slate-600 mt-0.5"><?= e($n['message']) ?></p>
                        <?php endif; ?>
                        <p class="text-[11px] text-slate-400 mt-1"><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></p>
                    </div>
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?= e($n['link']) ?>" class="cms-btn cms-btn-outline text-xs flex-shrink-0">View</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
