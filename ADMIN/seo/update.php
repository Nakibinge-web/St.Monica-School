<?php
/**
 * St. Monica Junior School CMS - Save Website SEO Settings
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('seo');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('seo/'));
}

require_csrf();

$pageKey = trim($_POST['page_key'] ?? '');
if (empty($pageKey)) {
    set_flash('danger', 'Page identifier is missing.');
    redirect(admin_url('seo/'));
}

$pageTitle       = trim($_POST['page_title'] ?? '');
$metaTitle       = trim($_POST['meta_title'] ?? '');
$metaDescription = trim($_POST['meta_description'] ?? '');
$metaKeywords    = trim($_POST['meta_keywords'] ?? '');
$ogTitle         = trim($_POST['og_title'] ?? '');
$ogDescription   = trim($_POST['og_description'] ?? '');
$ogImage         = trim($_POST['og_image'] ?? '');
$canonicalUrl    = trim($_POST['canonical_url'] ?? '');

if (empty($metaTitle) || empty($metaDescription)) {
    set_flash('danger', 'Meta Title and Meta Description are required for search engines.');
    redirect(admin_url('seo/?page=' . urlencode($pageKey)));
}

try {
    $exists = Database::fetchOne("SELECT `id` FROM `seo_settings` WHERE `page_key` = :key", ['key' => $pageKey]);

    $data = [
        'page_title'       => !empty($pageTitle) ? $pageTitle : ucfirst($pageKey),
        'meta_title'       => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords'    => !empty($metaKeywords) ? $metaKeywords : null,
        'og_title'         => !empty($ogTitle) ? $ogTitle : $metaTitle,
        'og_description'   => !empty($ogDescription) ? $ogDescription : $metaDescription,
        'og_image'         => !empty($ogImage) ? $ogImage : null,
        'canonical_url'    => !empty($canonicalUrl) ? $canonicalUrl : null
    ];

    if ($exists) {
        Database::update('seo_settings', $data, '`page_key` = :key', ['key' => $pageKey]);
        $recordId = $exists['id'];
    } else {
        $data['page_key'] = $pageKey;
        $recordId = Database::insert('seo_settings', $data);
    }

    log_activity('Updated SEO Settings', "Updated meta and social tags for page '{$pageKey}'", 'seo', $recordId);
    set_flash('success', "SEO settings for '{$pageTitle}' updated successfully.");
} catch (Exception $e) {
    set_flash('danger', 'Database error updating SEO settings: ' . $e->getMessage());
}

redirect(admin_url('seo/?page=' . urlencode($pageKey)));
