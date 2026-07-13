-- =============================================================================
-- FUSION 1-on-1 Alignment Capture™ — bootstrap / catch-up SQL
--
-- Jalankan di phpMyAdmin (atau mysql CLI) pada database WordPress yang sama
-- dengan Laravel. Aman di-re-run: CREATE TABLE IF NOT EXISTS + ADD COLUMN IF NOT EXISTS.
--
-- Gunakan file ini jika migration Laravel sudah ketinggalan jauh.
-- Prefix default: wp_ — ganti jika instalasi WordPress memakai prefix lain.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- 1. Tabel inti 1-on-1 (buat jika belum ada)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_ones` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_companies.id',
    `leader_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_users.ID',
    `employee_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'wp_users.ID',
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
    `meeting_link` VARCHAR(500) NULL COMMENT 'Video call URL (Zoom, Meet, Teams, etc.)',
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
    `brief` LONGTEXT NOT NULL COMMENT 'JSON: AI Meeting Brief',
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
    `is_revealed` TINYINT(1) NOT NULL DEFAULT 0,
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
    `section` VARCHAR(60) NOT NULL COMMENT 'priorities | progress | barriers | development | support | future_opportunities | general',
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
    `description` TEXT NULL COMMENT 'Optional free-form notes; Step 5 UI fields use dedicated columns below',
    `priority` VARCHAR(20) NOT NULL DEFAULT 'medium' COMMENT 'high | medium | low',
    `behavioral_driver` VARCHAR(40) NULL COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth (employee commitments)',
    `success_indicator` TEXT NULL COMMENT 'How success will be measured',
    `owner_role` VARCHAR(20) NOT NULL DEFAULT 'shared' COMMENT 'employee | leader | shared',
    `owner_user_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open | in_progress | done',
    `due_date` DATE NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooco_conversation_idx` (`conversation_id`),
    KEY `ooco_owner_idx` (`owner_user_id`),
    KEY `ooco_status_idx` (`status`),
    KEY `ooco_due_date_idx` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_fusion_one_on_one_ai_syntheses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `synthesis` LONGTEXT NOT NULL COMMENT 'JSON: AI Meeting Synthesis',
    `insight_model` VARCHAR(60) NULL,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `cost_usd` DECIMAL(10,4) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ooas_conversation_idx` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2. Patch tabel yang SUDAH ada (kolom baru / index)
-- -----------------------------------------------------------------------------

ALTER TABLE `wp_fusion_one_on_one_conversations`
    ADD COLUMN IF NOT EXISTS `meeting_link` VARCHAR(500) NULL
        COMMENT 'Video call URL (Zoom, Meet, Teams, etc.)'
        AFTER `held_at`;

ALTER TABLE `wp_fusion_one_on_one_commitments`
    ADD COLUMN IF NOT EXISTS `priority` VARCHAR(20) NOT NULL DEFAULT 'medium'
        COMMENT 'high | medium | low'
        AFTER `description`;

ALTER TABLE `wp_fusion_one_on_one_commitments`
    ADD COLUMN IF NOT EXISTS `behavioral_driver` VARCHAR(40) NULL
        COMMENT 'get_real | fill_buckets | be_intentional | foster_grit | drive_growth'
        AFTER `priority`;

ALTER TABLE `wp_fusion_one_on_one_commitments`
    ADD COLUMN IF NOT EXISTS `success_indicator` TEXT NULL
        COMMENT 'How success will be measured'
        AFTER `behavioral_driver`;

-- Index tambahan (abaikan error "Duplicate key name" jika sudah ada)
ALTER TABLE `wp_fusion_one_on_one_commitments`
    ADD KEY `ooco_status_idx` (`status`);

ALTER TABLE `wp_fusion_one_on_one_commitments`
    ADD KEY `ooco_due_date_idx` (`due_date`);


-- -----------------------------------------------------------------------------
-- 3. (Opsional) Migrasi data lama: description JSON → kolom dedicated
--     Hanya baris yang description-nya JSON object dari wizard lama.
-- -----------------------------------------------------------------------------

UPDATE `wp_fusion_one_on_one_commitments`
SET
    `priority` = COALESCE(
        NULLIF(`priority`, ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`description`, '$.priority')), ''),
        'medium'
    ),
    `behavioral_driver` = COALESCE(
        NULLIF(`behavioral_driver`, ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`description`, '$.behavioral_driver')), '')
    ),
    `success_indicator` = COALESCE(
        NULLIF(`success_indicator`, ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(`description`, '$.success_indicator')), '')
    )
WHERE `description` IS NOT NULL
  AND JSON_VALID(`description`)
  AND JSON_TYPE(`description`) = 'OBJECT';


-- -----------------------------------------------------------------------------
-- 4. Verifikasi cepat (opsional — hapus komentar untuk menjalankan)
-- -----------------------------------------------------------------------------
-- SHOW TABLES LIKE 'wp_fusion_one_on_one%';
-- DESCRIBE `wp_fusion_one_on_one_commitments`;
