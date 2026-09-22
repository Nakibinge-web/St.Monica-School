<?php
/**
 * St. Monica Junior School - Media Assets Public API
 * Endpoint: GET /ADMIN/api/media/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

$category = trim($_GET['category'] ?? '');
$type = trim($_GET['type'] ?? '');

$where = ["`status` = 'active'"];
$params = [];

if (!empty($category)) {
    $where[] = "`category` = :cat";
    $params['cat'] = $category;
}
if (!empty($type)) {
    $where[] = "`file_type` = :type";
    $params['type'] = $type;
}

$whereSql = implode(' AND ', $where);

try {
    $media = Database::fetchAll(
        "SELECT `id`, `title`, `alt_text`, `caption`, `file_path`, `file_type`, `file_size`, `dimensions`, `category`, `created_at` 
         FROM `media_library` 
         WHERE {$whereSql} 
         ORDER BY `id` DESC LIMIT 60",
        $params
    );

    json_response(true, 'Media library assets retrieved.', $media);
} catch (Exception $e) {
    json_response(false, 'Failed to retrieve media assets: ' . $e->getMessage(), null, 500);
}
