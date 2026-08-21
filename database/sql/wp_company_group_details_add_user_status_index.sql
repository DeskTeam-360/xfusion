-- canEditReview()/canAccessReview() (ARP, QBR, IRR, 1-on-1 controllers) all
-- filter wp_company_group_details by user_id first, then status - e.g.
-- WHERE user_id = ? AND status = 'leader'. The only index on this table is
-- the composite UNIQUE (company_group_id, user_id), which MySQL cannot use
-- for a user_id-led lookup (leftmost-prefix rule), so every one of those
-- access checks does a full table scan. This adds the index that actually
-- matches the query pattern. Safe to re-run — skipped if it already exists.
--
-- Run in phpMyAdmin -> select the WordPress database -> SQL tab -> Go.
-- (Same effect as database/migrations/2026_08_22_000000_add_user_status_index_to_company_group_details.php
-- for servers where `php artisan migrate` isn't run.)

SET @idx_exists = (
    SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'wp_company_group_details'
      AND INDEX_NAME = 'cgd_user_status_idx'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `wp_company_group_details` ADD INDEX `cgd_user_status_idx` (`user_id`, `status`)',
    'SELECT ''cgd_user_status_idx already exists, skipped.'' AS notice'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
