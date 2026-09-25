<?php
/**
 * St. Monica Junior School - About Us Public API
 * Endpoint: GET /ADMIN/api/about/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $rawSections = Database::fetchAll("SELECT `section_key`, `title`, `content`, `image` FROM `about_content`");
    $sections = [];
    foreach ($rawSections as $sec) {
        $sections[$sec['section_key']] = $sec;
    }

    $coreValues = Database::fetchAll("SELECT `id`, `title`, `display_order` FROM `core_values` WHERE `status` = 'active' ORDER BY `display_order` ASC");
    $facilities = Database::fetchAll("SELECT `id`, `title`, `description`, `image`, `display_order` FROM `facilities` WHERE `status` = 'active' ORDER BY `display_order` ASC");
    $administrators = Database::fetchAll("SELECT `id`, `name`, `position`, `department`, `biography`, `email`, `photo` FROM `staff` WHERE `status` = 'published' AND `deleted_at` IS NULL AND `department` = 'Administration' ORDER BY `display_order` ASC");

    json_response(true, 'About Us content loaded successfully.', [
        'sections'       => $sections,
        'core_values'    => $coreValues,
        'facilities'     => $facilities,
        'administrators' => $administrators
    ]);
} catch (Exception $e) {
    json_error($e, 'Failed to retrieve About Us data.');
}
