<?php
/**
 * St. Monica Junior School - Newsletter Subscription API
 * Endpoint: POST /ADMIN/api/newsletter/subscribe.php   body: { email, source?, website? }
 * Used by the "Newsletter" box in the footer of every public page.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/services/NewsletterService.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    json_response(true, 'Preflight OK');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Method not allowed. Only POST requests are accepted.', null, 405);
}

$input = $_POST;
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) $input = array_merge($input, $json);
}

$emailRaw = $input['email'] ?? '';
$email = is_string($emailRaw) ? NewsletterService::normalizeEmail($emailRaw) : '';
$sourceRaw = $input['source'] ?? '';
$source = is_string($sourceRaw) ? mb_substr(preg_replace('/[^\w.\-\/ ]/u', '', $sourceRaw), 0, 100) : null;
$honeypot = $input['website'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Bots fill in the hidden "website" field; pretend success so they learn nothing
if (!empty($honeypot)) {
    json_response(true, 'Thank you for subscribing to our newsletter!');
}

if ($email === '' || mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Please enter a valid email address.', ['errors' => ['email' => 'Please enter a valid email address.']], 422);
}

try {
    // Basic abuse protection: at most 5 new sign-ups from one address every 10 minutes
    if ($ip) {
        $recent = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `newsletter_subscribers` WHERE `ip_address` = :ip AND `subscribed_at` >= (NOW() - INTERVAL 10 MINUTE)",
            ['ip' => $ip]
        );
        if ($recent >= 5) {
            json_response(false, 'Too many sign-ups from your network. Please try again in a few minutes.', null, 429);
        }
    }

    $result = NewsletterService::subscribe($email, $source ?: 'website', $ip);

    if ($result !== 'already') {
        log_activity('Newsletter Subscription', ($result === 'resubscribed' ? 'Re-subscribed: ' : 'New subscriber: ') . $email, 'newsletter');

        try {
            require_once CMS_ROOT . '/services/NotificationService.php';
            NotificationService::create(
                'New newsletter subscription',
                $result === 'resubscribed'
                    ? "Existing subscriber re-subscribed: {$email}"
                    : "{$email} subscribed to the school newsletter via " . ($source ?: 'website'),
                admin_url('newsletter/'),
                'newsletter'
            );
        } catch (Exception $notifEx) {
            // Best effort; never break public response
        }
    }

    $message = match ($result) {
        'already'      => 'You are already subscribed to our newsletter. Thank you!',
        'resubscribed' => 'Welcome back! You are subscribed to our newsletter again.',
        default        => 'Thank you for subscribing to our newsletter!',
    };
    json_response(true, $message, ['status' => $result]);
} catch (Exception $e) {
    json_error($e, 'We could not complete your subscription right now. Please try again later.');
}
