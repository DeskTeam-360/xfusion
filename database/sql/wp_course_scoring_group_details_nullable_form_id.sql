-- Allow form_id NULL when CSV references a page title with no matching Gravity Form
-- (e.g. "No Orders - LMS Page 11"). Pair with nullable field_id migration.

ALTER TABLE `wp_course_scoring_group_details`
    MODIFY COLUMN `form_id` INT UNSIGNED NULL DEFAULT NULL;
