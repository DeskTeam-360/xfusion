<?php
/**
 * Shortcodes: send course scoring group answers to the XFusion-llm evaluation API,
 * and COR™ readiness summary dashboard with batch "Generate Insights".
 *
 * Usage:
 *   [send_evaluation category="Get Real"]  — display Greatest Strength / Greatest Opportunity from unified insight (read-only)
 *   [send_evaluation category="1"]  (numeric = group id)
 *   [send_evaluation category="My Group" user_id="89"]  (admin only)
 *   [xfusion_core_readiness]  — Generate Insights → POST /api/v1/evaluation/evaluate-unified (1 DB row)
 *   [xfusion_core_readiness user_id="89"]  (admin only)
 *   [xfusion_core_dimensions]
 *   [xfusion_core_dimensions user_id="89"]  (admin only)
 *   [xfusion_cor_organization_capabilities]  — unified insight narrative (read-only)
 *   [xfusion_cor_key_observation]  — unified key observation (read-only)
 *   [xfusion_insight_date_filter]
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_SEND_EVAL_NONCE_ACTION = 'xfusion_send_evaluation';

const XFUSION_COR_READINESS_NONCE_ACTION = 'xfusion_core_readiness';

/** Cooldown between Generate Insights runs (1 week). */
const XFUSION_SEND_EVAL_COOLDOWN_SECONDS = 7 * DAY_IN_SECONDS;

/**
 * @return array{
 *   on_cooldown: bool,
 *   seconds_remaining: int,
 *   available_at: string,
 *   available_at_ts: int,
 *   last_evaluated_at: string,
 *   last_record_id: int
 * }
 */
function xfusion_send_eval_cooldown_status(int $userId, int $groupId): array
{
    $empty = [
        'on_cooldown' => false,
        'seconds_remaining' => 0,
        'available_at' => '',
        'available_at_ts' => 0,
        'last_evaluated_at' => '',
        'last_record_id' => 0,
    ];

    if ($userId < 1 || $groupId < 1 || ! function_exists('xfusion_result_evaluation_latest_for_group')) {
        return $empty;
    }

    $latest = xfusion_result_evaluation_latest_for_group($userId, $groupId);
    if ($latest === null) {
        return $empty;
    }

    $evaluatedAt = trim((string) ($latest['evaluated_at'] ?? ''));
    if ($evaluatedAt === '') {
        return $empty;
    }

    $lastTs = strtotime($evaluatedAt . ' UTC');
    if ($lastTs === false) {
        return $empty;
    }

    $availableTs = $lastTs + XFUSION_SEND_EVAL_COOLDOWN_SECONDS;
    $remaining = $availableTs - time();

    return [
        'on_cooldown' => $remaining > 0,
        'seconds_remaining' => $remaining > 0 ? $remaining : 0,
        'available_at' => gmdate('Y-m-d H:i:s', $availableTs),
        'available_at_ts' => $availableTs,
        'last_evaluated_at' => $evaluatedAt,
        'last_record_id' => (int) ($latest['id'] ?? $latest['post_id'] ?? 0),
    ];
}

function xfusion_send_eval_format_cooldown_remaining(int $seconds): string
{
    if ($seconds < 1) {
        return '';
    }

    $days = (int) floor($seconds / DAY_IN_SECONDS);
    $hours = (int) floor(($seconds % DAY_IN_SECONDS) / HOUR_IN_SECONDS);
    $minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);

    if ($days > 0 && $hours > 0) {
        return sprintf(
            /* translators: 1: days, 2: hours */
            _n('%1$d day %2$d hours', '%1$d days %2$d hours', $days, 'xfusion'),
            $days,
            $hours
        );
    }

    if ($days > 0) {
        return sprintf(
            /* translators: %d: days */
            _n('%d day', '%d days', $days, 'xfusion'),
            $days
        );
    }

    if ($hours > 0 && $minutes > 0) {
        return sprintf(
            /* translators: 1: hours, 2: minutes */
            _n('%1$d hour %2$d minutes', '%1$d hours %2$d minutes', $hours, 'xfusion'),
            $hours,
            $minutes
        );
    }

    if ($hours > 0) {
        return sprintf(
            /* translators: %d: hours */
            _n('%d hour', '%d hours', $hours, 'xfusion'),
            $hours
        );
    }

    if ($minutes > 0) {
        return sprintf(
            /* translators: %d: minutes */
            _n('%d minute', '%d minutes', $minutes, 'xfusion'),
            $minutes
        );
    }

    return __('less than a minute', 'xfusion');
}

/**
 * Resolve course scoring group id from shortcode category (title or numeric id).
 */
function xfusion_send_eval_group_id_from_category(string $category): int
{
    global $wpdb;

    $category = trim($category);
    if ($category === '') {
        return 0;
    }

    if (ctype_digit($category)) {
        $id = (int) $category;
        if ($id < 1) {
            return 0;
        }
        $gtable = $wpdb->prefix . 'course_scoring_groups';
        $found = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$gtable} WHERE id = %d", $id));

        return $found ? (int) $found : 0;
    }

    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $found = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$gtable} WHERE title = %s LIMIT 1",
            $category
        )
    );

    if ($found) {
        return (int) $found;
    }

    $found = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$gtable} WHERE LOWER(title) = LOWER(%s) LIMIT 1",
            $category
        )
    );

    return $found ? (int) $found : 0;
}

/**
 * Gravity Forms field label from display_meta.
 */
function xfusion_send_eval_gf_field_label(int $form_id, int $field_id): string
{
    global $wpdb;

    if ($form_id < 1 || $field_id < 1) {
        return '';
    }

    $t = $wpdb->prefix . 'gf_form_meta';
    $raw = $wpdb->get_var(
        $wpdb->prepare("SELECT display_meta FROM {$t} WHERE form_id = %d LIMIT 1", $form_id)
    );

    if (! is_string($raw) || $raw === '') {
        return sprintf('Form %d — Field %d', $form_id, $field_id);
    }

    $decoded = json_decode($raw);
    if (! is_object($decoded) || ! isset($decoded->fields) || ! is_array($decoded->fields)) {
        return sprintf('Form %d — Field %d', $form_id, $field_id);
    }

    foreach ($decoded->fields as $field) {
        if (! is_object($field) || ! isset($field->id)) {
            continue;
        }
        if ((int) $field->id === $field_id) {
            $label = isset($field->label) ? trim((string) $field->label) : '';

            return $label !== '' ? $label : sprintf('Form %d — Field %d', $form_id, $field_id);
        }
    }

    return sprintf('Form %d — Field %d', $form_id, $field_id);
}

/**
 * Non-structural Gravity Forms fields (all question/input fields on the form).
 *
 * @return list<array{id: int, label: string, type: string}>
 */
function xfusion_send_eval_gf_question_fields_for_form(int $form_id): array
{
    global $wpdb;

    if ($form_id < 1) {
        return [];
    }

    $t = $wpdb->prefix . 'gf_form_meta';
    $raw = $wpdb->get_var(
        $wpdb->prepare("SELECT display_meta FROM {$t} WHERE form_id = %d LIMIT 1", $form_id)
    );

    if (! is_string($raw) || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw);
    if (! is_object($decoded) || ! isset($decoded->fields) || ! is_array($decoded->fields)) {
        return [];
    }

    $skipTypes = [
        'html', 'section', 'page', 'submit', 'captcha', 'honeypot', 'password',
    ];

    $out = [];
    foreach ($decoded->fields as $field) {
        if (! is_object($field) || ! isset($field->id)) {
            continue;
        }

        $id = (int) $field->id;
        if ($id < 1) {
            continue;
        }

        $type = isset($field->type) ? strtolower((string) $field->type) : '';
        if ($type === '' || in_array($type, $skipTypes, true)) {
            continue;
        }

        $label = isset($field->label) ? trim((string) $field->label) : '';
        if ($label === '') {
            $label = sprintf(__('Field %d', 'xfusion'), $id);
        }

        $out[] = [
            'id' => $id,
            'label' => $label,
            'type' => $type,
        ];
    }

    return $out;
}

/**
 * Unique form_id values from wp_course_scoring_group_details for one group.
 *
 * @return list<int>
 */
function xfusion_send_eval_form_ids_for_group(int $group_id): array
{
    global $wpdb;

    if ($group_id < 1) {
        return [];
    }

    $dtable = $wpdb->prefix . 'course_scoring_group_details';
    $rows = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT form_id FROM {$dtable} WHERE course_scoring_group_id = %d AND form_id > 0 ORDER BY form_id ASC",
            $group_id
        )
    );

    if (! is_array($rows)) {
        return [];
    }

    $ids = [];
    foreach ($rows as $formId) {
        $fid = (int) $formId;
        if ($fid > 0) {
            $ids[] = $fid;
        }
    }

    return $ids;
}

/**
 * @return array{
 *   group_title: string,
 *   question_answers: list<array{question: string, answer: string}>,
 *   created_at: string
 * }
 */
function xfusion_send_eval_collect_payload(int $group_id, int $user_id): ?array
{
    global $wpdb;

    if ($group_id < 1 || $user_id < 1) {
        return null;
    }

    if (! function_exists('xfusion_csg_latest_entry_id') || ! function_exists('xfusion_csg_entry_field_value')) {
        return null;
    }

    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $entryTable = $wpdb->prefix . 'gf_entry';

    $group = $wpdb->get_row(
        $wpdb->prepare("SELECT id, title FROM {$gtable} WHERE id = %d", $group_id),
        ARRAY_A
    );

    if ($group === null) {
        return null;
    }

    $formIds = xfusion_send_eval_form_ids_for_group($group_id);

    if ($formIds === []) {
        return null;
    }

    $questionAnswers = [];
    $latestEntryTs = 0;
    $seen = [];

    foreach ($formIds as $formId) {
        $fields = xfusion_send_eval_gf_question_fields_for_form($formId);
        if ($fields === []) {
            continue;
        }

        $entryId = xfusion_csg_latest_entry_id($formId, $user_id);

        if ($entryId > 0) {
            $entryDate = $wpdb->get_var(
                $wpdb->prepare("SELECT date_created FROM {$entryTable} WHERE id = %d", $entryId)
            );
            if (is_string($entryDate) && $entryDate !== '') {
                $ts = strtotime($entryDate);
                if ($ts !== false && $ts > $latestEntryTs) {
                    $latestEntryTs = $ts;
                }
            }
        }

        foreach ($fields as $field) {
            $fieldId = (int) ($field['id'] ?? 0);
            if ($fieldId < 1) {
                continue;
            }

            $dedupeKey = $formId . ':' . $fieldId;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $answer = '';
            if ($entryId > 0) {
                $raw = xfusion_csg_entry_field_value($entryId, $formId, $fieldId);
                $answer = $raw !== null ? trim((string) $raw) : '';
            }

            $label = (string) ($field['label'] ?? '');
            if ($label === '') {
                $label = xfusion_send_eval_gf_field_label($formId, $fieldId);
            }

            $questionAnswers[] = [
                'question' => $label,
                'answer' => $answer,
            ];
        }
    }

    $questionAnswers = xfusion_send_eval_only_with_answers($questionAnswers);

    if ($questionAnswers === []) {
        return null;
    }

    if ($latestEntryTs > 0) {
        $createdAt = gmdate('Y-m-d\TH:i:s\Z', $latestEntryTs);
    } else {
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');
    }

    return [
        'group_title' => (string) ($group['title'] ?? ''),
        'question_answers' => $questionAnswers,
        'created_at' => $createdAt,
    ];
}

/**
 * Keep only Q&A pairs with non-empty answers.
 *
 * @param list<array{question: string, answer: string}> $questionAnswers
 * @return list<array{question: string, answer: string}>
 */
function xfusion_send_eval_only_with_answers(array $questionAnswers): array
{
    $out = [];
    foreach ($questionAnswers as $qa) {
        $answer = isset($qa['answer']) ? trim((string) $qa['answer']) : '';
        if ($answer === '') {
            continue;
        }
        $out[] = [
            'question' => isset($qa['question']) ? (string) $qa['question'] : '',
            'answer' => $answer,
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $body
 * @return array{ok: bool, message: string, data?: array<string, mixed>}
 */
function xfusion_send_eval_call_api(array $body): array
{
    if (! function_exists('xfusion_llm_api_url')) {
        return ['ok' => false, 'message' => 'XFusion LLM helpers not loaded.'];
    }

    $skip = function_exists('xfusion_llm_config_skip_reason') ? xfusion_llm_config_skip_reason() : '';
    if ($skip !== '') {
        return ['ok' => false, 'message' => $skip];
    }

    $url = xfusion_llm_api_url() . '/api/v1/evaluation/evaluate';
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    $key = xfusion_llm_api_key();
    if ($key !== '') {
        $headers['Authorization'] = 'Bearer ' . $key;
    }

    $response = wp_remote_post($url, [
        'timeout' => 120,
        'headers' => $headers,
        'body' => wp_json_encode($body),
    ]);

    if (is_wp_error($response)) {
        return ['ok' => false, 'message' => $response->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw = wp_remote_retrieve_body($response);
    $decoded = json_decode($raw, true);

    if ($code < 200 || $code >= 300) {
        $detail = is_array($decoded) && isset($decoded['detail'])
            ? (is_string($decoded['detail']) ? $decoded['detail'] : wp_json_encode($decoded['detail']))
            : $raw;

        return ['ok' => false, 'message' => sprintf('API error (%d): %s', $code, (string) $detail)];
    }

    return [
        'ok' => true,
        'message' => __('Evaluation sent successfully.', 'xfusion'),
        'data' => is_array($decoded) ? $decoded : [],
    ];
}

add_action('wp_ajax_xfusion_send_evaluation', 'xfusion_send_eval_ajax_handler');

/**
 * Run evaluation for one scoring group (shared by single + batch handlers).
 *
 * @return array{
 *   ok: bool,
 *   skipped: bool,
 *   message: string,
 *   group_id: int,
 *   group_title: string,
 *   result_id: int,
 *   cooldown?: array<string, mixed>,
 *   evaluation?: array<string, mixed>
 * }
 */
function xfusion_send_eval_process_group(int $userId, int $groupId, bool $enforceCooldown = true): array
{
    global $wpdb;

    $base = [
        'ok' => false,
        'skipped' => false,
        'message' => '',
        'group_id' => $groupId,
        'group_title' => '',
        'result_id' => 0,
    ];

    if ($groupId < 1) {
        $base['skipped'] = true;
        $base['message'] = __('Invalid scoring group.', 'xfusion');

        return $base;
    }

    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $groupTitle = (string) $wpdb->get_var(
        $wpdb->prepare("SELECT title FROM {$gtable} WHERE id = %d", $groupId)
    );
    $base['group_title'] = $groupTitle;

    if ($enforceCooldown) {
        $cooldown = xfusion_send_eval_cooldown_status($userId, $groupId);
        if ($cooldown['on_cooldown']) {
            $base['skipped'] = true;
            $base['message'] = sprintf(
                /* translators: %s: remaining time */
                __('You already sent an evaluation for this group. Please wait %s.', 'xfusion'),
                xfusion_send_eval_format_cooldown_remaining($cooldown['seconds_remaining'])
            );
            $base['cooldown'] = $cooldown;

            return $base;
        }
    }

    $collected = xfusion_send_eval_collect_payload($groupId, $userId);
    if ($collected === null) {
        $base['skipped'] = true;
        $base['message'] = __('No answered questions found for this scoring group.', 'xfusion');

        return $base;
    }

    $body = [
        'user_id' => $userId,
        'created_at' => $collected['created_at'],
        'company_information' => 0,
        'question_answers' => $collected['question_answers'],
    ];

    $result = xfusion_send_eval_call_api($body);
    if (! $result['ok']) {
        $base['message'] = $result['message'];

        return $base;
    }

    $apiData = $result['data'] ?? [];
    $savedId = 0;
    if (function_exists('xfusion_result_evaluation_insert') && $apiData !== []) {
        $savedId = xfusion_result_evaluation_insert(
            $userId,
            $groupId,
            $collected['group_title'],
            $apiData,
            $body
        );
    }

    if ($savedId < 1) {
        $tableHint = function_exists('xfusion_result_evaluation_table_name')
            ? xfusion_result_evaluation_table_name()
            : 'wp_xfusion_result_evaluations';

        $base['message'] = sprintf(
            /* translators: %s: database table name */
            __('Evaluation received but failed to save to table %s. Ensure the table exists in this environment.', 'xfusion'),
            $tableHint
        );
        $base['evaluation'] = is_array($apiData) ? $apiData : [];

        return $base;
    }

    $base['ok'] = true;
    $base['message'] = $result['message'];
    $base['group_title'] = $collected['group_title'];
    $base['result_id'] = $savedId;

    return $base;
}

function xfusion_send_eval_ajax_handler(): void
{
    check_ajax_referer(XFUSION_SEND_EVAL_NONCE_ACTION, 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in.', 'xfusion')], 401);
    }

    if (function_exists('xfusion_once_popup_require_confirmed_or_error')) {
        xfusion_once_popup_require_confirmed_or_error();
    }

    $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash((string) $_POST['category'])) : '';
    $groupId = xfusion_send_eval_group_id_from_category($category);

    if ($groupId < 1) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: category from shortcode */
                __('Course scoring group not found for category: %s', 'xfusion'),
                $category
            ),
        ], 404);
    }

    $userId = (int) get_current_user_id();
    $postedUserId = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    if ($postedUserId > 0 && $postedUserId !== $userId) {
        if (! current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'xfusion')], 403);
        }
        $userId = $postedUserId;
    }

    $cooldown = xfusion_send_eval_cooldown_status($userId, $groupId);
    if ($cooldown['on_cooldown']) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: remaining time */
                __('You already sent an evaluation for this group. Please wait %s.', 'xfusion'),
                xfusion_send_eval_format_cooldown_remaining($cooldown['seconds_remaining'])
            ),
            'cooldown' => $cooldown,
        ], 429);
    }

    $processed = xfusion_send_eval_process_group($userId, $groupId, false);

    if ($processed['skipped'] || ! $processed['ok']) {
        $status = $processed['skipped'] ? 404 : 500;
        if (isset($processed['cooldown'])) {
            $status = 429;
        } elseif (! $processed['skipped'] && ! $processed['ok']) {
            $status = str_contains($processed['message'], 'API error') ? 502 : 500;
        }

        $error = ['message' => $processed['message']];
        if (isset($processed['cooldown'])) {
            $error['cooldown'] = $processed['cooldown'];
        }
        if (isset($processed['evaluation'])) {
            $error['evaluation'] = $processed['evaluation'];
        }

        wp_send_json_error($error, $status);
    }

    $cooldownAfter = xfusion_send_eval_cooldown_status($userId, $groupId);
    $latestBlock = xfusion_send_eval_build_latest_block($userId, $groupId);

    wp_send_json_success([
        'message' => $processed['message'],
        'group_id' => $groupId,
        'group_title' => $processed['group_title'],
        'evaluation' => [],
        'result_card_html' => $latestBlock['html'],
        'cooldown_notice_html' => xfusion_send_eval_cooldown_notice_html($cooldownAfter),
        'cooldown' => $cooldownAfter,
    ]);
}

/**
 * Frontend feedback block CSS (shortcode).
 */
function xfusion_send_eval_feedback_css(): string
{
    $shared = function_exists('xfusion_result_evaluation_feedback_sections_css')
        ? xfusion_result_evaluation_feedback_sections_css()
        : '';

    return $shared . <<<'CSS'
.xfusion-send-eval .xfusion-send-eval__latest{margin:0 0 1rem;}
.xfusion-send-eval .xfusion-send-eval__latest-date{display:block!important;margin:0 0 0.75rem;font-size:0.85rem;color:#6b7280;}
.xfusion-send-eval .xfusion-send-eval__latest-empty{display:block!important;margin:0;font-size:0.875rem;color:#6b7280;}
.xfusion-send-eval .xfusion-eval-feedback-rows{gap:20px;}
.xfusion-send-eval .xfusion-eval-feedback-row{margin:0 0 20px;}
.xfusion-send-eval .xfusion-eval-feedback-row:last-of-type{margin-bottom:0;}
.xfusion-send-eval .xfusion-send-eval__cooldown{margin:0.75rem 0 0;padding:0.65rem 0.85rem;font-size:0.875rem;color:#92400e;background:#fffbeb;border:1px solid #fcd34d;border-radius:0.375rem;}
.xfusion-send-eval .xfusion-send-eval__btn:disabled{opacity:0.55;cursor:not-allowed;}
.xfusion-send-eval .xfusion-send-eval__btn.is-cooldown-hidden{display:none!important;}
.xfusion-send-eval .xfusion-send-eval-ai-disclaimer{display:block!important;margin:12px 0 0;padding:0;font-size:10px;line-height:1.4;color:#9ca3af;font-style:italic;}
CSS;
}

/**
 * Evaluation card CSS for the frontend shortcode (once per page).
 */
function xfusion_send_eval_print_card_styles(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    echo '<style id="xfusion-send-eval-card-css">' . xfusion_send_eval_feedback_css() . '</style>';
}

function xfusion_send_eval_ai_disclaimer_html(): string
{
    return '<p class="xfusion-send-eval-ai-disclaimer">'
        . esc_html__(
            'AI-generated insight. Review and apply professional judgment before acting on recommendations.',
            'xfusion'
        )
        . '</p>';
}

/**
 * Shortcode-safe feedback markup (<p> only — survives wp_kses / theme filters on divs).
 *
 * @param array{strengths?: string, improvements?: string, evaluator_notes?: string}|array<string, mixed> $evaluation
 */
function xfusion_send_eval_render_feedback_html(array $evaluation, bool $withDisclaimer = false): string
{
    if (function_exists('xfusion_result_evaluation_extract_feedback')) {
        $feedback = xfusion_result_evaluation_extract_feedback($evaluation);
    } else {
        $feedback = [
            'strengths' => (string) ($evaluation['strengths'] ?? ''),
            'improvements' => (string) ($evaluation['improvements'] ?? ''),
            'evaluator_notes' => (string) ($evaluation['evaluator_notes'] ?? ''),
        ];
    }

    if (! function_exists('xfusion_result_evaluation_render_feedback_sections')) {
        return $withDisclaimer ? xfusion_send_eval_ai_disclaimer_html() : '';
    }

    $html = xfusion_result_evaluation_render_feedback_sections($feedback, true);

    if ($withDisclaimer) {
        $html .= xfusion_send_eval_ai_disclaimer_html();
    }

    return $html;
}

/**
 * Unified insight feedback: Greatest Strength + Greatest Opportunity.
 *
 * @param array{strength?: string, opportunity?: string} $feedback
 */
function xfusion_send_eval_render_unified_feedback_html(array $feedback, bool $withDisclaimer = false): string
{
    if (! function_exists('xfusion_result_evaluation_render_feedback_sections')) {
        return $withDisclaimer ? xfusion_send_eval_ai_disclaimer_html() : '';
    }

    $sections = function_exists('xfusion_result_evaluation_unified_feedback_sections')
        ? xfusion_result_evaluation_unified_feedback_sections()
        : null;

    $html = xfusion_result_evaluation_render_feedback_sections([
        'strength' => (string) ($feedback['strength'] ?? ''),
        'opportunity' => (string) ($feedback['opportunity'] ?? ''),
    ], true, $sections);

    if ($withDisclaimer) {
        $html .= xfusion_send_eval_ai_disclaimer_html();
    }

    return $html;
}

/**
 * Resolve group title for a scoring group id.
 */
function xfusion_send_eval_group_title(int $groupId): string
{
    if ($groupId < 1) {
        return '';
    }

    global $wpdb;

    $gtable = $wpdb->prefix . 'course_scoring_groups';

    return (string) $wpdb->get_var(
        $wpdb->prepare("SELECT title FROM {$gtable} WHERE id = %d", $groupId)
    );
}

/**
 * Build read-only feedback HTML from unified COR insight for one category.
 *
 * @return array{html: string, has_result: bool}
 */
function xfusion_send_eval_build_latest_block(int $userId, int $groupId, string $groupTitle = ''): array
{
    if ($userId < 1 || $groupId < 1) {
        return [
            'html' => '<p class="xfusion-send-eval__latest-empty">' . esc_html__('No insights generated yet.', 'xfusion') . '</p>',
            'has_result' => false,
        ];
    }

    if ($groupTitle === '') {
        $groupTitle = xfusion_send_eval_group_title($groupId);
    }

    $latest = function_exists('xfusion_insight_date_filter_resolve_unified')
        ? xfusion_insight_date_filter_resolve_unified($userId)
        : (function_exists('xfusion_result_evaluation_latest_unified')
            ? xfusion_result_evaluation_latest_unified($userId)
            : null);

    if ($latest === null) {
        $emptyMessage = function_exists('xfusion_insight_date_filter_is_active') && xfusion_insight_date_filter_is_active()
            ? __('No unified insights found for the selected date.', 'xfusion')
            : __('No insights generated yet.', 'xfusion');

        return [
            'html' => '<p class="xfusion-send-eval__latest-empty">' . esc_html($emptyMessage) . '</p>',
            'has_result' => false,
        ];
    }

    $feedback = function_exists('xfusion_cor_unified_performance_feedback_for_category')
        ? xfusion_cor_unified_performance_feedback_for_category($userId, $groupTitle)
        : ['strength' => '', 'opportunity' => ''];

    if (trim($feedback['strength']) === '' && trim($feedback['opportunity']) === '') {
        return [
            'html' => '<p class="xfusion-send-eval__latest-empty">' . esc_html__(
                'No feedback for this category in the latest unified insight.',
                'xfusion'
            ) . '</p>',
            'has_result' => false,
        ];
    }

    $feedbackHtml = xfusion_send_eval_render_unified_feedback_html($feedback);

    return [
        'html' => $feedbackHtml,
        'has_result' => true,
    ];
}

/**
 * Render the user's latest evaluation for a scoring group (from the DB table).
 * Always outputs a visible block (result or empty state).
 */
function xfusion_send_eval_latest_html(int $userId, int $groupId, string $groupTitle = ''): string
{
    $block = xfusion_send_eval_build_latest_block($userId, $groupId, $groupTitle);

    ob_start();
    xfusion_send_eval_print_card_styles();
    ?>
<div class="xfusion-send-eval__latest xfusion-result-eval-wrap<?php echo $block['has_result'] ? '' : ' xfusion-send-eval__latest--empty'; ?>">
    <?php echo $block['html']; ?>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * @param array{
 *   on_cooldown: bool,
 *   seconds_remaining: int,
 *   available_at: string,
 *   available_at_ts: int
 * } $cooldown
 */
function xfusion_send_eval_cooldown_notice_html(array $cooldown): string
{
    if (! $cooldown['on_cooldown']) {
        return '';
    }

    $remaining = xfusion_send_eval_format_cooldown_remaining((int) $cooldown['seconds_remaining']);

    ob_start();
    ?>
<p class="xfusion-send-eval__cooldown" data-cooldown-until="<?php echo (int) ($cooldown['available_at_ts'] ?? 0); ?>">
    <?php
    echo esc_html(sprintf(
        /* translators: %s: remaining time */
        __('Next evaluation available in %s. One evaluation per group every 7 days.', 'xfusion'),
        $remaining
    ));
    ?>
</p>
    <?php

    return (string) ob_get_clean();
}

/**
 * @param array<string, string> $atts
 */
function xfusion_send_evaluation_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'category' => '',
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'send_evaluation'
    );

    $category = trim((string) $atts['category']);
    if ($category === '') {
        return '<p class="xfusion-send-eval xfusion-send-eval--error">' . esc_html__('send_evaluation: category attribute is required.', 'xfusion') . '</p>';
    }

    if (! is_user_logged_in()) {
        return '<p class="xfusion-send-eval xfusion-send-eval--error">' . esc_html__('Please log in to view insights.', 'xfusion') . '</p>';
    }

    $groupId = xfusion_send_eval_group_id_from_category($category);
    if ($groupId < 1) {
        return '<p class="xfusion-send-eval xfusion-send-eval--error">' . esc_html(sprintf(
            __('Course scoring group not found for category: %s', 'xfusion'),
            $category
        )) . '</p>';
    }

    $groupTitle = xfusion_send_eval_group_title($groupId);

    $uid = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id']);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $uid = $attrUserId;
    }

    $wrapClass = trim('xfusion-send-eval ' . (string) $atts['class']);

    ob_start();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" data-category="<?php echo esc_attr($category); ?>" data-group-id="<?php echo (int) $groupId; ?>">
    <?php echo xfusion_send_eval_latest_html($uid, $groupId, $groupTitle); ?>
</div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('send_evaluation', 'xfusion_send_evaluation_shortcode');

// -------------------------------------------------------------------------
// COR™ readiness dashboard + batch Generate Insights (all 5 categories)
// -------------------------------------------------------------------------

/**
 * @return list<string>
 */
function xfusion_cor_readiness_categories(): array
{
    return [
        'Get Real',
        'Fill Buckets',
        'Be Intentional',
        'Foster Grit',
        'Drive Growth',
    ];
}

/**
 * Cooldown for batch "Generate Insights" — 1 week after the user's most recent
 * unified insight.
 *
 * @return array{
 *   on_cooldown: bool,
 *   seconds_remaining: int,
 *   available_at: string,
 *   available_at_ts: int,
 *   last_evaluated_at: string
 * }
 */
function xfusion_cor_readiness_batch_cooldown_status(int $userId): array
{
    if (function_exists('xfusion_cor_unified_cooldown_status')) {
        return xfusion_cor_unified_cooldown_status($userId);
    }

    $empty = [
        'on_cooldown' => false,
        'seconds_remaining' => 0,
        'available_at' => '',
        'available_at_ts' => 0,
        'last_evaluated_at' => '',
    ];

    if ($userId < 1) {
        return $empty;
    }

    $latestTs = 0;
    $latestEvaluatedAt = '';

    foreach (xfusion_cor_readiness_categories() as $title) {
        $groupId = xfusion_send_eval_group_id_from_category($title);
        if ($groupId < 1) {
            continue;
        }

        $status = xfusion_send_eval_cooldown_status($userId, $groupId);
        $evaluatedAt = trim((string) ($status['last_evaluated_at'] ?? ''));
        if ($evaluatedAt === '') {
            continue;
        }

        $ts = strtotime($evaluatedAt . ' UTC');
        if ($ts !== false && $ts > $latestTs) {
            $latestTs = $ts;
            $latestEvaluatedAt = $evaluatedAt;
        }
    }

    if ($latestTs < 1) {
        return $empty;
    }

    $availableTs = $latestTs + XFUSION_SEND_EVAL_COOLDOWN_SECONDS;
    $remaining = $availableTs - time();

    return [
        'on_cooldown' => $remaining > 0,
        'seconds_remaining' => $remaining > 0 ? $remaining : 0,
        'available_at' => gmdate('Y-m-d H:i:s', $availableTs),
        'available_at_ts' => $availableTs,
        'last_evaluated_at' => $latestEvaluatedAt,
    ];
}

/**
 * @param array{
 *   on_cooldown: bool,
 *   seconds_remaining: int,
 *   available_at: string,
 *   available_at_ts: int
 * } $cooldown
 */
function xfusion_cor_readiness_batch_cooldown_notice_html(array $cooldown): string
{
    if (! $cooldown['on_cooldown']) {
        return '';
    }

    $remaining = xfusion_send_eval_format_cooldown_remaining((int) $cooldown['seconds_remaining']);

    ob_start();
    ?>
<p class="xfusion-cor-readiness__cooldown" data-cooldown-until="<?php echo (int) ($cooldown['available_at_ts'] ?? 0); ?>">
    <?php
    echo esc_html(sprintf(
        /* translators: %s: remaining time */
        __('Generate Insights available again in %s (1 week after your last generated data).', 'xfusion'),
        $remaining
    ));
    ?>
</p>
    <?php

    return (string) ob_get_clean();
}

/**
 * All course scoring group titles from the database.
 *
 * @return list<string>
 */
function xfusion_cor_all_scoring_group_titles(): array
{
    global $wpdb;

    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $rows = $wpdb->get_col("SELECT title FROM {$gtable} WHERE title != '' ORDER BY id ASC");

    if (! is_array($rows)) {
        return [];
    }

    $titles = [];
    foreach ($rows as $row) {
        $title = trim((string) $row);
        if ($title !== '') {
            $titles[] = $title;
        }
    }

    return $titles;
}

/**
 * Fetch course scoring group scores by group title (same lookup as readiness / send_evaluation).
 *
 * @return list<array{title: string, group_id: int, average: ?float, zone_label: string, zone_color: string}>
 */
function xfusion_cor_scoring_groups_data(int $userId, array $titles): array
{
    $groups = [];

    foreach ($titles as $title) {
        $title = trim((string) $title);
        if ($title === '') {
            continue;
        }

        $groupId = xfusion_send_eval_group_id_from_category($title);

        $scorePeriod = function_exists('xfusion_insight_date_filter_score_period')
            ? xfusion_insight_date_filter_score_period()
            : null;

        $payload = ($groupId > 0 && function_exists('xfusion_csg_gauge_payload'))
            ? xfusion_csg_gauge_payload($groupId, $userId, $scorePeriod === null, $scorePeriod)
            : null;

        $average = is_array($payload) ? ($payload['average'] ?? null) : null;
        $zoneLabel = is_array($payload) ? (string) ($payload['gauge_zone_label'] ?? 'No data') : 'No data';
        $zoneColor = is_array($payload) ? (string) ($payload['gauge_zone_color'] ?? '#6b7280') : '#6b7280';
        $resolvedTitle = is_array($payload) && ! empty($payload['title'])
            ? (string) $payload['title']
            : $title;

        $groups[] = [
            'title' => $resolvedTitle,
            'group_id' => $groupId,
            'average' => $average,
            'zone_label' => $zoneLabel,
            'zone_color' => $zoneColor,
        ];
    }

    return $groups;
}

/**
 * Snapshot weighted gauge averages for every scoring group (saved with unified insights).
 *
 * @return array<string, array{group_id: int, title: string, average: float}>
 */
function xfusion_cor_collect_gauge_snapshots(int $userId): array
{
    if ($userId < 1) {
        return [];
    }

    $titles = xfusion_cor_all_scoring_group_titles();
    if ($titles === []) {
        $titles = xfusion_cor_readiness_categories();
    }

    $groups = xfusion_cor_scoring_groups_data($userId, $titles);
    $snapshots = [];

    foreach ($groups as $group) {
        $groupId = (int) ($group['group_id'] ?? 0);
        if ($groupId < 1 || $group['average'] === null) {
            continue;
        }

        $snapshots[(string) $groupId] = [
            'group_id' => $groupId,
            'title' => (string) ($group['title'] ?? ''),
            'average' => round((float) $group['average'], 2),
        ];
    }

    return $snapshots;
}

/**
 * @return array{
 *   readiness_average: ?float,
 *   readiness_label: string,
 *   readiness_color: string,
 *   primary_strength: array{title: string, average: ?float},
 *   primary_opportunity: array{title: string, average: ?float},
 *   key_observation: string,
 *   recommended_focus: string,
 *   groups: list<array{title: string, group_id: int, average: ?float, zone_label: string, zone_color: string}>
 * }
 */
function xfusion_cor_readiness_dashboard_data(int $userId): array
{
    $groups = xfusion_cor_scoring_groups_data($userId, xfusion_cor_readiness_categories());

    // COR™ Readiness Indicator = mean gauge score (0–5) across every scoring group in DB.
    $allTitles = xfusion_cor_all_scoring_group_titles();
    $allGroups = $allTitles !== []
        ? xfusion_cor_scoring_groups_data($userId, $allTitles)
        : $groups;

    $readinessScores = [];
    foreach ($allGroups as $group) {
        if ($group['average'] !== null) {
            $readinessScores[] = (float) $group['average'];
        }
    }

    $readinessAverage = $readinessScores !== []
        ? round(array_sum($readinessScores) / count($readinessScores), 2)
        : null;

    $scored = [];
    foreach ($groups as $group) {
        if ($group['average'] !== null) {
            $scored[] = [
                'title' => $group['title'],
                'average' => (float) $group['average'],
                'group_id' => $group['group_id'],
            ];
        }
    }

    $readinessZone = function_exists('xfusion_csg_gauge_zone_meta')
        ? xfusion_csg_gauge_zone_meta($readinessAverage)
        : ['label' => 'No data', 'color' => '#6b7280'];

    usort($scored, static function (array $a, array $b): int {
        return $b['average'] <=> $a['average'];
    });

    $primaryStrength = [
        'title' => $scored[0]['title'] ?? '—',
        'average' => isset($scored[0]) ? (float) $scored[0]['average'] : null,
    ];

    $primaryOpportunity = [
        'title' => $scored !== [] ? (string) $scored[count($scored) - 1]['title'] : '—',
        'average' => $scored !== [] ? (float) $scored[count($scored) - 1]['average'] : null,
    ];

    $keyObservation = __(
        'You demonstrate strong resilience and persistence when facing challenges. However, your lower score in Fill Buckets suggests limited awareness of the factors that restore and sustain your energy. This combination often leads to sustained effort but can reduce long-term effectiveness if recovery and energy management are neglected.',
        'xfusion'
    );

    if (function_exists('xfusion_cor_unified_key_observation_for_user')) {
        $fromAi = xfusion_cor_unified_key_observation_for_user($userId);
        if ($fromAi !== '') {
            $keyObservation = $fromAi;
        }
    }

    $recommendedFocus = $primaryOpportunity['title'] !== '—'
        ? $primaryOpportunity['title']
        : __('Fill Buckets', 'xfusion');
    if (function_exists('xfusion_cor_unified_recommended_focus_area_for_user')) {
        $fromAiFocus = xfusion_cor_unified_recommended_focus_area_for_user($userId);
        if ($fromAiFocus !== '') {
            $recommendedFocus = $fromAiFocus;
        }
    }

    return [
        'readiness_average' => $readinessAverage,
        'readiness_label' => (string) $readinessZone['label'],
        'readiness_color' => (string) $readinessZone['color'],
        'primary_strength' => $primaryStrength,
        'primary_opportunity' => $primaryOpportunity,
        'key_observation' => $keyObservation,
        'recommended_focus' => $recommendedFocus,
        'groups' => $groups,
    ];
}

function xfusion_cor_readiness_format_score(?float $score): string
{
    if ($score === null) {
        return '—';
    }

    return number_format($score, 2, '.', '');
}

function xfusion_cor_readiness_dashboard_css(): string
{
    return <<<'CSS'
.xfusion-cor-readiness{box-sizing:border-box;max-width:960px;margin:0;padding:0px;background:#fff;font-family:inherit;line-height:1.5;color:#1e3a5f;}
.xfusion-cor-readiness__metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;margin:0 0 28px;}
.xfusion-cor-readiness__metric{position:relative;padding:0 24px;text-align:center;}
.xfusion-cor-readiness__metric:not(:last-child)::after{content:"";position:absolute;top:8%;right:0;width:1px;height:84%;background:#d1d5db;}
.xfusion-cor-readiness__metric:first-child{padding-left:0;}
.xfusion-cor-readiness__metric:last-child{padding-right:0;}
.xfusion-cor-readiness__metric-label{margin:0 0 10px;font-size:22px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1e3a5f;line-height:1.35;}
.xfusion-cor-readiness__metric-value{margin:0;font-size:42px;font-weight:700;line-height:1;color:#1e3a5f;}
.xfusion-cor-readiness__metric-status{margin:8px 0 0;font-size:22px;font-weight:600;line-height:1.2;}
.xfusion-cor-readiness__metric-name{margin:0 0 6px;font-size:30px;font-weight:600;line-height:1.25;}
.xfusion-cor-readiness__metric-name--strength{color:#e8913a;}
.xfusion-cor-readiness__metric-name--opportunity{color:#dc2626;}
.xfusion-cor-readiness__metric-subscore{margin:0;font-size:22px;font-weight:700;color:#1e3a5f;line-height:1.2;}
.xfusion-cor-readiness__observation{margin:0 0 24px;}
.xfusion-cor-readiness__observation-title{margin:0 0 12px;font-size:22px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1e3a5f;}
.xfusion-cor-readiness__observation-text{margin:0;font-size:20px;line-height:1.65;color:#1e3a5f;}
.xfusion-cor-readiness__footer{display:flex;align-items:flex-end;justify-content:flex-end;gap:20px;flex-wrap:wrap;margin-top:4px;}
.xfusion-cor-readiness__actions{display:flex;flex-direction:column;align-items:flex-end;}
.xfusion-cor-readiness__btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:14px 28px;border:none;border-radius:999px;background:linear-gradient(135deg,#3d9a50 0%,#2f7d3e 100%);color:#fff;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;box-shadow:0 4px 14px rgba(47,125,62,.28);transition:opacity .15s ease,transform .15s ease;}
.xfusion-cor-readiness__btn:hover{opacity:.94;transform:translateY(-1px);}
.xfusion-cor-readiness__btn:disabled{opacity:.55;cursor:not-allowed;transform:none;}
.xfusion-cor-readiness__btn-icon{width:18px;height:18px;flex-shrink:0;}
.xfusion-cor-readiness__cooldown{margin:10px 0 0;font-size:13px;line-height:1.45;color:#92400e;text-align:center;}
.xfusion-cor-readiness__status{margin:16px 0 0;font-size:14px;line-height:1.5;}
.xfusion-cor-readiness__status--error{color:#b91c1c;}
.xfusion-cor-readiness__status--success{color:#166534;}
@media (max-width:860px){
.xfusion-cor-readiness{padding:20px 18px 24px;}
.xfusion-cor-readiness__metrics{grid-template-columns:1fr;row-gap:24px;}
.xfusion-cor-readiness__metric::after{display:none;}
.xfusion-cor-readiness__metric{padding:0;}
.xfusion-cor-readiness__metric-value{font-size:34px;}
.xfusion-cor-readiness__metric-name{font-size:30px;}
.xfusion-cor-readiness__metric-subscore,.xfusion-cor-readiness__metric-status{font-size:18px;}
.xfusion-cor-readiness__footer{flex-direction:column;align-items:stretch;}
.xfusion-cor-readiness__actions{margin-left:0;}
.xfusion-cor-readiness__btn{width:100%;}
}
CSS;
}

function xfusion_cor_readiness_print_styles(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    echo '<style id="xfusion-cor-readiness-css">' . xfusion_cor_readiness_dashboard_css() . '</style>';
}

/**
 * @param array<string, string> $atts
 */
function xfusion_cor_readiness_dashboard_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'xfusion_core_readiness'
    );

    if (! is_user_logged_in()) {
        return '<p class="xfusion-cor-readiness xfusion-cor-readiness--error">' . esc_html__('Please log in to view your readiness dashboard.', 'xfusion') . '</p>';
    }

    if (! function_exists('xfusion_csg_gauge_payload')) {
        return '<p class="xfusion-cor-readiness xfusion-cor-readiness--error">' . esc_html__('Required scoring helpers are not loaded.', 'xfusion') . '</p>';
    }

    $userId = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id']);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $userId = $attrUserId;
    }

    $data = xfusion_cor_readiness_dashboard_data($userId);
    $instanceId = 'xfusion-cor-readiness-' . wp_unique_id();
    $wrapClass = trim('xfusion-cor-readiness ' . (string) $atts['class']);
    $ajaxUrl = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce(XFUSION_COR_READINESS_NONCE_ACTION);
    $btnLabel = esc_html__('Generate Insights', 'xfusion');
    $sendingLabel = esc_attr__('Generating…', 'xfusion');
    $historicalView = function_exists('xfusion_insight_date_filter_is_active') && xfusion_insight_date_filter_is_active();
    $sandboxMode = function_exists('xfusion_llm_insight_cooldown_enabled') && ! xfusion_llm_insight_cooldown_enabled();
    $cooldown = xfusion_cor_readiness_batch_cooldown_status($userId);
    $onCooldown = ! $sandboxMode && $cooldown['on_cooldown'];
    $btnDisabled = $historicalView || $onCooldown;
    $cooldownHtml = ($historicalView || $sandboxMode) ? '' : xfusion_cor_readiness_batch_cooldown_notice_html($cooldown);
    $aiNotifyGate = function_exists('xfusion_once_popup_gate_for_shortcode')
        ? xfusion_once_popup_gate_for_shortcode($instanceId)
        : ['show' => false, 'element_id' => '', 'markup' => ''];
    if ($historicalView) {
        $aiNotifyGate['show'] = false;
    }

    ob_start();
    xfusion_cor_readiness_print_styles();
    if (function_exists('xfusion_insight_date_filter_print_styles')) {
        xfusion_insight_date_filter_print_styles();
    }
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" id="<?php echo esc_attr($instanceId); ?>" data-user-id="<?php echo (int) $userId; ?>" data-cooldown-until="<?php echo $sandboxMode ? 0 : (int) ($cooldown['available_at_ts'] ?? 0); ?>"<?php echo $sandboxMode ? ' data-sandbox-mode="1"' : ''; ?>>
    <?php
    if ($historicalView && function_exists('xfusion_insight_date_filter_notice_html')) {
        echo xfusion_insight_date_filter_notice_html();
    }
    ?>
    <div class="xfusion-cor-readiness__metrics" role="group" aria-label="<?php esc_attr_e('Readiness summary', 'xfusion'); ?>">
        <div class="xfusion-cor-readiness__metric">
            <p class="xfusion-cor-readiness__metric-label"><?php esc_html_e('COR™ Readiness Indicator', 'xfusion'); ?></p>
            <p class="xfusion-cor-readiness__metric-value"><?php echo esc_html(xfusion_cor_readiness_format_score($data['readiness_average'])); ?></p>
            <p class="xfusion-cor-readiness__metric-status" style="color:<?php echo esc_attr($data['readiness_color']); ?>">
                <?php echo esc_html($data['readiness_label']); ?>
            </p>
        </div>

        <div class="xfusion-cor-readiness__metric">
            <p class="xfusion-cor-readiness__metric-label"><?php esc_html_e('Primary Strength', 'xfusion'); ?></p>
            <p class="xfusion-cor-readiness__metric-name xfusion-cor-readiness__metric-name--strength"><?php echo esc_html($data['primary_strength']['title']); ?></p>
            <p class="xfusion-cor-readiness__metric-subscore"><?php echo esc_html(xfusion_cor_readiness_format_score($data['primary_strength']['average'])); ?></p>
        </div>

        <div class="xfusion-cor-readiness__metric">
            <p class="xfusion-cor-readiness__metric-label"><?php esc_html_e('Primary Opportunity', 'xfusion'); ?></p>
            <p class="xfusion-cor-readiness__metric-name xfusion-cor-readiness__metric-name--opportunity"><?php echo esc_html($data['primary_opportunity']['title']); ?></p>
            <p class="xfusion-cor-readiness__metric-subscore"><?php echo esc_html(xfusion_cor_readiness_format_score($data['primary_opportunity']['average'])); ?></p>
        </div>
    </div>

    <div class="xfusion-cor-readiness__observation">
        <p class="xfusion-cor-readiness__observation-title"><?php esc_html_e('Overall Insight', 'xfusion'); ?></p>
        <p class="xfusion-cor-readiness__observation-text"><?php echo esc_html($data['key_observation']); ?></p>
    </div>

    <div class="xfusion-cor-readiness__observation">
        <p class="xfusion-cor-readiness__observation-title"><?php esc_html_e('Recommended Focus Area', 'xfusion'); ?></p>
        <p class="xfusion-cor-readiness__observation-text"><?php echo esc_html($data['recommended_focus']); ?></p>
    </div>

    <div class="xfusion-cor-readiness__footer">
        <div class="xfusion-cor-readiness__actions">
            <button type="button" class="xfusion-cor-readiness__btn"<?php echo $btnDisabled ? ' disabled' : ''; ?>>
                <svg class="xfusion-cor-readiness__btn-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2l1.2 4.2L17 7l-3.8 1.8L12 13l-1.2-4.2L7 7l3.8-1.8L12 2z" fill="currentColor"/>
                    <path d="M19 11l.8 2.8L22.5 14l-2.7 1.2L19 18l-.8-2.8L15.5 14l2.7-1.2L19 11z" fill="currentColor"/>
                    <path d="M5 13l.6 2.1L7.5 16l-2.1.9L5 19l-.6-2.1L2.5 16l2.1-.9L5 13z" fill="currentColor"/>
                </svg>
                <span class="xfusion-cor-readiness__btn-label"><?php echo $btnLabel; ?></span>
            </button>
            <?php echo $cooldownHtml; ?>
        </div>
    </div>

    <div class="xfusion-cor-readiness__status" style="display:none;" role="status" aria-live="polite"></div>
</div>
<?php echo $aiNotifyGate['markup']; ?>
<script>
<?php
    $corCoreJs = <<<'CORJS'
    var root = document.getElementById(INSTANCE_ID);
    if (!root) return;
    var btn = root.querySelector('.xfusion-cor-readiness__btn');
    var btnLabelEl = root.querySelector('.xfusion-cor-readiness__btn-label');
    var statusEl = root.querySelector('.xfusion-cor-readiness__status');
    if (!btn || !btnLabelEl || !statusEl) return;

    var defaultBtnLabel = BTN_LABEL;
    var historicalView = HISTORICAL_VIEW;
    var sandboxMode = root.getAttribute('data-sandbox-mode') === '1';
    var cooldownEl = root.querySelector('.xfusion-cor-readiness__cooldown');

    function setButtonDisabled(disabled) {
        btn.disabled = disabled || historicalView;
    }

    function applyCooldown(untilTs) {
        if (sandboxMode) {
            setButtonDisabled(false);
            return false;
        }
        var now = Math.floor(Date.now() / 1000);
        var remaining = untilTs - now;
        if (remaining <= 0) {
            setButtonDisabled(false);
            if (cooldownEl) cooldownEl.style.display = 'none';
            root.setAttribute('data-cooldown-until', '0');
            return false;
        }
        setButtonDisabled(true);
        root.setAttribute('data-cooldown-until', String(untilTs));
        return true;
    }

    function tickCooldown() {
        if (sandboxMode) return;
        var until = parseInt(root.getAttribute('data-cooldown-until') || '0', 10);
        if (!until) return;
        if (!applyCooldown(until)) {
            cooldownEl = root.querySelector('.xfusion-cor-readiness__cooldown');
            if (cooldownEl) cooldownEl.style.display = 'none';
        }
    }

    tickCooldown();
    setInterval(tickCooldown, 30000);

    function showStatus(msg, isError) {
        statusEl.style.display = 'block';
        statusEl.className = 'xfusion-cor-readiness__status' + (isError ? ' xfusion-cor-readiness__status--error' : ' xfusion-cor-readiness__status--success');
        statusEl.textContent = msg;
    }

    function runEvaluation() {
        if (btn.disabled) return;

        setButtonDisabled(true);
        btnLabelEl.textContent = SENDING_LABEL;
        statusEl.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'xfusion_core_readiness_generate_all');
        fd.append('nonce', EVAL_NONCE);
        fd.append('user_id', root.getAttribute('data-user-id') || '0');

        fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.j || !res.j.success) {
                    var err = (res.j && res.j.data && res.j.data.message) ? res.j.data.message : 'Request failed.';
                    if (res.j && res.j.data && (res.j.data.require_popup || res.j.data.ai_notify_required) && typeof window.xfusionOpenAiNotifyGate === 'function') {
                        setButtonDisabled(false);
                        btnLabelEl.textContent = defaultBtnLabel;
                        statusEl.style.display = 'none';
                        window.xfusionOpenAiNotifyGate(runEvaluation);
                        return;
                    }
                    if (res.j && res.j.data && res.j.data.cooldown && res.j.data.cooldown.available_at_ts) {
                        applyCooldown(res.j.data.cooldown.available_at_ts);
                        if (res.j.data.cooldown_notice_html) {
                            if (cooldownEl) {
                                cooldownEl.outerHTML = res.j.data.cooldown_notice_html;
                            } else {
                                var temp = document.createElement('div');
                                temp.innerHTML = res.j.data.cooldown_notice_html.trim();
                                var notice = temp.firstElementChild;
                                if (notice) {
                                    btn.insertAdjacentElement('afterend', notice);
                                }
                            }
                            cooldownEl = root.querySelector('.xfusion-cor-readiness__cooldown');
                            if (cooldownEl) cooldownEl.style.display = 'block';
                        }
                    } else {
                        setButtonDisabled(false);
                    }
                    btnLabelEl.textContent = defaultBtnLabel;
                    showStatus(err, true);
                    return;
                }

                var data = res.j.data || {};
                showStatus(data.message || 'Insights generated.', false);

                if (data.reload) {
                    window.setTimeout(function () { window.location.reload(); }, 1200);
                }
            })
            .catch(function () {
                setButtonDisabled(false);
                btnLabelEl.textContent = defaultBtnLabel;
                showStatus('Network error. Please try again.', true);
            });
    }

    CLICK_BINDING
CORJS;
    $corClickBinding = ! empty($aiNotifyGate['show'])
        ? "btn.addEventListener('click', onGenerateClick(runEvaluation));"
        : "btn.addEventListener('click', runEvaluation);";
    $corCoreJs = str_replace(
        ['INSTANCE_ID', 'BTN_LABEL', 'SENDING_LABEL', 'EVAL_NONCE', 'AJAX_URL', 'HISTORICAL_VIEW', 'CLICK_BINDING'],
        [
            wp_json_encode($instanceId),
            wp_json_encode($btnLabel),
            wp_json_encode(trim($sendingLabel)),
            wp_json_encode($nonce),
            wp_json_encode($ajaxUrl),
            $historicalView ? 'true' : 'false',
            $corClickBinding,
        ],
        $corCoreJs
    );
    echo function_exists('xfusion_once_popup_gate_script')
        ? xfusion_once_popup_gate_script($aiNotifyGate, $corCoreJs)
        : '(function () { ' . $corCoreJs . ' })();';
    ?>
</script>
    <?php

    return (string) ob_get_clean();
}

add_action('wp_ajax_xfusion_core_readiness_generate_all', 'xfusion_cor_readiness_batch_ajax_handler');

function xfusion_cor_readiness_batch_ajax_handler(): void
{
    check_ajax_referer(XFUSION_COR_READINESS_NONCE_ACTION, 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in.', 'xfusion')], 401);
    }

    if (function_exists('xfusion_once_popup_require_confirmed_or_error')) {
        xfusion_once_popup_require_confirmed_or_error();
    }

    $userId = (int) get_current_user_id();
    $postedUserId = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    if ($postedUserId > 0 && $postedUserId !== $userId) {
        if (! current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'xfusion')], 403);
        }
        $userId = $postedUserId;
    }

    $batchCooldown = xfusion_cor_readiness_batch_cooldown_status($userId);
    if ($batchCooldown['on_cooldown']
        && (! function_exists('xfusion_llm_insight_cooldown_enabled') || xfusion_llm_insight_cooldown_enabled())) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: remaining time */
                __('Generate Insights is available again in %s.', 'xfusion'),
                xfusion_send_eval_format_cooldown_remaining((int) $batchCooldown['seconds_remaining'])
            ),
            'cooldown' => $batchCooldown,
            'cooldown_notice_html' => xfusion_cor_readiness_batch_cooldown_notice_html($batchCooldown),
        ], 429);
    }

    if (! function_exists('xfusion_cor_unified_process')) {
        wp_send_json_error([
            'message' => __('Unified insights module is not loaded. Deploy cor-unified-insights.php.', 'xfusion'),
        ], 500);
    }

    $processed = xfusion_cor_unified_process($userId, false);

    if (! $processed['ok']) {
        $status = $processed['skipped'] ? 429 : 502;
        if (isset($processed['cooldown'])) {
            $status = 429;
        }

        $error = [
            'message' => $processed['message'] ?: __('No insights could be generated.', 'xfusion'),
            'unified' => true,
        ];

        if (isset($processed['cooldown'])) {
            $error['cooldown'] = $processed['cooldown'];
            $error['cooldown_notice_html'] = xfusion_cor_readiness_batch_cooldown_notice_html($processed['cooldown']);
        }

        wp_send_json_error($error, $status);
    }

    wp_send_json_success([
        'message' => $processed['message'],
        'unified' => true,
        'result_id' => (int) ($processed['result_id'] ?? 0),
        'reload' => true,
    ]);
}

add_shortcode('xfusion_core_readiness', 'xfusion_cor_readiness_dashboard_shortcode');

/**
 * COR™ dimension pillars — course scoring group titles (must match wp_course_scoring_groups.title).
 *
 * @return list<array{key: string, title: string, icon: string, bar_color: string}>
 */
function xfusion_cor_dimensions_categories(): array
{
    $iconBase = content_url('uploads/2026/06');

    return [
        [
            'key' => 'alignment',
            'title' => 'Alignment',
            'icon' => $iconBase . '/icon-profile-blue.png',
            'bar_color' => '#16a34a',
        ],
        [
            'key' => 'accountability',
            'title' => 'Accountability',
            'icon' => $iconBase . '/icon-shield.png',
            'bar_color' => '#e8913a',
        ],
        [
            'key' => 'communication',
            'title' => 'Communication',
            'icon' => $iconBase . '/icon-chat.png',
            'bar_color' => '#3b82f6',
        ],
        [
            'key' => 'leadership',
            'title' => 'Leadership',
            'icon' => $iconBase . '/icon-group-of-people.png',
            'bar_color' => '#16a34a',
        ],
        [
            'key' => 'execution',
            'title' => 'Execution',
            'icon' => $iconBase . '/icon-gear.png',
            'bar_color' => '#1e3a5f',
        ],
    ];
}

/**
 * @return list<array{key: string, title: string, icon: string, bar_color: string, group_id: int, average: ?float, bar_percent: float, zone_label: string, zone_color: string}>
 */
function xfusion_cor_dimensions_data(int $userId): array
{
    $categories = xfusion_cor_dimensions_categories();
    $titles = array_column($categories, 'title');
    $groups = xfusion_cor_scoring_groups_data($userId, $titles);
    $groupsByTitle = [];

    foreach ($groups as $group) {
        $groupsByTitle[strtolower($group['title'])] = $group;
    }

    $items = [];
    $gaugeMax = defined('XFUSION_CSG_GAUGE_MAX') ? (float) XFUSION_CSG_GAUGE_MAX : 5.0;

    foreach ($categories as $category) {
        $lookupKey = strtolower($category['title']);
        $group = $groupsByTitle[$lookupKey] ?? [
            'title' => $category['title'],
            'group_id' => 0,
            'average' => null,
            'zone_label' => 'No data',
            'zone_color' => '#6b7280',
        ];

        $average = $group['average'];
        $barPercent = 0.0;
        if ($average !== null && $gaugeMax > 0) {
            $barPercent = min(100.0, max(0.0, round(((float) $average / $gaugeMax) * 100, 1)));
        }

        $items[] = array_merge($category, [
            'title' => (string) $group['title'],
            'group_id' => (int) $group['group_id'],
            'average' => $average,
            'bar_percent' => $barPercent,
            'zone_label' => (string) $group['zone_label'],
            'zone_color' => (string) $group['zone_color'],
        ]);
    }

    return $items;
}

function xfusion_cor_dimensions_css(): string
{
    return <<<'CSS'
.xfusion-cor-dimensions{box-sizing:border-box;max-width:1200px;margin:0 auto;padding:0px;background:#fff;font-family:inherit;line-height:1.5;color:#1e3a5f;}
.xfusion-cor-dimensions__grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0;margin:0;}
.xfusion-cor-dimensions__item{position:relative;display:flex;flex-direction:column;align-items:stretch;padding:0 16px;}
.xfusion-cor-dimensions__item:not(:last-child)::after{content:"";position:absolute;top:6%;right:0;width:1px;height:88%;background:#d1d5db;}
.xfusion-cor-dimensions__item:first-child{padding-left:0;}
.xfusion-cor-dimensions__item:last-child{padding-right:0;}
.xfusion-cor-dimensions__head{display:flex;align-items:flex-start;gap:12px;margin:0 0 12px;}
.xfusion-cor-dimensions__icon{flex-shrink:0;width:40px;height:40px;object-fit:contain;display:block;margin:0;}
.xfusion-cor-dimensions__text{flex:1;min-width:0;text-align:left;}
.xfusion-cor-dimensions__label{margin:0 0 6px;font-size:15px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1e3a5f;line-height:1.3;}
.xfusion-cor-dimensions__score{margin:0;font-size:20px;font-weight:600;line-height:1.2;color:#1e3a5f;}
.xfusion-cor-dimensions__bar{width:100%;height:10px;border-radius:999px;background:#e5e7eb;overflow:hidden;}
.xfusion-cor-dimensions__bar-fill{display:block;height:100%;border-radius:999px;transition:width .3s ease;}
@media (max-width:1024px){
.xfusion-cor-dimensions{padding:24px 20px;}
.xfusion-cor-dimensions__grid{grid-template-columns:repeat(3,minmax(0,1fr));row-gap:28px;}
.xfusion-cor-dimensions__item:nth-child(3n)::after{display:none;}
.xfusion-cor-dimensions__item{padding:0 12px;}
}
@media (max-width:640px){
.xfusion-cor-dimensions{padding:20px 16px;}
.xfusion-cor-dimensions__grid{grid-template-columns:1fr;row-gap:24px;}
.xfusion-cor-dimensions__item::after{display:none;}
.xfusion-cor-dimensions__item{padding:0;}
.xfusion-cor-dimensions__icon{width:64px;height:64px;}
}
CSS;
}

function xfusion_cor_dimensions_print_styles(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    echo '<style id="xfusion-cor-dimensions-css">' . xfusion_cor_dimensions_css() . '</style>';
}

/**
 * @param array<string, string> $atts
 */
function xfusion_cor_dimensions_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'xfusion_core_dimensions'
    );

    if (! is_user_logged_in()) {
        return '<p class="xfusion-cor-dimensions xfusion-cor-dimensions--error">' . esc_html__('Please log in to view your dimension scores.', 'xfusion') . '</p>';
    }

    if (! function_exists('xfusion_csg_gauge_payload')) {
        return '<p class="xfusion-cor-dimensions xfusion-cor-dimensions--error">' . esc_html__('Required scoring helpers are not loaded.', 'xfusion') . '</p>';
    }

    $userId = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id']);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $userId = $attrUserId;
    }

    $items = xfusion_cor_dimensions_data($userId);
    $wrapClass = trim('xfusion-cor-dimensions ' . (string) $atts['class']);
    $gaugeMax = defined('XFUSION_CSG_GAUGE_MAX') ? (float) XFUSION_CSG_GAUGE_MAX : 5.0;

    ob_start();
    xfusion_cor_dimensions_print_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" role="region" aria-label="<?php esc_attr_e('COR dimension scores', 'xfusion'); ?>">
    <div class="xfusion-cor-dimensions__grid" role="group" aria-label="<?php esc_attr_e('Alignment, accountability, communication, leadership, and execution', 'xfusion'); ?>">
        <?php foreach ($items as $item) : ?>
            <div class="xfusion-cor-dimensions__item xfusion-cor-dimensions__item--<?php echo esc_attr($item['key']); ?>">
                <div class="xfusion-cor-dimensions__head">
                    <img
                        class="xfusion-cor-dimensions__icon"
                        src="<?php echo esc_url($item['icon']); ?>"
                        alt=""
                        width="72"
                        height="72"
                        decoding="async"
                    />
                    <div class="xfusion-cor-dimensions__text">
                        <p class="xfusion-cor-dimensions__label"><?php echo esc_html($item['title']); ?></p>
                        <p class="xfusion-cor-dimensions__score"><?php echo esc_html(xfusion_cor_readiness_format_score($item['average'])); ?></p>
                    </div>
                </div>
                <div
                    class="xfusion-cor-dimensions__bar"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="<?php echo esc_attr((string) $gaugeMax); ?>"
                    aria-valuenow="<?php echo esc_attr($item['average'] !== null ? (string) $item['average'] : '0'); ?>"
                    aria-label="<?php echo esc_attr($item['title']); ?>"
                >
                    <span
                        class="xfusion-cor-dimensions__bar-fill"
                        style="width:<?php echo esc_attr((string) $item['bar_percent']); ?>%;background-color:<?php echo esc_attr($item['bar_color']); ?>"
                    ></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('xfusion_core_dimensions', 'xfusion_cor_dimensions_shortcode');

// -------------------------------------------------------------------------
// Unified insight snippets (read-only text blocks)
// -------------------------------------------------------------------------

/**
 * Resolve target user for COR insight shortcodes.
 */
function xfusion_cor_insight_shortcode_user_id(array $atts): int
{
    if (! is_user_logged_in()) {
        return 0;
    }

    $userId = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id'] ?? 0);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $userId = $attrUserId;
    }

    return $userId;
}

function xfusion_cor_insight_text_css(): string
{
    return <<<'CSS'
.xfusion-cor-insight{box-sizing:border-box;margin:0;padding:0;font-family:inherit;line-height:1.65;color:#1e3a5f;}
.xfusion-cor-insight__text{margin:0;font-size:18px;line-height:1.65;color:#1e3a5f;white-space:pre-wrap;word-break:break-word;}
.xfusion-cor-insight__empty{margin:0;font-size:15px;color:#6b7280;font-style:italic;}
CSS;
}

function xfusion_cor_insight_print_styles(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;

    echo '<style id="xfusion-cor-insight-css">' . xfusion_cor_insight_text_css() . '</style>';
}

/**
 * @param array<string, string> $atts
 */
function xfusion_cor_organization_capabilities_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'xfusion_cor_organization_capabilities'
    );

    if (! is_user_logged_in()) {
        return '<p class="xfusion-cor-insight xfusion-cor-insight--error">' . esc_html__('Please log in to view insights.', 'xfusion') . '</p>';
    }

    $userId = xfusion_cor_insight_shortcode_user_id($atts);
    $text = function_exists('xfusion_cor_unified_organization_capabilities_for_user')
        ? xfusion_cor_unified_organization_capabilities_for_user($userId)
        : '';

    $wrapClass = trim('xfusion-cor-insight xfusion-cor-insight--capabilities ' . (string) $atts['class']);
    $emptyMessage = function_exists('xfusion_insight_date_filter_is_active') && xfusion_insight_date_filter_is_active()
        ? __('No COR insight for the selected date.', 'xfusion')
        : __('No COR insight generated yet.', 'xfusion');

    ob_start();
    xfusion_cor_insight_print_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>">
    <?php if ($text !== '') : ?>
        <p class="xfusion-cor-insight__text"><?php echo esc_html($text); ?></p>
    <?php else : ?>
        <p class="xfusion-cor-insight__empty"><?php echo esc_html($emptyMessage); ?></p>
    <?php endif; ?>
</div>
    <?php

    return (string) ob_get_clean();
}

/**
 * @param array<string, string> $atts
 */
function xfusion_cor_key_observation_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'user_id' => '0',
            'class' => '',
        ],
        $atts,
        'xfusion_cor_key_observation'
    );

    if (! is_user_logged_in()) {
        return '<p class="xfusion-cor-insight xfusion-cor-insight--error">' . esc_html__('Please log in to view insights.', 'xfusion') . '</p>';
    }

    $userId = xfusion_cor_insight_shortcode_user_id($atts);
    $text = function_exists('xfusion_cor_unified_key_observation_for_user')
        ? xfusion_cor_unified_key_observation_for_user($userId)
        : '';

    $wrapClass = trim('xfusion-cor-insight xfusion-cor-insight--key-observation ' . (string) $atts['class']);
    $emptyMessage = function_exists('xfusion_insight_date_filter_is_active') && xfusion_insight_date_filter_is_active()
        ? __('No overall insight for the selected date.', 'xfusion')
        : __('No overall insight generated yet.', 'xfusion');

    ob_start();
    xfusion_cor_insight_print_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>">
    <?php if ($text !== '') : ?>
        <p class="xfusion-cor-insight__text"><?php echo esc_html($text); ?></p>
    <?php else : ?>
        <p class="xfusion-cor-insight__empty"><?php echo esc_html($emptyMessage); ?></p>
    <?php endif; ?>
</div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('xfusion_cor_organization_capabilities', 'xfusion_cor_organization_capabilities_shortcode');
add_shortcode('xfusion_cor_key_observation', 'xfusion_cor_key_observation_shortcode');
