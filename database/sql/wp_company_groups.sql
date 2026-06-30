-- Company groups (parent + member details). Run if migrate is not used on production.
-- FK constraints are intentionally omitted here: wp_companies/wp_users engine or
-- collation may not match exactly, which makes `errno: 150` likely. Referential
-- integrity for these columns is enforced at the application layer instead
-- (see App\Models\CompanyGroup / CompanyGroupDetail).

CREATE TABLE IF NOT EXISTS `wp_company_groups` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_company_group_details` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_group_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'member',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cg_unique_group_user` (`company_group_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
