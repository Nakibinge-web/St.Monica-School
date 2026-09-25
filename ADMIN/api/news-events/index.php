<?php
/**
 * St. Monica Junior School - News & Events Public API
 * Endpoint: GET /ADMIN/api/news-events/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $type = trim($_GET['type'] ?? '');
    $limit = (int)($_GET['limit'] ?? 0);
    $slug = trim($_GET['slug'] ?? '');

    // Single item query
    if (!empty($slug)) {
        $single = Database::fetchOne("SELECT * FROM `news_events` WHERE `slug` = :slug AND `status` = 'published' AND `deleted_at` IS NULL AND (`published_at` IS NULL OR `published_at` <= NOW()) AND (`expires_at` IS NULL OR `expires_at` > NOW()) LIMIT 1", ['slug' => $slug]);
        if ($single) {
            json_response(true, 'Article found.', $single);
        } else {
            json_response(false, 'Article not found, expired, or scheduled for future publication.', null, 404);
        }
    }

    $where = ["`status` = 'published'", "`deleted_at` IS NULL", "(`published_at` IS NULL OR `published_at` <= NOW())", "(`expires_at` IS NULL OR `expires_at` > NOW())"];
    $params = [];

    if (in_array($type, ['news', 'event', 'sports'])) {
        $where[] = "`type` = :type";
        $params['type'] = $type;
    }

    $whereSql = implode(' AND ', $where);
    $limitSql = $limit > 0 ? "LIMIT {$limit}" : "";

    $items = Database::fetchAll("SELECT `id`, `title`, `slug`, `type`, `excerpt`, `content`, `featured_image`, `event_date`, `event_location`, `published_at`, `created_at` FROM `news_events` WHERE {$whereSql} ORDER BY `created_at` DESC {$limitSql}", $params);

    json_response(true, 'News & events loaded successfully.', $items);
} catch (Exception $e) {
    json_error($e, 'Failed to retrieve news & events.');
}
