-- Company URL slug, used for /{company-slug}/annual-readiness-plan/, /annual-readiness-review/, /quarterly-business-review/, /individual-readiness-review/, /1-on-1-alignment/ on the WordPress site.
-- Paste in phpMyAdmin → select the WordPress database → SQL tab → Go
-- Safe to re-run. Existing companies get their slug via:  php artisan companies:backfill-slugs
-- (or just open and save a company in the admin — an empty slug is filled from its name).

ALTER TABLE `wp_companies`
    ADD COLUMN IF NOT EXISTS `slug` VARCHAR(120) NULL AFTER `title`;

-- Unique index only if it does not exist yet.
SET @has_idx := (SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'wp_companies' AND index_name = 'companies_slug_uq');
SET @sql := IF(@has_idx = 0, 'ALTER TABLE `wp_companies` ADD UNIQUE KEY `companies_slug_uq` (`slug`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
