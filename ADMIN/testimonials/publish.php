<?php
/**
 * St. Monica Junior School CMS - One-click Publish Testimonial
 * Publishes a draft testimonial (e.g. a visitor "Rate Us" review) straight from the list.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('testimonials');

// Return to the list the admin came from (same filters/page), never an outside URL
$back = $_POST['redirect'] ?? '';
if (!is_string($back) || !str_starts_with($back, admin_url('testimonials/'))) {
    $back = admin_url('testimonials/');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect($back);
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);

try {
    $item = Database::fetchOne("SELECT `id`, `name`, `rating`, `status` FROM `testimonials` WHERE `id` = :id AND `deleted_at` IS NULL", ['id' => $id]);

    if (!$item) {
        set_flash('danger', 'That testimonial no longer exists.');
    } elseif ($item['status'] === 'published') {
        set_flash('info', "The review by {$item['name']} is already published.");
    } else {
        Database::update('testimonials', ['status' => 'published'], 'id = :id', ['id' => $id]);
        log_activity('Published Testimonial', "Published review by {$item['name']} ({$item['rating']}\u{2605})", 'testimonials', $id);
        set_flash('success', "Review by {$item['name']} is now published and visible on the website.");
    }
} catch (Exception $e) {
    set_flash('danger', 'Could not publish the review: ' . $e->getMessage());
}

redirect($back);
