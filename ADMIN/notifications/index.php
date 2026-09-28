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
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$totalRows = NotificationService::totalCount($adminId);
$unreadCount = NotificationService::unreadCount($adminId);
$readCount = NotificationService::readCount($adminId);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;
$notifications = NotificationService::all($adminId, $perPage, $offset);

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Notification Center</h1>
        <p class="text-sm text-slate-500 mt-1">
            All system events, pupil applications, and website inquiries (<?= $totalRows ?> total<?php if ($unreadCount > 0): ?>, <span class="text-red-600 font-semibold"><?= $unreadCount ?> unread</span><?php endif; ?>).
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($unreadCount > 0): ?>
            <form method="POST" action="<?= admin_url('notifications/mark-all-read.php') ?>" class="inline-block">
                <?= csrf_field() ?>
                <button type="submit" class="cms-btn cms-btn-outline text-xs">
                    <span class="material-symbols-outlined text-[16px]">done_all</span>
                    <span>Mark All as Read</span>
                </button>
            </form>
        <?php endif; ?>

        <?php if ($readCount > 0): ?>
            <button type="button"
                    data-delete-btn
                    data-action="<?= admin_url('notifications/clear.php?action=clear_read') ?>"
                    data-name="all <?= $readCount ?> read notification(s)"
                    class="cms-btn cms-btn-outline text-xs text-slate-700 hover:text-red-600 border-slate-200 hover:border-red-200 hover:bg-red-50">
                <span class="material-symbols-outlined text-[16px]">cleaning_services</span>
                <span>Clear Read (<?= $readCount ?>)</span>
            </button>
        <?php endif; ?>

        <?php if ($totalRows > 0): ?>
            <button type="button"
                    data-delete-btn
                    data-action="<?= admin_url('notifications/clear.php?action=clear_all') ?>"
                    data-name="all <?= $totalRows ?> notification(s)"
                    class="cms-btn cms-btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">
                <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                <span>Delete All</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden bulk action form used by javascript -->
<form id="bulkNotifForm" method="POST" action="<?= admin_url('notifications/clear.php') ?>" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_selected">
</form>

<div class="cms-card overflow-hidden">
    <?php if (empty($notifications)): ?>
        <div class="text-center py-16 text-slate-400">
            <span class="material-symbols-outlined text-5xl text-slate-300 mb-3 block">notifications_off</span>
            <p class="font-medium text-slate-600">No notifications found.</p>
            <p class="text-xs text-slate-400 mt-1">When new admission applications, inquiries, or system alerts occur, they will appear here.</p>
        </div>
    <?php else: ?>
        <!-- Top Toolbar with Selection Controls -->
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-xs text-slate-600">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="selectAllNotifs" class="rounded border-slate-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                <span class="font-medium text-slate-700">Select All on this page</span>
            </label>
            <div id="bulkActionToolbar" class="hidden items-center gap-3">
                <span id="selectedCountText" class="font-semibold text-slate-700">0 selected</span>
                <button type="button" id="bulkDeleteBtn" class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-semibold transition shadow-sm">
                    <span class="material-symbols-outlined text-[15px]">delete</span>
                    <span>Delete Selected</span>
                </button>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            <?php foreach ($notifications as $n):
                $icon = match($n['type'] ?? '') {
                    'admission' => 'how_to_reg',
                    'enquiry'   => 'mail',
                    'content'   => 'article',
                    'testimonial', 'review' => 'rate_review',
                    'newsletter' => 'mark_email_read',
                    default     => 'notifications'
                };
                $iconStyle = match($n['type'] ?? '') {
                    'admission' => 'bg-blue-50 text-[#1e2a4a]',
                    'enquiry'   => 'bg-emerald-50 text-emerald-700',
                    'content'   => 'bg-purple-50 text-purple-700',
                    'testimonial', 'review' => 'bg-amber-50 text-amber-700',
                    'newsletter' => 'bg-sky-50 text-sky-700',
                    default     => 'bg-slate-100 text-slate-700'
                };
            ?>
                <div class="flex items-start gap-3 sm:gap-4 p-5 hover:bg-slate-50/70 transition <?= !$n['is_read'] ? 'bg-red-50/20' : '' ?>">
                    <!-- Row Checkbox -->
                    <div class="pt-2 flex-shrink-0">
                        <input type="checkbox" value="<?= (int)$n['id'] ?>" class="notif-checkbox rounded border-slate-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                    </div>

                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-lg <?= $iconStyle ?> flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-slate-900 text-sm"><?= e($n['title']) ?></p>
                            <?php if (!$n['is_read']): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">New</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($n['message'])): ?>
                            <p class="text-sm text-slate-600 mt-1 leading-relaxed"><?= e($n['message']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-2">
                            <span><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></span>
                            <?php if (!empty($n['type'])): ?>
                                <span>&bull;</span>
                                <span class="uppercase tracking-wider font-semibold text-[10px] text-slate-500"><?= e($n['type']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Row Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if (!$n['is_read']): ?>
                            <form method="POST" action="<?= admin_url('notifications/mark-read.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? admin_url('notifications/')) ?>">
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100" title="Mark as read">
                                    <span class="material-symbols-outlined text-[18px]">check</span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($n['link'])): ?>
                            <a href="<?= e($n['link']) ?>" class="cms-btn cms-btn-outline text-xs">View Details</a>
                        <?php endif; ?>

                        <button type="button"
                                data-delete-btn
                                data-action="<?= admin_url('notifications/delete.php?id=' . (int)$n['id']) ?>"
                                data-name="Notification: <?= e($n['title']) ?>"
                                class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50 transition"
                                title="Delete Notification">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-slate-100">
                <?= render_pagination($page, $totalPages, admin_url('notifications/')) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('selectAllNotifs');
    const checkboxes = document.querySelectorAll('.notif-checkbox');
    const toolbar = document.getElementById('bulkActionToolbar');
    const countText = document.getElementById('selectedCountText');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkForm = document.getElementById('bulkNotifForm');

    function updateToolbar() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        const count = checked.length;
        if (count > 0) {
            if (toolbar) {
                toolbar.classList.remove('hidden');
                toolbar.classList.add('flex');
            }
            if (countText) {
                countText.textContent = `${count} selected`;
            }
        } else {
            if (toolbar) {
                toolbar.classList.add('hidden');
                toolbar.classList.remove('flex');
            }
        }
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateToolbar();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateToolbar);
    });

    if (bulkDeleteBtn && bulkForm) {
        bulkDeleteBtn.addEventListener('click', () => {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0) return;

            if (confirm(`Are you sure you want to delete ${checked.length} selected notification(s)? This operation cannot be undone.`)) {
                // Clear any previous dynamic inputs in bulkForm
                bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
                checked.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = cb.value;
                    bulkForm.appendChild(input);
                });
                bulkForm.submit();
            }
        });
    }
});
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
