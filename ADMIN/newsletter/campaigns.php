<?php
/**
 * St. Monica Junior School CMS - Sent newsletters (history + per-newsletter report)
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/services/NewsletterService.php';
require_module('newsletter');

$pageTitle = 'Sent Newsletters';
$activeMenu = 'newsletter';
$newsletterTab = 'sent';

$viewId = (int)($_GET['id'] ?? 0);
$campaign = null;
$campaigns = [];
$failedRows = [];
$progress = null;

try {
    if ($viewId > 0) {
        $campaign = Database::fetchOne(
            "SELECT c.*, a.`name` AS `sender_name` FROM `newsletter_campaigns` c
             LEFT JOIN `admins` a ON a.`id` = c.`created_by` WHERE c.`id` = :id",
            ['id' => $viewId]
        );
        if ($campaign) {
            $progress = NewsletterService::refreshCampaign($viewId);
            $failedRows = Database::fetchAll(
                "SELECT `email`, `error`, `attempted_at` FROM `newsletter_deliveries`
                 WHERE `campaign_id` = :id AND `status` = 'failed' ORDER BY `id` ASC LIMIT 200",
                ['id' => $viewId]
            );
        }
    } else {
        $campaigns = Database::fetchAll(
            "SELECT c.*, a.`name` AS `sender_name` FROM `newsletter_campaigns` c
             LEFT JOIN `admins` a ON a.`id` = c.`created_by` ORDER BY c.`created_at` DESC, c.`id` DESC LIMIT 100"
        );
    }
} catch (Exception $e) {
    $dbError = 'The newsletter tables are not set up yet. Run the database setup to apply migration 024.';
}

function newsletter_status_badge(string $status): string {
    [$label, $cls] = match ($status) {
        'sent'    => ['Sent', 'bg-emerald-100 text-emerald-800'],
        'partial' => ['Sent with failures', 'bg-amber-100 text-amber-800'],
        default   => ['Not finished', 'bg-blue-100 text-blue-800'],
    };
    return '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold whitespace-nowrap ' . $cls . '">' . $label . '</span>';
}

include CMS_ROOT . '/includes/header.php';
include __DIR__ . '/_tabs.php';
?>

<?php if (!empty($dbError)): ?>
    <div class="mb-6 p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-sm"><?= e($dbError) ?></div>
<?php endif; ?>

<?php if ($viewId > 0 && !$campaign && empty($dbError)): ?>
    <div class="cms-card p-10 text-center text-slate-500">
        <p class="font-semibold">That newsletter was not found.</p>
        <a href="<?= admin_url('newsletter/campaigns.php') ?>" class="text-sm text-red-600 font-semibold hover:underline mt-2 inline-block">Back to Sent Newsletters</a>
    </div>

<?php elseif ($campaign): ?>
    <!-- Single newsletter report -->
    <a href="<?= admin_url('newsletter/campaigns.php') ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 mb-4">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span> All sent newsletters
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="nlReport"
         data-endpoint="<?= e(admin_url('newsletter/send.php')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         data-campaign="<?= (int)$campaign['id'] ?>">
        <div class="lg:col-span-2 space-y-6">
            <div class="cms-card p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-900 brand-font break-words"><?= e($campaign['subject']) ?></h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Started <?= date('M j, Y \a\t H:i', strtotime($campaign['created_at'])) ?>
                            <?= $campaign['sender_name'] ? ' by ' . e($campaign['sender_name']) : '' ?>
                            <?= $campaign['completed_at'] ? ' &bull; finished ' . date('M j, Y \a\t H:i', strtotime($campaign['completed_at'])) : '' ?>
                        </p>
                    </div>
                    <span id="nlStatusBadge"><?= newsletter_status_badge($progress['status']) ?></span>
                </div>
                <div class="mt-5 border border-slate-200 rounded-lg p-5 bg-slate-50/60 text-sm text-slate-700 leading-relaxed cms-rich-content max-h-[520px] overflow-y-auto">
                    <?= $campaign['body_html'] /* sanitized with sanitize_html() when sent */ ?>
                </div>
            </div>

            <?php if (!empty($failedRows)): ?>
                <div class="cms-card overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 brand-font">Failed recipients (<?= count($failedRows) ?>)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="cms-table">
                            <thead><tr><th>Email</th><th>Reason</th></tr></thead>
                            <tbody>
                                <?php foreach ($failedRows as $f): ?>
                                    <tr>
                                        <td class="text-sm font-semibold text-slate-800 break-words whitespace-nowrap"><?= e($f['email']) ?></td>
                                        <td class="text-xs text-red-700"><?= e($f['error'] ?: 'Unknown error') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <div class="cms-card p-6">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Delivery</p>
                <?php $done = $progress['sent'] + $progress['failed'] + $progress['skipped']; ?>
                <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all duration-300" id="nlBar" style="width:<?= $progress['recipients'] ? round($done / $progress['recipients'] * 100) : 100 ?>%"></div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-[11px] text-slate-500">Recipients</dt><dd class="text-xl font-bold text-slate-900" id="nlRecipients"><?= $progress['recipients'] ?></dd></div>
                    <div class="rounded-lg bg-emerald-50 p-3"><dt class="text-[11px] text-emerald-800">Sent</dt><dd class="text-xl font-bold text-emerald-700" id="nlSent"><?= $progress['sent'] ?></dd></div>
                    <div class="rounded-lg bg-red-50 p-3"><dt class="text-[11px] text-red-700">Failed</dt><dd class="text-xl font-bold text-red-600" id="nlFailed"><?= $progress['failed'] ?></dd></div>
                    <div class="rounded-lg bg-blue-50 p-3"><dt class="text-[11px] text-blue-800">Not sent yet</dt><dd class="text-xl font-bold text-blue-700" id="nlPending"><?= $progress['pending'] ?></dd></div>
                </dl>
                <?php if ($progress['skipped'] > 0): ?>
                    <p class="text-[11px] text-slate-400 mt-2"><?= $progress['skipped'] ?> skipped because they unsubscribed before their turn.</p>
                <?php endif; ?>

                <div class="mt-5 space-y-2">
                    <?php if ($progress['pending'] > 0): ?>
                        <button type="button" class="cms-btn cms-btn-accent w-full justify-center" data-nl-action="resume">
                            <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                            <span>Resume sending (<?= $progress['pending'] ?> left)</span>
                        </button>
                    <?php endif; ?>
                    <?php if ($progress['failed'] > 0): ?>
                        <button type="button" class="cms-btn cms-btn-outline w-full justify-center" data-nl-action="retry">
                            <span class="material-symbols-outlined text-[18px]">replay</span>
                            <span>Retry <?= $progress['failed'] ?> failed</span>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="hidden mt-3 text-xs font-semibold p-3 rounded-lg" id="nlMessage" role="status"></p>
            </div>
        </div>
    </div>

    <script src="<?= admin_url('assets/js/newsletter-send.js') ?>"></script>
    <script>
    (() => {
        const root = document.getElementById('nlReport');
        const endpoint = root.dataset.endpoint;
        const csrf = root.dataset.csrf;
        const campaignId = root.dataset.campaign;
        const message = document.getElementById('nlMessage');
        let busy = false;

        const paint = (p) => {
            const done = p.sent + p.failed + (p.skipped || 0);
            document.getElementById('nlBar').style.width = (p.recipients ? Math.round(done / p.recipients * 100) : 100) + '%';
            document.getElementById('nlSent').textContent = p.sent;
            document.getElementById('nlFailed').textContent = p.failed;
            document.getElementById('nlPending').textContent = p.pending;
        };
        const say = (text, tone) => {
            message.textContent = text;
            message.className = 'mt-3 text-xs font-semibold p-3 rounded-lg ' + (tone === 'ok' ? 'bg-emerald-50 text-emerald-800' : tone === 'warn' ? 'bg-amber-50 text-amber-900' : tone === 'info' ? 'bg-slate-50 text-slate-600' : 'bg-red-50 text-red-700');
        };

        root.querySelectorAll('[data-nl-action]').forEach(btn => btn.addEventListener('click', async () => {
            if (busy) return;
            busy = true;
            root.querySelectorAll('[data-nl-action]').forEach(b => b.disabled = true);
            say('Sending… please keep this page open.', 'info');
            try {
                if (btn.dataset.nlAction === 'retry') {
                    const r = await NewsletterSender.post(endpoint, csrf, { action: 'retry', campaign_id: campaignId });
                    paint(r.data);
                }
                const final = await NewsletterSender.run({ endpoint, csrf, campaignId, onProgress: paint });
                say(final.failed === 0 ? `Finished: delivered to ${final.sent} subscriber(s).` : `Finished: ${final.sent} delivered, ${final.failed} failed.`, final.failed === 0 ? 'ok' : 'warn');
                setTimeout(() => location.reload(), 1800);
            } catch (err) {
                if (err.progress) paint(err.progress);
                say(`Sending paused: ${err.message}`, 'error');
                root.querySelectorAll('[data-nl-action]').forEach(b => b.disabled = false);
            } finally {
                busy = false;
            }
        }));

        window.addEventListener('beforeunload', (e) => { if (busy) { e.preventDefault(); e.returnValue = ''; } });
    })();
    </script>

<?php else: ?>
    <!-- History list -->
    <div class="cms-card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 brand-font">Sent Newsletters (<?= count($campaigns) ?>)</h2>
            <a href="<?= admin_url('newsletter/compose.php') ?>" class="cms-btn cms-btn-accent text-xs">
                <span class="material-symbols-outlined text-[16px]">edit_square</span>
                <span>New Newsletter</span>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="cms-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-center">Sent</th>
                        <th class="text-center">Failed</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campaigns)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 block">outbox</span>
                                No newsletters have been sent yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($campaigns as $c): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td>
                                    <a href="<?= admin_url('newsletter/campaigns.php?id=' . (int)$c['id']) ?>" class="font-semibold text-slate-900 text-sm hover:text-red-600"><?= e($c['subject']) ?></a>
                                    <?php if ($c['sender_name']): ?><div class="text-[11px] text-slate-400">by <?= e($c['sender_name']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-xs text-slate-500 whitespace-nowrap"><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></td>
                                <td><?= newsletter_status_badge($c['status']) ?></td>
                                <td class="text-center text-sm font-semibold text-emerald-700"><?= (int)$c['sent_count'] ?> <span class="text-slate-400 font-normal">/ <?= (int)$c['recipient_count'] ?></span></td>
                                <td class="text-center text-sm font-semibold <?= $c['failed_count'] ? 'text-red-600' : 'text-slate-400' ?>"><?= (int)$c['failed_count'] ?></td>
                                <td class="text-right">
                                    <a href="<?= admin_url('newsletter/campaigns.php?id=' . (int)$c['id']) ?>" class="cms-btn cms-btn-outline text-xs">View report</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
