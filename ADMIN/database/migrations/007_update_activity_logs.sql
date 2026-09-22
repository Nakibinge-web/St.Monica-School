-- Migration 007: Update Activity Logs Table for Module and Record Tracking
ALTER TABLE `activity_logs` 
    ADD COLUMN IF NOT EXISTS `module` VARCHAR(50) NULL AFTER `action`,
    ADD COLUMN IF NOT EXISTS `record_id` INT NULL AFTER `module`;
