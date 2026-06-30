-- Company groups (parent + member details). Run if migrate is not used on production.

CREATE TABLE IF NOT EXISTS `wp_company_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `wp_company_groups_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `wp_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_company_group_details` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'member',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cg_unique_group_user` (`company_group_id`, `user_id`),
    CONSTRAINT `wp_company_group_details_group_id_foreign`
        FOREIGN KEY (`company_group_id`) REFERENCES `wp_company_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `wp_company_group_details_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
