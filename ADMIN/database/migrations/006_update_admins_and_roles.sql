-- Migration 006: Update Admins Table for Role & Status Management
ALTER TABLE `admins` 
    ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(50) NULL;
