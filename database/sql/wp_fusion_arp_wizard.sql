-- =============================================================================
-- FUSION Annual Readiness Plan™ (ARP) — bootstrap / catch-up SQL
--
-- Jalankan di phpMyAdmin (atau mysql CLI) pada database WordPress yang sama
-- dengan Laravel. Aman di-re-run: CREATE TABLE IF NOT EXISTS + ADD COLUMN IF NOT EXISTS.
--
-- WordPress auto-migration: annual-readiness-plan/arp-db-migration.php
--   Option: xfusion_arp_db_version = 1.0
--
-- Prefix default: wp_ — ganti jika instalasi WordPress memakai prefix lain.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1. Tabel inti ARP (buat jika belum ada)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wp_fusion_arps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id — denormalized copy of company_group_id.company_id, for display/joins',
    `company_group_id` BIGINT UNSIGNED NULL COMMENT 'wp_company_groups.id — the real scoping key: one ARP per group per year',
    `year` SMALLINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `mission` TEXT NULL,
    `vision` TEXT NULL,
    `core_values` TEXT NULL,
    `organizational_description` TEXT NULL,
    `business_environment` TEXT NULL,
    `executive_narrative` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | active | archived',
    `created_by` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID — executive who created it',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `arp_group_year_uq` (`company_group_id`, `year`),
    KEY `arp_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_future_states` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `narrative` TEXT NOT NULL COMMENT 'Future State Narrative',
    `future_characteristics` TEXT NULL,
    `desired_culture` TEXT NULL,
    `desired_customer_experience` TEXT NULL,
    `desired_employee_experience` TEXT NULL,
    `desired_leadership_environment` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpfs_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3 — Organizational Readiness™ (repeatable cards)
CREATE TABLE IF NOT EXISTS `wp_fusion_arp_readiness_priorities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Priority Name',
    `cor_capability` VARCHAR(40) NOT NULL DEFAULT 'leadership'
        COMMENT 'alignment | accountability | communication | leadership | execution',
    `primary_driver` VARCHAR(40) NOT NULL DEFAULT 'be_intentional'
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth',
    `secondary_driver` VARCHAR(40) NULL
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth',
    `priority_level` VARCHAR(20) NOT NULL DEFAULT 'medium' COMMENT 'high | medium | low',
    `description` TEXT NULL,
    `business_rationale` TEXT NULL,
    `executive_owner_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID',
    `expected_impact` TEXT NULL,
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Drag order within ARP',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arprp_arp_idx` (`arp_id`),
    KEY `arprp_rank_idx` (`arp_id`, `priority_rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 4 — Strategic Priorities™ (repeatable cards)
CREATE TABLE IF NOT EXISTS `wp_fusion_arp_strategic_priorities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `readiness_priority_id` BIGINT UNSIGNED NULL COMMENT 'FK wp_fusion_arp_readiness_priorities.id',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `owner_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID — Executive Owner',
    `target_date` DATE NULL,
    `success_measures` TEXT NULL,
    `org_kpi` VARCHAR(80) NULL COMMENT 'Slug e.g. leadership_effectiveness',
    `readiness_indicator` VARCHAR(80) NULL COMMENT 'Slug e.g. leadership_bench',
    `related_groups` VARCHAR(80) NULL COMMENT 'Slug e.g. all_leaders',
    `kpi` TEXT NULL COMMENT 'Legacy/free-form KPI notes',
    `status` VARCHAR(20) NOT NULL DEFAULT 'not_started'
        COMMENT 'not_started | in_progress | done | at_risk',
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Drag order within ARP',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpsp_arp_idx` (`arp_id`),
    KEY `arpsp_owner_idx` (`owner_user_id`),
    KEY `arpsp_readiness_idx` (`readiness_priority_id`),
    KEY `arpsp_rank_idx` (`arp_id`, `priority_rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_learnings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(30) NOT NULL DEFAULT 'assumption'
        COMMENT 'assumption | risk | opportunity | learning_objective | leadership_question',
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpl_arp_idx` (`arp_id`),
    KEY `arpl_type_idx` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_ai_assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `assessment` LONGTEXT NOT NULL COMMENT 'JSON: AI Readiness Review output',
    `leadership_context` TEXT NULL COMMENT 'Step 6 leadership context (editable)',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpaa_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2. Patch tabel LAMA (schema wp_fusion_core.sql v1 — kolom tambahan)
--    Aman di-re-run: ADD COLUMN IF NOT EXISTS (MySQL 8+ / MariaDB 10.3+)
-- -----------------------------------------------------------------------------

-- wp_fusion_arps
ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `core_values` TEXT NULL AFTER `vision`;

ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `organizational_description` TEXT NULL AFTER `core_values`;

ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `business_environment` TEXT NULL AFTER `organizational_description`;

ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `executive_narrative` TEXT NULL AFTER `business_environment`;

-- wp_fusion_arp_future_states
ALTER TABLE `wp_fusion_arp_future_states`
    ADD COLUMN IF NOT EXISTS `future_characteristics` TEXT NULL AFTER `narrative`;

ALTER TABLE `wp_fusion_arp_future_states`
    ADD COLUMN IF NOT EXISTS `desired_culture` TEXT NULL AFTER `future_characteristics`;

ALTER TABLE `wp_fusion_arp_future_states`
    ADD COLUMN IF NOT EXISTS `desired_customer_experience` TEXT NULL AFTER `desired_culture`;

ALTER TABLE `wp_fusion_arp_future_states`
    ADD COLUMN IF NOT EXISTS `desired_employee_experience` TEXT NULL AFTER `desired_customer_experience`;

ALTER TABLE `wp_fusion_arp_future_states`
    ADD COLUMN IF NOT EXISTS `desired_leadership_environment` TEXT NULL AFTER `desired_employee_experience`;

-- Step 3 — readiness priorities (upgrade dari schema minimal)
ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Priority Name'
        AFTER `arp_id`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `primary_driver` VARCHAR(40) NOT NULL DEFAULT 'be_intentional'
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth'
        AFTER `cor_capability`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `secondary_driver` VARCHAR(40) NULL
        AFTER `primary_driver`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `priority_level` VARCHAR(20) NOT NULL DEFAULT 'medium'
        COMMENT 'high | medium | low'
        AFTER `secondary_driver`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `business_rationale` TEXT NULL AFTER `description`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `executive_owner_user_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_users.ID'
        AFTER `business_rationale`;

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD COLUMN IF NOT EXISTS `expected_impact` TEXT NULL AFTER `executive_owner_user_id`;

-- Step 4 — strategic priorities (upgrade dari schema minimal)
ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `readiness_priority_id` BIGINT UNSIGNED NULL
        COMMENT 'FK wp_fusion_arp_readiness_priorities.id'
        AFTER `arp_id`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `target_date` DATE NULL AFTER `owner_user_id`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `success_measures` TEXT NULL AFTER `target_date`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `org_kpi` VARCHAR(80) NULL AFTER `success_measures`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `readiness_indicator` VARCHAR(80) NULL AFTER `org_kpi`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `related_groups` VARCHAR(80) NULL AFTER `readiness_indicator`;

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD COLUMN IF NOT EXISTS `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Drag order within ARP'
        AFTER `status`;

-- wp_fusion_arp_ai_assessments
ALTER TABLE `wp_fusion_arp_ai_assessments`
    ADD COLUMN IF NOT EXISTS `leadership_context` TEXT NULL
        COMMENT 'Step 6 leadership context'
        AFTER `assessment`;


-- -----------------------------------------------------------------------------
-- 3. Index tambahan (abaikan error "Duplicate key name" jika sudah ada)
-- -----------------------------------------------------------------------------

ALTER TABLE `wp_fusion_arp_readiness_priorities`
    ADD KEY `arprp_rank_idx` (`arp_id`, `priority_rank`);

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD KEY `arpsp_readiness_idx` (`readiness_priority_id`);

ALTER TABLE `wp_fusion_arp_strategic_priorities`
    ADD KEY `arpsp_rank_idx` (`arp_id`, `priority_rank`);


-- -----------------------------------------------------------------------------
-- 4. Tandai versi schema (opsional — sinkron dengan WordPress option)
-- -----------------------------------------------------------------------------

INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`)
VALUES ('xfusion_arp_db_version', '1.0', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = '1.0';


-- -----------------------------------------------------------------------------
-- 5. Verifikasi cepat (opsional — hapus komentar untuk menjalankan)
-- -----------------------------------------------------------------------------
-- SHOW TABLES LIKE 'wp_fusion_arp%';
-- DESCRIBE `wp_fusion_arp_readiness_priorities`;
-- DESCRIBE `wp_fusion_arp_strategic_priorities`;
