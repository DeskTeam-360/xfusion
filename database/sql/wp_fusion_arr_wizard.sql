-- Annual Readiness Review™ (ARR) wizard — extends the base wp_fusion_arr*
-- schema already in wp_fusion_core.sql with the columns/tables the 7-step
-- wizard needs. Idempotent — safe to re-run. Paste in phpMyAdmin → select
-- the WordPress database → SQL tab → Go.
--
-- Scoping: one ARR per (company_id, year) — unchanged from the existing
-- `arr_company_year_uq` key already defined in wp_fusion_core.sql. ARR is
-- organization-wide (not per group), consistent with it sitting above QBR
-- (per-group) in the FUSION cycle.

-- -----------------------------------------------------------------------------
-- wp_fusion_arrs: executive ownership + wizard bookkeeping
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arrs`
    ADD COLUMN IF NOT EXISTS `executive_owner_user_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — the executive who owns this ARR (Step 4 reflection author)'
        AFTER `company_id`,
    ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — who started this ARR'
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `started_at` TIMESTAMP NULL AFTER `created_by`,
    ADD COLUMN IF NOT EXISTS `published_at` TIMESTAMP NULL AFTER `started_at`,
    ADD COLUMN IF NOT EXISTS `step_progress` LONGTEXT NULL
        COMMENT 'JSON: bool map of completed steps, same pattern as wp_fusion_arps.step_progress'
        AFTER `published_at`;

-- `status` on the base table only allowed draft|scheduled|held|closed; the
-- wizard needs the same in_progress/ready_to_publish/published vocabulary
-- used by ARP/QBR/IRR (idempotent — MODIFY always runs, superset of values
-- so existing rows are unaffected).
ALTER TABLE `wp_fusion_arrs`
    MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'draft'
        COMMENT 'draft | in_progress | ready_to_publish | published | archived';

ALTER TABLE `wp_fusion_arrs`
    ADD INDEX IF NOT EXISTS `arr_owner_idx` (`executive_owner_user_id`);

-- -----------------------------------------------------------------------------
-- wp_fusion_arr_ai_assessments: Step 3 AI Annual Readiness Assessment™ —
-- executive agreement rating (radio group) + prompt governance metadata.
-- The `assessment` JSON already covers organizational readiness/strategic
-- alignment/behavioral intelligence/COR capability/leadership readiness/
-- development trends/readiness progress/strategic risks & opportunities/
-- emerging themes — no schema change needed there.
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arr_ai_assessments`
    ADD COLUMN IF NOT EXISTS `agreement_rating` VARCHAR(20) NULL
        COMMENT 'strongly_agree | agree | neutral | disagree | strongly_disagree'
        AFTER `executive_context`,
    ADD COLUMN IF NOT EXISTS `prompt_version_id` VARCHAR(60) NULL AFTER `insight_model`,
    ADD COLUMN IF NOT EXISTS `prompt_version_label` VARCHAR(120) NULL AFTER `prompt_version_id`;

-- -----------------------------------------------------------------------------
-- wp_fusion_arr_executive_reflections: Step 4 Executive Strategic Reflection™
-- — the 8 free-text prompts + shared conversation notes, one row per ARR
-- (executive-only; distinct from the AI assessment which is read-only).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_arr_executive_reflections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arr_id` BIGINT UNSIGNED NOT NULL,
    `organizational_learning` TEXT NULL COMMENT 'What were our most important organizational learnings this year?',
    `readiness_progression` TEXT NULL COMMENT 'How has our organizational readiness progressed over the past year?',
    `strategic_assumptions` TEXT NULL COMMENT 'What assumptions about our strategy or environment were validated or challenged?',
    `organizational_barriers` TEXT NULL COMMENT 'What barriers continue to limit our performance and growth?',
    `organizational_strengths` TEXT NULL COMMENT 'What are our greatest strengths that we should leverage more?',
    `leadership_effectiveness` TEXT NULL COMMENT 'How effective was our leadership this year? What should we continue, stop, or start?',
    `resource_allocation` TEXT NULL COMMENT 'Did we allocate our resources to the right priorities? What should change?',
    `future_opportunities` TEXT NULL COMMENT 'What opportunities should we pursue to accelerate our future state next year?',
    `conversation_notes` TEXT NULL COMMENT 'Free-form notes captured during the reflection conversation',
    `author_user_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `arrxr_arr_uq` (`arr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- wp_fusion_arr_renewal_recommendations: Step 5 Strategic Renewal
-- Recommendations™ fields beyond the base schema (base only has
-- title/description). These recommendations flow into the next ARP as
-- draft planning considerations.
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arr_renewal_recommendations`
    ADD COLUMN IF NOT EXISTS `priority` VARCHAR(20) NOT NULL DEFAULT 'medium'
        COMMENT 'high | medium | low'
        AFTER `description`,
    ADD COLUMN IF NOT EXISTS `executive_owner_user_id` BIGINT UNSIGNED NULL
        AFTER `priority`,
    ADD COLUMN IF NOT EXISTS `executive_owner_name` VARCHAR(255) NULL
        COMMENT 'Free-text owner when not a wp_users.ID'
        AFTER `executive_owner_user_id`,
    ADD COLUMN IF NOT EXISTS `cor_capability` VARCHAR(40) NULL
        COMMENT 'alignment | accountability | communication | leadership | execution'
        AFTER `executive_owner_name`,
    ADD COLUMN IF NOT EXISTS `behavioral_driver` VARCHAR(40) NULL
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth'
        AFTER `cor_capability`,
    ADD COLUMN IF NOT EXISTS `expected_organizational_impact` TEXT NULL
        AFTER `behavioral_driver`,
    ADD COLUMN IF NOT EXISTS `recommended_timeline` VARCHAR(20) NULL
        COMMENT 'q1 | q2 | q3 | q4 | fy | multi_year'
        AFTER `expected_organizational_impact`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'proposed'
        COMMENT 'proposed | accepted | rejected | carried_to_arp'
        AFTER `recommended_timeline`;

-- -----------------------------------------------------------------------------
-- wp_fusion_arr_ai_syntheses: Step 6 AI Strategic Renewal Synthesis™ metadata
-- (synthesis JSON already covers the 7 summary sections shown in Step 6:
-- annual organizational learning, readiness progress, behavioral
-- intelligence, leadership intelligence, strategic intelligence, strategic
-- renewal, recommended future focus, executive summary).
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arr_ai_syntheses`
    ADD COLUMN IF NOT EXISTS `prompt_version_id` VARCHAR(60) NULL AFTER `insight_model`,
    ADD COLUMN IF NOT EXISTS `prompt_version_label` VARCHAR(120) NULL AFTER `prompt_version_id`;
