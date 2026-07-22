-- QBR wizard: extends the base wp_fusion_qbrs* schema (wp_fusion_core.sql)
-- with the columns/tables the 7-step wizard actually needs. Idempotent —
-- safe to re-run. Paste in phpMyAdmin → select the WordPress database →
-- SQL tab → Go.
--
-- Scoping: one QBR per (company_group_id, quarter, year) — same pattern as
-- ARP's arp_group_year_uq. company_id is kept as a denormalized copy.

-- -----------------------------------------------------------------------------
-- wp_fusion_qbrs: add facilitator + discussion notes + a group-scoped unique key
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_qbrs`
    ADD COLUMN IF NOT EXISTS `facilitator_user_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — defaults to the leader who created this QBR'
        AFTER `company_group_id`,
    ADD COLUMN IF NOT EXISTS `discussion_notes` LONGTEXT NULL
        COMMENT 'Step 4 Leadership Discussion Notes (rich text HTML)'
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — leader who created this QBR'
        AFTER `discussion_notes`,
    ADD COLUMN IF NOT EXISTS `step_progress` LONGTEXT NULL
        COMMENT 'JSON: bool map of completed steps, same pattern as wp_fusion_arps.step_progress'
        AFTER `created_by`;

SET @xf_qbr_drop_old_uq := (
    SELECT IF(COUNT(*) > 0,
        'ALTER TABLE `wp_fusion_qbrs` DROP INDEX `qbr_scope_period_uq`',
        'SELECT ''qbr_scope_period_uq already absent — skip drop'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_qbrs'
      AND index_name = 'qbr_scope_period_uq'
);
PREPARE xf_qbr_stmt FROM @xf_qbr_drop_old_uq;
EXECUTE xf_qbr_stmt;
DEALLOCATE PREPARE xf_qbr_stmt;

SET @xf_qbr_add_group_uq := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `wp_fusion_qbrs` ADD UNIQUE KEY `qbr_group_period_uq` (`company_group_id`, `quarter`, `year`)',
        'SELECT ''qbr_group_period_uq already exists — skip add'' AS note')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'wp_fusion_qbrs'
      AND index_name = 'qbr_group_period_uq'
);
PREPARE xf_qbr_stmt FROM @xf_qbr_add_group_uq;
EXECUTE xf_qbr_stmt;
DEALLOCATE PREPARE xf_qbr_stmt;

ALTER TABLE `wp_fusion_qbrs`
    ADD INDEX IF NOT EXISTS `qbr_group_idx` (`company_group_id`);

-- -----------------------------------------------------------------------------
-- wp_fusion_qbr_ai_assessments: leadership agreement rating (Step 3)
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_qbr_ai_assessments`
    ADD COLUMN IF NOT EXISTS `agreement_rating` VARCHAR(20) NULL
        COMMENT 'strongly_agree | agree | neutral | disagree | strongly_disagree'
        AFTER `leadership_context`;

-- -----------------------------------------------------------------------------
-- wp_fusion_qbr_commitments: Step 5 fields beyond the base schema
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_qbr_commitments`
    ADD COLUMN IF NOT EXISTS `priority` VARCHAR(20) NOT NULL DEFAULT 'medium'
        COMMENT 'high | medium | low'
        AFTER `owner_user_id`,
    ADD COLUMN IF NOT EXISTS `related_arp_objective` VARCHAR(255) NULL
        COMMENT 'Free-text label of the linked ARP strategic priority (matched by name, same pattern as ARP Step 4)'
        AFTER `priority`,
    ADD COLUMN IF NOT EXISTS `success_measure` VARCHAR(255) NULL
        AFTER `related_arp_objective`,
    ADD COLUMN IF NOT EXISTS `due_date` DATE NULL
        AFTER `success_measure`,
    ADD COLUMN IF NOT EXISTS `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Drag order within the QBR (max 5 commitments)'
        AFTER `carried_forward_from_id`;

-- -----------------------------------------------------------------------------
-- wp_fusion_qbr_kpis: custom business KPIs shown in Step 2's KPI Summary table.
-- No other table in this system tracks arbitrary business KPIs (revenue,
-- retention, etc.) — leaders enter current/target values per QBR.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_kpis` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `current_value` VARCHAR(60) NULL,
    `target_value` VARCHAR(60) NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'on_track' COMMENT 'on_track | at_risk | off_track',
    `trend` VARCHAR(20) NULL COMMENT 'up | down | flat',
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbrk_qbr_idx` (`qbr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- wp_fusion_qbr_decisions: Step 4 "Key Decisions & Takeaways" table
-- (distinct from wp_fusion_qbr_commitments, which is Step 5's forward-looking
-- quarterly commitments — this table is the in-meeting decision log).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_decisions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `decision` VARCHAR(255) NOT NULL,
    `owner_user_id` BIGINT UNSIGNED NULL,
    `impact_area` VARCHAR(80) NULL,
    `next_step` TEXT NULL,
    `target_date` DATE NULL,
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbrd_qbr_idx` (`qbr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
