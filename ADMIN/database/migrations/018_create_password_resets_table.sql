-- Migration 018: Create Password Resets Table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_password_resets_admin` (`admin_id`),
    INDEX `idx_password_resets_token` (`token_hash`),
    CONSTRAINT `fk_password_resets_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
