<?php
/**
 * St. Monica Junior School CMS - Admissions Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('admissions');

$pageTitle = 'Admissions Applications';
$activeMenu = 'admissions';

// Parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$classFilter = trim($_GET['class'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Metric counters
$totalCount = 0;
$newCount = 0;
$reviewCount = 0;
$acceptedCount = 0;

try {
    $totalCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions`");
    $newCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'New'");
    $reviewCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'Under Review'");
    $acceptedCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'Accepted'");
} catch (Exception $e) {
    // Database table might not be initialized
}

// Build query
$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`application_number` LIKE :s OR `pupil_name` LIKE :s OR `parent_name` LIKE :s OR `mobile` LIKE :s OR `location` LIKE :s)";
    $params['s'] = "%{$search}%";
}
if (!empty($statusFilter)) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}
if (!empty($classFilter)) {
    $where[] = "`pupil_class` = :class";
    $params['class'] = $classFilter;
}
if (!empty($dateFrom)) {
    $where[] = "DATE(`submitted_at`) >= :date_from";
    $params['date_from'] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(`submitted_at`) <= :date_to";
    $params['date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$applications = [];

try {
    $totalRows = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE {$whereSql}", $params);
    $applications = Database::fetchAll("SELECT * FROM `admissions` WHERE {$whereSql} ORDER BY `submitted_at` DESC, `id` DESC LIMIT {$perPage} OFFSET {$offset}", $params);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$totalPages = ceil($totalRows / $perPage);

// Classes list for filter
$classesList = [
    'Baby Class', 'Middle Class', 'Top Class',
    'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4',
    'Primary 5', 'Primary 6', 'Primary 7'
];

$statusesList = ['New', 'Under Review', 'Contacted', 'Accepted', 'Rejected', 'Withdrawn'];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Admissions Applications</h1>
        <p class="text-sm text-slate-500 mt-1">Review, process, and manage incoming pupil admission applications.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('admissions/export.php?' . http_build_query($_GET)) ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">file_download</span>
            <span>Export CSV</span>
        </a>
        <a href="<?= admin_url('admission-info/') ?>" class="cms-btn cms-btn-primary text-xs">
            <span class="material-symbols-outlined text-[16px]">menu_book</span>
            <span>Admission Information</span>
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="cms-card p-4 border-l-4 border-l-[#1e2a4a]">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Applications</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5"><?= $totalCount ?></h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-slate-100 text-[#1e2a4a] flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">inbox</span>
            </div>
        </div>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-red-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">New (Pending)</p>
                <h3 class="text-2xl font-bold text-red-600 mt-0.5"><?= $newCount ?></h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">mark_email_unread</span>
            </div>
        </div>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-amber-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Under Review</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-0.5"><?= $reviewCount ?></h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">pending_actions</span>
            </div>
        </div>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-emerald-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Accepted</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-0.5"><?= $acceptedCount ?></h3>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">verified</span>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('admissions/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-4 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name, app #, parent or phone..."
                   class="cms-input pl-10 text-sm">
        </div>

        <div class="sm:col-span-2">
            <select name="status" class="cms-select text-sm">
                <option value="">All Statuses</option>
                <?php foreach ($statusesList as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sm:col-span-2">
            <select name="class" class="cms-select text-sm">
                <option value="">All Classes</option>
                <?php foreach ($classesList as $c): ?>
                    <option value="<?= $c ?>" <?= $classFilter === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sm:col-span-1">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" title="Submitted From" class="cms-input text-xs">
        </div>
        <div class="sm:col-span-1">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" title="Submitted To" class="cms-input text-xs">
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="cms-btn cms-btn-primary flex-1 text-xs">Filter</button>
            <?php if ($search || $statusFilter || $classFilter || $dateFrom || $dateTo): ?>
                <a href="<?= admin_url('admissions/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Applications Table -->
<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Submitted Applications (<?= $totalRows ?>)</h2>
        <span class="text-xs text-slate-400">Sorted by submission date</span>
    </div>

    <div class="overflow-x-auto">
        <table class="cms-table">
            <thead>
                <tr>
                    <th style="width: 140px;">App Number</th>
                    <th>Pupil Name</th>
                    <th>Class</th>
                    <th>Parent / Contact</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">assignment_late</span>
                            No applications found matching your search and filter criteria.<br>
                            Applications submitted through the public website will appear here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): 
                        $statusBadge = match($app['status']) {
                            'New'          => 'bg-red-100 text-red-700 border border-red-200',
                            'Under Review' => 'bg-amber-100 text-amber-800 border border-amber-200',
                            'Contacted'    => 'bg-blue-100 text-blue-800 border border-blue-200',
                            'Accepted'     => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                            'Rejected'     => 'bg-slate-100 text-slate-700 border border-slate-200',
                            'Withdrawn'    => 'bg-purple-100 text-purple-800 border border-purple-200',
                            default        => 'bg-slate-100 text-slate-700'
                        };
                    ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td>
                                <a href="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="font-bold text-[#1e2a4a] hover:text-red-600 text-xs">
                                    <?= e($app['application_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm"><?= e($app['pupil_name']) ?></div>
                            </td>
                            <td>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                    <?= e($app['pupil_class']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-xs font-medium text-slate-800"><?= e($app['parent_name']) ?></div>
                                <div class="text-[11px] text-slate-500 font-mono"><?= e($app['mobile']) ?></div>
                            </td>
                            <td>
                                <span class="text-xs text-slate-600 truncate max-w-[150px] block" title="<?= e($app['location']) ?>">
                                    <?= e($app['location']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1 <?= $statusBadge ?>">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?= e($app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-xs text-slate-500">
                                    <?= date('M j, Y', strtotime($app['submitted_at'])) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Review Application">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </a>
                                    <button type="button" data-delete-btn data-action="<?= admin_url('admissions/delete.php?id=' . $app['id']) ?>" data-name="Application <?= e($app['application_number']) ?> (<?= e($app['pupil_name']) ?>)" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
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

    <!-- Pagination -->
    <?= render_pagination($page, $totalPages, admin_url('admissions/'), array_filter(['search' => $search, 'status' => $statusFilter, 'class' => $classFilter, 'date_from' => $dateFrom, 'date_to' => $dateTo])) ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
