-- Tool Library catalog: adds an `icon` column to the existing course group
-- (category, e.g. "Leadership Development") and course list (each tool)
-- tables, plus a new pivot table tagging which of the 5 COR Organizational
-- Capabilities each tool addresses. Reuses wp_course_scoring_groups as the
-- canonical source of the 5 capability rows (Alignment/Accountability/
-- Communication/Leadership/Execution) instead of redefining them here.
--
-- Run in phpMyAdmin -> select the WordPress database -> SQL tab -> Go.
-- Same effect as
-- database/migrations/2026_08_29_000000_add_tool_library_capability_tags.php
-- for servers where `php artisan migrate` isn't run. Safe to re-run.

ALTER TABLE `wp_course_groups`
    ADD COLUMN IF NOT EXISTS `icon` VARCHAR(255) NULL AFTER `title`;

ALTER TABLE `wp_course_lists`
    ADD COLUMN IF NOT EXISTS `icon` VARCHAR(255) NULL AFTER `course_title`;

CREATE TABLE IF NOT EXISTS `wp_fusion_tool_scoring_group_tags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_list_id` BIGINT UNSIGNED NOT NULL,
    `course_scoring_group_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tool_capability_uq` (`course_list_id`, `course_scoring_group_id`),
    KEY `tool_capability_group_idx` (`course_scoring_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
