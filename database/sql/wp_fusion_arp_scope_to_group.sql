-- ARP scoping correction: one ARP per (company GROUP, year), not per company.
-- A company can have several groups (e.g. Operations, Sales); leadership is
-- granted per-group (wp_company_group_details.status = 'leader'), so ARP
-- access/edit checks must match on the same unit — company-level scoping
-- caused leaders to see "view only" on ARPs that weren't tied to their group.
--
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
-- Run ONCE. If you already have ARPs created under the old company-level
-- scoping, back them up first — this does not attempt to guess which group
-- an existing ARP belongs to.

ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `company_group_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_company_groups.id — the real scoping key. company_id is kept as a denormalized copy for display/joins.'
        AFTER `company_id`;

-- Drop the old company-level unique key (only run if it still exists).
SET @xf_arp_drop_old_uq := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `wp_fusion_arps` DROP INDEX `arp_company_year_uq`',
        'SELECT ''arp_company_year_uq already absent — skip drop'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND index_name = 'arp_company_year_uq'
);
PREPARE xf_arp_stmt FROM @xf_arp_drop_old_uq;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;

-- One ARP per group per year.
SET @xf_arp_add_group_uq := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `wp_fusion_arps` ADD UNIQUE KEY `arp_group_year_uq` (`company_group_id`, `year`)',
        'SELECT ''arp_group_year_uq already exists — skip add'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND index_name = 'arp_group_year_uq'
);
PREPARE xf_arp_stmt FROM @xf_arp_add_group_uq;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;

ALTER TABLE `wp_fusion_arps`
    ADD INDEX IF NOT EXISTS `arp_group_idx` (`company_group_id`);
