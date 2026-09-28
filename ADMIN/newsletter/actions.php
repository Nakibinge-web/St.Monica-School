<?php
/**
 * St. Monica Junior School CMS - Newsletter subscriber actions (POST only)
 * add | unsubscribe | resubscribe | delete
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_once CMS_ROOT . '/services/NewsletterService.php';
require_module('newsletter');

// Return to the list the admin came from (keeps filters/page), never an outside URL
$back = $_POST['redirect'] ?? '';
if (!is_string($back) || !str_starts_with($back, admin_url('newsletter/'))) {
    $back = admin_url('newsletter/');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect($back);
}
require_csrf();

// The shared delete-confirmation modal posts to "...?action=delete&id=N"
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

try {
    switch ($action) {
        case 'add':
            $email = NewsletterService::normalizeEmail((string)($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                set_flash('danger', 'Please enter a valid email address.');
                break;
            }
            $result = NewsletterService::subscribe($email, 'admin', null);
            if ($result === 'already') {
                set_flash('info', "{$email} is already subscribed.");
            } else {
                log_activity('Added Newsletter Subscriber', $email, 'newsletter');
                set_flash('success', $result === 'resubscribed' ? "{$email} has been re-subscribed." : "{$email} has been added to the newsletter.");
            }
            break;

        case 'unsubscribe':
        case 'resubscribe':
            $sub = Database::fetchOne("SELECT `id`, `email` FROM `newsletter_subscribers` WHERE `id` = :id", ['id' => $id]);
            if (!$sub) {
                set_flash('danger', 'Subscriber not found.');
                break;
            }
            $subscribe = $action === 'resubscribe';
            Database::update('newsletter_subscribers', [
                'status'          => $subscribe ? 'subscribed' : 'unsubscribed',
                'unsubscribed_at' => $subscribe ? null : date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);
            log_activity($subscribe ? 'Re-subscribed Newsletter Subscriber' : 'Unsubscribed Newsletter Subscriber', $sub['email'], 'newsletter', $id);
            set_flash('success', $subscribe ? "{$sub['email']} is subscribed again." : "{$sub['email']} will no longer receive newsletters.");
            break;

        case 'delete':
            $sub = Database::fetchOne("SELECT `id`, `email` FROM `newsletter_subscribers` WHERE `id` = :id", ['id' => $id]);
            if (!$sub) {
                set_flash('danger', 'Subscriber not found.');
                break;
            }
            // Past delivery records keep their own copy of the email address for the send history
            Database::delete('newsletter_subscribers', 'id = :id', ['id' => $id]);
            log_activity('Removed Newsletter Subscriber', $sub['email'], 'newsletter', $id);
            set_flash('success', "{$sub['email']} was removed from the subscriber list.");
            break;

        default:
            set_flash('danger', 'Unknown action.');
    }
} catch (Exception $e) {
    set_flash('danger', 'Could not complete the action: ' . $e->getMessage());
}

redirect($back);
