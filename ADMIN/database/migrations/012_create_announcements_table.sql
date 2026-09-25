-- Migration 012: Create Announcements Table (short urgent notices)
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `display_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_announcements_status_order` (`status`, `display_order`),
    INDEX `idx_announcements_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
