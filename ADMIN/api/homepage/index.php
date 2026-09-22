<?php
/**
 * St. Monica Junior School - Homepage Public API
 * Endpoint: GET /ADMIN/api/homepage/
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

try {
    // 1. Active Hero Slides
    $slides = Database::fetchAll("SELECT `id`, `title`, `subtitle`, `description`, `image`, `button_text`, `button_url`, `display_order` FROM `hero_slides` WHERE `status` = 'active' ORDER BY `display_order` ASC, `id` ASC");

    // 2. Director's Welcome Message
    $director = Database::fetchOne("SELECT `title`, `subtitle`, `content`, `image`, `author_name`, `author_title` FROM `homepage_sections` WHERE `section_key` = 'director_message' LIMIT 1");

    // 3. Why Choose Us Intro & Items
    $whyIntro = Database::fetchOne("SELECT `title`, `subtitle`, `content`, `image` FROM `homepage_sections` WHERE `section_key` = 'why_choose_intro' LIMIT 1");
    $whyItems = Database::fetchAll("SELECT `id`, `title`, `description`, `icon`, `color_theme`, `display_order` FROM `why_choose_us_items` WHERE `status` = 'active' ORDER BY `display_order` ASC, `id` ASC");

    // 4. Statistics Counters
    $stats = Database::fetchAll("SELECT `id`, `number_value`, `suffix`, `label`, `icon`, `display_order` FROM `statistics` WHERE `status` = 'active' ORDER BY `display_order` ASC, `id` ASC");

    // 5. Featured Staff Team for Homepage
    $featuredStaff = Database::fetchAll("SELECT `id`, `name`, `position`, `department`, `biography`, `email`, `photo` FROM `staff` WHERE `status` = 'published' AND `is_featured` = 1 ORDER BY `display_order` ASC, `id` ASC LIMIT 6");

    // 6. Latest News & Events
    $latestNews = Database::fetchAll("SELECT `id`, `title`, `slug`, `type`, `excerpt`, `featured_image`, `event_date`, `event_location`, `created_at` FROM `news_events` WHERE `status` = 'published' ORDER BY `created_at` DESC LIMIT 3");

    // 7. Contact Information
    $contact = Database::fetchOne("SELECT `school_name`, `phone`, `alternative_phone`, `email`, `admissions_email`, `opening_hours`, `address`, `village`, `district`, `map_url`, `whatsapp`, `facebook`, `instagram`, `youtube` FROM `contact_information` LIMIT 1");

    json_response(true, 'Homepage CMS data loaded successfully.', [
        'hero_slides'      => $slides,
        'director_message' => $director,
        'why_choose_us'    => [
            'intro' => $whyIntro,
            'items' => $whyItems
        ],
        'statistics'       => $stats,
        'featured_staff'   => $featuredStaff,
        'latest_news'      => $latestNews,
        'contact'          => $contact
    ]);
} catch (Exception $e) {
    json_response(false, 'Failed to retrieve homepage data: ' . $e->getMessage(), null, 500);
}
