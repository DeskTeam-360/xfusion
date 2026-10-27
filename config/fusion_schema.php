<?php
/**
 * Hand-kept list of every table/column FUSION needs in the shared database.
 * Edit it when a feature starts using a new table or column; it is what
 * `php artisan fusion:check-schema` and Tools > FUSION Schema Check verify.
 * (Not derived from migrations or models on purpose: the unused default
 * `users` table, for instance, is not needed — wp_users is.)
 *
 * Columns are checked case-insensitively. 'sql' names the file in database/sql
 * to run when the table/columns are missing.
 */
return [
    // ---- Used by the Laravel app (models). Models on the 'wordpress' connection are listed with their wp_ prefix. ----
    // Not listed: the activity log (WpViewAllLog) — it declares no table name, so its real name is unknown here.
    'laravel' => [
        'wp_company_employees' => ['user_id', 'company_id'],
        'wp_gf_entry' => ['form_id', 'post_id', 'date_created', 'date_updated', 'is_starred', 'is_read', 'ip', 'source_url', 'user_agent', 'currency', 'payment_status', 'payment_date', 'payment_amount', 'payment_method', 'is_fulfilled', 'transaction_id', 'created_by', 'transaction_type', 'status'],
        'wp_gf_entry_meta' => ['form_id', 'entry_id', 'meta_key', 'meta_value', 'item_index'],
        'wp_gf_form' => ['title', 'date_created', 'date_updated', 'is_active', 'is_trash'],
        'wp_gf_form_meta' => ['form_id', 'display_meta', 'entries_grid_meta', 'confirmations', 'notifications'],
        'wp_usermeta' => ['user_id', 'meta_key', 'meta_value'],
        'wp_users' => ['user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_activation_key', 'user_status', 'display_name'],
        'wp_campaign_logs' => ['user_id', 'tag_id', 'status'],
        'wp_campaigns' => ['users', 'created_by_group', 'tags', 'status', 'time_schedule'],
        'wp_companies' => ['user_id', 'title', 'slug', 'logo_url', 'qrcode_url', 'company_url', 'role', 'team', 'organizational_goals', 'readiness_priorities'],
        'wp_company_group_details' => ['company_group_id', 'user_id', 'status'],
        'wp_company_groups' => ['company_id', 'title', 'description', 'role', 'team', 'organizational_goals', 'readiness_priorities'],
        'wp_course_group_details' => ['course_group_id', 'course_list_id', 'orders'],
        'wp_course_groups' => ['title', 'sub_title', 'type', 'chart', 'icon', 'tools'],
        'wp_course_lists' => ['url', 'course_title', 'page_title', 'wp_gf_form_id', 'lms_topic_id', 'keap_tag', 'keap_tag_next', 'delay', 'url_next', 'repeat_entry', 'legacy', 'icon'],
        'wp_course_scoring_group_details' => ['course_scoring_group_id', 'form_id', 'field_id', 'weight'],
        'wp_course_scoring_groups' => ['title', 'description'],
        'wp_fusion_360_ai_assessments' => ['review_id', 'assessment', 'insight_model', 'tokens_used', 'cost_usd', 'prompt_version_id', 'prompt_version_label'],
        'wp_fusion_360_ai_syntheses' => ['review_id', 'synthesis', 'insight_model', 'tokens_used', 'cost_usd', 'prompt_version_id', 'prompt_version_label'],
        'wp_fusion_360_commitments' => ['review_id', 'title', 'description', 'owner_user_id', 'owner_name', 'priority', 'success_indicator', 'behavioral_driver', 'org_priority_type', 'org_priority_label', 'status', 'due_date', 'priority_rank'],
        'wp_fusion_360_conversation_agreements' => ['review_id', 'conversation_notes', 'conversation_date', 'employee_signed_at', 'employee_signature_name', 'leader_signed_at', 'leader_signature_name'],
        'wp_fusion_360_evidence_snapshots' => ['review_id', 'snapshot', 'captured_at'],
        'wp_fusion_360_reviews' => ['employee_user_id', 'manager_user_id', 'company_id', 'company_group_id', 'year', 'status', 'created_by', 'started_at', 'published_at', 'step_progress'],
        'wp_fusion_arp_ai_assessments' => ['arp_id', 'assessment', 'leadership_context', 'insight_model', 'tokens_used', 'cost_usd'],
        'wp_fusion_arp_future_states' => ['arp_id', 'narrative', 'future_characteristics', 'desired_culture', 'desired_customer_experience', 'desired_employee_experience', 'desired_leadership_environment'],
        'wp_fusion_arp_kpis' => ['arp_id', 'name', 'type', 'description', 'why_it_matters', 'current_baseline', 'target_value', 'target_date', 'measurement_frequency', 'data_source', 'owner_user_id', 'readiness_priority_ids', 'notes', 'priority_rank'],
        'wp_fusion_arp_learnings' => ['arp_id', 'type', 'description'],
        'wp_fusion_arp_readiness_priorities' => ['arp_id', 'name', 'cor_capability', 'primary_driver', 'secondary_driver', 'priority_level', 'description', 'business_rationale', 'executive_owner_user_ids', 'expected_impact', 'priority_rank'],
        'wp_fusion_arp_strategic_priorities' => ['arp_id', 'readiness_priority_id', 'title', 'description', 'owner_user_ids', 'target_date', 'success_measures', 'org_kpi', 'readiness_indicator', 'related_groups', 'kpi', 'status', 'priority_rank'],
        'wp_fusion_arp_versions' => ['arp_id', 'version', 'status', 'snapshot', 'published_by_user_id', 'published_at', 'created_at'],
        'wp_fusion_arps' => ['company_id', 'year', 'title', 'mission', 'vision', 'core_values', 'organizational_description', 'business_environment', 'executive_narrative', 'step_progress', 'status', 'version', 'published_at', 'created_by'],
        'wp_fusion_arr_ai_assessments' => ['arr_id', 'assessment', 'executive_context', 'agreement_rating', 'insight_model', 'prompt_version_id', 'prompt_version_label', 'tokens_used', 'cost_usd'],
        'wp_fusion_arr_ai_syntheses' => ['arr_id', 'synthesis', 'insight_model', 'prompt_version_id', 'prompt_version_label', 'tokens_used', 'cost_usd'],
        'wp_fusion_arr_evidence_snapshots' => ['arr_id', 'snapshot', 'captured_at'],
        'wp_fusion_arr_executive_reflections' => ['arr_id', 'organizational_learning', 'readiness_progression', 'strategic_assumptions', 'organizational_barriers', 'organizational_strengths', 'leadership_effectiveness', 'resource_allocation', 'future_opportunities', 'conversation_notes', 'author_user_id'],
        'wp_fusion_arr_renewal_recommendations' => ['arr_id', 'title', 'description', 'priority', 'executive_owner_user_id', 'executive_owner_name', 'cor_capability', 'behavioral_driver', 'expected_organizational_impact', 'recommended_timeline', 'status', 'priority_rank'],
        'wp_fusion_arrs' => ['company_id', 'executive_owner_user_id', 'year', 'status', 'created_by', 'started_at', 'published_at', 'step_progress'],
        'wp_fusion_evidence_log' => ['source_type', 'source_id', 'event_type', 'user_id', 'behavioral_driver', 'cor_capability', 'evidence_date', 'metadata'],
        'wp_fusion_one_on_one_ai_briefs' => ['conversation_id', 'brief', 'insight_model', 'tokens_used', 'cost_usd'],
        'wp_fusion_one_on_one_ai_syntheses' => ['conversation_id', 'synthesis', 'insight_model', 'tokens_used', 'cost_usd'],
        'wp_fusion_one_on_one_commitments' => ['conversation_id', 'title', 'description', 'priority', 'behavioral_driver', 'success_indicator', 'owner_role', 'owner_user_id', 'status', 'due_date'],
        'wp_fusion_one_on_one_conversations' => ['one_on_one_id', 'scheduled_at', 'held_at', 'meeting_link', 'status', 'last_step'],
        'wp_fusion_one_on_one_notes' => ['conversation_id', 'section', 'note', 'created_by'],
        'wp_fusion_one_on_one_preparations' => ['conversation_id', 'author_role', 'author_user_id', 'content', 'is_revealed', 'revealed_at'],
        'wp_fusion_one_on_ones' => ['company_id', 'leader_user_id', 'employee_user_id', 'status'],
        'wp_fusion_qbr_ai_assessments' => ['qbr_id', 'assessment', 'leadership_context', 'agreement_rating', 'insight_model', 'tokens_used', 'cost_usd'],
        'wp_fusion_qbr_ai_syntheses' => ['qbr_id', 'synthesis', 'insight_model', 'tokens_used', 'cost_usd'],
        'wp_fusion_qbr_commitments' => ['qbr_id', 'title', 'description', 'owner_user_id', 'owner_name', 'priority', 'related_arp_objective', 'success_measure', 'due_date', 'status', 'carried_forward_from_id', 'priority_rank'],
        'wp_fusion_qbr_decisions' => ['qbr_id', 'decision', 'owner_user_id', 'owner_name', 'impact_area', 'next_step', 'target_date', 'priority_rank'],
        'wp_fusion_qbr_evidence_snapshots' => ['qbr_id', 'snapshot', 'captured_at'],
        'wp_fusion_qbr_kpis' => ['qbr_id', 'name', 'current_value', 'target_value', 'status', 'trend', 'priority_rank'],
        'wp_fusion_qbrs' => ['company_id', 'company_group_id', 'facilitator_user_id', 'quarter', 'year', 'status', 'discussion_notes', 'held_at', 'step_progress', 'created_by'],
        'wp_gf_addon_feed' => [],
        'wp_gf_form_view' => ['form_id', 'date_created', 'ip', 'count'],
        'wp_postmeta' => ['meta_id', 'post_id', 'meta_key', 'meta_value'],
        'wp_posts' => ['ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'],
        'wp_tags' => ['id', 'name', 'description', 'category'],
        'wp_user_roles' => ['id', 'title', 'accesses', 'tag_starter'],
        'wp_xfusion_result_evaluations' => [],
    ],

    // ---- Read directly by the WordPress plugin ($wpdb) or WordPress/Gravity Forms core ----
    'wordpress' => [
        'wp_users' => ['ID', 'user_login', 'user_email', 'display_name', 'user_nicename'],
        'wp_usermeta' => ['user_id', 'meta_key', 'meta_value'],
        'wp_posts' => ['ID', 'post_title', 'post_name', 'post_type', 'post_status'],
        'wp_postmeta' => ['post_id', 'meta_key', 'meta_value'],
        'wp_gf_entry' => ['id', 'form_id', 'created_by', 'date_created'],
        'wp_gf_entry_meta' => ['entry_id', 'form_id', 'meta_key', 'meta_value'],
        'wp_gf_form_meta' => ['form_id', 'display_meta'],
        'wp_companies' => ['id', 'title', 'slug'],
        'wp_company_employees' => ['user_id', 'company_id'],
        'wp_company_groups' => ['id', 'company_id'],
        'wp_course_scoring_groups' => ['id'],
        'wp_course_scoring_group_details' => ['id'],
        'wp_xfusion_result_evaluations' => ['id', 'user_id', 'scoring_group_id', 'evaluation', 'status'],
        'wp_fusion_arps' => ['id', 'company_id'],
        'wp_fusion_arp_ai_assessments' => ['id', 'arp_id'],
        'wp_fusion_one_on_ones' => ['id', 'company_id'],
        'wp_fusion_one_on_one_conversations' => ['id', 'one_on_one_id'],
        'wp_fusion_one_on_one_ai_briefs' => ['id'],
        'wp_fusion_one_on_one_ai_syntheses' => ['id'],
    ],

    // Tables listed above that are known to be optional on a given server.
    'optional' => [],
];
