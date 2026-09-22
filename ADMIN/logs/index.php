<?php
/**
 * St. Monica Junior School CMS - System Activity & Audit Logs
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'System Audit Logs';
$activeMenu = 'logs';

$search       = trim($_GET['search'] ?? '');
$moduleFilter = trim($_GET['module'] ?? '');
$adminFilter  = trim($_GET['admin'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 25;

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`action` LIKE :s OR `details` LIKE :s OR `admin_name` LIKE :s)";
    $params['s'] = "%{$search}%";
}

if (!empty($moduleFilter)) {
    $where[] = "`module` = :module";
    $params['module'] = $moduleFilter;
}

if (!empty($adminFilter)) {
    $where[] = "`admin_name` = :admin";
    $params['admin'] = $adminFilter;
}

$whereSql = implode(' AND ', $where);

// Total count
$totalCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `activity_logs` WHERE {$whereSql}", $params);
$totalPages = max(1, ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

// Fetch logs
$logs = Database::fetchAll(
    "SELECT * FROM `activity_logs` 
     WHERE {$whereSql} 
     ORDER BY `created_at` DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// Fetch distinct modules and admins for filter dropdowns
try {
    $availableModules = Database::fetchColumnAll("SELECT DISTINCT `module` FROM `activity_logs` WHERE `module` IS NOT NULL AND `module` != '' ORDER BY `module` ASC");
    $availableAdmins  = Database::fetchColumnAll("SELECT DISTINCT `admin_name` FROM `activity_logs` WHERE `admin_name` IS NOT NULL AND `admin_name` != '' ORDER BY `admin_name` ASC");
} catch (Throwable $e) {
    $availableModules = [];
    $availableAdmins  = [];
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">System Audit & Activity Logs</h1>
        <p class="text-sm text-slate-500 mt-1">Trace administrative actions, content updates, logins, and admission status changes.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700">
            Total Recorded Events: <strong><?= number_format($totalCount) ?></strong>
        </span>
    </div>
</div>

<!-- Filters Bar -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('logs/') ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="search">Keyword Search</label>
            <input type="text" id="search" name="search" value="<?= e($search) ?>" placeholder="Search action, details, user..." class="cms-input text-xs">
        </div>

        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="module">Filter by Module</label>
            <select id="module" name="module" class="cms-select text-xs">
                <option value="">All Modules</option>
                <?php foreach ($availableModules as $mod): ?>
                    <option value="<?= e($mod) ?>" <?= $moduleFilter === $mod ? 'selected' : '' ?>>
                        <?= e(ucfirst(str_replace('_', ' ', $mod))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1" for="admin">Administrator</label>
            <select id="admin" name="admin" class="cms-select text-xs">
                <option value="">All Staff</option>
                <?php foreach ($availableAdmins as $adm): ?>
                    <option value="<?= e($adm) ?>" <?= $adminFilter === $adm ? 'selected' : '' ?>>
                        <?= e($adm) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="cms-btn cms-btn-accent text-xs flex-1">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                <span>Filter Logs</span>
            </button>
            <?php if (!empty($search) || !empty($moduleFilter) || !empty($adminFilter)): ?>
                <a href="<?= admin_url('logs/') ?>" class="cms-btn cms-btn-outline text-xs px-2.5" title="Reset Filters">
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="cms-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="cms-table text-xs">
            <thead>
                <tr>
                    <th style="width: 170px;">Timestamp</th>
                    <th style="width: 160px;">Administrator</th>
                    <th style="width: 130px;">Module</th>
                    <th>Action</th>
                    <th>Audit Details</th>
                    <th style="width: 80px;" class="text-right">Ref ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">
                            <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">history</span>
                            No activity records match your filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $mod = $log['module'] ?? 'system';
                        $modBadgeColor = match($mod) {
                            'admissions' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'news_events', 'news' => 'bg-red-100 text-red-800 border-red-200',
                            'media' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'testimonials' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'seo' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'users', 'auth' => 'bg-purple-100 text-purple-800 border-purple-200',
                            default => 'bg-slate-100 text-slate-800 border-slate-200'
                        };
                    ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="font-mono text-slate-500 whitespace-nowrap">
                                <?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-900"><?= e($log['admin_name'] ?? 'System') ?></div>
                                <div class="text-[10px] text-slate-400 font-mono">ID: #<?= $log['admin_id'] ?? '—' ?></div>
                            </td>
                            <td>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border <?= $modBadgeColor ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $mod))) ?>
                                </span>
                            </td>
                            <td>
                                <span class="font-bold text-slate-800"><?= e($log['action']) ?></span>
                            </td>
                            <td class="text-slate-600 max-w-md break-words">
                                <?= e($log['details'] ?? '—') ?>
                            </td>
                            <td class="text-right font-mono text-slate-400">
                                <?= !empty($log['record_id']) ? '#' . $log['record_id'] : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500">
                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalCount) ?> of <?= number_format($totalCount) ?> entries
            </span>
            <div class="flex items-center gap-1">
                <?= render_pagination($page, $totalPages, admin_url('logs/'), [
                    'search' => $search,
                    'module' => $moduleFilter,
                    'admin'  => $adminFilter
                ]) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
