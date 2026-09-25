<?php
/**
 * St. Monica Junior School CMS - Website Settings
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');
require_once CMS_ROOT . '/services/SettingsService.php';
require_once CMS_ROOT . '/services/EmailService.php';

$pageTitle = 'Website Settings';
$activeMenu = 'settings';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $section = $_POST['section'] ?? '';

    if ($section === 'website') {
        SettingsService::setMany([
            'maintenance_mode'    => !empty($_POST['maintenance_mode']) ? '1' : '0',
            'maintenance_message' => trim($_POST['maintenance_message'] ?? ''),
            'pagination_default'  => max(5, min(100, (int)($_POST['pagination_default'] ?? 10))),
            'date_format'         => trim($_POST['date_format'] ?? 'M j, Y'),
        ]);
        log_activity('Updated Settings', 'Website settings updated', 'settings');
        set_flash('success', 'Website settings saved.');
    } elseif ($section === 'email') {
        SettingsService::setMany([
            'mail_from_name'  => trim($_POST['mail_from_name'] ?? ''),
            'mail_from_email' => trim($_POST['mail_from_email'] ?? ''),
        ]);
        log_activity('Updated Settings', 'Email sender settings updated', 'settings');
        set_flash('success', 'Email settings saved. SMTP host/credentials are configured via the .env file, not here.');
    }

    redirect(admin_url('settings/?tab=' . urlencode($section)));
}

$settings = SettingsService::all();
$activeTab = $_GET['tab'] ?? 'general';
$emailConfigured = EmailService::isConfigured();

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Website Settings</h1>
        <p class="text-sm text-slate-500 mt-1">General configuration, maintenance mode, and system-level defaults.</p>
    </div>
</div>

<!-- Tabs -->
<div class="flex items-center gap-1 mb-6 border-b border-slate-200 overflow-x-auto">
    <?php
    $tabs = [
        'general' => ['label' => 'General', 'icon' => 'settings'],
        'contact' => ['label' => 'Contact', 'icon' => 'contacts'],
        'website' => ['label' => 'Website', 'icon' => 'public'],
        'email'   => ['label' => 'Email', 'icon' => 'mail'],
    ];
    foreach ($tabs as $key => $t):
    ?>
        <a href="<?= admin_url('settings/?tab=' . $key) ?>" class="px-4 py-2.5 text-xs font-semibold border-b-2 flex items-center gap-1.5 <?= $activeTab === $key ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
            <span class="material-symbols-outlined text-[16px]"><?= $t['icon'] ?></span>
            <?= $t['label'] ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($activeTab === 'general'): ?>
    <div class="cms-card p-6 max-w-2xl">
        <h2 class="font-bold text-slate-900 mb-2">General & Branding</h2>
        <p class="text-sm text-slate-500 mb-5">School name, logo, and title are managed under Homepage and SEO settings to avoid duplicating those controls.</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= admin_url('homepage/') ?>" class="cms-btn cms-btn-outline text-xs">Homepage Content &rarr;</a>
            <a href="<?= admin_url('seo/') ?>" class="cms-btn cms-btn-outline text-xs">SEO & Site Title &rarr;</a>
        </div>
    </div>

<?php elseif ($activeTab === 'contact'): ?>
    <div class="cms-card p-6 max-w-2xl">
        <h2 class="font-bold text-slate-900 mb-2">Contact & Social Links</h2>
        <p class="text-sm text-slate-500 mb-5">Phone, email, address, WhatsApp, and social media links are managed in the dedicated Contact Information module.</p>
        <a href="<?= admin_url('contact/') ?>" class="cms-btn cms-btn-accent text-xs">Manage Contact Info &rarr;</a>
    </div>

<?php elseif ($activeTab === 'website'): ?>
    <div class="cms-card p-6 max-w-2xl">
        <form method="POST" action="<?= admin_url('settings/') ?>" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="website">

            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= SettingsService::getBool('maintenance_mode') ? 'checked' : '' ?> class="mt-1 rounded border-slate-300">
                    <div>
                        <span class="text-sm font-bold text-amber-900">Enable Maintenance Mode</span>
                        <p class="text-xs text-amber-700 mt-1">Public website visitors will see a maintenance notice instead of normal content. The admin panel remains fully accessible while this is on.</p>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maintenance Message</label>
                <textarea name="maintenance_message" rows="3" class="cms-textarea text-sm"><?= e($settings['maintenance_message'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Default Pagination Size</label>
                    <input type="number" name="pagination_default" min="5" max="100" value="<?= e($settings['pagination_default'] ?? '10') ?>" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Timezone</label>
                    <input type="text" value="<?= e($settings['timezone'] ?? 'Africa/Kampala') ?>" disabled class="cms-input bg-slate-50 text-slate-400">
                    <p class="text-[11px] text-slate-400 mt-1">Set via APP_TIMEZONE in .env - defaults to Africa/Kampala.</p>
                </div>
            </div>

            <button type="submit" class="cms-btn cms-btn-primary text-xs">
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>Save Website Settings</span>
            </button>
        </form>
    </div>

<?php elseif ($activeTab === 'email'): ?>
    <div class="cms-card p-6 max-w-2xl">
        <div class="mb-5 p-4 rounded-lg <?= $emailConfigured ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
            <p class="text-sm font-bold <?= $emailConfigured ? 'text-emerald-800' : 'text-amber-800' ?>">
                SMTP Status: <?= $emailConfigured ? 'Configured' : 'Not Configured' ?>
            </p>
            <p class="text-xs <?= $emailConfigured ? 'text-emerald-700' : 'text-amber-700' ?> mt-1">
                SMTP host, port, and credentials are set via the <code>.env</code> file (never shown here) and are not editable from this screen for security reasons.
            </p>
        </div>

        <form method="POST" action="<?= admin_url('settings/') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="email">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sender Name</label>
                <input type="text" name="mail_from_name" value="<?= e($settings['mail_from_name'] ?? '') ?>" class="cms-input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sender Email</label>
                <input type="email" name="mail_from_email" value="<?= e($settings['mail_from_email'] ?? '') ?>" class="cms-input">
            </div>

            <button type="submit" class="cms-btn cms-btn-primary text-xs">
                <span class="material-symbols-outlined text-[16px]">save</span>
                <span>Save Email Settings</span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('email-templates/') ?>" class="cms-btn cms-btn-outline text-xs">Manage Email Templates &rarr;</a>
        </div>
    </div>
<?php endif; ?>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
