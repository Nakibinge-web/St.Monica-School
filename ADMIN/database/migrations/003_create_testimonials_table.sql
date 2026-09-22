-- Migration 003: Create Testimonials Table
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
