-- Migration 001: Create Admissions Table
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
