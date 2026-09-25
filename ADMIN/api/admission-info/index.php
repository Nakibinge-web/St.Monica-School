<?php
/**
 * St. Monica Junior School - Admission Information Public API
 * Endpoint: GET /ADMIN/api/admission-info/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    // Retrieve published admission content
    $items = Database::fetchAll(
        "SELECT `section_key`, `title`, `subtitle`, `content`, `icon`, `display_order` 
         FROM `admission_info` 
         WHERE `status` = 'published' 
         ORDER BY `display_order` ASC, `id` ASC"
    );

    // Group items into structured payload
    $procedure = [];
    $sections = [];

    foreach ($items as $row) {
        if (strpos($row['section_key'], 'step_') === 0) {
            $procedure[] = $row;
        } else {
            $sections[$row['section_key']] = $row;
        }
    }

    // Include global admissions contact info from contact_information
    $contact = Database::fetchOne(
        "SELECT `school_name`, `phone`, `alternative_phone`, `admissions_email`, `email`, `opening_hours`, `address` 
         FROM `contact_information` LIMIT 1"
    );

    json_response(true, 'Admission information retrieved successfully.', [
        'procedure_steps' => $procedure,
        'sections'        => $sections,
        'contact'         => $contact
    ]);
} catch (Exception $e) {
    json_error($e, 'Failed to retrieve admission info.');
}
