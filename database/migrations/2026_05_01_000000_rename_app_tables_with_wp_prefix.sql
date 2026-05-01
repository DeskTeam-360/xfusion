-- =============================================================================
-- SETARA: rename tabel ke prefix wp_
-- (sama dengan migrasi 2026_05_01_000000_rename_app_tables_with_wp_prefix.php)
--
-- Backup DB dulu. Jalankan di mysql client / phpMyAdmin / Adminer.
--
-- Kalau error "Can't DROP FOREIGN KEY" atau "Unknown constraint", pakai OPSI 2.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- OPSI 1 — Paling ringan: matikan pengecekan FK sementara, RENAME saja.
-- InnoDB biasanya otomatis menyesuaikan definisi FK setelah RENAME.
-- -----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE `tags` TO `wp_tags`;
RENAME TABLE `campaigns` TO `wp_campaigns`;
RENAME TABLE `campaign_log` TO `wp_campaign_logs`;
RENAME TABLE `companies` TO `wp_companies`;
RENAME TABLE `company_employees` TO `wp_company_employees`;
RENAME TABLE `course_groups` TO `wp_course_groups`;
RENAME TABLE `course_group_details` TO `wp_course_group_details`;
RENAME TABLE `course_lists` TO `wp_course_lists`;
RENAME TABLE `user_roles` TO `wp_user_roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- Verifikasi FK masih benar:
--   SHOW CREATE TABLE `wp_company_employees`\G
-- Harus mereferensikan `wp_users` dan `wp_companies`.


-- =============================================================================
-- OPSI 2 — Pakai ini jika OPSI 1 gagal / FK masih mengarah ke nama tabel lama.
-- (1) Cari nama constraint asli:
--     SHOW CREATE TABLE `company_employees`\G
-- (2) Ganti `..._foreign` di bawah sesuai output Anda (bukan tebak Laravel).
-- =============================================================================
/*
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `company_employees`
    DROP FOREIGN KEY `company_employees_user_id_foreign`;

ALTER TABLE `company_employees`
    DROP FOREIGN KEY `company_employees_company_id_foreign`;

RENAME TABLE `tags` TO `wp_tags`;
RENAME TABLE `campaigns` TO `wp_campaigns`;
RENAME TABLE `campaign_log` TO `wp_campaign_logs`;
RENAME TABLE `companies` TO `wp_companies`;
RENAME TABLE `company_employees` TO `wp_company_employees`;
RENAME TABLE `course_groups` TO `wp_course_groups`;
RENAME TABLE `course_group_details` TO `wp_course_group_details`;
RENAME TABLE `course_lists` TO `wp_course_lists`;
RENAME TABLE `user_roles` TO `wp_user_roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- Hapus constraint lama di tabel baru jika masih ada duplikat / salah nama:
-- ALTER TABLE `wp_company_employees` DROP FOREIGN KEY `...`;

ALTER TABLE `wp_company_employees`
    ADD CONSTRAINT `wp_company_employees_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,
    ADD CONSTRAINT `wp_company_employees_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `wp_companies` (`id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION;
*/


-- =============================================================================
-- OPSI ROLLBACK (manual) — hanya jika Anda belum mengubah aplikasi / model.
-- =============================================================================
/*
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `wp_company_employees`
    DROP FOREIGN KEY `wp_company_employees_user_id_foreign`;
ALTER TABLE `wp_company_employees`
    DROP FOREIGN KEY `wp_company_employees_company_id_foreign`;

RENAME TABLE `wp_tags` TO `tags`;
RENAME TABLE `wp_campaigns` TO `campaigns`;
RENAME TABLE `wp_campaign_logs` TO `campaign_log`;
RENAME TABLE `wp_companies` TO `companies`;
RENAME TABLE `wp_company_employees` TO `company_employees`;
RENAME TABLE `wp_course_groups` TO `course_groups`;
RENAME TABLE `wp_course_group_details` TO `course_group_details`;
RENAME TABLE `wp_course_lists` TO `course_lists`;
RENAME TABLE `wp_user_roles` TO `user_roles`;

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE `company_employees`
    ADD CONSTRAINT `company_employees_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `wp_users` (`id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,
    ADD CONSTRAINT `company_employees_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION;
*/
