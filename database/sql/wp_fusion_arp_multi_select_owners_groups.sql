-- ARP Step 3/4: Executive Owner becomes multi-select (was a single
-- wp_users.ID), and Step 4's Related Group(s) becomes a real multi-select
-- sourced from this ARP's company groups (was a hardcoded pseudo-scope
-- slug like "all_leaders" - never a real wp_company_groups row).
--
-- Paste in phpMyAdmin -> select the WordPress database -> SQL tab -> Go.
-- Run ONCE. Existing ARP rows are test data being deleted separately -
-- this does not attempt to migrate old single-value data into the new
-- JSON array columns.

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    DROP COLUMN IF EXISTS `executive_owner_user_id`,
    ADD COLUMN IF NOT EXISTS `executive_owner_user_ids` TEXT NULL
        COMMENT 'JSON array of wp_users.ID'
        AFTER `business_rationale`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    DROP COLUMN IF EXISTS `owner_user_id`,
    ADD COLUMN IF NOT EXISTS `owner_user_ids` TEXT NULL
        COMMENT 'JSON array of wp_users.ID'
        AFTER `description`,
    MODIFY COLUMN `related_groups` TEXT NULL
        COMMENT 'JSON array of wp_company_groups.id (was a single hardcoded pseudo-scope slug)';
