-- =============================================================================
-- FUSION Operating System: core schema for ARP, QBR, 1-on-1, 360, ARR
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
--
-- Naming: `wp_fusion_*` (default wp_ prefix; change if your WordPress table
-- prefix differs). Safe to re-run: CREATE TABLE IF NOT EXISTS.
--
-- No FK constraints by design (see database/sql/wp_company_groups.sql notes):
-- wp_companies / wp_users engine or collation can differ from this app's
-- default, which makes FK creation fail with errno 150. Referential
-- integrity for company_id / user_id / *_id columns is enforced at the
-- application layer (Eloquent models in app/Models).
--
-- evaluation/assessment/synthesis JSON payloads are stored as LONGTEXT
-- (consistent with wp_xfusion_result_evaluations.evaluation).
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Cross-cutting: evidence log
-- Every component (1-on-1, QBR, 360, ARR) reads from this log to build its
-- "evidence" view instead of touching raw Gravity Forms answers directly.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_evidence_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_type` VARCHAR(60) NOT NULL COMMENT 'one_on_one | qbr | 360 | arr | arp | result_evaluation',
    `source_id` BIGINT UNSIGNED NOT NULL COMMENT 'PK of the row in the source table',
    `event_type` VARCHAR(60) NOT NULL COMMENT 'e.g. commitment_completed, conversation_held, ai_assessment_generated',
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
-- ARP — Annual Readiness Plan™ (strategic anchor, tahunan)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_arps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id',
    `year` SMALLINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `mission` TEXT NULL,
    `vision` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | active | archived',
    `created_by` BIGINT UNSIGNED NULL COMMENT 'wp_users.id, executive who created it',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `arp_company_year_uq` (`company_id`, `year`),
    KEY `arp_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_future_states` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `narrative` TEXT NOT NULL COMMENT '"Harus jadi organisasi seperti apa kita?"',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpfs_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_readiness_priorities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `cor_capability` VARCHAR(40) NOT NULL COMMENT 'alignment | accountability | communication | leadership | execution',
    `description` TEXT NOT NULL,
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arprp_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_strategic_priorities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `owner_user_id` BIGINT UNSIGNED NULL COMMENT 'wp_users.id',
    `kpi` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'not_started' COMMENT 'not_started | in_progress | done | at_risk',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpsp_arp_idx` (`arp_id`),
    KEY `arpsp_owner_idx` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arp_learnings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arp_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(30) NOT NULL COMMENT 'assumption | risk | learning_objective',
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
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arpaa_arp_idx` (`arp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- QBR — Quarterly Business Review™ (kesiapan organisasi, per kuartal)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_qbrs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id',
    `company_group_id` BIGINT UNSIGNED NULL COMMENT 'wp_company_groups.id, null = whole company',
    `quarter` TINYINT UNSIGNED NOT NULL COMMENT '1-4',
    `year` SMALLINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | scheduled | held | closed',
    `held_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `qbr_scope_period_uq` (`company_id`, `company_group_id`, `quarter`, `year`),
    KEY `qbr_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_evidence_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `snapshot` LONGTEXT NOT NULL COMMENT 'JSON: evidence pulled from wp_fusion_evidence_log + wp_xfusion_result_evaluations.evaluation only',
    `captured_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbres_qbr_idx` (`qbr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_ai_assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `assessment` LONGTEXT NOT NULL COMMENT 'JSON: AI Organizational Assessment output',
    `leadership_context` TEXT NULL COMMENT 'free-text context supplied by the leader running the QBR',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbraa_qbr_idx` (`qbr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_commitments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `owner_user_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open | in_progress | done | carried_forward',
    `carried_forward_from_id` BIGINT UNSIGNED NULL COMMENT 'self-reference: previous quarter commitment id',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbrc_qbr_idx` (`qbr_id`),
    KEY `qbrc_owner_idx` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_qbr_ai_syntheses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `qbr_id` BIGINT UNSIGNED NOT NULL,
    `synthesis` LONGTEXT NOT NULL COMMENT 'JSON: final QBR synthesis output',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `qbras_qbr_idx` (`qbr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 1-on-1 — Alignment Capture™ (coaching individu, bulanan)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_ones` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id',
    `leader_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_users.id',
    `employee_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_users.id',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active | inactive',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `oo_pair_uq` (`leader_user_id`, `employee_user_id`),
    KEY `oo_company_idx` (`company_id`),
    KEY `oo_employee_idx` (`employee_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `one_on_one_id` BIGINT UNSIGNED NOT NULL,
    `scheduled_at` TIMESTAMP NULL,
    `held_at` TIMESTAMP NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled | in_progress | completed | cancelled',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooc_pair_idx` (`one_on_one_id`),
    KEY `ooc_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_ai_briefs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `brief` LONGTEXT NOT NULL COMMENT 'JSON: AI Meeting Brief (pre-meeting), built from AI synthesis of prior conversations only',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooab_conversation_idx` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_preparations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `author_role` VARCHAR(20) NOT NULL COMMENT 'employee | leader',
    `author_user_id` BIGINT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `is_revealed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'false until meeting starts; employee/leader cannot see each other before that',
    `revealed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `oop_conversation_role_uq` (`conversation_id`, `author_role`),
    KEY `oop_author_idx` (`author_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_notes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `section` VARCHAR(60) NOT NULL COMMENT 'e.g. wins, blockers, alignment, growth',
    `note` TEXT NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `oon_conversation_idx` (`conversation_id`),
    KEY `oon_section_idx` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_commitments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `owner_role` VARCHAR(20) NOT NULL DEFAULT 'shared' COMMENT 'employee | leader | shared',
    `owner_user_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open | in_progress | done',
    `due_date` DATE NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooco_conversation_idx` (`conversation_id`),
    KEY `ooco_owner_idx` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_ai_syntheses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `synthesis` LONGTEXT NOT NULL COMMENT 'JSON: AI Meeting Synthesis (post-meeting)',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooas_conversation_idx` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 360 Review — sintesis pengembangan individu (tahunan)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_360_reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_users.id',
    `year` SMALLINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | scheduled | held | closed',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `r360_employee_year_uq` (`employee_user_id`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_360_evidence_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `snapshot` LONGTEXT NOT NULL COMMENT 'JSON: annual evidence (1-on-1 syntheses + evaluations), never raw GF answers',
    `captured_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `r360es_review_idx` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_360_ai_assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `assessment` LONGTEXT NOT NULL COMMENT 'JSON: AI Development Assessment output',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `r360aa_review_idx` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_360_reflections` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `author_role` VARCHAR(20) NOT NULL COMMENT 'employee | leader',
    `author_user_id` BIGINT UNSIGNED NOT NULL,
    `reflection` TEXT NOT NULL,
    `agreement_rating` TINYINT UNSIGNED NULL COMMENT '1-5, how much they agree with the AI assessment',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `r360r_review_idx` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_360_commitments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open | in_progress | done',
    `due_date` DATE NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `r360c_review_idx` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_360_ai_syntheses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `synthesis` LONGTEXT NOT NULL COMMENT 'JSON: Annual Development Synthesis output',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `r360as_review_idx` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- ARR — Annual Readiness Review™ (pembelajaran organisasi, tahunan)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_fusion_arrs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id',
    `year` SMALLINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | scheduled | held | closed',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `arr_company_year_uq` (`company_id`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arr_evidence_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arr_id` BIGINT UNSIGNED NOT NULL,
    `snapshot` LONGTEXT NOT NULL COMMENT 'JSON: annual evidence from ARP, QBR, 1-on-1, 360 syntheses',
    `captured_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arres_arr_idx` (`arr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arr_ai_assessments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arr_id` BIGINT UNSIGNED NOT NULL,
    `assessment` LONGTEXT NOT NULL COMMENT 'JSON: AI Annual Readiness Assessment output',
    `executive_context` TEXT NULL,
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arraa_arr_idx` (`arr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arr_renewal_recommendations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arr_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority_rank` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arrr_arr_idx` (`arr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_arr_ai_syntheses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `arr_id` BIGINT UNSIGNED NOT NULL,
    `synthesis` LONGTEXT NOT NULL COMMENT 'JSON: Strategic Renewal Synthesis output, feeds next ARP',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `arras_arr_idx` (`arr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
