<?php
/**
 * St. Monica Junior School CMS - Compose & send a newsletter to all subscribers
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/services/NewsletterService.php';
require_module('newsletter');

$pageTitle = 'Compose Newsletter';
$activeMenu = 'newsletter';
$newsletterTab = 'compose';

$activeCount = 0;
try {
    $activeCount = NewsletterService::activeCount();
} catch (Exception $e) {
    $dbError = 'The newsletter tables are not set up yet. Run the database setup to apply migration 024.';
}
$mailReady = EmailService::isConfigured();
$mailCfg = EmailService::config();
$fromAddress = $mailCfg['from_address'] ?? '';
$isGmail = stripos($mailCfg['host'] ?? '', 'gmail') !== false;
$admin = current_admin();

include CMS_ROOT . '/includes/header.php';
include __DIR__ . '/_tabs.php';
?>

<?php if (!empty($dbError)): ?>
    <div class="mb-6 p-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 text-sm"><?= e($dbError) ?></div>
<?php endif; ?>

<?php if (!$mailReady): ?>
    <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 flex items-start gap-3">
        <span class="material-symbols-outlined text-red-600">error</span>
        <div class="text-sm text-red-900">
            <p class="font-bold">Email sending is not set up</p>
            <p class="mt-0.5">Add your SMTP email details under <a class="underline font-semibold" href="<?= admin_url('settings/') ?>">Settings</a> before sending a newsletter.</p>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="newsletterComposer"
     data-endpoint="<?= e(admin_url('newsletter/send.php')) ?>"
     data-csrf="<?= e(csrf_token()) ?>"
     data-report-url="<?= e(admin_url('newsletter/campaigns.php')) ?>"
     data-recipients="<?= (int)$activeCount ?>">

    <!-- Message -->
    <div class="lg:col-span-2 cms-card p-6 space-y-5">
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2" for="nlSubject">Subject *</label>
            <input type="text" id="nlSubject" maxlength="200" placeholder="e.g. End of Term Newsletter - Term 3, 2026" class="cms-input">
            <p class="hidden text-xs font-semibold text-red-600 mt-1.5" data-error-for="subject"></p>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Message *</label>
            <textarea id="nlBody" rows="14" data-rich-editor class="cms-textarea"><p>Dear Parents and Friends of St. Monica,</p><p><br></p><p>Warm regards,<br>St. Monica Junior School Kasanje</p></textarea>
            <p class="hidden text-xs font-semibold text-red-600 mt-1.5" data-error-for="body"></p>
            <p class="text-xs text-slate-400 mt-1.5">The school's branded header and footer are added automatically, including a personal unsubscribe link for each subscriber.</p>
        </div>
    </div>

    <!-- Send panel -->
    <div class="space-y-6">
        <div class="cms-card p-6">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recipients</p>
            <p class="text-3xl font-bold text-slate-900 mt-1"><?= number_format($activeCount) ?></p>
            <p class="text-xs text-slate-500">active subscriber<?= $activeCount === 1 ? '' : 's' ?> will receive this newsletter</p>
            <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1.5">
                <p><span class="font-semibold text-slate-700">From:</span> <?= e($fromAddress ?: 'Not configured') ?></p>
                <p><span class="font-semibold text-slate-700">Delivery:</span> each subscriber gets their own copy, so nobody sees anyone else's address.</p>
            </div>
            <?php if ($isGmail && $activeCount > 450): ?>
                <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">
                    <strong>Gmail limit:</strong> a regular Gmail account can send about 500 emails a day. If sending stops, resume it tomorrow from <em>Sent Newsletters</em>. Nobody will receive it twice.
                </div>
            <?php endif; ?>
            <div class="mt-5 space-y-2">
                <button type="button" id="nlTestBtn" class="cms-btn cms-btn-outline w-full justify-center text-xs" <?= $mailReady ? '' : 'disabled' ?>>
                    <span class="material-symbols-outlined text-[16px]">science</span>
                    <span>Send test to me</span>
                </button>
                <p class="text-[11px] text-slate-400 text-center"><?= e($admin['email'] ?? '') ?></p>
                <button type="button" id="nlSendBtn" class="cms-btn cms-btn-accent w-full justify-center" <?= ($mailReady && $activeCount > 0) ? '' : 'disabled' ?>>
                    <span class="material-symbols-outlined text-[18px]">send</span>
                    <span>Send to <?= number_format($activeCount) ?> subscriber<?= $activeCount === 1 ? '' : 's' ?></span>
                </button>
                <?php if ($activeCount === 0): ?>
                    <p class="text-[11px] text-slate-400 text-center">No subscribers yet. Visitors subscribe from the website footer.</p>
                <?php endif; ?>
            </div>
            <p class="hidden mt-3 text-xs font-semibold" id="nlTestResult" role="status"></p>
        </div>
    </div>
</div>

<!-- Confirm send -->
<div id="nlConfirmModal" class="cms-modal-backdrop">
    <div class="cms-modal p-6">
        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="material-symbols-outlined text-[30px]">send</span>
        </div>
        <h3 class="text-lg font-bold text-slate-900 text-center mb-2">Send this newsletter?</h3>
        <p class="text-sm text-slate-600 text-center leading-relaxed">
            <strong id="nlConfirmSubject" class="text-slate-900"></strong><br>
            will be emailed to <strong><?= number_format($activeCount) ?></strong> subscriber<?= $activeCount === 1 ? '' : 's' ?>. Emails cannot be recalled once sent.
        </p>
        <div class="flex items-center gap-3 mt-6">
            <button type="button" class="cms-btn cms-btn-outline w-1/2 justify-center" id="nlConfirmCancel">Cancel</button>
            <button type="button" class="cms-btn cms-btn-accent w-1/2 justify-center" id="nlConfirmGo">Send now</button>
        </div>
    </div>
</div>

<!-- Progress -->
<div id="nlProgressModal" class="cms-modal-backdrop">
    <div class="cms-modal p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-1" id="nlProgressTitle">Sending newsletter…</h3>
        <p class="text-xs text-slate-500 mb-4" id="nlProgressHint">Please keep this page open until sending finishes.</p>
        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-emerald-500 transition-all duration-300" id="nlProgressBar" style="width:0%"></div>
        </div>
        <div class="grid grid-cols-3 gap-2 mt-4 text-center">
            <div class="rounded-lg bg-emerald-50 p-2"><p class="text-lg font-bold text-emerald-700" id="nlSent">0</p><p class="text-[11px] text-emerald-800">Sent</p></div>
            <div class="rounded-lg bg-red-50 p-2"><p class="text-lg font-bold text-red-600" id="nlFailed">0</p><p class="text-[11px] text-red-700">Failed</p></div>
            <div class="rounded-lg bg-slate-50 p-2"><p class="text-lg font-bold text-slate-700" id="nlPending">0</p><p class="text-[11px] text-slate-600">Remaining</p></div>
        </div>
        <p class="hidden mt-4 text-xs font-semibold p-3 rounded-lg" id="nlProgressMessage"></p>
        <div class="hidden mt-5 flex justify-end gap-2" id="nlProgressActions">
            <button type="button" class="cms-btn cms-btn-outline text-xs" id="nlCloseBtn">Close</button>
            <a href="#" class="cms-btn cms-btn-primary text-xs" id="nlReportLink">View report</a>
        </div>
    </div>
</div>

<script src="<?= admin_url('assets/js/newsletter-send.js') ?>"></script>
<script>
(() => {
    const root = document.getElementById('newsletterComposer');
    const endpoint = root.dataset.endpoint;
    const csrf = root.dataset.csrf;
    const reportUrl = root.dataset.reportUrl;
    const subject = document.getElementById('nlSubject');
    const textarea = document.getElementById('nlBody');
    const testBtn = document.getElementById('nlTestBtn');
    const sendBtn = document.getElementById('nlSendBtn');
    const testResult = document.getElementById('nlTestResult');
    const confirmModal = document.getElementById('nlConfirmModal');
    const progressModal = document.getElementById('nlProgressModal');
    let sending = false;

    // Current HTML of the message (the rich editor keeps the textarea in sync; read it directly to be safe)
    function bodyHtml() {
        const editor = textarea.parentElement.querySelector('[contenteditable="true"]');
        return editor ? editor.innerHTML : textarea.value;
    }

    function showError(field, text) {
        document.querySelectorAll('[data-error-for]').forEach(el => {
            const on = el.dataset.errorFor === field && text;
            el.textContent = on ? text : '';
            el.classList.toggle('hidden', !on);
        });
        if (field === 'subject' && text) subject.focus();
    }

    function validate() {
        if (!subject.value.trim()) { showError('subject', 'Please enter a subject.'); return false; }
        const tmp = document.createElement('div');
        tmp.innerHTML = bodyHtml();
        if (!tmp.textContent.trim() && !tmp.querySelector('img')) { showError('body', 'Please write the newsletter message.'); return false; }
        showError(null, '');
        return true;
    }

    testBtn.addEventListener('click', async () => {
        if (!validate()) return;
        testBtn.disabled = true;
        testResult.className = 'mt-3 text-xs font-semibold text-slate-500';
        testResult.textContent = 'Sending test…';
        try {
            const json = await NewsletterSender.post(endpoint, csrf, { action: 'test', subject: subject.value.trim(), body: bodyHtml() });
            testResult.className = 'mt-3 text-xs font-semibold text-emerald-700';
            testResult.textContent = json.message;
        } catch (err) {
            testResult.className = 'mt-3 text-xs font-semibold text-red-600';
            testResult.textContent = err.message;
        } finally {
            testBtn.disabled = false;
        }
    });

    sendBtn.addEventListener('click', () => {
        if (!validate()) return;
        document.getElementById('nlConfirmSubject').textContent = '"' + subject.value.trim() + '"';
        confirmModal.classList.add('active');
    });
    document.getElementById('nlConfirmCancel').addEventListener('click', () => confirmModal.classList.remove('active'));

    function paint(p) {
        const done = p.sent + p.failed + (p.skipped || 0);
        const pct = p.recipients ? Math.round((done / p.recipients) * 100) : 100;
        document.getElementById('nlProgressBar').style.width = pct + '%';
        document.getElementById('nlSent').textContent = p.sent;
        document.getElementById('nlFailed').textContent = p.failed;
        document.getElementById('nlPending').textContent = p.pending;
    }

    function finish(title, message, tone, campaignId) {
        sending = false;
        document.getElementById('nlProgressTitle').textContent = title;
        document.getElementById('nlProgressHint').textContent = '';
        const msg = document.getElementById('nlProgressMessage');
        msg.textContent = message;
        msg.className = 'mt-4 text-xs font-semibold p-3 rounded-lg ' + (tone === 'ok'
            ? 'bg-emerald-50 text-emerald-800' : tone === 'warn' ? 'bg-amber-50 text-amber-900' : 'bg-red-50 text-red-700');
        const report = document.getElementById('nlReportLink');
        report.href = campaignId ? `${reportUrl}?id=${campaignId}` : reportUrl;
        report.classList.toggle('hidden', !campaignId);
        document.getElementById('nlProgressActions').classList.remove('hidden');
    }

    document.getElementById('nlCloseBtn').addEventListener('click', () => {
        if (sending) return;
        progressModal.classList.remove('active');
    });

    document.getElementById('nlConfirmGo').addEventListener('click', async () => {
        confirmModal.classList.remove('active');
        progressModal.classList.add('active');
        sending = true;
        sendBtn.disabled = testBtn.disabled = true;
        let campaignId = null;
        try {
            const start = await NewsletterSender.post(endpoint, csrf, { action: 'start', subject: subject.value.trim(), body: bodyHtml() });
            campaignId = start.data.campaign_id;
            paint(start.data);
            const final = await NewsletterSender.run({ endpoint, csrf, campaignId, onProgress: paint });
            if (final.failed === 0) {
                finish('Newsletter sent', `Delivered to all ${final.sent} subscriber${final.sent === 1 ? '' : 's'}.`, 'ok', campaignId);
            } else {
                finish('Sent with some failures', `${final.sent} delivered, ${final.failed} failed. You can retry the failed ones from the report.`, 'warn', campaignId);
            }
        } catch (err) {
            if (err.progress) paint(err.progress);
            finish(campaignId ? 'Sending paused' : 'Could not start sending',
                campaignId
                    ? `${err.message} Nobody has been emailed twice; open the report later to resume the remaining recipients.`
                    : err.message,
                'error', campaignId);
            if (!campaignId) {
                sendBtn.disabled = testBtn.disabled = false;
            }
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (sending) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
