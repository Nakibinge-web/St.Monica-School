<?php
/**
 * St. Monica Junior School - Media Gallery Public API
 * Endpoint: GET /ADMIN/api/gallery/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $category = trim($_GET['category'] ?? '');
    $where = ["`status` = 'published'"];
    $params = [];

    if (!empty($category) && $category !== 'All Photos') {
        $where[] = "`category` = :category";
        $params['category'] = $category;
    }

    $whereSql = implode(' AND ', $where);
    $gallery = Database::fetchAll("SELECT `id`, `title`, `description`, `file_path`, `category`, `display_order` FROM `gallery` WHERE {$whereSql} ORDER BY `display_order` ASC, `id` DESC", $params);

    json_response(true, 'Gallery images retrieved successfully.', $gallery);
} catch (Exception $e) {
    json_response(false, 'Failed to retrieve gallery: ' . $e->getMessage(), null, 500);
}
