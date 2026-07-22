-- Individual Readiness Review™ (IRR) wizard — extends the base
-- wp_fusion_360_* schema already in wp_fusion_core.sql with the columns/
-- tables the 7-step wizard needs. Idempotent — safe to re-run. Paste in
-- phpMyAdmin → select the WordPress database → SQL tab → Go.
--
-- Naming note (per client direction): the product-facing name is now
-- "Individual Readiness Review™ / IRR" everywhere (UI, API, prompts). The
-- physical table names stay `wp_fusion_360_*` — legacy identifiers kept as-is
-- so we don't have to rename storage that other code/reports may reference.
--
-- Scoping: one IRR per (employee_user_id, year) — unchanged from the
-- existing `r360_employee_year_uq` key already defined in wp_fusion_core.sql.

-- -----------------------------------------------------------------------------
-- wp_fusion_360_reviews: manager/company scoping + wizard bookkeeping
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_360_reviews`
    ADD COLUMN IF NOT EXISTS `manager_user_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — the employee''s leader for this review (Step 4 conversation partner)'
        AFTER `employee_user_id`,
    ADD COLUMN IF NOT EXISTS `company_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_companies.id — denormalized copy for display/joins'
        AFTER `manager_user_id`,
    ADD COLUMN IF NOT EXISTS `company_group_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_company_groups.id — denormalized copy for display/joins'
        AFTER `company_id`,
    ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — who started this review (usually the manager)'
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `started_at` TIMESTAMP NULL
        AFTER `created_by`,
    ADD COLUMN IF NOT EXISTS `published_at` TIMESTAMP NULL
        AFTER `started_at`,
    ADD COLUMN IF NOT EXISTS `step_progress` LONGTEXT NULL
        COMMENT 'JSON: bool map of completed steps, same pattern as wp_fusion_arps.step_progress'
        AFTER `published_at`;

-- `status` on the base table only allowed draft|scheduled|held|closed; the
-- wizard needs the same in_progress/ready_to_publish/published vocabulary
-- used by ARP/QBR, so it is redefined here (idempotent — MODIFY always runs,
-- but the ENUM values are a superset so existing rows are unaffected).
ALTER TABLE `wp_fusion_360_reviews`
    MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'draft'
        COMMENT 'draft | in_progress | ready_to_publish | published | archived';

ALTER TABLE `wp_fusion_360_reviews`
    ADD INDEX IF NOT EXISTS `r360_manager_idx` (`manager_user_id`),
    ADD INDEX IF NOT EXISTS `r360_group_idx` (`company_group_id`);

-- -----------------------------------------------------------------------------
-- wp_fusion_360_ai_assessments: Step 3 AI Development Assessment™ metadata
-- (assessment JSON itself already covers strengths/opportunities/patterns/
-- readiness indicators/contributions — no schema change needed there).
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_360_ai_assessments`
    ADD COLUMN IF NOT EXISTS `prompt_version_id` VARCHAR(60) NULL AFTER `insight_model`,
    ADD COLUMN IF NOT EXISTS `prompt_version_label` VARCHAR(120) NULL AFTER `prompt_version_id`;

-- -----------------------------------------------------------------------------
-- wp_fusion_360_conversation_agreements: Step 4 Development Conversation™ —
-- shared notes + dual digital signatures. Distinct from `reflections` (which
-- stays available for a private per-side reflection + agreement_rating on
-- the AI assessment, if that pattern is reintroduced later).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_360_conversation_agreements` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `conversation_notes` TEXT NULL COMMENT 'Shared notes captured during the Development Conversation™; private to employee + leader',
    `conversation_date` DATE NULL COMMENT 'Date the conversation is acknowledged to have taken place',
    `employee_signed_at` TIMESTAMP NULL,
    `employee_signature_name` VARCHAR(255) NULL,
    `leader_signed_at` TIMESTAMP NULL,
    `leader_signature_name` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `r360ca_review_uq` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- wp_fusion_360_commitments: Step 5 Annual Development Commitments™ fields
-- beyond the base schema (base only has title/description/status/due_date).
-- Max 5 commitments per review, enforced in application code.
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_360_commitments`
    ADD COLUMN IF NOT EXISTS `owner_user_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID — usually the employee; free text fallback in owner_name'
        AFTER `description`,
    ADD COLUMN IF NOT EXISTS `owner_name` VARCHAR(255) NULL AFTER `owner_user_id`,
    ADD COLUMN IF NOT EXISTS `priority` VARCHAR(20) NOT NULL DEFAULT 'medium'
        COMMENT 'high | medium | low'
        AFTER `owner_name`,
    ADD COLUMN IF NOT EXISTS `success_indicator` VARCHAR(255) NULL
        COMMENT 'How progress/success will be measured'
        AFTER `priority`,
    ADD COLUMN IF NOT EXISTS `behavioral_driver` VARCHAR(40) NULL
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth'
        AFTER `success_indicator`,
    ADD COLUMN IF NOT EXISTS `org_priority_type` VARCHAR(20) NULL
        COMMENT 'arp | qbr — which object the optional org priority link points to'
        AFTER `behavioral_driver`,
    ADD COLUMN IF NOT EXISTS `org_priority_label` VARCHAR(255) NULL
        COMMENT 'Free-text label of the linked ARP/QBR priority (matched by name, same pattern as QBR Step 5)'
        AFTER `org_priority_type`,
    ADD COLUMN IF NOT EXISTS `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Drag order within the review (max 5 commitments)'
        AFTER `org_priority_label`;

-- -----------------------------------------------------------------------------
-- wp_fusion_360_ai_syntheses: Step 6 AI Development Synthesis™ metadata
-- (synthesis JSON already covers annual/behavioral/strength/opportunity
-- summaries, readiness gauge, roadmap, focus areas, coaching summary).
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_360_ai_syntheses`
    ADD COLUMN IF NOT EXISTS `prompt_version_id` VARCHAR(60) NULL AFTER `insight_model`,
    ADD COLUMN IF NOT EXISTS `prompt_version_label` VARCHAR(120) NULL AFTER `prompt_version_id`;
