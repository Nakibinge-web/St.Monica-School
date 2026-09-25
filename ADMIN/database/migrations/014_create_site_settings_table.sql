-- Migration 014: Create Site Settings Table (key/value website configuration)
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_site_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('maintenance_mode', '0'),
('maintenance_message', 'We are currently performing scheduled maintenance. Please check back shortly.'),
('pagination_default', '10'),
('timezone', 'Africa/Kampala'),
('date_format', 'M j, Y'),
('mail_from_name', 'St. Monica Junior School'),
('mail_from_email', 'no-reply@stmonicakasanje.ac.ug')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);
