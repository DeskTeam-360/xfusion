<?php
/**
 * Shortcode: send course scoring group answers to the XFusion-llm evaluation API.
 *
 * Usage:
 *   [send_evaluation category="Customer Service"]
 *   [send_evaluation category="1"]  (numeric = group id)
 *   [send_evaluation category="My Group" user_id="89"]  (admin only)
 *
 * `category` must match `title` in wp_course_scoring_groups (or the group id).
 * form_id comes from wp_course_scoring_group_details; all GF question fields per form are collected.
 * Only Q&A pairs with non-empty answers are sent to the API.
 * Results are stored in wp_xfusion_result_evaluations (not wp_posts).
 * 24-hour cooldown per user + scoring group (same shortcode placement).
 * company_information is sent as 0 (empty) until wired up later.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_SEND_EVAL_NONCE_ACTION = 'xfusion_send_evaluation';

/** Cooldown between evaluations for the same user + scoring group (24 hours). */
const XFUSION_SEND_EVAL_COOLDOWN_SECONDS = DAY_IN_SECONDS;

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

    $hours = (int) floor($seconds / HOUR_IN_SECONDS);
    $minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);

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

function xfusion_send_eval_ajax_handler(): void
{
    check_ajax_referer(XFUSION_SEND_EVAL_NONCE_ACTION, 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => __('You must be logged in.', 'xfusion')], 401);
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

    $collected = xfusion_send_eval_collect_payload($groupId, $userId);
    if ($collected === null) {
        wp_send_json_error([
            'message' => __('No answered questions found for this scoring group.', 'xfusion'),
        ], 404);
    }

    $answeredOnly = $collected['question_answers'];

    $body = [
        'user_id' => $userId,
        'created_at' => $collected['created_at'],
        'company_information' => 0,
        'question_answers' => $answeredOnly,
    ];

    $result = xfusion_send_eval_call_api($body);
    if (! $result['ok']) {
        wp_send_json_error(['message' => $result['message']], 502);
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

        wp_send_json_error([
            'message' => sprintf(
                /* translators: %s: database table name */
                __('Evaluation received but failed to save to table %s. Ensure the table exists in this environment.', 'xfusion'),
                $tableHint
            ),
            'evaluation' => $apiData,
        ], 500);
    }

    $cooldownAfter = xfusion_send_eval_cooldown_status($userId, $groupId);
    $latestBlock = xfusion_send_eval_build_latest_block($userId, $groupId);

    wp_send_json_success([
        'message' => $result['message'],
        'group_id' => $groupId,
        'group_title' => $collected['group_title'],
        'evaluation' => $apiData,
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
    return <<<'CSS'
.xfusion-send-eval .xfusion-send-eval__latest{margin:0 0 1rem;}
.xfusion-send-eval .xfusion-send-eval__latest-date{display:block!important;margin:0 0 0.75rem;font-size:0.85rem;color:#6b7280;}
.xfusion-send-eval .xfusion-send-eval__latest-empty{display:block!important;margin:0;font-size:0.875rem;color:#6b7280;}
.xfusion-send-eval .xfusion-eval-card--feedback-only{display:block!important;box-sizing:border-box;border:1px solid #e5e7eb;border-radius:8px;background:#fff;overflow:hidden;line-height:1.5;}
.xfusion-send-eval .xfusion-eval-card--feedback-only .xfusion-eval-card__body{display:flex!important;flex-direction:column;gap:12px;padding:16px;}
.xfusion-send-eval .xfusion-eval-card__section{display:block!important;margin:0;padding:12px 14px;border-radius:6px;background:#f6f7f7;border-left:4px solid #c3c4c7;}
.xfusion-send-eval .xfusion-eval-card__section--strengths{border-left-color:#00a32a;background:#edfaef;}
.xfusion-send-eval .xfusion-eval-card__section--improvements{border-left-color:#dba617;background:#fcf9e8;}
.xfusion-send-eval .xfusion-eval-card__section--notes{border-left-color:#2271b1;background:#f0f6fc;}
.xfusion-send-eval .xfusion-eval-card__section-title{display:block!important;margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-send-eval .xfusion-eval-card__section-text{display:block!important;margin:0;font-size:13px;color:#1d2327;white-space:pre-wrap;word-break:break-word;}
.xfusion-send-eval .xfusion-send-eval__cooldown{margin:0.75rem 0 0;padding:0.65rem 0.85rem;font-size:0.875rem;color:#92400e;background:#fffbeb;border:1px solid #fcd34d;border-radius:0.375rem;}
.xfusion-send-eval .xfusion-send-eval__btn:disabled{opacity:0.55;cursor:not-allowed;}
.xfusion-send-eval .xfusion-send-eval__btn.is-cooldown-hidden{display:none!important;}
.xfusion-send-eval .xfusion-send-eval-fb{display:block!important;margin:0 0 12px;padding:12px 14px;border-radius:6px;background:#f6f7f7;border-left:4px solid #c3c4c7;font-size:13px;line-height:1.5;color:#1d2327;white-space:pre-wrap;word-break:break-word;}
.xfusion-send-eval .xfusion-send-eval-fb strong{display:block;margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-send-eval .xfusion-send-eval-fb--strengths{border-left-color:#00a32a;background:#edfaef;}
.xfusion-send-eval .xfusion-send-eval-fb--improvements{border-left-color:#dba617;background:#fcf9e8;}
.xfusion-send-eval .xfusion-send-eval-fb--notes{border-left-color:#2271b1;background:#f0f6fc;}
.xfusion-send-eval .xfusion-send-eval-ai-disclaimer{display:block!important;margin:2px 0 0;padding:0;font-size:10px;line-height:1.4;color:#9ca3af;font-style:italic;}
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
function xfusion_send_eval_render_feedback_html(array $evaluation): string
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

    $sections = [
        ['class' => 'xfusion-send-eval-fb--strengths', 'label' => __('Strengths', 'xfusion'), 'text' => $feedback['strengths']],
        ['class' => 'xfusion-send-eval-fb--improvements', 'label' => __('Improvements', 'xfusion'), 'text' => $feedback['improvements']],
        ['class' => 'xfusion-send-eval-fb--notes', 'label' => __('Evaluator notes', 'xfusion'), 'text' => $feedback['evaluator_notes']],
    ];

    $html = '';
    foreach ($sections as $section) {
        $text = trim((string) $section['text']);
        $html .= sprintf(
            '<p class="xfusion-send-eval-fb %s"><strong>%s</strong>%s</p>',
            esc_attr($section['class']),
            esc_html((string) $section['label']),
            esc_html($text !== '' ? $text : '—')
        );
    }

    return $html . xfusion_send_eval_ai_disclaimer_html();
}

/**
 * @return array{html: string, has_result: bool}
 */
function xfusion_send_eval_build_latest_block(int $userId, int $groupId): array
{
    if ($userId < 1 || $groupId < 1 || ! function_exists('xfusion_result_evaluation_latest_for_group')) {
        return [
            'html' => '<p class="xfusion-send-eval__latest-empty">' . esc_html__('No evaluation submitted yet.', 'xfusion') . '</p>',
            'has_result' => false,
        ];
    }

    $latest = xfusion_result_evaluation_latest_for_group($userId, $groupId);
    if ($latest === null) {
        return [
            'html' => '<p class="xfusion-send-eval__latest-empty">' . esc_html__('No evaluation submitted yet.', 'xfusion') . '</p>',
            'has_result' => false,
        ];
    }

    $evaluation = is_array($latest['evaluation'] ?? null) ? $latest['evaluation'] : [];

    if ($evaluation === [] && ! empty($latest['id']) && function_exists('xfusion_result_evaluation_get')) {
        $row = xfusion_result_evaluation_get((int) $latest['id']);
        if ($row !== null && function_exists('xfusion_result_evaluation_decode_json')) {
            $decoded = xfusion_result_evaluation_decode_json((string) $row->evaluation);
            if (is_array($decoded)) {
                $evaluation = $decoded;
            }
        }
    }

    $evaluatedAt = trim((string) ($latest['evaluated_at'] ?? ''));
    $feedbackHtml = xfusion_send_eval_render_feedback_html($evaluation);

    return [
        'html' => ($evaluatedAt !== ''
                ? '<p class="xfusion-send-eval__latest-date"><span>' . esc_html__('Last evaluated', 'xfusion') . ':</span> ' . esc_html($evaluatedAt) . ' UTC</p>'
                : '')
            . $feedbackHtml,
        'has_result' => true,
    ];
}

/**
 * Render the user's latest evaluation for a scoring group (from the DB table).
 * Always outputs a visible block (result or empty state).
 */
function xfusion_send_eval_latest_html(int $userId, int $groupId): string
{
    $block = xfusion_send_eval_build_latest_block($userId, $groupId);

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
        __('Next evaluation available in %s. One evaluation per group every 24 hours.', 'xfusion'),
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
            'button_label' => __('Generate Insights', 'xfusion'),
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
        return '<p class="xfusion-send-eval xfusion-send-eval--error">' . esc_html__('Please log in to send evaluation.', 'xfusion') . '</p>';
    }

    $groupId = xfusion_send_eval_group_id_from_category($category);
    if ($groupId < 1) {
        return '<p class="xfusion-send-eval xfusion-send-eval--error">' . esc_html(sprintf(
            __('Course scoring group not found for category: %s', 'xfusion'),
            $category
        )) . '</p>';
    }

    global $wpdb;
    $gtable = $wpdb->prefix . 'course_scoring_groups';
    $groupTitle = (string) $wpdb->get_var(
        $wpdb->prepare("SELECT title FROM {$gtable} WHERE id = %d", $groupId)
    );

    $uid = (int) get_current_user_id();
    $attrUserId = absint($atts['user_id']);
    if ($attrUserId > 0 && current_user_can('edit_users')) {
        $uid = $attrUserId;
    }

    $instanceId = 'xfusion-send-eval-' . wp_unique_id();
    $wrapClass = trim('xfusion-send-eval ' . (string) $atts['class']);
    $ajaxUrl = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce(XFUSION_SEND_EVAL_NONCE_ACTION);
    $btnLabel = (string) $atts['button_label'];
    $sendingLabel = esc_attr__('Generating…', 'xfusion');
    $cooldown = xfusion_send_eval_cooldown_status($uid, $groupId);
    $onCooldown = $cooldown['on_cooldown'];
    $cooldownHtml = xfusion_send_eval_cooldown_notice_html($cooldown);
    $latestBlock = xfusion_send_eval_build_latest_block($uid, $groupId);

    ob_start();
    xfusion_send_eval_print_card_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" id="<?php echo esc_attr($instanceId); ?>" data-category="<?php echo esc_attr($category); ?>" data-user-id="<?php echo (int) $uid; ?>" data-group-id="<?php echo (int) $groupId; ?>" data-group-title="<?php echo esc_attr($groupTitle); ?>" data-cooldown-until="<?php echo (int) ($cooldown['available_at_ts'] ?? 0); ?>">
    <div class="xfusion-send-eval__latest-slot" aria-live="polite"><div class="xfusion-send-eval__latest xfusion-result-eval-wrap<?php echo $latestBlock['has_result'] ? '' : ' xfusion-send-eval__latest--empty'; ?>"><?php echo $latestBlock['html']; ?></div></div>

    <?php echo $cooldownHtml; ?>

    <button type="button" class="xfusion-send-eval__btn button<?php echo $onCooldown ? ' is-cooldown-hidden' : ''; ?>" style="cursor:pointer;margin-top:0.75rem;">
        <?php echo esc_html($btnLabel); ?>
    </button>
    <div class="xfusion-send-eval__status" style="margin-top:0.75rem;font-size:0.9rem;display:none;" role="status" aria-live="polite"></div>
</div>
<script>
(function () {
    var root = document.getElementById(<?php echo wp_json_encode($instanceId); ?>);
    if (!root) return;
    var btn = root.querySelector('.xfusion-send-eval__btn');
    var statusEl = root.querySelector('.xfusion-send-eval__status');
    var latestSlot = root.querySelector('.xfusion-send-eval__latest-slot');
    var cooldownEl = root.querySelector('.xfusion-send-eval__cooldown');
    if (!btn || !statusEl) return;

    var defaultBtnLabel = <?php echo wp_json_encode($btnLabel); ?>;

    function setButtonCooldownHidden(hidden) {
        if (hidden) {
            btn.classList.add('is-cooldown-hidden');
            btn.disabled = true;
            btn.textContent = defaultBtnLabel;
        } else {
            btn.classList.remove('is-cooldown-hidden');
            btn.disabled = false;
            btn.textContent = defaultBtnLabel;
        }
    }

    function applyCooldown(untilTs) {
        var now = Math.floor(Date.now() / 1000);
        var remaining = untilTs - now;
        if (remaining <= 0) {
            setButtonCooldownHidden(false);
            if (cooldownEl) cooldownEl.style.display = 'none';
            root.setAttribute('data-cooldown-until', '0');
            return false;
        }
        setButtonCooldownHidden(true);
        root.setAttribute('data-cooldown-until', String(untilTs));
        return true;
    }

    function tickCooldown() {
        var until = parseInt(root.getAttribute('data-cooldown-until') || '0', 10);
        if (!until) return;
        if (!applyCooldown(until)) {
            var el = root.querySelector('.xfusion-send-eval__cooldown');
            if (el) {
                el.style.display = 'none';
            }
        }
    }

    tickCooldown();
    setInterval(tickCooldown, 30000);

    function showStatus(msg, isError, html) {
        statusEl.style.display = 'block';
        statusEl.style.color = isError ? '#b91c1c' : '#166534';
        if (html) {
            statusEl.innerHTML = msg;
        } else {
            statusEl.textContent = msg;
        }
    }

    btn.addEventListener('click', function () {
        if (btn.classList.contains('is-cooldown-hidden')) return;

        btn.disabled = true;
        var prev = btn.textContent;
        btn.textContent = <?php echo wp_json_encode($sendingLabel); ?>;
        showStatus('', false);
        statusEl.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'xfusion_send_evaluation');
        fd.append('nonce', <?php echo wp_json_encode($nonce); ?>);
        fd.append('category', root.getAttribute('data-category') || '');
        fd.append('user_id', root.getAttribute('data-user-id') || '0');

        fetch(<?php echo wp_json_encode($ajaxUrl); ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.j || !res.j.success) {
                    var err = (res.j && res.j.data && res.j.data.message) ? res.j.data.message : 'Request failed.';
                    if (res.j && res.j.data && res.j.data.cooldown && res.j.data.cooldown.available_at_ts) {
                        applyCooldown(res.j.data.cooldown.available_at_ts);
                    } else {
                        btn.disabled = false;
                        btn.textContent = prev;
                    }
                    showStatus(err, true);
                    return;
                }

                var data = res.j.data || {};

                if (data.result_card_html && latestSlot) {
                    var latestInner = latestSlot.querySelector('.xfusion-send-eval__latest');
                    if (latestInner) {
                        latestInner.innerHTML = data.result_card_html;
                        latestInner.classList.remove('xfusion-send-eval__latest--empty');
                    } else {
                        latestSlot.innerHTML = '<div class="xfusion-send-eval__latest xfusion-result-eval-wrap">' + data.result_card_html + '</div>';
                    }
                }

                if (data.cooldown && data.cooldown.available_at_ts) {
                    applyCooldown(data.cooldown.available_at_ts);
                    if (data.cooldown_notice_html) {
                        if (cooldownEl) {
                            cooldownEl.outerHTML = data.cooldown_notice_html;
                        } else {
                            var temp = document.createElement('div');
                            temp.innerHTML = data.cooldown_notice_html.trim();
                            var notice = temp.firstElementChild;
                            if (notice && btn.parentNode) {
                                btn.parentNode.insertBefore(notice, btn);
                            }
                            cooldownEl = root.querySelector('.xfusion-send-eval__cooldown');
                        }
                    }
                    if (cooldownEl) {
                        cooldownEl.style.display = 'block';
                    }
                } else {
                    setButtonCooldownHidden(false);
                }

                statusEl.style.display = 'none';
                statusEl.textContent = '';
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = prev;
                showStatus('Network error. Please try again.', true);
            });
    });
})();
</script>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('send_evaluation', 'xfusion_send_evaluation_shortcode');
