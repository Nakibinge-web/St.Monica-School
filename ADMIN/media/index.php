<?php
/**
 * St. Monica Junior School CMS - Central Media Library
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

$pageTitle = 'Central Media Library';
$activeMenu = 'media';

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 16;

$where = ["1 = 1"];
$params = [];

if ($search !== '') {
    $where[] = "(`title` LIKE :s1 OR `alt_text` LIKE :s2 OR `caption` LIKE :s3 OR `file_path` LIKE :s4)";
    $params += ['s1' => "%{$search}%", 's2' => "%{$search}%", 's3' => "%{$search}%", 's4' => "%{$search}%"];
}
if (!empty($typeFilter)) {
    $where[] = "`file_type` = :type";
    $params['type'] = $typeFilter;
}
if (!empty($categoryFilter)) {
    $where[] = "`category` = :cat";
    $params['cat'] = $categoryFilter;
}
if (!empty($dateFrom)) {
    $where[] = "DATE(`created_at`) >= :date_from";
    $params['date_from'] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(`created_at`) <= :date_to";
    $params['date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$mediaItems = [];

try {
    $totalRows = (int)Database::fetchColumn("SELECT COUNT(*) FROM `media_library` WHERE {$whereSql}", $params);
    $mediaItems = Database::fetchAll(
        "SELECT * FROM `media_library` WHERE {$whereSql} ORDER BY `id` DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    );
} catch (Exception $e) {
    // Error handling
}

$totalPages = ceil($totalRows / $perPage);

// format_bytes() and get_media_usage() are defined in includes/functions.php,
// shared with media/delete.php and media/cleanup.php.

$categories = media_categories();

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Central Media Library</h1>
        <p class="text-sm text-slate-500 mt-1">Upload, search, optimize, and manage reusable school photos, videos, and media assets.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('media/cleanup.php') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">cleaning_services</span>
            <span>Cleanup Tool</span>
        </a>
        <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">photo_library</span>
            <span>Gallery Albums</span>
        </a>
        <a href="<?= admin_url('media/upload.php') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
            <span>Upload New Media</span>
        </a>
    </div>
</div>

<!-- Search & Filters Bar -->
<div class="cms-card p-4 mb-6">
    <form method="GET" action="<?= admin_url('media/') ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-4 relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by title, alt text, or filename..."
                   class="cms-input pl-10 text-sm">
        </div>

        <div class="sm:col-span-2">
            <select name="type" class="cms-select text-sm">
                <option value="">All Media Types</option>
                <option value="image" <?= $typeFilter === 'image' ? 'selected' : '' ?>>Images Only</option>
                <option value="video" <?= $typeFilter === 'video' ? 'selected' : '' ?>>Videos Only</option>
                <option value="document" <?= $typeFilter === 'document' ? 'selected' : '' ?>>Documents Only</option>
            </select>
        </div>

        <div class="sm:col-span-2">
            <select name="category" class="cms-select text-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="sm:col-span-1">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" title="Uploaded From" class="cms-input text-xs">
        </div>
        <div class="sm:col-span-1">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" title="Uploaded To" class="cms-input text-xs">
        </div>

        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="cms-btn cms-btn-primary flex-1 text-xs">Filter</button>
            <?php if ($search || $typeFilter || $categoryFilter || $dateFrom || $dateTo): ?>
                <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs" title="Clear Filters">
                    <span class="material-symbols-outlined text-[16px]">clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Media Grid -->
<div class="cms-card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900 brand-font">Media Assets (<?= $totalRows ?>)</h2>
        <span class="text-xs text-slate-400">Showing page <?= $page ?> of <?= max(1, $totalPages) ?></span>
    </div>

    <?php if (empty($mediaItems)): ?>
        <div class="p-12 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl mb-2 text-slate-300 block">perm_media</span>
            <p class="text-base font-semibold text-slate-700">No media assets found.</p>
            <p class="text-xs text-slate-400 mt-1">Upload school photos or banners with alt text and optimization.</p>
            <div class="mt-4">
                <a href="<?= admin_url('media/upload.php') ?>" class="cms-btn cms-btn-accent text-xs">Upload Media Now</a>
            </div>
        </div>
    <?php else: ?>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($mediaItems as $item): 
                $usages = get_media_usage($item['file_path']);
            ?>
                <div class="cms-card overflow-hidden flex flex-col justify-between group border border-slate-200">
                    <!-- Media Preview Area -->
                    <div class="relative h-44 bg-slate-900/5 overflow-hidden flex items-center justify-center">
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="<?= public_url($item['file_path']) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <?php elseif ($item['file_type'] === 'video'): ?>
                            <div class="text-center p-4">
                                <span class="material-symbols-outlined text-4xl text-red-600 mb-1">play_circle</span>
                                <span class="text-xs font-semibold block text-slate-700">Video Asset</span>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <span class="material-symbols-outlined text-4xl text-blue-600 mb-1">description</span>
                                <span class="text-xs font-semibold block text-slate-700">Document</span>
                            </div>
                        <?php endif; ?>

                        <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900/80 text-white backdrop-blur-sm">
                            <?= e($item['category']) ?>
                        </span>

                        <?php if ($item['alt_text']): ?>
                            <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white shadow-sm" title="Alt Text: <?= e($item['alt_text']) ?>">
                                ALT
                            </span>
                        <?php else: ?>
                            <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white shadow-sm" title="Missing Alt Text">
                                NO ALT
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Metadata Details -->
                    <div class="p-4 flex-1 flex flex-col justify-between text-xs">
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm truncate" title="<?= e($item['title']) ?>">
                                <?= e($item['title']) ?>
                            </h3>
                            <?php if ($item['caption']): ?>
                                <p class="text-slate-500 line-clamp-1 mt-0.5"><?= e($item['caption']) ?></p>
                            <?php endif; ?>

                            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                <span><?= format_bytes($item['file_size']) ?></span>
                                <span><?= e($item['dimensions'] ?: strtoupper($item['file_type'])) ?></span>
                            </div>

                            <!-- Usage Badges -->
                            <div class="mt-2 flex flex-wrap gap-1">
                                <?php if (!empty($usages)): ?>
                                    <?php foreach ($usages as $u): ?>
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-semibold rounded">
                                            <?= e($u) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-400 italic">Unlinked / Standalone</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" onclick="navigator.clipboard.writeText('<?= public_url($item['file_path']) ?>'); alert('Public file URL copied to clipboard!');" 
                                    class="text-slate-500 hover:text-slate-800 p-1 rounded hover:bg-slate-100 inline-flex items-center gap-1 text-[11px]" title="Copy Public URL">
                                <span class="material-symbols-outlined text-[16px]">content_copy</span>
                                <span>Copy Link</span>
                            </button>

                            <div class="flex items-center gap-1">
                                <a href="<?= admin_url('media/edit.php?id=' . $item['id']) ?>" class="p-1 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit Metadata">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                                <button type="button" data-delete-btn data-action="<?= admin_url('media/delete.php?id=' . $item['id']) ?>" data-name="<?= e($item['title']) ?>" class="p-1 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?= render_pagination($page, $totalPages, admin_url('media/'), array_filter(['search' => $search, 'type' => $typeFilter, 'category' => $categoryFilter, 'date_from' => $dateFrom, 'date_to' => $dateTo])) ?>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
