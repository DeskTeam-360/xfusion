-- ARP versioning: adds version/published_at to wp_fusion_arps and a history
-- table capturing a full snapshot every time an ARP is archived or published.
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
-- Safe to re-run: ADD COLUMN IF NOT EXISTS / CREATE TABLE IF NOT EXISTS.

ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `version` DECIMAL(4,1) NOT NULL DEFAULT 1.0
        COMMENT 'Bumped by 0.1 on every publish' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `published_at` TIMESTAMP NULL
        COMMENT 'Set when this ARP is first published; updated on every re-publish' AFTER `version`;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `version` DECIMAL(4,1) NOT NULL,
    `status` VARCHAR(20) NOT NULL COMMENT 'archived | published',
    `snapshot` LONGTEXT NOT NULL COMMENT 'JSON: full ARP + readiness + strategic + learnings state at this version',
    `published_by_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID',
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpv_arp_idx` (`arp_id`),
    KEY `arpv_version_idx` (`arp_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
