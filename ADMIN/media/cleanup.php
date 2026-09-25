<?php
/**
 * St. Monica Junior School CMS - Media Cleanup Tool
 * Identifies unused, missing, and duplicate media library files.
 * Never deletes automatically - every removal requires explicit admin confirmation.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

$pageTitle = 'Media Cleanup Tool';
$activeMenu = 'media';

// Handle bulk confirmed deletion of selected media_library rows
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $selectedIds = array_map('intval', $_POST['ids'] ?? []);
    $deletedCount = 0;

    foreach ($selectedIds as $mid) {
        try {
            $item = Database::fetchOne("SELECT * FROM `media_library` WHERE `id` = :id", ['id' => $mid]);
            if (!$item) continue;

            Database::delete('media_library', 'id = :id', ['id' => $mid]);

            if (!empty($item['file_path']) && str_contains($item['file_path'], 'ADMIN/uploads/')) {
                $fullPath = dirname(CMS_ROOT) . '/' . ltrim($item['file_path'], '/');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            $deletedCount++;
        } catch (Exception $e) {
            // Continue processing remaining selections
        }
    }

    if ($deletedCount > 0) {
        log_activity('Media Cleanup', "Removed {$deletedCount} media asset(s) via cleanup tool", 'media');
        set_flash('success', "Removed {$deletedCount} media asset(s).");
    } else {
        set_flash('warning', 'No media assets were selected for removal.');
    }
    redirect(admin_url('media/cleanup.php'));
}

// Scan for issues
$allMedia = [];
try {
    $allMedia = Database::fetchAll("SELECT * FROM `media_library` ORDER BY `id` ASC");
} catch (Exception $e) {
    // ignore
}

$unused = [];
$missing = [];
$duplicateGroups = [];
$hashMap = [];

foreach ($allMedia as $item) {
    $fullPath = dirname(CMS_ROOT) . '/' . ltrim($item['file_path'], '/');

    if (!file_exists($fullPath)) {
        $missing[] = $item;
        continue; // Can't hash a file that isn't there
    }

    $usages = get_media_usage($item['file_path']);
    if (empty($usages)) {
        $unused[] = $item;
    }

    // Only hash reasonably-sized files to keep the scan fast
    if ($item['file_size'] > 0 && $item['file_size'] <= 20 * 1024 * 1024) {
        $hash = @md5_file($fullPath);
        if ($hash) {
            $hashMap[$hash][] = $item;
        }
    }
}

foreach ($hashMap as $hash => $items) {
    if (count($items) > 1) {
        $duplicateGroups[] = $items;
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Media Cleanup Tool</h1>
        <p class="text-sm text-slate-500 mt-1">Identify unused, missing, and duplicate media files. Nothing is deleted automatically - review and confirm below.</p>
    </div>
    <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to Media Library</span>
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="cms-card p-4 border-l-4 border-l-amber-500">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Unused Files</p>
        <h3 class="text-2xl font-bold text-amber-600 mt-0.5"><?= count($unused) ?></h3>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-red-600">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Missing / Broken</p>
        <h3 class="text-2xl font-bold text-red-600 mt-0.5"><?= count($missing) ?></h3>
    </div>
    <div class="cms-card p-4 border-l-4 border-l-indigo-600">
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Duplicate Groups</p>
        <h3 class="text-2xl font-bold text-indigo-600 mt-0.5"><?= count($duplicateGroups) ?></h3>
    </div>
</div>

<form method="POST" action="<?= admin_url('media/cleanup.php') ?>" onsubmit="return confirm('Permanently delete the selected media assets? This cannot be undone.');">
    <?= csrf_field() ?>

    <!-- Unused Media -->
    <div class="cms-card overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 brand-font flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">visibility_off</span>
                Unused Media <span class="text-xs font-normal text-slate-400">- not referenced by any published content</span>
            </h2>
        </div>
        <?php if (empty($unused)): ?>
            <p class="p-6 text-sm text-slate-400 text-center">No unused media detected. Everything in the library appears to be in use.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($unused as $item): ?>
                    <label class="flex items-center gap-4 p-4 hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="ids[]" value="<?= $item['id'] ?>" class="rounded border-slate-300">
                        <?php if ($item['file_type'] === 'image'): ?>
                            <img src="<?= public_url($item['file_path']) ?>" class="w-12 h-12 rounded object-cover border border-slate-200">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded bg-slate-100 flex items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-[20px]"><?= $item['file_type'] === 'video' ? 'movie' : 'description' ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= e($item['title']) ?></p>
                            <p class="text-[11px] text-slate-400"><?= e($item['file_path']) ?> &bull; <?= format_bytes($item['file_size']) ?></p>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Missing Files -->
    <div class="cms-card overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 brand-font flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">broken_image</span>
                Missing / Broken References <span class="text-xs font-normal text-slate-400">- library entry exists but the file is gone from disk</span>
            </h2>
        </div>
        <?php if (empty($missing)): ?>
            <p class="p-6 text-sm text-slate-400 text-center">No broken file references found.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($missing as $item): ?>
                    <label class="flex items-center gap-4 p-4 hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="ids[]" value="<?= $item['id'] ?>" class="rounded border-slate-300">
                        <div class="w-12 h-12 rounded bg-red-50 flex items-center justify-center text-red-400">
                            <span class="material-symbols-outlined text-[20px]">error</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= e($item['title']) ?></p>
                            <p class="text-[11px] text-red-500 font-mono truncate"><?= e($item['file_path']) ?> (file not found)</p>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Duplicate Files -->
    <div class="cms-card overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 brand-font flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">content_copy</span>
                Duplicate Files <span class="text-xs font-normal text-slate-400">- identical file content uploaded more than once</span>
            </h2>
        </div>
        <?php if (empty($duplicateGroups)): ?>
            <p class="p-6 text-sm text-slate-400 text-center">No duplicate files detected.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($duplicateGroups as $group): ?>
                    <div class="p-4">
                        <p class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider mb-2">
                            <?= count($group) ?> identical copies - keep one, select the rest to remove
                        </p>
                        <div class="space-y-2">
                            <?php foreach ($group as $i => $item): ?>
                                <label class="flex items-center gap-4 p-2 rounded hover:bg-slate-50 cursor-pointer">
                                    <input type="checkbox" name="ids[]" value="<?= $item['id'] ?>" class="rounded border-slate-300">
                                    <?php if ($item['file_type'] === 'image'): ?>
                                        <img src="<?= public_url($item['file_path']) ?>" class="w-10 h-10 rounded object-cover border border-slate-200">
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate"><?= e($item['title']) ?> <?= $i === 0 ? '<span class="text-emerald-600">(suggested keeper)</span>' : '' ?></p>
                                        <p class="text-[10px] text-slate-400"><?= e($item['file_path']) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($unused) || !empty($missing) || !empty($duplicateGroups)): ?>
        <div class="cms-card p-5 flex items-center justify-between sticky bottom-4">
            <p class="text-xs text-slate-500">Select items above, then confirm removal. This permanently deletes the library entry and file.</p>
            <button type="submit" class="cms-btn cms-btn-danger text-xs flex-shrink-0">
                <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                <span>Delete Selected</span>
            </button>
        </div>
    <?php endif; ?>
</form>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
