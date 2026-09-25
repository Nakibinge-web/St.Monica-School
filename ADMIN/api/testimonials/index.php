<?php
/**
 * St. Monica Junior School - Testimonials Public API
 * Endpoint: GET /ADMIN/api/testimonials/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    // Only return published testimonials
    $testimonials = Database::fetchAll(
        "SELECT `id`, `name`, `role`, `child_info`, `rating`, `content`, `photo`, `initials`, `display_order` 
         FROM `testimonials` 
         WHERE `status` = 'published' AND `deleted_at` IS NULL
         ORDER BY `display_order` ASC, `id` DESC"
    );

    json_response(true, 'Testimonials retrieved successfully.', $testimonials);
} catch (Exception $e) {
    json_error($e, 'Failed to retrieve testimonials.');
}
