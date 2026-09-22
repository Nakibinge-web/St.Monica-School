-- Migration 002: Create Admission Info Table
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
