<?php
/**
 * St. Monica Junior School CMS - Update About Us Content Handler
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('about');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('about/'));
}

require_csrf();

try {
    // 1. History
    $historyTitle = trim($_POST['history_title'] ?? 'Our History');
    $historyContent = trim($_POST['history_content'] ?? '');
    Database::update('about_content', [
        'title'   => $historyTitle,
        'content' => $historyContent
    ], "section_key = 'history'");

    // 2. Vision
    $visionContent = trim($_POST['vision_content'] ?? '');
    Database::update('about_content', [
        'content' => $visionContent
    ], "section_key = 'vision'");

    // 3. Mission
    $missionContent = trim($_POST['mission_content'] ?? '');
    Database::update('about_content', [
        'content' => $missionContent
    ], "section_key = 'mission'");

    // 4. Motto
    $mottoContent = trim($_POST['motto_content'] ?? '');
    Database::update('about_content', [
        'content' => $mottoContent
    ], "section_key = 'motto'");

    // 5. Support CTA
    $supportTitle = trim($_POST['support_title'] ?? 'Support St.Monica');
    $supportContent = trim($_POST['support_content'] ?? '');
    Database::update('about_content', [
        'title'   => $supportTitle,
        'content' => $supportContent
    ], "section_key = 'support_cta'");

    log_activity('Updated About Us Content');
    set_flash('success', 'About Us content updated successfully.');
} catch (Exception $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect(admin_url('about/'));
