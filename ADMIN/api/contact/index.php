<?php
/**
 * St. Monica Junior School - Global Contact Information Public API
 * Endpoint: GET /ADMIN/api/contact/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $contact = Database::fetchOne("SELECT `school_name`, `phone`, `alternative_phone`, `email`, `admissions_email`, `opening_hours`, `address`, `village`, `district`, `country`, `map_url`, `whatsapp`, `facebook`, `instagram`, `youtube`, `updated_at` FROM `contact_information` LIMIT 1");

    if ($contact) {
        json_response(true, 'Contact information retrieved successfully.', $contact);
    } else {
        json_response(false, 'Contact information not found.', null, 404);
    }
} catch (Exception $e) {
    json_response(false, 'Failed to retrieve contact information: ' . $e->getMessage(), null, 500);
}
