-- Add meeting_link column to wp_fusion_one_on_one_conversations
-- Run in phpMyAdmin if the table already exists (created before this column was added).
-- Safe to skip if running wp_fusion_core.sql fresh — the column is already included there.

ALTER TABLE `wp_fusion_one_on_one_conversations`
    ADD COLUMN IF NOT EXISTS `meeting_link` VARCHAR(500) NULL
        COMMENT 'Video call URL set by leader (Zoom, Meet, Teams, etc.)'
        AFTER `held_at`;
