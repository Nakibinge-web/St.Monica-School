-- Migration 022: Add TikTok channel URL to the school's contact information
ALTER TABLE `contact_information` ADD COLUMN IF NOT EXISTS `tiktok` VARCHAR(255) NULL AFTER `youtube`;
