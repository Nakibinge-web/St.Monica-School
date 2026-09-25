-- Migration 013: Add Automatic Expiration Support to News & Events
ALTER TABLE `news_events`
    ADD COLUMN IF NOT EXISTS `expires_at` DATETIME NULL AFTER `published_at`;
