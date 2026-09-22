<?php
/**
 * St. Monica Junior School CMS - Media Gallery Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'Media Gallery Management';
$activeMenu = 'gallery';

$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ['1 = 1'];
$params = [];

if (!empty($categoryFilter)) {
    $where[] = "`category` = :category";
    $params['category'] = $categoryFilter;
}
if (in_array($statusFilter, ['published', 'draft'])) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);
$images = Database::fetchAll("SELECT * FROM `gallery` WHERE {$whereSql} ORDER BY `display_order` ASC, `id` DESC", $params);

// Categories list
$categories = ['Campus Life', 'Academics', 'Co-curricular Activities', 'Special Events', 'Administration'];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Photo & Media Gallery</h1>
        <p class="text-sm text-slate-500 mt-1">Manage school photos, gallery albums, and categorize media.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= public_url('gallery.html') ?>" target="_blank" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">visibility</span>
            <span>View Public Gallery</span>
        </a>
        <a href="<?= admin_url('gallery/upload.php') ?>" class="cms-btn cms-btn-accent text-xs">
            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
            <span>Upload Images</span>
        </a>
    </div>
</div>

<!-- Category & Status Filter Pills -->
<div class="cms-card p-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= admin_url('gallery/') ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold <?= empty($categoryFilter) ? 'bg-[#1e2a4a] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?> transition">
            All Photos (<?= (int)Database::fetchColumn("SELECT COUNT(*) FROM `gallery`") ?>)
        </a>
        <?php foreach ($categories as $cat): 
            $catCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `gallery` WHERE `category` = :cat", ['cat' => $cat]);
        ?>
            <a href="<?= admin_url('gallery/?category=' . urlencode($cat)) ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold <?= $categoryFilter === $cat ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?> transition">
                <?= e($cat) ?> (<?= $catCount ?>)
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Gallery Grid -->
<?php if (empty($images)): ?>
    <div class="cms-card p-12 text-center text-slate-400">
        <span class="material-symbols-outlined text-5xl mb-2 text-slate-300">photo_library</span>
        <p class="text-base font-semibold text-slate-600">No gallery images found.</p>
        <p class="text-xs text-slate-400 mt-1">Upload photos to display them in this category.</p>
        <div class="mt-4">
            <a href="<?= admin_url('gallery/upload.php') ?>" class="cms-btn cms-btn-accent text-xs">Upload Photo Now</a>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($images as $img): ?>
            <div class="cms-card overflow-hidden group flex flex-col justify-between">
                <div>
                    <!-- Photo Thumbnail -->
                    <div class="relative h-44 overflow-hidden bg-slate-100 border-b border-slate-100">
                        <img src="<?= public_url($img['file_path']) ?>" alt="<?= e($img['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <span class="absolute top-2.5 left-2.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-900/80 text-white backdrop-blur-sm">
                            <?= e($img['category']) ?>
                        </span>
                        <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded text-[10px] font-bold <?= $img['status'] === 'published' ? 'bg-emerald-600 text-white' : 'bg-slate-600 text-white' ?>">
                            <?= ucfirst($img['status']) ?>
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="p-4">
                        <h3 class="font-bold text-slate-900 text-sm truncate"><?= e($img['title']) ?></h3>
                        <?php if ($img['description']): ?>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-1"><?= e($img['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="p-4 pt-0 flex items-center justify-between border-t border-slate-50 text-xs text-slate-400">
                    <span>Order: #<?= e($img['display_order']) ?></span>
                    <div class="flex items-center gap-1">
                        <a href="<?= admin_url('gallery/edit.php?id=' . $img['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </a>
                        <button type="button" data-delete-btn data-action="<?= admin_url('gallery/delete.php?id=' . $img['id']) ?>" data-name="<?= e($img['title']) ?>" class="p-1.5 text-slate-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
