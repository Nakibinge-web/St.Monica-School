<?php
/**
 * St. Monica Junior School - Staff Public API
 * Endpoint: GET /ADMIN/api/staff/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $department = trim($_GET['department'] ?? '');
    $where = ["`status` = 'published'"];
    $params = [];

    if (!empty($department)) {
        $where[] = "`department` = :dept";
        $params['dept'] = $department;
    }

    $whereSql = implode(' AND ', $where);
    $staff = Database::fetchAll("SELECT `id`, `name`, `position`, `department`, `biography`, `email`, `photo`, `display_order`, `is_featured` FROM `staff` WHERE {$whereSql} ORDER BY `display_order` ASC, `id` ASC", $params);

    json_response(true, 'Staff team retrieved successfully.', $staff);
} catch (Exception $e) {
    json_response(false, 'Failed to retrieve staff team: ' . $e->getMessage(), null, 500);
}
