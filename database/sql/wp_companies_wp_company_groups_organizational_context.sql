-- Adds Organizational Context™ fields to wp_companies and wp_company_groups,
-- managed via the Laravel admin (Company / Company Group forms) and
-- surfaced read-only in the 1-on-1 wizard's Step 1 "Organizational Context"
-- evidence card. Idempotent — safe to re-run. Paste in phpMyAdmin → select
-- the WordPress database → SQL tab → Go.
--
-- Company-level fields are the organization-wide default; a group's fields
-- (when set) are more specific to that team and take precedence in display.

ALTER TABLE `wp_companies`
    ADD COLUMN IF NOT EXISTS `role` VARCHAR(255) NULL
        COMMENT 'Organizational Context™ — typical role/function this organization operates in'
        AFTER `company_url`,
    ADD COLUMN IF NOT EXISTS `team` VARCHAR(255) NULL
        COMMENT 'Organizational Context™ — team/structure description'
        AFTER `role`,
    ADD COLUMN IF NOT EXISTS `organizational_goals` TEXT NULL
        COMMENT 'Organizational Context™ — current organizational goals'
        AFTER `team`,
    ADD COLUMN IF NOT EXISTS `readiness_priorities` TEXT NULL
        COMMENT 'Organizational Context™ — current readiness priorities'
        AFTER `organizational_goals`;

ALTER TABLE `wp_company_groups`
    ADD COLUMN IF NOT EXISTS `role` VARCHAR(255) NULL
        COMMENT 'Organizational Context™ — typical role/function this group operates in (overrides company default when set)'
        AFTER `description`,
    ADD COLUMN IF NOT EXISTS `team` VARCHAR(255) NULL
        COMMENT 'Organizational Context™ — team/structure description (overrides company default when set)'
        AFTER `role`,
    ADD COLUMN IF NOT EXISTS `organizational_goals` TEXT NULL
        COMMENT 'Organizational Context™ — current organizational goals for this group'
        AFTER `team`,
    ADD COLUMN IF NOT EXISTS `readiness_priorities` TEXT NULL
        COMMENT 'Organizational Context™ — current readiness priorities for this group'
        AFTER `organizational_goals`;
