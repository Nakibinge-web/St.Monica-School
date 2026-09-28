<?php
/**
 * Shared header + tab navigation for the Newsletter module pages.
 * Expects $newsletterTab = 'subscribers' | 'compose' | 'sent'
 */
if (!function_exists('admin_url')) {
    http_response_code(404);
    exit;
}
$newsletterTabs = [
    'subscribers' => ['Subscribers', 'group', admin_url('newsletter/')],
    'compose'     => ['Compose & Send', 'edit_square', admin_url('newsletter/compose.php')],
    'sent'        => ['Sent Newsletters', 'outbox', admin_url('newsletter/campaigns.php')],
];
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Newsletter</h1>
        <p class="text-sm text-slate-500 mt-1">Visitors who subscribed on the website, and school newsletters sent to them by email.</p>
    </div>
</div>

<div class="flex items-center gap-2 border-b border-slate-200 mb-6 pb-3 overflow-x-auto">
    <?php foreach ($newsletterTabs as $key => [$label, $icon, $url]): ?>
        <a href="<?= $url ?>" class="px-4 py-2 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 whitespace-nowrap transition <?= $newsletterTab === $key ? 'bg-[#1e2a4a] text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $icon ?></span>
            <span><?= $label ?></span>
        </a>
    <?php endforeach; ?>
</div>
