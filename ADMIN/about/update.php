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

/**
 * Update an About Us section, creating its row first if it does not exist yet
 * (otherwise the save would silently do nothing on a fresh install).
 */
function save_about_section(string $key, array $data): void {
    if (Database::fetchOne("SELECT `id` FROM `about_content` WHERE `section_key` = :k", ['k' => $key])) {
        Database::update('about_content', $data, 'section_key = :k', ['k' => $key]);
    } else {
        $data['section_key'] = $key;
        $data['title'] = $data['title'] ?? ucwords(str_replace('_', ' ', $key));
        Database::insert('about_content', $data);
    }
}

try {
    // 0. Page Hero Banner (photo chosen from the Media Library; empty = keep current)
    $heroData = [
        'title'   => trim($_POST['hero_title'] ?? '') ?: 'Discover St.Monica Junior School Kasanje',
        'content' => trim($_POST['hero_content'] ?? '')
    ];
    if (!empty($_POST['hero_image'])) {
        $heroImage = resolve_media_selection($_POST['hero_image']);
        if (!$heroImage) {
            set_flash('danger', 'The selected banner photo is no longer available in the Media Library. Please choose another.');
            redirect(admin_url('about/'));
        }
        $heroData['image'] = $heroImage;
    }
    save_about_section('hero', $heroData);

    // 1. History
    $historyTitle = trim($_POST['history_title'] ?? 'Our History');
    $historyContent = trim($_POST['history_content'] ?? '');
    save_about_section('history', [
        'title'   => $historyTitle,
        'content' => $historyContent
    ]);

    // 2. Vision
    $visionContent = trim($_POST['vision_content'] ?? '');
    save_about_section('vision', [
        'content' => $visionContent
    ]);

    // 3. Mission
    $missionContent = trim($_POST['mission_content'] ?? '');
    save_about_section('mission', [
        'content' => $missionContent
    ]);

    // 4. Motto
    $mottoContent = trim($_POST['motto_content'] ?? '');
    save_about_section('motto', [
        'content' => $mottoContent
    ]);

    // 5. Support CTA
    $supportTitle = trim($_POST['support_title'] ?? 'Support St.Monica');
    $supportContent = trim($_POST['support_content'] ?? '');
    save_about_section('support_cta', [
        'title'   => $supportTitle,
        'content' => $supportContent
    ]);

    log_activity('Updated About Us Content');
    set_flash('success', 'About Us content updated successfully.');
} catch (Exception $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect(admin_url('about/'));
