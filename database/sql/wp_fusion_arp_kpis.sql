-- ARP Step 4 — Key Performance Indicators™ (new wizard step, inserted
-- between Organizational Readiness and Strategic Priorities).
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
-- Safe to re-run: CREATE TABLE IF NOT EXISTS.
-- (dbDelta also creates this automatically on page load once the plugin
-- code is deployed — this file is only needed if you want to apply it
-- manually/immediately instead of waiting for that.)

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_kpis` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `type` VARCHAR(20) NOT NULL DEFAULT 'leading' COMMENT 'leading | trailing',
    `description` TEXT NULL,
    `why_it_matters` TEXT NULL,
    `current_baseline` VARCHAR(120) NULL,
    `target_value` VARCHAR(120) NULL,
    `target_date` DATE NULL,
    `measurement_frequency` VARCHAR(20) NOT NULL DEFAULT 'quarterly',
    `data_source` VARCHAR(255) NULL,
    `owner_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID',
    `readiness_priority_ids` TEXT NULL COMMENT 'JSON array of wp_fusion_arp_readiness_priorities.id',
    `notes` TEXT NULL,
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `arpkpi_arp_idx` (`arp_id`),
    KEY `arpkpi_rank_idx` (`arp_id`, `priority_rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
