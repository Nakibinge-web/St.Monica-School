-- Migration 004: Create Media Library Table
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
