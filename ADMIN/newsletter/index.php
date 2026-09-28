<?php
/**
 * St. Monica Junior School CMS - Newsletter Subscribers
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('newsletter');

$pageTitle = 'Newsletter Subscribers';
$activeMenu = 'newsletter';
$newsletterTab = 'subscribers';

$search = trim($_GET['search'] ?? '');
$statusFilter = in_array($_GET['status'] ?? '', ['subscribed', 'unsubscribed'], true) ? $_GET['status'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1 = 1'];
$params = [];
if ($search !== '') {
    $where[] = '(`email` LIKE :s1 OR `source` LIKE :s2)';
    $params += ['s1' => "%{$search}%", 's2' => "%{$search}%"];
}
if ($statusFilter !== '') {
    $where[] = '`status` = :status';
    $params['status'] = $statusFilter;
}
$whereSql = implode(' AND ', $where);
$offset = ($page - 1) * $perPage;

$stats = ['active' => 0, 'month' => 0, 'unsubscribed' => 0, 'sent' => 0];
$subscribers = [];
$totalRows = 0;
try {
    $stats['active'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_subscribers` WHERE `status` = 'subscribed'");
    $stats['month'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_subscribers` WHERE `status` = 'subscribed' AND `subscribed_at` >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stats['unsubscribed'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_subscribers` WHERE `status` = 'unsubscribed'");
    $stats['sent'] = (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_campaigns`");

    $totalRows = (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_subscribers` WHERE {$whereSql}", $params);
    $subscribers = Database::fetchAll(
        "SELECT * FROM `newsletter_subscribers` WHERE {$whereSql} ORDER BY `subscribed_at` DESC, `id` DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    );
} catch (Exception $e) {
    $dbError = 'The newsletter tables are not set up yet. Run the database setup to apply migration 024.';
}
$totalPages = (int)ceil($totalRows / $perPage);
$currentUrl = $_SERVER['REQUEST_URI'] ?? admin_url('newsletter/');

include CMS_ROOT . '/includes/header.php';
include __DIR__ . '/_tabs.php';
?>

<?php if (!empty($dbError)): ?>
    <div class="mb-6 p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-sm"><?= e($dbError) ?></div>
<?php endif; ?>

<!-- Summary -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ([
        ['Active subscribers', $stats['active'], 'group', 'text-[#1e2a4a] bg-blue-50'],
        ['New this month', $stats['month'], 'person_add', 'text-emerald-700 bg-emerald-50'],
        ['Unsubscribed', $stats['unsubscribed'], 'person_remove', 'text-slate-600 bg-slate-100'],
        ['Newsletters sent', $stats['sent'], 'outbox', 'text-red-600 bg-red-50'],
    ] as [$label, $value, $icon, $style]): ?>
        <div class="cms-card p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"><?= $label ?></p>
                <p class="text-2xl font-bold text-slate-900 mt-1"><?= number_format($value) ?></p>
            </div>
            <span class="w-11 h-11 rounded-xl flex items-center justify-center <?= $style ?>">
                <span class="material-symbols-outlined text-[24px]"><?= $icon ?></span>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="cms-card p-4 mb-6 flex flex-col lg:flex-row gap-3 lg:items-center justify-between">
    <form method="GET" action="<?= admin_url('newsletter/') ?>" class="flex flex-col sm:flex-row gap-2 flex-1">
        <div class="relative flex-1">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by email address..." class="cms-input pl-10 text-sm">
        </div>
        <select name="status" class="cms-select text-sm sm:w-44">
            <option value="">All statuses</option>
            <option value="subscribed" <?= $statusFilter === 'subscribed' ? 'selected' : '' ?>>Subscribed</option>
            <option value="unsubscribed" <?= $statusFilter === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
        </select>
        <button type="submit" class="cms-btn cms-btn-primary text-xs">Filter</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="<?= admin_url('newsletter/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear filters">
                <span class="material-symbols-outlined text-[16px]">clear</span>
            </a>
        <?php endif; ?>
    </form>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="cms-btn cms-btn-outline text-xs" onclick="document.getElementById('addSubscriberBox').classList.toggle('hidden'); document.getElementById('addSubscriberEmail').focus();">
            <span class="material-symbols-outlined text-[16px]">person_add</span>
            <span>Add Subscriber</span>
        </button>
        <a href="<?= admin_url('newsletter/export.php' . ($statusFilter ? '?status=' . $statusFilter : '')) ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">download</span>
            <span>Export CSV</span>
        </a>
        <a href="<?= admin_url('newsletter/compose.php') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">send</span>
            <span>Compose Newsletter</span>
        </a>
    </div>
</div>

<!-- Add subscriber -->
<div id="addSubscriberBox" class="hidden cms-card p-4 mb-6">
    <form method="POST" action="<?= admin_url('newsletter/actions.php') ?>" class="flex flex-col sm:flex-row gap-2 sm:items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="redirect" value="<?= e($currentUrl) ?>">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="addSubscriberEmail">Email address</label>
            <input type="email" id="addSubscriberEmail" name="email" required maxlength="190" placeholder="parent@example.com" class="cms-input">
            <p class="text-[11px] text-slate-400 mt-1">Only add people who have agreed to receive the school newsletter.</p>
        </div>
        <button type="submit" class="cms-btn cms-btn-primary text-xs sm:mb-5">Add</button>
    </form>
</div>

<!-- Subscribers table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Subscribers (<?= number_format($totalRows) ?>)</h2>
        <span class="text-xs text-slate-400">Newest first</span>
    </div>
    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Signed up from</th>
                    <th>Subscribed</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">mark_email_unread</span>
                            <?= ($search !== '' || $statusFilter !== '')
                                ? 'No subscribers match your filters.'
                                : 'No subscribers yet. Visitors can subscribe from the "Newsletter" box at the bottom of every page of the website.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $s): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="font-semibold text-slate-900 text-sm break-all"><?= e($s['email']) ?></td>
                            <td>
                                <?php if ($s['status'] === 'subscribed'): ?>
                                    <span class="cms-badge badge-published whitespace-nowrap">Subscribed</span>
                                <?php else: ?>
                                    <span class="cms-badge badge-draft whitespace-nowrap" title="Unsubscribed <?= e($s['unsubscribed_at'] ? date('M j, Y', strtotime($s['unsubscribed_at'])) : '') ?>">Unsubscribed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-xs text-slate-500"><?= e($s['source'] === 'admin' ? 'Added by admin' : ($s['source'] ?: 'Website')) ?></td>
                            <td class="text-xs text-slate-500 whitespace-nowrap"><?= date('M j, Y', strtotime($s['subscribed_at'])) ?></td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <form method="POST" action="<?= admin_url('newsletter/actions.php') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                        <input type="hidden" name="redirect" value="<?= e($currentUrl) ?>">
                                        <?php if ($s['status'] === 'subscribed'): ?>
                                            <input type="hidden" name="action" value="unsubscribe">
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-amber-700 rounded hover:bg-amber-50" title="Unsubscribe">
                                                <span class="material-symbols-outlined text-[18px]">unsubscribe</span>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="resubscribe">
                                            <button type="submit" class="p-1.5 text-slate-500 hover:text-emerald-700 rounded hover:bg-emerald-50" title="Re-subscribe (only if they asked to be added back)">
                                                <span class="material-symbols-outlined text-[18px]">mark_email_read</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('newsletter/actions.php?action=delete&id=' . (int)$s['id']) ?>" data-name="<?= e($s['email']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Remove permanently">
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
    <?= render_pagination($page, $totalPages, admin_url('newsletter/'), array_filter(['search' => $search, 'status' => $statusFilter])) ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
