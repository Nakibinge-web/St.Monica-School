<?php
/**
 * St. Monica Junior School CMS
 * Outgoing Email Service (SMTP via PHPMailer, with template support)
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

    private static function config(): array {
        if (empty(self::$mailConfig)) {
            $allConfig = require CMS_ROOT . '/includes/config.php';
            self::$mailConfig = $allConfig['mail'] ?? [];
        }
        return self::$mailConfig;
    }

    /**
     * Whether SMTP has been configured. Without it, emails are logged only,
     * so the rest of the system never breaks because credentials are missing.
     */
    public static function isConfigured(): bool {
        $cfg = self::config();
        return !empty($cfg['host']);
    }

    /**
     * Send a raw HTML email. Returns true on success (or when silently
     * logged because SMTP isn't configured), false on a genuine send failure.
     */
    public static function send(string $toEmail, string $subject, string $bodyHtml, ?string $toName = null): bool {
        $cfg = self::config();

        if (!self::isConfigured() || !class_exists(PHPMailer::class)) {
            // Graceful degradation: record intent without failing the calling action
            self::logAttempt($toEmail, $subject, self::isConfigured() ? 'PHPMailer library missing' : 'SMTP not configured');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->Port = $cfg['port'] ?? 587;
            $mail->SMTPAuth = !empty($cfg['username']);
            if (!empty($cfg['username'])) {
                $mail->Username = $cfg['username'];
                $mail->Password = $cfg['password'] ?? '';
            }
            if (!empty($cfg['encryption'])) {
                $mail->SMTPSecure = $cfg['encryption'];
            }

            $mail->setFrom($cfg['from_address'] ?? 'no-reply@example.com', $cfg['from_name'] ?? 'St. Monica CMS');
            $mail->addAddress($toEmail, $toName ?? '');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = trim(strip_tags($bodyHtml));

            $mail->send();
            return true;
        } catch (PHPMailerException|Exception $e) {
            self::logAttempt($toEmail, $subject, $e->getMessage());
            return false;
        }
    }

    /**
     * Send using a stored template, replacing {{placeholder}} tokens with
     * values from $vars. Only plain text substitution is performed - no
     * code evaluation ever occurs on template content.
     */
    public static function sendTemplate(string $templateKey, string $toEmail, array $vars = [], ?string $toName = null): bool {
        try {
            $template = Database::fetchOne(
                "SELECT `subject`, `body_html` FROM `email_templates` WHERE `template_key` = :key LIMIT 1",
                ['key' => $templateKey]
            );
        } catch (Exception $e) {
            return false;
        }

        if (!$template) {
            return false;
        }

        $subject = self::applyPlaceholders($template['subject'], $vars);
        $body = self::applyPlaceholders($template['body_html'], $vars);

        return self::send($toEmail, $subject, $body, $toName);
    }

    private static function applyPlaceholders(string $text, array $vars): string {
        $search = [];
        $replace = [];
        foreach ($vars as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = e((string)$value);
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
