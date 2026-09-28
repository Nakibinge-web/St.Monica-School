-- ==========================================================
-- St. Monica Junior School - CMS Database Schema
-- Database: st_monica
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `st_monica` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `st_monica`;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'administrator',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Hero Slides Table (Multiple hero carousel slides)
CREATE TABLE IF NOT EXISTS `hero_slides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `image` VARCHAR(255) NOT NULL,
    `button_text` VARCHAR(100) DEFAULT 'Know More',
    `button_url` VARCHAR(255) DEFAULT 'about.html',
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hero_status_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Homepage Sections Table (For section headers, welcome text, director message, etc.)
CREATE TABLE IF NOT EXISTS `homepage_sections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_key` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NULL,
    `subtitle` VARCHAR(255) NULL,
    `content` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `author_name` VARCHAR(150) NULL,
    `author_title` VARCHAR(150) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Why Choose Us Items
CREATE TABLE IF NOT EXISTS `why_choose_us_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `icon` VARCHAR(100) NOT NULL DEFAULT 'fa-solid fa-star',
    `color_theme` VARCHAR(50) NOT NULL DEFAULT 'navy',
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_why_status_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Statistics / Counters Table
CREATE TABLE IF NOT EXISTS `statistics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `number_value` INT NOT NULL,
    `suffix` VARCHAR(10) DEFAULT '+',
    `label` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(100) NOT NULL DEFAULT 'verified',
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_stats_status_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Staff Table
CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `position` VARCHAR(150) NOT NULL,
    `department` VARCHAR(100) NULL DEFAULT 'Teaching',
    `biography` TEXT NULL,
    `email` VARCHAR(150) NULL,
    `photo` VARCHAR(255) NULL,
    `display_order` INT DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_staff_status_order` (`status`, `display_order`),
    INDEX `idx_staff_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. News & Events Table
CREATE TABLE IF NOT EXISTS `news_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `type` ENUM('news', 'event', 'sports') NOT NULL DEFAULT 'news',
    `excerpt` TEXT NULL,
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(255) NULL,
    `event_date` DATE NULL,
    `event_location` VARCHAR(255) NULL,
    `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    `published_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_news_status` (`status`),
    INDEX `idx_news_type` (`type`),
    INDEX `idx_news_slug` (`slug`),
    INDEX `idx_news_event_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) DEFAULT 'image',
    `category` VARCHAR(100) NOT NULL DEFAULT 'Campus Life',
    `display_order` INT DEFAULT 0,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_gallery_category` (`category`),
    INDEX `idx_gallery_status_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. About Content Table
CREATE TABLE IF NOT EXISTS `about_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_key` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NULL,
    `content` LONGTEXT NULL,
    `image` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_about_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Core Values Table
CREATE TABLE IF NOT EXISTS `core_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_core_values_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Facilities Table
CREATE TABLE IF NOT EXISTS `facilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_facilities_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Contact Information Table (Global singleton record id=1)
CREATE TABLE IF NOT EXISTS `contact_information` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `school_name` VARCHAR(200) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `village` VARCHAR(100) DEFAULT 'Kkoba village, Kasanje',
    `district` VARCHAR(100) DEFAULT 'Wakiso District',
    `country` VARCHAR(100) DEFAULT 'Uganda',
    `phone` VARCHAR(50) NOT NULL,
    `alternative_phone` VARCHAR(50) NULL,
    `email` VARCHAR(150) NOT NULL,
    `admissions_email` VARCHAR(150) NULL,
    `opening_hours` VARCHAR(255) NULL,
    `facebook` VARCHAR(255) NULL,
    `instagram` VARCHAR(255) NULL,
    `youtube` VARCHAR(255) NULL,
    `tiktok` VARCHAR(255) NULL,
    `whatsapp` VARCHAR(50) NULL,
    `map_url` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NULL,
    `admin_name` VARCHAR(100) NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NULL,
    `record_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activity_admin` (`admin_id`),
    INDEX `idx_activity_action` (`action`),
    INDEX `idx_activity_module` (`module`),
    INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- PHASE TWO EXTENSIONS
-- ==========================================================

-- 14. Admissions Applications Table
CREATE TABLE IF NOT EXISTS `admissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_number` VARCHAR(50) NOT NULL UNIQUE,
    `parent_name` VARCHAR(150) NOT NULL,
    `mobile` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NULL,
    `pupil_name` VARCHAR(150) NOT NULL,
    `pupil_class` VARCHAR(50) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('New', 'Under Review', 'Contacted', 'Accepted', 'Rejected', 'Withdrawn') NOT NULL DEFAULT 'New',
    `admin_notes` TEXT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_status` (`status`),
    INDEX `idx_adm_class` (`pupil_class`),
    INDEX `idx_adm_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Admission Information CMS Table
CREATE TABLE IF NOT EXISTS `admission_info` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `section_key` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `subtitle` VARCHAR(255) NULL,
    `content` LONGTEXT NOT NULL,
    `icon` VARCHAR(100) NULL,
    `display_order` INT DEFAULT 0,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_info_key` (`section_key`),
    INDEX `idx_adm_info_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Testimonials Table
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `role` VARCHAR(100) NOT NULL DEFAULT 'Parent',
    `child_info` VARCHAR(150) NULL,
    `rating` TINYINT NOT NULL DEFAULT 5,
    `content` TEXT NOT NULL,
    `photo` VARCHAR(255) NULL,
    `initials` VARCHAR(10) NULL,
    `display_order` INT DEFAULT 0,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_testi_status_order` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Media Library Table
CREATE TABLE IF NOT EXISTS `media_library` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(255) NULL,
    `caption` TEXT NULL,
    `description` TEXT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL DEFAULT 'image',
    `file_size` INT NOT NULL DEFAULT 0,
    `dimensions` VARCHAR(50) NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_media_cat` (`category`),
    INDEX `idx_media_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. SEO Settings Table
CREATE TABLE IF NOT EXISTS `seo_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_key` VARCHAR(50) NOT NULL UNIQUE,
    `page_title` VARCHAR(150) NOT NULL,
    `meta_title` VARCHAR(255) NOT NULL,
    `meta_description` TEXT NOT NULL,
    `meta_keywords` VARCHAR(255) NULL,
    `og_title` VARCHAR(255) NULL,
    `og_description` TEXT NULL,
    `og_image` VARCHAR(255) NULL,
    `canonical_url` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_seo_key` (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

