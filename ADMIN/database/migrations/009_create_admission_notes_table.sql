-- Migration 009: Create Admission Notes Table (append-only internal notes timeline)
CREATE TABLE IF NOT EXISTS `admission_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admission_id` INT NOT NULL,
    `admin_id` INT NULL,
    `admin_name` VARCHAR(100) NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admission_notes_admission` (`admission_id`),
    CONSTRAINT `fk_admission_notes_admission` FOREIGN KEY (`admission_id`) REFERENCES `admissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
