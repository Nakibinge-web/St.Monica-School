<?php
/**
 * St. Monica Junior School CMS - Secure Preview System
 * Protects preview of draft, unpublished, or scheduled content
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/includes/database.php';

// Strict session check
require_auth();

$type = $_GET['type'] ?? 'news';
$id   = (int)($_GET['id'] ?? 0);

$item = null;
if ($type === 'news' || $type === 'event' || $type === 'sports') {
    $item = Database::fetchOne("SELECT * FROM `news_events` WHERE `id` = :id", ['id' => $id]);
}

if (!$item) {
    die("Error: The requested preview item does not exist or has been removed.");
}

$pageTitle = '[Preview] ' . $item['title'];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — St. Monica School Preview</title>
    <link rel="icon" type="image/png" href="<?= public_url('assets/imgz/logo2-cut.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; }
        h1, h2, h3, h4, .brand-font { font-family: 'Montserrat', sans-serif; }
        .article-content p { margin-bottom: 1.25rem; line-height: 1.75; font-size: 1.05rem; color: #334155; }
        .article-content h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; color: #0f172a; }
        .article-content h3 { font-size: 1.25rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #0f172a; }
        .article-content ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1.25rem; }
        .article-content ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1.25rem; }
        .article-content li { margin-bottom: 0.5rem; }
        .article-content blockquote { border-left: 4px solid #d93633; padding-left: 1.25rem; font-style: italic; color: #475569; margin: 1.5rem 0; }
        .article-content a { color: #d93633; text-decoration: underline; }
    </style>
</head>
<body class="min-h-full flex flex-col antialiased">

    <!-- Top Administrative Preview Bar -->
    <div class="sticky top-0 z-50 bg-[#1e2a4a] text-white px-4 sm:px-8 py-3 shadow-md flex flex-wrap items-center justify-between gap-3 border-b-2 border-red-600">
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-red-600 text-white animate-pulse">
                Preview Mode
            </span>
            <span class="text-xs text-slate-300 hidden sm:inline">
                Status: <strong class="text-white capitalize"><?= e($item['status']) ?></strong>
                <?php if ($item['published_at']): ?>
                    &bull; Published Date: <?= date('M j, Y g:i A', strtotime($item['published_at'])) ?>
                <?php endif; ?>
            </span>
        </div>

        <div class="flex items-center gap-3 text-xs">
            <span class="text-slate-300 hidden md:inline">This preview is protected and invisible to public visitors.</span>
            <a href="<?= admin_url('news-events/edit.php?id=' . $item['id']) ?>" class="px-3 py-1.5 rounded-md bg-white text-slate-900 font-bold hover:bg-slate-100 transition inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">edit</span>
                <span>Edit Article</span>
            </a>
            <a href="<?= admin_url('news-events/') ?>" class="px-3 py-1.5 rounded-md bg-slate-800 text-white font-medium hover:bg-slate-700 transition">
                Exit Preview
            </a>
        </div>
    </div>

    <!-- Public School Header Simulation -->
    <header class="bg-white border-b border-slate-200 py-4 px-4 sm:px-8">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="<?= public_url('assets/imgz/logo2-cut.png') ?>" alt="Logo" class="h-10 w-auto">
                <div>
                    <span class="brand-font font-bold text-slate-900 text-lg leading-tight block">St. Monica Junior School Kasanje</span>
                    <span class="text-xs text-red-600 font-semibold tracking-wider uppercase block">Always Aim Higher</span>
                </div>
            </div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest hidden sm:inline">Public Website View</span>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 py-10 px-4 sm:px-8">
        <article class="max-w-4xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Featured Image -->
            <?php if (!empty($item['featured_image'])): ?>
                <div class="w-full h-80 sm:h-96 overflow-hidden bg-slate-100 relative">
                    <img src="<?= public_url($item['featured_image']) ?>" alt="<?= e($item['title']) ?>" class="w-full h-full object-cover">
                    <div class="absolute bottom-4 left-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#1e2a4a]/90 text-white backdrop-blur-sm shadow">
                            <?= ucfirst($item['type']) ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="p-6 sm:p-12">
                <!-- Meta tags row -->
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 mb-6 pb-4 border-b border-slate-100">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-red-600">calendar_today</span>
                        <?= date('F j, Y', strtotime($item['published_at'] ?? $item['created_at'])) ?>
                    </span>

                    <?php if (!empty($item['event_date'])): ?>
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1 rounded-md">
                            <span class="material-symbols-outlined text-[16px]">event</span>
                            Event Date: <?= date('l, M j, Y', strtotime($item['event_date'])) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($item['event_location'])): ?>
                        <span class="inline-flex items-center gap-1.5 text-slate-700">
                            <span class="material-symbols-outlined text-[16px] text-red-600">location_on</span>
                            <?= e($item['event_location']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Title -->
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 brand-font tracking-tight mb-6 leading-tight">
                    <?= e($item['title']) ?>
                </h1>

                <!-- Excerpt -->
                <?php if (!empty($item['excerpt'])): ?>
                    <div class="text-lg font-medium text-slate-600 leading-relaxed italic mb-8 p-4 bg-slate-50 border-l-4 border-red-600 rounded-r-lg">
                        <?= e($item['excerpt']) ?>
                    </div>
                <?php endif; ?>

                <!-- Full Rich-Text Body -->
                <div class="article-content">
                    <?= sanitize_html($item['content']) ?>
                </div>

                <!-- Footer / Share buttons placeholder -->
                <div class="mt-12 pt-8 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Category:</span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700">
                            <?= ucfirst($item['type']) ?>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="<?= admin_url('news-events/edit.php?id=' . $item['id']) ?>" class="px-4 py-2 rounded-full bg-[#1e2a4a] text-white text-xs font-bold hover:bg-[#2b3b64] transition">
                            Continue Editing
                        </a>
                    </div>
                </div>
            </div>
        </article>
    </main>

    <!-- Public Footer Simulation -->
    <footer class="bg-slate-900 text-white py-8 px-4 text-center text-xs text-slate-400">
        <p>&copy; <?= date('Y') ?> St. Monica Junior School Kasanje. All rights reserved.</p>
    </footer>

</body>
</html>
