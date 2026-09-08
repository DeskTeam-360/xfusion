-- Diagnostic: why "Tool Usage" evidence shows "No development tools are
-- configured for this group yet." for course groups 17, 20, 21, 22, 23, 24.
-- See QbrEvidenceService::toolUtilizationStats() for the logic this mirrors.

-- 1. Are these groups actually flagged as "Tools" (tools = 1)?
SELECT id, title, sub_title, type, tools, icon
FROM wp_course_groups
WHERE id IN (17, 20, 21, 22, 23, 24);

-- 2. What course lists are attached to each of these groups?
SELECT cgd.course_group_id, cgd.course_list_id, cgd.orders
FROM wp_course_group_details cgd
WHERE cgd.course_group_id IN (17, 20, 21, 22, 23, 24)
ORDER BY cgd.course_group_id;

-- 3. Of those course lists, which ones are actually linked to a Gravity
--    Form (wp_gf_form_id NOT NULL and > 0)? Only these count as "tools".
SELECT cl.id AS course_list_id, cl.page_title, cl.course_title, cl.wp_gf_form_id,
       cgd.course_group_id
FROM wp_course_lists cl
INNER JOIN wp_course_group_details cgd ON cgd.course_list_id = cl.id
WHERE cgd.course_group_id IN (17, 20, 21, 22, 23, 24);

-- 4. Same as #3 but only the ones that FAIL the wp_gf_form_id check
--    (these are exactly why total_tools comes out lower than expected).
SELECT cl.id AS course_list_id, cl.page_title, cl.course_title, cl.wp_gf_form_id,
       cgd.course_group_id
FROM wp_course_lists cl
INNER JOIN wp_course_group_details cgd ON cgd.course_list_id = cl.id
WHERE cgd.course_group_id IN (17, 20, 21, 22, 23, 24)
  AND (cl.wp_gf_form_id IS NULL OR cl.wp_gf_form_id <= 0);
