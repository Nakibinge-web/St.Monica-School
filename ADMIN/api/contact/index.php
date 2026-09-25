<?php
/**
 * St. Monica Junior School - Global Contact Information Public API
 * Endpoint: GET /ADMIN/api/contact/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/services/SettingsService.php';

try {
    $contact = Database::fetchOne("SELECT `school_name`, `phone`, `alternative_phone`, `email`, `admissions_email`, `opening_hours`, `address`, `village`, `district`, `country`, `map_url`, `whatsapp`, `facebook`, `instagram`, `youtube`, `updated_at` FROM `contact_information` LIMIT 1");

    // Site-wide maintenance flag is folded into this endpoint since every public
    // page already calls it on load, rather than adding a dedicated round-trip.
    $maintenance = [
        'maintenance_mode'    => SettingsService::getBool('maintenance_mode', false),
        'maintenance_message' => SettingsService::get('maintenance_message', '')
    ];

    if ($contact) {
        json_response(true, 'Contact information retrieved successfully.', array_merge($contact, $maintenance));
    } else {
        json_response(false, 'Contact information not found.', $maintenance, 404);
    }
} catch (Exception $e) {
    json_error($e, 'Failed to retrieve contact information.');
}
