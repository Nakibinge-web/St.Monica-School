-- Migration 010: Create Enquiries Table (public contact form submissions)
CREATE TABLE IF NOT EXISTS `enquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied', 'archived') NOT NULL DEFAULT 'new',
    `ip_address` VARCHAR(50) NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_enquiries_status` (`status`),
    INDEX `idx_enquiries_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
