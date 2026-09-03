-- ARP rescoping: back to one ARP per COMPANY per year (reverses
-- wp_fusion_arp_scope_to_group.sql), matching ARR's scoping model.
-- ARP no longer stores company_group_id at all — the "create new ARP" form
-- still picks a group (leader-identification UX only), but Laravel resolves
-- and stores company_id, same pattern as wp_fusion_arrs.
--
-- Paste in phpMyAdmin -> select the WordPress database -> SQL tab -> Go.
-- Run ONCE. Existing ARP rows are test data being deleted separately -
-- this does not attempt to migrate/preserve them.

-- Drop the group-level unique key (only run if it still exists).
SET @xf_arp_drop_group_uq := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `wp_fusion_arps` DROP INDEX `arp_group_year_uq`',
        'SELECT ''arp_group_year_uq already absent — skip drop'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND index_name = 'arp_group_year_uq'
);
PREPARE xf_arp_stmt FROM @xf_arp_drop_group_uq;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;

-- Drop the now-dead group index.
SET @xf_arp_drop_group_idx := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `wp_fusion_arps` DROP INDEX `arp_group_idx`',
        'SELECT ''arp_group_idx already absent — skip drop'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND index_name = 'arp_group_idx'
);
PREPARE xf_arp_stmt FROM @xf_arp_drop_group_idx;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;

-- Drop the company_group_id column itself (ARR has no equivalent column).
SET @xf_arp_drop_group_col := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `wp_fusion_arps` DROP COLUMN `company_group_id`',
        'SELECT ''company_group_id already absent — skip drop'' AS note')
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND column_name = 'company_group_id'
);
PREPARE xf_arp_stmt FROM @xf_arp_drop_group_col;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;

-- One ARP per company per year (same shape as wp_fusion_arrs.arr_company_year_uq).
SET @xf_arp_add_company_uq := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `wp_fusion_arps` ADD UNIQUE KEY `arp_company_year_uq` (`company_id`, `year`)',
        'SELECT ''arp_company_year_uq already exists — skip add'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_arps'
      AND index_name = 'arp_company_year_uq'
);
PREPARE xf_arp_stmt FROM @xf_arp_add_company_uq;
EXECUTE xf_arp_stmt;
DEALLOCATE PREPARE xf_arp_stmt;
