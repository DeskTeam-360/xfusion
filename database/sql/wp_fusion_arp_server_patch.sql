-- =============================================================================
-- ARP server patch — run on the WordPress DB used by Laravel (sandbox/production)
--
-- WHEN TO USE THIS FILE
-- -------------------
-- Existing server that already runs ARP (Steps 3–6 were working): run THIS file
-- once in phpMyAdmin (SQL tab) or mysql CLI. Most statements are idempotent.
--
-- Brand-new FUSION install with NO wp_fusion_* tables yet: run
--   database/sql/wp_fusion_core.sql
-- instead (full schema including QBR, 1-on-1, evidence_log, ARP).
--
-- ORDER IF YOU RUN FILES SEPARATELY (instead of this bundle)
-- -----------------------------------------------------------
-- 1. wp_fusion_arp_wizard.sql      — CREATE missing ARP tables + column alters
-- 2. wp_fusion_arp_scope_to_group.sql — company_group_id + unique per group/year
-- 3. wp_fusion_arp_versioning.sql  — version, published_at, wp_fusion_arp_versions
-- 4. wp_fusion_arp_server_patch.sql — step_progress + evidence_log safety net
--
-- NOTES
-- -----
-- • DROP INDEX / ADD UNIQUE below use information_schema — safe to re-run.
-- • ADD KEY duplicates may still error — safe to ignore.
-- • Requires MySQL 8.0.29+ / MariaDB 10.0.2+ for ADD COLUMN IF NOT EXISTS.
-- • After SQL: deploy Laravel code + WP plugin, then re-save ARP steps 1/2/5
--   (GF data is no longer read; testing data can be re-entered in the wizard).
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1. Evidence log (cross-cutting — required for ARP publish / AI events)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_evidence_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_type` VARCHAR(60) NOT NULL COMMENT 'one_on_one | qbr | 360 | arr | arp | result_evaluation',
    `source_id` BIGINT UNSIGNED NOT NULL COMMENT 'PK of the row in the source table',
    `event_type` VARCHAR(60) NOT NULL COMMENT 'e.g. arp_published, ai_readiness_review_generated',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'WP user ID this evidence is about',
    `behavioral_driver` VARCHAR(40) NULL COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth',
    `cor_capability` VARCHAR(40) NULL COMMENT 'alignment | accountability | communication | leadership | execution',
    `evidence_date` DATE NOT NULL,
    `metadata` LONGTEXT NULL COMMENT 'JSON: free-form context, never raw GF answers',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `fel_user_idx` (`user_id`),
    KEY `fel_source_idx` (`source_type`, `source_id`),
    KEY `fel_driver_idx` (`behavioral_driver`),
    KEY `fel_capability_idx` (`cor_capability`),
    KEY `fel_evidence_date_idx` (`evidence_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2. ARP scope: one plan per company GROUP per year (not per company)
--    From: wp_fusion_arp_scope_to_group.sql
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `company_group_id` BIGINT UNSIGNED NULL
        COMMENT 'wp_company_groups.id — the real scoping key'
        AFTER `company_id`;

-- Drop old company-level unique key only if it still exists (#1091 = already gone → OK).
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

-- Add group-level unique key only if not present yet.
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


-- -----------------------------------------------------------------------------
-- 3. ARP versioning (publish / archive snapshots)
--    From: wp_fusion_arp_versioning.sql
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `version` DECIMAL(4,1) NOT NULL DEFAULT 1.0
        COMMENT 'Bumped by 0.1 on every publish' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `published_at` TIMESTAMP NULL
        COMMENT 'Set when first published; updated on re-publish' AFTER `version`;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `version` DECIMAL(4,1) NOT NULL,
    `status` VARCHAR(20) NOT NULL COMMENT 'archived | published',
    `snapshot` LONGTEXT NOT NULL COMMENT 'JSON: full ARP plan state at this version',
    `published_by_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.ID',
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpv_arp_idx` (`arp_id`),
    KEY `arpv_version_idx` (`arp_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 4. Laravel SoT rewrite: step progress JSON on wp_fusion_arps
-- -----------------------------------------------------------------------------
ALTER TABLE `wp_fusion_arps`
    ADD COLUMN IF NOT EXISTS `step_progress` LONGTEXT NULL
        COMMENT 'JSON: foundation, future_state, readiness, strategic, learning, ai_review, publish'
        AFTER `executive_narrative`;


-- -----------------------------------------------------------------------------
-- 5. Child tables (safe if wp_fusion_arp_wizard.sql was never run)
-- -----------------------------------------------------------------------------
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
    `leadership_context` TEXT NULL,
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpaa_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `wp_fusion_arp_ai_assessments`
    ADD COLUMN IF NOT EXISTS `leadership_context` TEXT NULL
        COMMENT 'Step 6 leadership context'
        AFTER `assessment`;


-- -----------------------------------------------------------------------------
-- 6. Quick verify (optional — uncomment to run)
-- -----------------------------------------------------------------------------
-- SHOW TABLES LIKE 'wp_fusion_arp%';
-- DESCRIBE `wp_fusion_arps`;
-- SELECT COUNT(*) FROM wp_fusion_evidence_log;
