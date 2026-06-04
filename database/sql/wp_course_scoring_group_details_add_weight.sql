-- =============================================================================
-- Course scoring group details: kolom beban (weight)
-- Paste di phpMyAdmin → pilih database aplikasi → tab SQL → Go
--
-- Tabel: wp_course_scoring_group_details
-- (sesuaikan prefix wp_ jika instalasi WordPress memakai prefix lain)
--
-- weight: bobot per field dalam grup (2 angka di belakang koma)
-- Baris lama otomatis 1.00 (bobot sama) setelah kolom ditambahkan.
-- =============================================================================

ALTER TABLE `wp_course_scoring_group_details`
    ADD COLUMN `weight` DECIMAL(10, 2) NOT NULL DEFAULT 1.00
        COMMENT 'Beban/bobot field dalam grup scoring'
        AFTER `field_id`;

-- Opsional: pastikan tidak ada nilai negatif (uncomment jika perlu)
-- ALTER TABLE `wp_course_scoring_group_details`
--     ADD CONSTRAINT `wp_csgd_weight_non_negative` CHECK (`weight` >= 0);

-- Rollback (hapus kolom):
-- ALTER TABLE `wp_course_scoring_group_details` DROP COLUMN `weight`;
