<?php
/**
 * St. Monica Junior School - Public Contact Form Submission API
 * Endpoint: POST /ADMIN/api/contact/submit.php
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/services/NotificationService.php';
require_once CMS_ROOT . '/services/EmailService.php';

// Handle CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    json_response(true, 'Preflight OK', null, 200);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Method not allowed. Only POST requests are accepted.', null, 405);
}

// Parse request data (support form-data, x-www-form-urlencoded, and json)
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    if (is_array($jsonData)) {
        $input = array_merge($input, $jsonData);
    }
}

$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$phone   = trim($input['phone'] ?? '');
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$ip      = $_SERVER['REMOTE_ADDR'] ?? null;

// Validation
$errors = [];
if (empty($name) || mb_strlen($name) < 3) {
    $errors['name'] = 'Please enter your full name (minimum 3 characters).';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (empty($subject) || mb_strlen($subject) < 3) {
    $errors['subject'] = 'Please enter a subject for your message.';
}
if (empty($message) || mb_strlen($message) < 5) {
    $errors['message'] = 'Please enter your message.';
}

if (!empty($errors)) {
    json_response(false, 'Please correct the highlighted validation errors.', ['errors' => $errors], 422);
}

try {
    // Lightweight spam throttle: block more than 5 submissions from the same
    // IP within 10 minutes (no CAPTCHA in place yet).
    if ($ip) {
        $recentCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `enquiries` WHERE `ip_address` = :ip AND `submitted_at` >= (NOW() - INTERVAL 10 MINUTE)",
            ['ip' => $ip]
        );
        if ($recentCount >= 5) {
            json_response(false, 'Too many messages submitted recently. Please try again later.', null, 429);
        }
    }

    $newId = Database::insert('enquiries', [
        'name'       => $name,
        'email'      => $email,
        'phone'      => !empty($phone) ? $phone : null,
        'subject'    => $subject,
        'message'    => $message,
        'status'     => 'new',
        'ip_address' => $ip
    ]);

    log_activity('Public Enquiry Submitted', "Enquiry from {$name} ({$email}): {$subject}", 'enquiries', $newId);

    NotificationService::create(
        'New contact enquiry',
        "{$name} submitted an enquiry: \"{$subject}\"",
        admin_url('inquiries/view.php?id=' . $newId),
        'enquiry'
    );

    // Best-effort staff alert email; never blocks the public response
    try {
        $staffRecipients = Database::fetchAll("SELECT `name`, `email` FROM `admins` WHERE `status` = 'active' AND `role` IN ('super_admin', 'administrator')");
        foreach ($staffRecipients as $staff) {
            EmailService::sendTemplate('new_enquiry_staff', $staff['email'], [
                'enquiry_name'    => $name,
                'enquiry_email'   => $email,
                'enquiry_subject' => $subject,
                'school_name'     => 'St. Monica Junior School Kasanje'
            ], $staff['name']);
        }
    } catch (Exception $mailEx) {
        // Notification email is best-effort only
    }

    json_response(true, 'Thank you! Your message has been received and our team will get back to you soon.', [
        'id' => $newId
    ], 201);

} catch (Exception $e) {
    json_error($e, 'Unable to submit your message at this time.');
}
