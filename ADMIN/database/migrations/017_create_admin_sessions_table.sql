-- Migration 017: Create Admin Sessions Table (for Security Center session visibility)
CREATE TABLE IF NOT EXISTS `admin_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `session_id` VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(50) NULL,
    `user_agent` VARCHAR(255) NULL,
    `login_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `revoked_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_admin_sessions_admin` (`admin_id`),
    INDEX `idx_admin_sessions_session` (`session_id`),
    CONSTRAINT `fk_admin_sessions_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
