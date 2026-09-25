-- Migration 016: Add Soft-Delete Support (Trash & Recovery) to Recoverable Content Tables
ALTER TABLE `news_events` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `gallery` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
ALTER TABLE `testimonials` ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;
