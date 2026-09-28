<?php
/**
 * St. Monica Junior School CMS
 * Newsletter Service: subscriptions, unsubscribe links, and batched sending of newsletters.
 *
 * Sending model: a newsletter ("campaign") snapshots every current subscriber into
 * newsletter_deliveries as 'pending'. The admin page then calls sendNextBatch() repeatedly,
 * each call emailing a small batch over one SMTP connection. Because each recipient's row is
 * marked sent/failed as it goes, a send can be resumed at any time and nobody is emailed twice.
 */

if (!defined('CMS_ROOT')) {
    define('CMS_ROOT', dirname(__DIR__));
}

require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/services/EmailService.php';

class NewsletterService {
    /** Emails sent per request; small enough to finish well within PHP/SMTP timeouts */
    public const BATCH_SIZE = 15;

    public static function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    /**
     * Add (or re-activate) a subscriber.
     * @return string 'subscribed' | 'already' | 'resubscribed'
     */
    public static function subscribe(string $email, ?string $source = null, ?string $ip = null): string {
        $email = self::normalizeEmail($email);
        $existing = Database::fetchOne("SELECT `id`, `status` FROM `newsletter_subscribers` WHERE `email` = :e", ['e' => $email]);

        if ($existing) {
            if ($existing['status'] === 'subscribed') {
                return 'already';
            }
            Database::update('newsletter_subscribers', [
                'status'          => 'subscribed',
                'unsubscribed_at' => null,
                'subscribed_at'   => date('Y-m-d H:i:s'),
                'source'          => $source,
            ], 'id = :id', ['id' => $existing['id']]);
            return 'resubscribed';
        }

        Database::insert('newsletter_subscribers', [
            'email'             => $email,
            'status'            => 'subscribed',
            'unsubscribe_token' => bin2hex(random_bytes(32)),
            'source'            => $source,
            'ip_address'        => $ip,
        ]);
        return 'subscribed';
    }

    /**
     * Unsubscribe by the private token from an email link.
     * @return array|null The subscriber row (email, status) or null if the token is unknown
     */
    public static function unsubscribeByToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = Database::fetchOne("SELECT `id`, `email`, `status` FROM `newsletter_subscribers` WHERE `unsubscribe_token` = :t", ['t' => $token]);
        if (!$row) {
            return null;
        }
        if ($row['status'] !== 'unsubscribed') {
            Database::update('newsletter_subscribers', [
                'status'          => 'unsubscribed',
                'unsubscribed_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $row['id']]);
            $row['status'] = 'unsubscribed';
        }
        return $row;
    }

    public static function findByToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::fetchOne("SELECT `id`, `email`, `status` FROM `newsletter_subscribers` WHERE `unsubscribe_token` = :t", ['t' => $token]) ?: null;
    }

    /**
     * Absolute URL for links inside emails. Uses APP_URL when set (recommended in production),
     * otherwise the address the admin panel is currently being used on.
     */
    public static function absoluteUrl(string $path): string {
        $appUrl = rtrim((string)(getenv('APP_URL') ?: ''), '/');
        if ($appUrl !== '') {
            return $appUrl . '/' . ltrim($path, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https://' : 'http://') . $host . public_url($path);
    }

    public static function unsubscribeUrl(string $token): string {
        return self::absoluteUrl('ADMIN/api/newsletter/unsubscribe.php?token=' . $token);
    }

    public static function activeCount(): int {
        return (int)Database::fetchColumn("SELECT COUNT(*) FROM `newsletter_subscribers` WHERE `status` = 'subscribed'");
    }

    /**
     * Build the final email (branded layout + personal unsubscribe footer) for one recipient.
     */
    public static function buildMessage(string $subject, string $bodyHtml, string $email, string $token): array {
        // Links/images that point at this site with a site-relative path ("/St.monica/...") would be
        // broken inside an email client, so make them absolute
        $bodyHtml = preg_replace_callback(
            '/\b(href|src)=(["\'])(\/[^"\']*)\2/i',
            fn($m) => $m[1] . '=' . $m[2] . self::absoluteUrl(ltrim(preg_replace('#^' . preg_quote(rtrim(public_url(''), '/'), '#') . '#', '', $m[3]), '/')) . $m[2],
            $bodyHtml
        );
        $unsubscribeUrl = self::unsubscribeUrl($token);
        $safeUrl = htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        $footer = "You are receiving this because <strong>{$safeEmail}</strong> subscribed to our school newsletter on our website. "
                . "<a href=\"{$safeUrl}\" style=\"color:#d93633; text-decoration:underline;\">Unsubscribe</a><br>&copy; {$year} St. Monica Junior School Kasanje.";

        $html = EmailService::wrapHtml($bodyHtml, $subject, 'St. Monica Junior School Kasanje', 'School Newsletter', $footer);
        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $bodyHtml)), ENT_QUOTES, 'UTF-8'))
              . "\n\n---\nTo unsubscribe from the St. Monica newsletter, open: {$unsubscribeUrl}";

        return [
            'to'      => $email,
            'subject' => $subject,
            'html'    => $html,
            'text'    => $text,
            'headers' => [
                // Lets Gmail/Outlook show a one-click "Unsubscribe" button (RFC 2369 / RFC 8058)
                'List-Unsubscribe'      => '<' . $unsubscribeUrl . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        ];
    }

    /**
     * Create a newsletter and queue every current subscriber as a pending delivery.
     * @return array ['id' => campaign id, 'recipients' => count]
     */
    public static function createCampaign(string $subject, string $bodyHtml, ?int $adminId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $campaignId = Database::insert('newsletter_campaigns', [
                'subject'    => $subject,
                'body_html'  => $bodyHtml,
                'status'     => 'sending',
                'created_by' => $adminId,
            ]);
            $stmt = $pdo->prepare(
                "INSERT INTO `newsletter_deliveries` (`campaign_id`, `subscriber_id`, `email`, `status`)
                 SELECT :cid, `id`, `email`, 'pending' FROM `newsletter_subscribers` WHERE `status` = 'subscribed'"
            );
            $stmt->execute(['cid' => $campaignId]);
            $recipients = $stmt->rowCount();
            Database::update('newsletter_campaigns', ['recipient_count' => $recipients], 'id = :id', ['id' => $campaignId]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        return ['id' => (int)$campaignId, 'recipients' => (int)$recipients];
    }

    /**
     * Send the next batch of pending deliveries for a newsletter.
     * @return array progress: recipients, sent, failed, pending, done, last_error
     */
    public static function sendNextBatch(int $campaignId, int $batchSize = self::BATCH_SIZE): array {
        $campaign = Database::fetchOne("SELECT * FROM `newsletter_campaigns` WHERE `id` = :id", ['id' => $campaignId]);
        if (!$campaign) {
            throw new RuntimeException('Newsletter not found.');
        }

        $batchSize = max(1, min(50, $batchSize));
        $pending = Database::fetchAll(
            "SELECT d.`id`, d.`email`, s.`unsubscribe_token`, s.`status` AS `subscriber_status`
             FROM `newsletter_deliveries` d
             LEFT JOIN `newsletter_subscribers` s ON s.`id` = d.`subscriber_id`
             WHERE d.`campaign_id` = :cid AND d.`status` = 'pending'
             ORDER BY d.`id` ASC LIMIT {$batchSize}",
            ['cid' => $campaignId]
        );

        $messages = [];
        $lastError = null;
        foreach ($pending as $row) {
            // Someone who unsubscribed (or was removed) after the send started must not be emailed
            if (($row['subscriber_status'] ?? null) !== 'subscribed') {
                Database::update('newsletter_deliveries', ['status' => 'skipped', 'attempted_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
                continue;
            }
            $messages[$row['id']] = self::buildMessage($campaign['subject'], $campaign['body_html'], $row['email'], $row['unsubscribe_token']);
        }

        $batchSent = 0;
        $batchFailed = 0;
        if ($messages) {
            foreach (EmailService::sendBatch($messages) as $deliveryId => $result) {
                Database::update('newsletter_deliveries', [
                    'status'       => $result['ok'] ? 'sent' : 'failed',
                    'error'        => $result['ok'] ? null : $result['error'],
                    'attempted_at' => date('Y-m-d H:i:s'),
                ], 'id = :id', ['id' => $deliveryId]);
                if ($result['ok']) {
                    $batchSent++;
                } else {
                    $batchFailed++;
                    $lastError = $result['error'];
                }
            }
        }

        return self::refreshCampaign($campaignId) + [
            'last_error'   => $lastError,
            'batch_sent'   => $batchSent,
            'batch_failed' => $batchFailed,
        ];
    }

    /**
     * Put failed deliveries of a newsletter back in the queue so they can be retried.
     */
    public static function requeueFailed(int $campaignId): int {
        $n = Database::update('newsletter_deliveries', ['status' => 'pending', 'error' => null], "campaign_id = :cid AND status = 'failed'", ['cid' => $campaignId]);
        Database::update('newsletter_campaigns', ['status' => 'sending', 'completed_at' => null], 'id = :id', ['id' => $campaignId]);
        return (int)$n;
    }

    /**
     * Recount a newsletter's deliveries and update its status.
     */
    public static function refreshCampaign(int $campaignId): array {
        $counts = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach (Database::fetchAll("SELECT `status`, COUNT(*) AS c FROM `newsletter_deliveries` WHERE `campaign_id` = :cid GROUP BY `status`", ['cid' => $campaignId]) as $r) {
            $counts[$r['status']] = (int)$r['c'];
        }
        $done = $counts['pending'] === 0;
        $status = !$done ? 'sending' : ($counts['failed'] > 0 ? 'partial' : 'sent');

        Database::update('newsletter_campaigns', [
            'status'       => $status,
            'sent_count'   => $counts['sent'],
            'failed_count' => $counts['failed'],
            'completed_at' => $done ? date('Y-m-d H:i:s') : null,
        ], 'id = :id', ['id' => $campaignId]);

        return [
            'campaign_id' => $campaignId,
            'status'      => $status,
            'recipients'  => array_sum($counts),
            'sent'        => $counts['sent'],
            'failed'      => $counts['failed'],
            'skipped'     => $counts['skipped'],
            'pending'     => $counts['pending'],
            'done'        => $done,
        ];
    }
}
