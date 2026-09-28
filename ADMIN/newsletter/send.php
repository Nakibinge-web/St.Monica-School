<?php
/**
 * St. Monica Junior School CMS - Newsletter sending endpoint (JSON, POST only)
 *
 *   action=test   subject, body          -> send one test copy to the signed-in admin
 *   action=start  subject, body          -> create the newsletter, queue all subscribers
 *   action=batch  campaign_id            -> send the next batch; returns progress
 *   action=retry  campaign_id            -> re-queue failed recipients for another attempt
 *
 * The Compose page calls "batch" repeatedly until done, showing live progress.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/services/NewsletterService.php';

if (!is_logged_in()) {
    json_response(false, 'Your session has expired. Please sign in again.', null, 401);
}
if (!can_manage('newsletter')) {
    json_response(false, 'You do not have permission to send newsletters.', null, 403);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Method not allowed.', null, 405);
}
if (!verify_csrf_token()) {
    json_response(false, 'Your security token has expired. Please refresh the page and try again.', null, 403);
}

@set_time_limit(120);
$action = $_POST['action'] ?? '';

/** Validate and clean the composed message */
function newsletter_input(): array {
    $subject = trim(preg_replace('/[\r\n]+/', ' ', (string)($_POST['subject'] ?? '')));
    $body = sanitize_html((string)($_POST['body'] ?? ''));
    $plain = trim(html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8'));

    if ($subject === '' || mb_strlen($subject) > 200) {
        json_response(false, 'Please enter a subject (up to 200 characters).', ['field' => 'subject'], 422);
    }
    if ($plain === '' && stripos($body, '<img') === false) {
        json_response(false, 'Please write the newsletter message.', ['field' => 'body'], 422);
    }
    return [$subject, $body];
}

try {
    switch ($action) {
        case 'test': {
            [$subject, $body] = newsletter_input();
            $admin = current_admin();
            $to = $admin['email'] ?? '';
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                json_response(false, 'Your admin account has no valid email address to send the test to. Add one under My Profile.', null, 422);
            }
            // Same layout as the real newsletter, with a placeholder unsubscribe link
            $message = NewsletterService::buildMessage('[TEST] ' . $subject, $body, $to, str_repeat('0', 64));
            $result = EmailService::sendBatch([$message])[0];
            if (!$result['ok']) {
                json_response(false, 'The test email could not be sent: ' . $result['error'], null, 502);
            }
            log_activity('Newsletter Test Sent', "Subject: {$subject} | To: {$to}", 'newsletter');
            json_response(true, "Test email sent to {$to}. Check your inbox (and spam folder).");
        }

        case 'start': {
            [$subject, $body] = newsletter_input();
            if (!EmailService::isConfigured()) {
                json_response(false, 'Email sending is not set up yet. Add your SMTP details under Settings > Email first.', null, 422);
            }
            if (NewsletterService::activeCount() === 0) {
                json_response(false, 'There are no active subscribers to send to yet.', null, 422);
            }
            $admin = current_admin();
            $campaign = NewsletterService::createCampaign($subject, $body, (int)($admin['id'] ?? 0) ?: null);
            log_activity('Newsletter Sending Started', "Subject: {$subject} | Recipients: {$campaign['recipients']}", 'newsletter', $campaign['id']);
            json_response(true, 'Newsletter queued.', NewsletterService::refreshCampaign($campaign['id']));
        }

        case 'batch': {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $progress = NewsletterService::sendNextBatch($campaignId);
            if ($progress['done']) {
                $c = Database::fetchOne("SELECT `subject` FROM `newsletter_campaigns` WHERE `id` = :id", ['id' => $campaignId]);
                log_activity('Newsletter Sent', "Subject: " . ($c['subject'] ?? '') . " | Sent: {$progress['sent']} | Failed: {$progress['failed']}", 'newsletter', $campaignId);
            }
            json_response(true, 'Batch processed.', $progress);
        }

        case 'retry': {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $n = NewsletterService::requeueFailed($campaignId);
            json_response(true, "{$n} failed recipient(s) queued for another attempt.", NewsletterService::refreshCampaign($campaignId));
        }

        default:
            json_response(false, 'Unknown action.', null, 400);
    }
} catch (RuntimeException $e) {
    json_response(false, $e->getMessage(), null, 404);
} catch (Exception $e) {
    json_response(false, 'Something went wrong: ' . $e->getMessage(), null, 500);
}
