-- Migration 019: Create Remember-Me Tokens Table (selector/validator pattern)
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `selector` VARCHAR(64) NOT NULL UNIQUE,
    `validator_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_remember_tokens_selector` (`selector`),
    CONSTRAINT `fk_remember_tokens_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
