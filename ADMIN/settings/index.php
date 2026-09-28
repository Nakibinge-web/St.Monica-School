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
        redirect(admin_url('settings/?tab=' . urlencode($section)));
    } elseif ($section === 'email') {
        $smtpHost       = trim($_POST['smtp_host'] ?? '');
        $smtpPort       = (int)($_POST['smtp_port'] ?? 587);
        $smtpEncryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $smtpUsername   = trim($_POST['smtp_username'] ?? '');
        $smtpPassword   = $_POST['smtp_password'] ?? '';
        $mailFromName   = trim($_POST['mail_from_name'] ?? '');
        $mailFromEmail  = trim($_POST['mail_from_email'] ?? '');

        if (stripos($smtpHost, 'gmail.com') !== false && !empty($smtpUsername) && strpos($smtpUsername, '@') === false) {
            set_flash('danger', "Invalid SMTP Username: For Gmail, your username must be your full email address (e.g. yourname@gmail.com), not '" . e($smtpUsername) . "'.");
            redirect(admin_url('settings/?tab=email'));
        }

        $saveSettings = [
            'smtp_host'       => $smtpHost,
            'smtp_port'       => (string)$smtpPort,
            'smtp_encryption' => $smtpEncryption,
            'smtp_username'   => $smtpUsername,
            'mail_from_name'  => $mailFromName,
            'mail_from_email' => $mailFromEmail,
        ];

        // Only overwrite password if user typed a new one
        if ($smtpPassword !== '') {
            $saveSettings['smtp_password'] = $smtpPassword;
        }

        SettingsService::setMany($saveSettings);
        EmailService::clearConfigCache();

        // Also sync to .env file if it exists and is writable
        $envPath = dirname(CMS_ROOT) . '/.env';
        if (file_exists($envPath) && is_writable($envPath)) {
            $envContent = file_get_contents($envPath);
            $replacements = [
                'MAIL_HOST'         => $smtpHost,
                'MAIL_PORT'         => $smtpPort,
                'MAIL_ENCRYPTION'   => $smtpEncryption,
                'MAIL_USERNAME'     => $smtpUsername,
                'MAIL_FROM_ADDRESS' => $mailFromEmail,
                'MAIL_FROM_NAME'    => '"' . addslashes($mailFromName) . '"'
            ];
            if ($smtpPassword !== '') {
                $replacements['MAIL_PASSWORD'] = $smtpPassword;
            }
            foreach ($replacements as $k => $v) {
                if (preg_match("/^{$k}=.*/m", $envContent)) {
                    $envContent = preg_replace("/^{$k}=.*/m", "{$k}={$v}", $envContent);
                } else {
                    $envContent .= "\n{$k}={$v}";
                }
            }
            @file_put_contents($envPath, $envContent);
        }

        log_activity('Updated Settings', 'SMTP and outgoing email settings updated', 'settings');
        set_flash('success', 'Email and SMTP settings saved successfully.');
        redirect(admin_url('settings/?tab=email'));
    } elseif ($section === 'send_test_email') {
        $testEmail = trim($_POST['test_email'] ?? '');
        EmailService::clearConfigCache();

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Please enter a valid recipient email address for the test.');
        } else {
            $sent = EmailService::sendTestEmail($testEmail);
            if ($sent) {
                set_flash('success', "Test email successfully delivered to {$testEmail} via SMTP!");
            } else {
                $err = EmailService::getLastError() ?: 'SMTP connection failed. Check host, port, username, and password.';
                set_flash('danger', "Failed to send test email to {$testEmail}: {$err}");
            }
        }
        redirect(admin_url('settings/?tab=email'));
    }

    redirect(admin_url('settings/?tab=' . urlencode($section)));
}

$settings = SettingsService::all();
$activeTab = $_GET['tab'] ?? 'general';
EmailService::clearConfigCache();
$emailConfigured = EmailService::isConfigured();
$mailConfig = EmailService::config();

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
        'email'   => ['label' => 'Email & SMTP', 'icon' => 'mail'],
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 max-w-5xl">
        <div class="lg:col-span-7 space-y-6">
            <div class="cms-card p-6">
                <!-- Status Banner -->
                <div class="mb-6 p-4 rounded-lg <?= $emailConfigured ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full <?= $emailConfigured ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                        <p class="text-sm font-bold <?= $emailConfigured ? 'text-emerald-800' : 'text-amber-800' ?>">
                            SMTP Status: <?= $emailConfigured ? 'Configured & Active' : 'Not Configured' ?>
                        </p>
                    </div>
                    <p class="text-xs <?= $emailConfigured ? 'text-emerald-700' : 'text-amber-700' ?> mt-1.5">
                        <?= $emailConfigured 
                            ? 'Outgoing emails will be sent via <strong>' . e($mailConfig['host']) . ':' . e($mailConfig['port']) . '</strong>.' 
                            : 'Without SMTP configured, email notifications are recorded in activity logs but cannot be delivered to applicants.' ?>
                    </p>
                </div>

                <form method="POST" action="<?= admin_url('settings/') ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="email">

                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <h2 class="font-bold text-slate-900 text-sm">SMTP Server Settings</h2>
                        <button type="button" onclick="fillGmailPreset()" class="text-xs text-blue-600 hover:underline inline-flex items-center gap-1 font-semibold">
                            <span class="material-symbols-outlined text-[14px]">auto_fix_high</span>
                            <span>Use Gmail Preset</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_host">
                                SMTP Host <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="smtp_host" name="smtp_host" placeholder="e.g. smtp.gmail.com" value="<?= e($mailConfig['host'] ?? '') ?>" class="cms-input font-mono text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_port">
                                Port <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="smtp_port" name="smtp_port" placeholder="587" value="<?= e($mailConfig['port'] ?? 587) ?>" class="cms-input font-mono text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_encryption">Encryption</label>
                            <select id="smtp_encryption" name="smtp_encryption" class="cms-select text-xs font-mono">
                                <?php $enc = strtolower($mailConfig['encryption'] ?? 'tls'); ?>
                                <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>TLS (Recommended - Port 587)</option>
                                <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                <option value="none" <?= $enc === 'none' ? 'selected' : '' ?>>None (Port 25)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_username">SMTP Username</label>
                            <input type="text" id="smtp_username" name="smtp_username" placeholder="e.g. stmonicajuniorschool2012@gmail.com" value="<?= e($mailConfig['username'] ?? '') ?>" class="cms-input font-mono text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_password">
                            SMTP Password / App Password
                            <?php if (!empty($mailConfig['password'])): ?>
                                <span class="text-emerald-600 font-normal ml-1 text-[11px]">(Password currently saved)</span>
                            <?php endif; ?>
                        </label>
                        <div class="relative">
                            <input type="password" id="smtp_password" name="smtp_password" placeholder="<?= !empty($mailConfig['password']) ? '•••••••••••••••• (Leave blank to keep saved password)' : 'Enter SMTP password or App Password' ?>" class="cms-input font-mono text-xs pr-10">
                            <button type="button" onclick="togglePassVisibility()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <span id="passToggleIcon" class="material-symbols-outlined text-[16px]">visibility</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">For Gmail accounts with 2FA, generate a 16-character <strong>App Password</strong> in Google Account Security.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="mail_from_name">Sender Name</label>
                            <input type="text" id="mail_from_name" name="mail_from_name" placeholder="St. Monica Junior School" value="<?= e($mailConfig['from_name'] ?? 'St. Monica Junior School') ?>" class="cms-input text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="mail_from_email">Sender Email</label>
                            <input type="email" id="mail_from_email" name="mail_from_email" placeholder="no-reply@stmonicakasanje.ac.ug" value="<?= e($mailConfig['from_address'] ?? 'no-reply@stmonicakasanje.ac.ug') ?>" class="cms-input text-xs">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="cms-btn cms-btn-primary text-xs">
                            <span class="material-symbols-outlined text-[16px]">save</span>
                            <span>Save Email & SMTP Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Test Email & Templates -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Test Email Card -->
            <div class="cms-card p-6 border-t-4 border-t-blue-600">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-blue-600 text-[20px]">send_spark</span>
                    <h3 class="font-bold text-slate-900 text-sm">Send a Test Email</h3>
                </div>
                <p class="text-xs text-slate-500 mb-4">Send a sample email to any address to verify your SMTP connection and credentials.</p>

                <form method="POST" action="<?= admin_url('settings/') ?>" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="send_test_email">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Recipient Email</label>
                        <input type="email" name="test_email" required placeholder="e.g. nakibingecollins1@gmail.com" value="<?= e($_SESSION['admin_email'] ?? 'nakibingecollins1@gmail.com') ?>" class="cms-input text-xs">
                    </div>

                    <button type="submit" <?= !$emailConfigured ? 'disabled' : '' ?> class="cms-btn cms-btn-accent w-full text-xs <?= !$emailConfigured ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        <span class="material-symbols-outlined text-[16px]">mark_email_read</span>
                        <span>Send Test Email</span>
                    </button>
                    <?php if (!$emailConfigured): ?>
                        <p class="text-[11px] text-amber-600 mt-1">Please fill in SMTP Host above and save before testing.</p>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Gmail Setup Instructions Card -->
            <div class="cms-card p-6 border-l-4 border-l-red-500 bg-red-50/20">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-red-600 text-[20px]">help</span>
                    <h3 class="font-bold text-slate-900 text-sm">How to Connect Gmail / Google Workspace</h3>
                </div>
                <p class="text-xs text-slate-600 mb-3">Google blocks regular passwords on SMTP. You must generate a free 16-character <strong>App Password</strong>:</p>
                <ol class="text-xs text-slate-600 space-y-2 list-decimal list-inside pl-1">
                    <li>Enable <strong>2-Step Verification</strong> on your Google Account: <a href="https://myaccount.google.com/security" target="_blank" class="text-blue-600 hover:underline font-semibold">myaccount.google.com/security</a>.</li>
                    <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-blue-600 hover:underline font-semibold">App Passwords &rarr;</a></li>
                    <li>Type an app name (e.g. <code>St Monica Website</code>) and click <strong>Create</strong>.</li>
                    <li>Copy the 16-letter code (e.g. <code>abcd efgh ijkl mnop</code>) and paste it into the <strong>SMTP Password</strong> field on the left.</li>
                    <li>Ensure <strong>SMTP Username</strong> is your full Gmail address (e.g. <code>nakibingecollins1@gmail.com</code>).</li>
                </ol>
            </div>

            <!-- Email Templates Shortcut -->
            <div class="cms-card p-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-purple-600 text-[20px]">mark_email_unread</span>
                    <h3 class="font-bold text-slate-900 text-sm">Status Notification Templates</h3>
                </div>
                <p class="text-xs text-slate-500 mb-4">Customize the text and subject lines for each admission status (Accepted, Under Review, Contacted, Rejected, Withdrawn).</p>
                <a href="<?= admin_url('email-templates/') ?>" class="cms-btn cms-btn-outline w-full text-xs flex items-center justify-center gap-1.5">
                    <span>Manage Email Templates</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>

    <script>
    function fillGmailPreset() {
        document.getElementById('smtp_host').value = 'smtp.gmail.com';
        document.getElementById('smtp_port').value = '587';
        document.getElementById('smtp_encryption').value = 'tls';
        const fromEmail = document.getElementById('mail_from_email').value;
        const currentUsername = document.getElementById('smtp_username').value;
        if (!currentUsername || currentUsername === 'gmail.com') {
            document.getElementById('smtp_username').value = fromEmail ? fromEmail : 'nakibingecollins1@gmail.com';
        }
    }

    function togglePassVisibility() {
        const input = document.getElementById('smtp_password');
        const icon = document.getElementById('passToggleIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    }
    </script>
<?php endif; ?>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
