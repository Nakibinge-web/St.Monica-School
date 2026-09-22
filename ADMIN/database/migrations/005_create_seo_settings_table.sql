-- Migration 005: Create SEO Settings Table
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
