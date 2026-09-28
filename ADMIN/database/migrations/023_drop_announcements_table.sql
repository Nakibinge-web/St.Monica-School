-- Migration 023: Remove the retired Announcements module's table
-- (Its admin pages, menu entry, permission and homepage API output were removed with it.)
DROP TABLE IF EXISTS `announcements`;
