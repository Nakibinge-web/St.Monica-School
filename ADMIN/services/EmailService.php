<?php
/**
 * St. Monica Junior School CMS
 * Outgoing Email Service (SMTP via PHPMailer, with template support and status notifications)
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';

$vendorAutoload = dirname(CMS_ROOT) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService {
    private static array $mailConfig = [];
    private static ?string $lastError = null;

    /**
     * Clear cached configuration
     */
    public static function clearConfigCache(): void {
        self::$mailConfig = [];
        self::$lastError = null;
    }

    /**
     * Get the last error message from sending
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }

    /**
     * Set the last error message
     */
    public static function setLastError(?string $err): void {
        self::$lastError = $err;
    }

    /**
     * Load mail configuration from .env / config.php and override with site_settings if set
     */
    public static function config(): array {
        if (!empty(self::$mailConfig)) {
            return self::$mailConfig;
        }

        $allConfig = [];
        $configFile = CMS_ROOT . '/includes/config.php';
        if (file_exists($configFile)) {
            $allConfig = require $configFile;
        }
        $cfg = $allConfig['mail'] ?? [
            'host'        => getenv('MAIL_HOST') ?: '',
            'port'        => (int)(getenv('MAIL_PORT') ?: 587),
            'encryption'  => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'username'    => getenv('MAIL_USERNAME') ?: '',
            'password'    => getenv('MAIL_PASSWORD') !== false ? getenv('MAIL_PASSWORD') : '',
            'from_address'=> getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@stmonicakasanje.ac.ug',
            'from_name'   => getenv('MAIL_FROM_NAME') ?: 'St. Monica Junior School'
        ];

        // Override with site_settings database values if set
        try {
            require_once CMS_ROOT . '/services/SettingsService.php';
            $dbHost = SettingsService::get('smtp_host');
            if (!empty($dbHost)) {
                $cfg['host'] = trim($dbHost);
                $cfg['port'] = (int)SettingsService::get('smtp_port', 587);
                $cfg['encryption'] = SettingsService::get('smtp_encryption', 'tls');
                $cfg['username'] = trim(SettingsService::get('smtp_username', ''));
                $cfg['password'] = SettingsService::get('smtp_password', '');
            }
            $fromEmail = SettingsService::get('mail_from_email');
            if (!empty($fromEmail)) {
                $cfg['from_address'] = trim($fromEmail);
            }
            $fromName = SettingsService::get('mail_from_name');
            if (!empty($fromName)) {
                $cfg['from_name'] = trim($fromName);
            }
        } catch (Exception $e) {
            // Ignore if settings table isn't ready
        }

        self::$mailConfig = $cfg;
        return self::$mailConfig;
    }

    /**
     * Whether SMTP has been configured with a host.
     */
    public static function isConfigured(): bool {
        $cfg = self::config();
        return !empty($cfg['host']);
    }

    /**
     * Wrap raw body HTML in an official branded school email template layout
     */
    public static function wrapHtml(
        string $bodyHtml,
        string $title = 'Notification',
        string $schoolName = 'St. Monica Junior School Kasanje',
        string $tagline = 'Admissions Directorate',
        ?string $footerNoteHtml = null   // trusted HTML; defaults to the admissions notice
    ): string {
        $phone = '+256 752 406176';
        $email = 'stmonicajuniorschool2012@gmail.com';
        try {
            if (class_exists('SettingsService')) {
                $phone = SettingsService::get('school_phone', $phone);
                $email = SettingsService::get('school_email', $email);
                $schoolName = SettingsService::get('school_name', $schoolName);
            }
        } catch (Exception $e) {}

        $safeSchoolName = htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeTagline = htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8');
        $currentYear = date('Y');
        $footerNote = $footerNoteHtml
            ?? "&copy; {$currentYear} {$safeSchoolName}. All rights reserved. This email was sent regarding an official admission application.";

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle}</title>
    <style>
        body { margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#1e293b; }
        .email-container { max-width:600px; margin:24px auto; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.06); border:1px solid #e2e8f0; }
        .email-header { background:linear-gradient(135deg, #1e2a4a 0%, #0d1629 100%); padding:28px 24px; text-align:center; color:#ffffff; border-bottom:4px solid #d93633; }
        .email-header h1 { margin:0; font-size:22px; font-weight:800; letter-spacing:-0.5px; text-transform:uppercase; color:#ffffff; }
        .email-header p { margin:6px 0 0 0; font-size:12px; color:#cbd5e1; letter-spacing:1px; text-transform:uppercase; }
        .email-body { padding:32px 28px; line-height:1.65; font-size:14px; color:#334155; }
        .email-body p { margin:0 0 16px 0; }
        .email-body ul, .email-body ol { margin:0 0 16px 0; padding-left:22px; }
        .email-body li { margin-bottom:6px; }
        .email-footer { background-color:#f8fafc; padding:24px 28px; text-align:center; font-size:12px; color:#64748b; border-top:1px solid #e2e8f0; }
        .email-footer p { margin:4px 0; }
        .email-footer a { color:#1e2a4a; text-decoration:none; font-weight:600; }
        .badge { display:inline-block; padding:4px 12px; border-radius:9999px; font-size:11px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; }
        @media only screen and (max-width: 600px) {
            .email-container { margin:0; border-radius:0; border:none; }
            .email-body { padding:24px 16px; }
            .email-header { padding:24px 16px; }
        }
    </style>
</head>
<body>
    <div style="background-color:#f1f5f9; padding:20px 10px;">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>{$safeSchoolName}</h1>
                <p>Always Aim Higher &bull; {$safeTagline}</p>
            </div>

            <!-- Main Content -->
            <div class="email-body">
                {$bodyHtml}
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p style="font-weight:700; color:#1e2a4a;">{$safeSchoolName}</p>
                <p>Kasanje, Wakiso District &bull; P.O. Box Kasanje, Uganda</p>
                <p>Tel: <a href="tel:{$safePhone}">{$safePhone}</a> &bull; Email: <a href="mailto:{$safeEmail}">{$safeEmail}</a></p>
                <p style="margin-top:12px; font-size:11px; color:#94a3b8;">{$footerNote}</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Send a raw HTML email.
     * Returns true on success, false on failure (with detailed reason stored in getLastError()).
     */
    public static function send(string $toEmail, string $subject, string $bodyHtml, ?string $toName = null, bool $wrapLayout = true): bool {
        self::$lastError = null;
        $cfg = self::config();

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient email address: ' . $toEmail;
            self::logAttempt($toEmail, $subject, self::$lastError);
            return false;
        }

        if (!self::isConfigured()) {
            self::$lastError = 'SMTP host is not configured. Please enter your SMTP server details in Website Settings > Email.';
            self::logAttempt($toEmail, $subject, 'SMTP not configured');
            return false;
        }

        if (!class_exists(PHPMailer::class)) {
            self::$lastError = 'PHPMailer library missing from vendor/autoload.php.';
            self::logAttempt($toEmail, $subject, self::$lastError);
            return false;
        }

        $finalHtml = $wrapLayout ? self::wrapHtml($bodyHtml, $subject, $cfg['from_name'] ?? 'St. Monica Junior School') : $bodyHtml;

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->Port       = (int)($cfg['port'] ?? 587);
            $mail->SMTPAuth   = !empty($cfg['username']);

            if (!empty($cfg['username'])) {
                $mail->Username = $cfg['username'];
                $mail->Password = $cfg['password'] ?? '';
            }

            $encryption = strtolower($cfg['encryption'] ?? 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // Timeout in seconds
            $mail->Timeout = 15;

            $fromEmail = !empty($cfg['from_address']) ? $cfg['from_address'] : 'no-reply@stmonicakasanje.ac.ug';
            $fromName  = !empty($cfg['from_name']) ? $cfg['from_name'] : 'St. Monica Junior School';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName ?? '');
            
            // Allow reply to school email
            if (!empty($cfg['from_address'])) {
                $mail->addReplyTo($cfg['from_address'], $fromName);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $finalHtml;
            $mail->AltBody = trim(strip_tags($bodyHtml));

            $mail->send();

            // Record success in activity log
            try {
                log_activity('Email Sent', "To: {$toEmail} | Subject: {$subject}", 'email');
            } catch (Exception $e) {}

            return true;
        } catch (PHPMailerException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Could not authenticate') !== false) {
                if (stripos($cfg['host'], 'gmail.com') !== false) {
                    $msg = "Google rejected the login. For Gmail, your SMTP Username must be your full email address (e.g. yourname@gmail.com, NOT 'gmail.com') and your password MUST be a 16-character Google App Password (not your normal Gmail password).";
                } else {
                    $msg = "SMTP Authentication Failed: Please check that your SMTP Username and Password are correct.";
                }
            }
            self::$lastError = $msg;
            self::logAttempt($toEmail, $subject, $msg);
            return false;
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Could not authenticate') !== false) {
                if (stripos($cfg['host'], 'gmail.com') !== false) {
                    $msg = "Google rejected the login. For Gmail, your SMTP Username must be your full email address (e.g. yourname@gmail.com, NOT 'gmail.com') and your password MUST be a 16-character Google App Password (not your normal Gmail password).";
                } else {
                    $msg = "SMTP Authentication Failed: Please check that your SMTP Username and Password are correct.";
                }
            }
            self::$lastError = $msg;
            self::logAttempt($toEmail, $subject, $msg);
            return false;
        }
    }

    /**
     * Send many individual emails over a single SMTP connection (used by the newsletter).
     * Each message goes to exactly one recipient, so no subscriber ever sees another's address.
     *
     * @param array $messages Each: ['to' => email, 'subject' => ..., 'html' => final HTML,
     *                        'text' => plain-text alternative, 'headers' => [name => value]]
     * @return array One result per message, same keys: ['ok' => bool, 'error' => ?string]
     */
    public static function sendBatch(array $messages): array {
        self::$lastError = null;
        $results = [];
        $cfg = self::config();

        $failAll = function (string $reason) use ($messages): array {
            self::$lastError = $reason;
            $out = [];
            foreach (array_keys($messages) as $k) $out[$k] = ['ok' => false, 'error' => $reason];
            return $out;
        };

        if (!self::isConfigured()) {
            return $failAll('SMTP host is not configured. Please enter your SMTP server details in Website Settings > Email.');
        }
        if (!class_exists(PHPMailer::class)) {
            return $failAll('PHPMailer library missing from vendor/autoload.php.');
        }

        $mail = new PHPMailer(true);
        $mail->CharSet    = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = (int)($cfg['port'] ?? 587);
        $mail->SMTPAuth   = !empty($cfg['username']);
        $mail->SMTPKeepAlive = true;   // reuse one connection for the whole batch
        $mail->Timeout    = 15;
        if (!empty($cfg['username'])) {
            $mail->Username = $cfg['username'];
            $mail->Password = $cfg['password'] ?? '';
        }
        $encryption = strtolower($cfg['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromEmail = !empty($cfg['from_address']) ? $cfg['from_address'] : 'no-reply@stmonicakasanje.ac.ug';
        $fromName  = !empty($cfg['from_name']) ? $cfg['from_name'] : 'St. Monica Junior School';

        foreach ($messages as $key => $msg) {
            $to = (string)($msg['to'] ?? '');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $results[$key] = ['ok' => false, 'error' => 'Invalid email address.'];
                continue;
            }
            try {
                $mail->clearAllRecipients();
                $mail->clearReplyTos();
                $mail->clearCustomHeaders();
                $mail->setFrom($fromEmail, $fromName);
                $mail->addReplyTo($fromEmail, $fromName);
                $mail->addAddress($to);
                foreach (($msg['headers'] ?? []) as $name => $value) {
                    $mail->addCustomHeader($name, $value);
                }
                $mail->isHTML(true);
                $mail->Subject = (string)$msg['subject'];
                $mail->Body    = (string)$msg['html'];
                $mail->AltBody = (string)($msg['text'] ?? trim(strip_tags((string)$msg['html'])));
                $mail->send();
                $results[$key] = ['ok' => true, 'error' => null];
            } catch (Exception $e) {
                $error = $e->getMessage();
                if (stripos($error, 'Could not authenticate') !== false) {
                    $error = 'SMTP login failed. Check the email settings (for Gmail, use an App Password).';
                }
                $results[$key] = ['ok' => false, 'error' => mb_substr($error, 0, 500)];
                self::$lastError = $error;
                // A broken connection would fail every remaining message the same way; reset it
                try { $mail->smtpClose(); } catch (Exception $ignored) {}
            }
        }

        try { $mail->smtpClose(); } catch (Exception $ignored) {}
        return $results;
    }

    /**
     * Send using a stored template, replacing {{placeholder}} tokens with
     * values from $vars.
     */
    public static function sendTemplate(string $templateKey, string $toEmail, array $vars = [], ?string $toName = null): bool {
        try {
            $template = Database::fetchOne(
                "SELECT `subject`, `body_html` FROM `email_templates` WHERE `template_key` = :key LIMIT 1",
                ['key' => $templateKey]
            );
        } catch (Exception $e) {
            self::$lastError = 'Database query failed for template: ' . $templateKey;
            return false;
        }

        if (!$template) {
            self::$lastError = "Email template '{$templateKey}' was not found in the database.";
            return false;
        }

        $subject = self::applyPlaceholders($template['subject'], $vars);
        $body = self::applyPlaceholders($template['body_html'], $vars);

        return self::send($toEmail, $subject, $body, $toName, true);
    }

    /**
     * Send a status-specific email to an admission applicant.
     * Maps the status ('Accepted', 'Under Review', 'Contacted', 'Rejected', 'Withdrawn', 'New')
     * to the appropriate template, applies placeholders and custom admin notes,
     * and sends via SMTP.
     */
    public static function sendStatusEmail(string $status, string $toEmail, array $vars = [], ?string $toName = null): bool {
        // Map status to template key
        $statusKeyMap = [
            'Accepted'     => 'application_status_accepted',
            'Under Review' => 'application_status_under_review',
            'Contacted'    => 'application_status_contacted',
            'Rejected'     => 'application_status_rejected',
            'Withdrawn'    => 'application_status_withdrawn',
            'New'          => 'application_status_new'
        ];

        $templateKey = $statusKeyMap[$status] ?? 'application_status_update';

        // Prepare default variables if not supplied
        $vars['application_status'] = $status;
        $vars['school_name'] = $vars['school_name'] ?? 'St. Monica Junior School Kasanje';
        $vars['school_phone'] = $vars['school_phone'] ?? '+256 752 406176';
        $vars['school_email'] = $vars['school_email'] ?? 'stmonicajuniorschool2012@gmail.com';
        $vars['date'] = date('M j, Y');

        // Format custom message from admin if present
        if (!empty($vars['custom_message'])) {
            $customText = nl2br(htmlspecialchars(trim($vars['custom_message']), ENT_QUOTES, 'UTF-8'));
            $vars['custom_message'] = '<div style="background:#f8fafc; border-left:4px solid #1e2a4a; padding:14px 18px; margin:18px 0; border-radius:4px;"><p style="margin:0 0 6px 0; font-weight:700; font-size:13px; color:#1e2a4a; text-transform:uppercase; letter-spacing:0.5px;">Message from Admissions Office:</p><p style="margin:0; font-size:14px; color:#334155; line-height:1.6;">' . $customText . '</p></div>';
        } else {
            $vars['custom_message'] = '';
        }

        // Try sending using the template from DB
        try {
            $template = Database::fetchOne(
                "SELECT `subject`, `body_html` FROM `email_templates` WHERE `template_key` = :key LIMIT 1",
                ['key' => $templateKey]
            );
        } catch (Exception $e) {
            $template = null;
        }

        // Fallback to generic template in DB if specific status template isn't found
        if (!$template && $templateKey !== 'application_status_update') {
            try {
                $template = Database::fetchOne(
                    "SELECT `subject`, `body_html` FROM `email_templates` WHERE `template_key` = 'application_status_update' LIMIT 1"
                );
            } catch (Exception $e) {
                $template = null;
            }
        }

        // Hardcoded reliable fallback in case database template rows are missing
        if (!$template) {
            $fallback = self::getStatusFallbackTemplate($status);
            $subject = self::applyPlaceholders($fallback['subject'], $vars);
            $body = self::applyPlaceholders($fallback['body_html'], $vars);
        } else {
            $subject = self::applyPlaceholders($template['subject'], $vars);
            $body = self::applyPlaceholders($template['body_html'], $vars);
        }

        return self::send($toEmail, $subject, $body, $toName, true);
    }

    /**
     * Fallback templates if database records are completely empty
     */
    private static function getStatusFallbackTemplate(string $status): array {
        switch ($status) {
            case 'Accepted':
                return [
                    'subject' => 'Congratulations! {{pupil_name}}\'s Admission Application Accepted — {{application_number}}',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>We are delighted to inform you that your admission application for <strong>{{pupil_name}}</strong> to join <strong>{{pupil_class}}</strong> at <strong>{{school_name}}</strong> has been <strong>ACCEPTED</strong>!</p><div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:15px; border-radius:8px; margin:16px 0;"><p style="margin:0 0 8px 0; color:#166534; font-weight:bold;">Admission Summary:</p><ul style="margin:0; padding-left:20px; color:#15803d;"><li><strong>Application Reference:</strong> {{application_number}}</li><li><strong>Pupil Name:</strong> {{pupil_name}}</li><li><strong>Admitted Class:</strong> {{pupil_class}}</li><li><strong>Status:</strong> Accepted</li></ul></div>{{custom_message}}<p><strong>Next Steps:</strong></p><ol><li>Please visit the school administration office in Kasanje to collect your child\'s official Admission Letter and Requirements List.</li><li>Confirm enrollment and complete registration formalities with the school registrar.</li><li>Settle the required admission and tuition fees through our partner banks or mobile money before the term begins.</li></ol><p>If you have any questions or need guidance, please feel free to reach out to us at <strong>{{school_phone}}</strong> or reply to this email at <strong>{{school_email}}</strong>.</p><p>Once again, congratulations and welcome to the {{school_name}} family!</p><p>Warm regards,<br><strong>Admissions Office</strong><br>{{school_name}}</p>'
                ];
            case 'Under Review':
                return [
                    'subject' => 'Application Under Review: {{pupil_name}} — {{application_number}}',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>Thank you for choosing <strong>{{school_name}}</strong> for your child\'s education.</p><p>The admission application for <strong>{{pupil_name}}</strong> ({{pupil_class}}, Application #<strong>{{application_number}}</strong>) is currently <strong>UNDER REVIEW</strong> by our admissions committee.</p><div style="background:#fffbeb; border:1px solid #fef3c7; padding:15px; border-radius:8px; margin:16px 0;"><p style="margin:0; color:#92400e;"><strong>Current Status:</strong> Under Review<br>Our team is verifying documents and assessing class capacity.</p></div>{{custom_message}}<p>We will contact you shortly if an assessment or additional documents are required. Thank you for your patience.</p><p>Best regards,<br><strong>Admissions Committee</strong><br>{{school_name}}</p>'
                ];
            case 'Contacted':
                return [
                    'subject' => 'Admissions Follow-up regarding {{pupil_name}} — {{application_number}}',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>This is a follow-up from the admissions office at <strong>{{school_name}}</strong> regarding application #<strong>{{application_number}}</strong> for <strong>{{pupil_name}}</strong> ({{pupil_class}}).</p><div style="background:#eff6ff; border:1px solid #bfdbfe; padding:15px; border-radius:8px; margin:16px 0;"><p style="margin:0; color:#1e40af;"><strong>Status:</strong> Contacted / Action Required<br>Our admissions team reached out or is requesting a brief follow-up regarding your application.</p></div>{{custom_message}}<p>Please call or WhatsApp our admissions team at <strong>{{school_phone}}</strong> or reply to <strong>{{school_email}}</strong> during office hours (Monday - Friday: 8:00 AM - 5:00 PM).</p><p>Thank you,<br><strong>Admissions Office</strong><br>{{school_name}}</p>'
                ];
            case 'Rejected':
                return [
                    'subject' => 'Update on Admission Application {{application_number}} — {{pupil_name}}',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>Thank you for your interest in <strong>{{school_name}}</strong> and for giving us the opportunity to consider <strong>{{pupil_name}}</strong> for admission into <strong>{{pupil_class}}</strong> (Application #<strong>{{application_number}}</strong>).</p><p>Due to class capacity limitations, we regret to inform you that we are unable to offer an admission placement for {{pupil_name}} at this time.</p>{{custom_message}}<p>Your application will remain on our active waiting list. We wish {{pupil_name}} the very best in their educational journey.</p><p>Sincerely,<br><strong>Admissions Committee</strong><br>{{school_name}}</p>'
                ];
            case 'Withdrawn':
                return [
                    'subject' => 'Application Withdrawn — {{application_number}} ({{pupil_name}})',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>This email confirms that admission application #<strong>{{application_number}}</strong> for <strong>{{pupil_name}}</strong> (Class: {{pupil_class}}) has been marked as <strong>WITHDRAWN</strong>.</p>{{custom_message}}<p>If this was in error, please contact our admissions office at <strong>{{school_phone}}</strong> or <strong>{{school_email}}</strong>.</p><p>Kind regards,<br><strong>Admissions Office</strong><br>{{school_name}}</p>'
                ];
            default:
                return [
                    'subject' => 'Update on Application {{application_number}} — {{pupil_name}}',
                    'body_html' => '<p>Dear {{parent_name}},</p><p>Your application <strong>{{application_number}}</strong> for <strong>{{pupil_name}}</strong> has been updated to: <strong>{{application_status}}</strong>.</p>{{custom_message}}<p>If you have any questions, please contact the {{school_name}} admissions office at {{school_phone}} or {{school_email}}.</p><p>Best regards,<br><strong>Admissions Office</strong><br>{{school_name}}</p>'
                ];
        }
    }

    /**
     * Send a test email to verify SMTP configuration
     */
    public static function sendTestEmail(string $toEmail): bool {
        $schoolName = 'St. Monica Junior School';
        $subject = 'Test Email from ' . $schoolName . ' CMS';
        $body = '<p>Hello,</p><p>This is a test email sent from the <strong>' . htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') . '</strong> CMS Administration System.</p><p>If you are reading this message, your outgoing SMTP email configuration is <strong>working perfectly!</strong></p><p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>';
        return self::send($toEmail, $subject, $body, 'Test Recipient', true);
    }

    private static function applyPlaceholders(string $text, array $vars): string {
        $search = [];
        $replace = [];
        foreach ($vars as $key => $value) {
            $search[] = '{{' . $key . '}}';
            // Allow pre-formatted HTML in custom_message
            if ($key === 'custom_message') {
                $replace[] = (string)$value;
            } else {
                $replace[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            }
        }
        return str_replace($search, $replace, $text);
    }

    private static function logAttempt(string $toEmail, string $subject, string $reason): void {
        try {
            log_activity('Email Not Sent', "To: {$toEmail} | Subject: {$subject} | Reason: {$reason}", 'email');
        } catch (Exception $e) {
            // Never let logging failures cascade
        }
    }
}
