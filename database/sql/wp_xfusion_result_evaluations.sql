-- =============================================================================
-- XFusion: AI evaluation results table
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
--
-- Table name: wp_xfusion_result_evaluations
-- (default wp_ prefix; change if your WordPress table prefix differs)
-- Safe to re-run: CREATE TABLE IF NOT EXISTS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `wp_xfusion_result_evaluations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'WP user ID (employee)',
    `created_at` DATETIME NOT NULL COMMENT 'Client/API request submission time',
    `evaluated_at` DATETIME NOT NULL COMMENT 'LLM evaluation completion time',
    `company_information` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'WP post ID for knowledge base',
    `scoring_group_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID from wp_course_scoring_groups',
    `scoring_group_title` VARCHAR(255) NOT NULL DEFAULT '',
    `score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
    `evaluation_input` LONGTEXT NOT NULL COMMENT 'JSON payload IN (question_answers, etc.)',
    `evaluation` LONGTEXT NOT NULL COMMENT 'JSON OUT: score, strengths, improvements, evaluator_notes',
    `prompt_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `completion_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
    `tokens_used` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total LLM tokens',
    `inserted_at` DATETIME NOT NULL COMMENT 'Row insert time',
    PRIMARY KEY (`id`),
    KEY `xfre_user_group_idx` (`user_id`, `scoring_group_id`),
    KEY `xfre_evaluated_at_idx` (`evaluated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
