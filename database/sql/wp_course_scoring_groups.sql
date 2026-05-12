-- Course scoring groups (Laravel admin). Run on the application database (MySQL/MariaDB).
-- Table names use the wp_* prefix per project convention.

CREATE TABLE IF NOT EXISTS `wp_course_scoring_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_course_scoring_group_details` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_scoring_group_id` BIGINT UNSIGNED NOT NULL,
    `form_id` INT UNSIGNED NOT NULL,
    `field_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `csg_unique_group_form_field` (`course_scoring_group_id`,`form_id`,`field_id`),
    KEY `wp_course_scoring_group_details_course_scoring_group_id_index` (`course_scoring_group_id`),
    CONSTRAINT `wp_course_scoring_group_details_group_fk`
        FOREIGN KEY (`course_scoring_group_id`)
        REFERENCES `wp_course_scoring_groups` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
