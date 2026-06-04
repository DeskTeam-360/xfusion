-- Allow field_id NULL when CSV has no question or GF field label cannot be matched.
-- MySQL UNIQUE (group, form, field_id) allows multiple NULL field_id values.

ALTER TABLE `wp_course_scoring_group_details`
    MODIFY COLUMN `field_id` INT UNSIGNED NULL DEFAULT NULL;
