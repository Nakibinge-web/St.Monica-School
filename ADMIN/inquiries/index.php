<?php
/**
 * St. Monica Junior School CMS - Contact Enquiries Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('inquiries');

$pageTitle = 'Contact Enquiries';
$activeMenu = 'inquiries';

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$totalCount = 0;
$newCount = 0;
$repliedCount = 0;

try {
    $totalCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `enquiries`");
    $newCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `enquiries` WHERE `status` = 'new'");
    $repliedCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `enquiries` WHERE `status` = 'replied'");
} catch (Exception $e) {
    // Table might not be initialized yet
}

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`name` LIKE :s OR `email` LIKE :s OR `subject` LIKE :s OR `message` LIKE :s)";
    $params['s'] = "%{$search}%";
}
if (!empty($statusFilter)) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$enquiries = [];

try {
    $totalRows = (int)Database::fetchColumn("SELECT COUNT(*) FROM `enquiries` WHERE {$whereSql}", $params);
    $enquiries = Database::fetchAll("SELECT * FROM `enquiries` WHERE {$whereSql} ORDER BY `submitted_at` DESC LIMIT {$perPage} OFFSET {$offset}", $params);
} catch (Exception $e) {
    // ignore
}

$totalPages = ceil($totalRows / $perPage);
$statusesList = ['new', 'read', 'replied', 'archived'];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Contact Enquiries</h1>
        <p class="text-sm text-slate-500 mt-1">Messages submitted through the public website contact form.</p>
    </div>
</div>

<!-- Metrics -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="cms-card p-4 border-l-4 border-l-[#1e2a4a]">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Enquiries</p>
        <h3 class="text-2xl font-bold text-slate-900 mt-0.5"><?= $totalCount ?></h3>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-red-600">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">New (Unread)</p>
        <h3 class="text-2xl font-bold text-red-600 mt-0.5"><?= $newCount ?></h3>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-emerald-600">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Replied</p>
        <h3 class="text-2xl font-bold text-emerald-600 mt-0.5"><?= $repliedCount ?></h3>
    </div>
</div>

<!-- Search & Filters -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('inquiries/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-7 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, subject or message..."
                   class="cms-input pl-10 text-sm">
        </div>
        <div class="sm:col-span-3">
            <select name="status" class="cms-select text-sm">
                <option value="">All Statuses</option>
                <?php foreach ($statusesList as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="cms-btn cms-btn-primary flex-1 text-xs">Filter</button>
            <?php if ($search || $statusFilter): ?>
                <a href="<?= admin_url('inquiries/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Enquiries Table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Messages (<?= $totalRows ?>)</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enquiries)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">mail</span>
                            No enquiries found matching your search and filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($enquiries as $item):
                        $statusBadge = match($item['status']) {
                            'new'      => 'bg-red-100 text-red-700 border border-red-200',
                            'read'     => 'bg-blue-100 text-blue-800 border border-blue-200',
                            'replied'  => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                            'archived' => 'bg-slate-100 text-slate-700 border border-slate-200',
                            default    => 'bg-slate-100 text-slate-700'
                        };
                    ?>
                        <tr class="hover:bg-slate-50/80 transition <?= $item['status'] === 'new' ? 'font-semibold' : '' ?>">
                            <td>
                                <div class="text-sm text-slate-900"><?= e($item['name']) ?></div>
                                <div class="text-[11px] text-slate-500"><?= e($item['email']) ?></div>
                            </td>
                            <td>
                                <a href="<?= admin_url('inquiries/view.php?id=' . $item['id']) ?>" class="text-sm text-[#1e2a4a] hover:text-red-600 max-w-xs truncate block">
                                    <?= e($item['subject']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $statusBadge ?>"><?= e(ucfirst($item['status'])) ?></span>
                            </td>
                            <td>
                                <span class="text-xs text-slate-500"><?= date('M j, Y g:i A', strtotime($item['submitted_at'])) ?></span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= admin_url('inquiries/view.php?id=' . $item['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="View Message">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('inquiries/delete.php?id=' . $item['id']) ?>" data-name="Enquiry from <?= e($item['name']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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

    <?= render_pagination($page, $totalPages, admin_url('inquiries/'), array_filter(['search' => $search, 'status' => $statusFilter])) ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
