<?php
/**
 * St. Monica Junior School CMS - System Health
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'System Health';
$activeMenu = 'system';

// PHP version check
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.0.0', '>=');

// Database check
$dbConnected = false;
$dbVersion = null;
try {
    $pdo = Database::getConnection();
    $dbConnected = true;
    $dbVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Exception $e) {
    // stays false
}

// Uploads directory writable check
$uploadsDir = CMS_ROOT . '/uploads';
$uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);

// Storage usage (uploads directory size vs. free disk space)
function dir_size(string $dir): int {
    $size = 0;
    if (!is_dir($dir)) return 0;
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($items as $item) {
        if ($item->isFile()) $size += $item->getSize();
    }
    return $size;
}

$uploadsSize = dir_size($uploadsDir);
$diskFree = @disk_free_space(CMS_ROOT);
$diskTotal = @disk_total_space(CMS_ROOT);
$diskUsedPercent = ($diskTotal && $diskTotal > 0) ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : null;

// GD extension (image optimization)
$gdLoaded = extension_loaded('gd');

// Required extensions
$requiredExtensions = ['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'curl'];
$extensionStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionStatus[$ext] = extension_loaded($ext);
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">System Health</h1>
        <p class="text-sm text-slate-500 mt-1">Server, database, and storage diagnostics.</p>
    </div>
    <a href="<?= admin_url('system/api-health.php') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">network_check</span>
        <span>Check Public API Health</span>
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">PHP Version</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= e($phpVersion) ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] <?= $phpOk ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $phpOk ? 'check_circle' : 'warning' ?></span>
    </div>

    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Database</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= $dbConnected ? 'Connected (v' . e($dbVersion) . ')' : 'Connection Failed' ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] <?= $dbConnected ? 'text-emerald-600' : 'text-red-600' ?>"><?= $dbConnected ? 'check_circle' : 'error' ?></span>
    </div>

    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Upload Directory</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= $uploadsWritable ? 'Writable' : 'Not Writable' ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] <?= $uploadsWritable ? 'text-emerald-600' : 'text-red-600' ?>"><?= $uploadsWritable ? 'check_circle' : 'error' ?></span>
    </div>

    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Image Optimization (GD)</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= $gdLoaded ? 'Available' : 'Not Available' ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] <?= $gdLoaded ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $gdLoaded ? 'check_circle' : 'warning' ?></span>
    </div>

    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Media Storage Used</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= format_bytes($uploadsSize) ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] text-slate-400">folder</span>
    </div>

    <div class="cms-card p-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Disk Usage</p>
            <p class="text-sm font-bold text-slate-800 mt-1"><?= $diskUsedPercent !== null ? $diskUsedPercent . '% used' : 'Unknown' ?></p>
        </div>
        <span class="material-symbols-outlined text-[24px] <?= ($diskUsedPercent !== null && $diskUsedPercent > 90) ? 'text-red-600' : (($diskUsedPercent !== null && $diskUsedPercent > 75) ? 'text-amber-600' : 'text-emerald-600') ?>">
            <?= ($diskUsedPercent !== null && $diskUsedPercent > 75) ? 'warning' : 'check_circle' ?>
        </span>
    </div>
</div>

<div class="cms-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h2 class="text-base font-bold text-slate-900 brand-font">Required PHP Extensions</h2>
    </div>
    <div class="p-6 grid grid-cols-2 sm:grid-cols-5 gap-4">
        <?php foreach ($extensionStatus as $ext => $loaded): ?>
            <div class="text-center">
                <span class="material-symbols-outlined text-[28px] <?= $loaded ? 'text-emerald-600' : 'text-red-600' ?>"><?= $loaded ? 'check_circle' : 'cancel' ?></span>
                <p class="text-xs font-semibold text-slate-700 mt-1"><?= e($ext) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
