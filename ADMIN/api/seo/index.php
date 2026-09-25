<?php
/**
 * St. Monica Junior School - Website SEO Public API
 * Endpoint: GET /ADMIN/api/seo/?page={page_key}
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    $page = trim($_GET['page'] ?? '');

    if ($page !== '') {
        $seo = Database::fetchOne(
            "SELECT `page_key`, `page_title`, `meta_title`, `meta_description`, 
                    `meta_keywords`, `og_title`, `og_description`, `og_image`, `canonical_url`, `updated_at`
             FROM `seo_settings` 
             WHERE `page_key` = :key LIMIT 1",
            ['key' => $page]
        );

        if (!$seo) {
            json_response(false, "SEO configuration for page '{$page}' not found.", null, 404);
        }

        json_response(true, 'SEO settings retrieved.', $seo);
    } else {
        $all = Database::fetchAll(
            "SELECT `page_key`, `page_title`, `meta_title`, `meta_description`, 
                    `meta_keywords`, `og_title`, `og_description`, `og_image`, `canonical_url`, `updated_at`
             FROM `seo_settings` 
             ORDER BY `id` ASC"
        );

        $grouped = [];
        foreach ($all as $item) {
            $grouped[$item['page_key']] = $item;
        }

        json_response(true, 'All SEO settings retrieved.', $grouped);
    }
} catch (Exception $e) {
    json_error($e, 'Failed to fetch SEO settings.');
}
