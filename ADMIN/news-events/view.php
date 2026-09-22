<?php
/**
 * St. Monica Junior School CMS - View News / Event Article
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `news_events` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Post not found.');
    redirect(admin_url('news-events/'));
}

$pageTitle = 'View: ' . $item['title'];
$activeMenu = 'news-events';

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('news-events/') ?>" class="hover:text-slate-800">News & Events</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Article Preview</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight"><?= e($item['title']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to List</span>
        </a>
        <a href="<?= admin_url('preview/index.php?type=news&id=' . $item['id']) ?>" target="_blank" class="cms-btn cms-btn-outline bg-white text-[#1e2a4a]">
            <span class="material-symbols-outlined text-[18px] text-red-600">visibility</span>
            <span>Public-Style Preview</span>
        </a>
        <a href="<?= admin_url('news-events/edit.php?id=' . $item['id']) ?>" class="cms-btn cms-btn-primary">
            <span class="material-symbols-outlined text-[18px]">edit</span>
            <span>Edit Post</span>
        </a>
    </div>
</div>

<div class="cms-card overflow-hidden max-w-3xl mx-auto mb-8">
    <?php if (!empty($item['featured_image'])): ?>
        <div class="h-72 w-full overflow-hidden bg-slate-100">
            <img src="<?= public_url($item['featured_image']) ?>" alt="Featured Image" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <div class="p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="cms-badge badge-<?= e($item['type']) ?>"><?= ucfirst($item['type']) ?></span>
                <span class="cms-badge <?= $item['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>"><?= ucfirst($item['status']) ?></span>
            </div>
            <div class="text-xs text-slate-500">
                Slug: <code class="text-red-600 bg-red-50 px-1 py-0.5 rounded">/news/<?= e($item['slug']) ?></code>
            </div>
        </div>

        <?php if ($item['event_date'] || $item['event_location']): ?>
            <div class="p-4 bg-slate-50 rounded-lg border border-slate-200 mb-6 flex flex-wrap items-center gap-6 text-xs">
                <?php if ($item['event_date']): ?>
                    <div class="flex items-center gap-2 text-slate-700">
                        <span class="material-symbols-outlined text-red-600 text-[18px]">calendar_today</span>
                        <span>Date: <strong><?= date('l, F j, Y', strtotime($item['event_date'])) ?></strong></span>
                    </div>
                <?php endif; ?>
                <?php if ($item['event_location']): ?>
                    <div class="flex items-center gap-2 text-slate-700">
                        <span class="material-symbols-outlined text-red-600 text-[18px]">location_on</span>
                        <span>Venue: <strong><?= e($item['event_location']) ?></strong></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($item['excerpt']): ?>
            <div class="text-base font-semibold text-slate-800 italic mb-6">
                "<?= e($item['excerpt']) ?>"
            </div>
        <?php endif; ?>

        <div class="text-sm text-slate-700 leading-relaxed space-y-4">
            <?= sanitize_html($item['content']) ?>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
            <span>Published: <?= $item['published_at'] ? date('M j, Y g:i A', strtotime($item['published_at'])) : 'Unpublished (Draft)' ?></span>
            <span>Created: <?= date('M j, Y', strtotime($item['created_at'])) ?></span>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
