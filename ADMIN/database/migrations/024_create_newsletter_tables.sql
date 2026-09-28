-- Migration 024: Newsletter subscribers, sent newsletters (campaigns) and per-recipient deliveries

CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `status` ENUM('subscribed', 'unsubscribed') NOT NULL DEFAULT 'subscribed',
    `unsubscribe_token` CHAR(64) NOT NULL UNIQUE,
    `source` VARCHAR(100) NULL,               -- page the visitor subscribed from, or 'admin'
    `ip_address` VARCHAR(45) NULL,
    `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_newsletter_status` (`status`),
    INDEX `idx_newsletter_ip_time` (`ip_address`, `subscribed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `subject` VARCHAR(255) NOT NULL,
    `body_html` MEDIUMTEXT NOT NULL,
    `status` ENUM('sending', 'sent', 'partial') NOT NULL DEFAULT 'sending',
    `recipient_count` INT NOT NULL DEFAULT 0,
    `sent_count` INT NOT NULL DEFAULT 0,
    `failed_count` INT NOT NULL DEFAULT 0,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_newsletter_campaign_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per recipient per newsletter: makes sending resumable and prevents duplicates
CREATE TABLE IF NOT EXISTS `newsletter_deliveries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `subscriber_id` INT NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `status` ENUM('pending', 'sent', 'failed', 'skipped') NOT NULL DEFAULT 'pending',
    `error` VARCHAR(500) NULL,
    `attempted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uniq_campaign_subscriber` (`campaign_id`, `subscriber_id`),
    INDEX `idx_delivery_campaign_status` (`campaign_id`, `status`),
    CONSTRAINT `fk_delivery_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `newsletter_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
