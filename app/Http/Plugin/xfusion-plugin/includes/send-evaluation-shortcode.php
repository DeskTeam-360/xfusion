<?php
/**
 * Shortcodes: send course scoring group answers to the XFusion-llm evaluation API,
 * and COR™ readiness summary dashboard with batch "Generate Insights".
 *
 * Usage:
 *   [send_evaluation category="Customer Service"]
 *   [send_evaluation category="1"]  (numeric = group id)
 *   [send_evaluation category="My Group" user_id="89"]  (admin only)
 *   [xfusion_core_readiness]
 *   [xfusion_core_readiness user_id="89"]  (admin only)
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_SEND_EVAL_NONCE_ACTION = 'xfusion_send_evaluation';

const XFUSION_COR_READINESS_NONCE_ACTION = 'xfusion_core_readiness';

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

    if (function_exists('xfusion_result_evaluation_render_feedback_sections')) {
        return xfusion_result_evaluation_render_feedback_sections($feedback, true)
            . xfusion_send_eval_ai_disclaimer_html();
    }

    return xfusion_send_eval_ai_disclaimer_html();
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
 * @return array{
 *   readiness_average: ?float,
 *   readiness_label: string,
 *   readiness_color: string,
 *   primary_strength: array{title: string, average: ?float},
 *   primary_opportunity: array{title: string, average: ?float},
 *   suggested_count: int,
 *   key_observation: string,
 *   recommended_focus: string,
 *   groups: list<array{title: string, group_id: int, average: ?float, zone_label: string, zone_color: string}>
 * }
 */
function xfusion_cor_readiness_dashboard_data(int $userId): array
{
    $groups = [];
    $scored = [];

    foreach (xfusion_cor_readiness_categories() as $title) {
        $groupId = xfusion_send_eval_group_id_from_category($title);

        $payload = ($groupId > 0 && function_exists('xfusion_csg_gauge_payload'))
            ? xfusion_csg_gauge_payload($groupId, $userId)
            : null;

        $average = is_array($payload) ? ($payload['average'] ?? null) : null;
        $zoneLabel = is_array($payload) ? (string) ($payload['gauge_zone_label'] ?? 'No data') : 'No data';
        $zoneColor = is_array($payload) ? (string) ($payload['gauge_zone_color'] ?? '#6b7280') : '#6b7280';

        $groups[] = [
            'title' => $title,
            'group_id' => $groupId,
            'average' => $average,
            'zone_label' => $zoneLabel,
            'zone_color' => $zoneColor,
        ];

        if ($average !== null) {
            $scored[] = [
                'title' => $title,
                'average' => (float) $average,
                'group_id' => $groupId,
            ];
        }
    }

    $readinessAverage = null;
    if ($scored !== []) {
        $readinessAverage = round(array_sum(array_column($scored, 'average')) / count($scored), 2);
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

    return [
        'readiness_average' => $readinessAverage,
        'readiness_label' => (string) $readinessZone['label'],
        'readiness_color' => (string) $readinessZone['color'],
        'primary_strength' => $primaryStrength,
        'primary_opportunity' => $primaryOpportunity,
        'suggested_count' => 3,
        'key_observation' => __(
            'You demonstrate strong resilience and persistence when facing challenges. However, your lower score in Fill Buckets suggests limited awareness of the factors that restore and sustain your energy. This combination often leads to sustained effort but can reduce long-term effectiveness if recovery and energy management are neglected.',
            'xfusion'
        ),
        'recommended_focus' => $primaryOpportunity['title'] !== '—' ? $primaryOpportunity['title'] : __('Fill Buckets', 'xfusion'),
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

function xfusion_cor_readiness_note_icon_url(): string
{
    return content_url('uploads/2026/06/xfusion-note-icon.png');
}

function xfusion_cor_readiness_dashboard_css(): string
{
    return <<<'CSS'
.xfusion-cor-readiness{box-sizing:border-box;max-width:960px;margin:0 auto;padding:28px 32px 32px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;font-family:inherit;line-height:1.5;color:#1e3a5f;}
.xfusion-cor-readiness__metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin:0 0 28px;}
.xfusion-cor-readiness__metric{position:relative;padding:0 24px;text-align:center;}
.xfusion-cor-readiness__metric:not(:last-child)::after{content:"";position:absolute;top:8%;right:0;width:1px;height:84%;background:#d1d5db;}
.xfusion-cor-readiness__metric:first-child{padding-left:0;}
.xfusion-cor-readiness__metric:last-child{padding-right:0;}
.xfusion-cor-readiness__metric-label{margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1e3a5f;line-height:1.35;}
.xfusion-cor-readiness__metric-value{margin:0;font-size:42px;font-weight:700;line-height:1;color:#1e3a5f;}
.xfusion-cor-readiness__metric-status{margin:8px 0 0;font-size:22px;font-weight:600;line-height:1.2;}
.xfusion-cor-readiness__metric-name{margin:0 0 6px;font-size:22px;font-weight:600;line-height:1.25;}
.xfusion-cor-readiness__metric-name--strength{color:#e8913a;}
.xfusion-cor-readiness__metric-name--opportunity{color:#dc2626;}
.xfusion-cor-readiness__metric-subscore{margin:0;font-size:22px;font-weight:700;color:#1e3a5f;line-height:1.2;}
.xfusion-cor-readiness__activities{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:4px;}
.xfusion-cor-readiness__activities-icon{width:52px;height:52px;object-fit:contain;flex-shrink:0;}
.xfusion-cor-readiness__activities-body{text-align:left;}
.xfusion-cor-readiness__activities-count{margin:0;font-size:42px;font-weight:700;line-height:1;color:#1e3a5f;}
.xfusion-cor-readiness__activities-label{margin:4px 0 0;font-size:13px;font-weight:700;color:#1e3a5f;line-height:1.2;}
.xfusion-cor-readiness__observation{margin:0 0 24px;}
.xfusion-cor-readiness__observation-title{margin:0 0 12px;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#1e3a5f;}
.xfusion-cor-readiness__observation-text{margin:0;font-size:15px;line-height:1.65;color:#1e3a5f;}
.xfusion-cor-readiness__footer{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.xfusion-cor-readiness__focus{margin:0;font-size:13px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;color:#1e3a5f;line-height:1.5;}
.xfusion-cor-readiness__focus-value{color:#dc2626;font-weight:700;}
.xfusion-cor-readiness__actions{margin-left:auto;}
.xfusion-cor-readiness__btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:14px 28px;border:none;border-radius:999px;background:linear-gradient(135deg,#3d9a50 0%,#2f7d3e 100%);color:#fff;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;box-shadow:0 4px 14px rgba(47,125,62,.28);transition:opacity .15s ease,transform .15s ease;}
.xfusion-cor-readiness__btn:hover{opacity:.94;transform:translateY(-1px);}
.xfusion-cor-readiness__btn:disabled{opacity:.55;cursor:not-allowed;transform:none;}
.xfusion-cor-readiness__btn-icon{width:18px;height:18px;flex-shrink:0;}
.xfusion-cor-readiness__status{margin:16px 0 0;font-size:14px;line-height:1.5;}
.xfusion-cor-readiness__status--error{color:#b91c1c;}
.xfusion-cor-readiness__status--success{color:#166534;}
@media (max-width:860px){
.xfusion-cor-readiness{padding:20px 18px 24px;}
.xfusion-cor-readiness__metrics{grid-template-columns:repeat(2,minmax(0,1fr));row-gap:24px;}
.xfusion-cor-readiness__metric:nth-child(2)::after{display:none;}
.xfusion-cor-readiness__metric:nth-child(odd){padding-left:0;}
.xfusion-cor-readiness__metric:nth-child(even){padding-right:0;}
.xfusion-cor-readiness__metric-value,.xfusion-cor-readiness__activities-count{font-size:34px;}
.xfusion-cor-readiness__metric-name,.xfusion-cor-readiness__metric-subscore,.xfusion-cor-readiness__metric-status{font-size:18px;}
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
    $noteIcon = esc_url(xfusion_cor_readiness_note_icon_url());
    $btnLabel = esc_html__('Generate Insights', 'xfusion');
    $sendingLabel = esc_attr__('Generating…', 'xfusion');

    ob_start();
    xfusion_cor_readiness_print_styles();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" id="<?php echo esc_attr($instanceId); ?>" data-user-id="<?php echo (int) $userId; ?>">
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

        <div class="xfusion-cor-readiness__metric">
            <p class="xfusion-cor-readiness__metric-label"><?php esc_html_e('Suggested Activities', 'xfusion'); ?></p>
            <div class="xfusion-cor-readiness__activities">
                <img class="xfusion-cor-readiness__activities-icon" src="<?php echo $noteIcon; ?>" alt="" width="52" height="52" decoding="async" />
                <div class="xfusion-cor-readiness__activities-body">
                    <p class="xfusion-cor-readiness__activities-count"><?php echo (int) $data['suggested_count']; ?></p>
                    <p class="xfusion-cor-readiness__activities-label"><?php esc_html_e('Recommended', 'xfusion'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="xfusion-cor-readiness__observation">
        <h3 class="xfusion-cor-readiness__observation-title"><?php esc_html_e('Key Observation', 'xfusion'); ?></h3>
        <p class="xfusion-cor-readiness__observation-text"><?php echo esc_html($data['key_observation']); ?></p>
    </div>

    <div class="xfusion-cor-readiness__footer">
        <p class="xfusion-cor-readiness__focus">
            <?php esc_html_e('Recommended Focus:', 'xfusion'); ?>
            <span class="xfusion-cor-readiness__focus-value"><?php echo esc_html($data['recommended_focus']); ?></span>
        </p>
        <div class="xfusion-cor-readiness__actions">
            <button type="button" class="xfusion-cor-readiness__btn">
                <svg class="xfusion-cor-readiness__btn-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2l1.2 4.2L17 7l-3.8 1.8L12 13l-1.2-4.2L7 7l3.8-1.8L12 2z" fill="currentColor"/>
                    <path d="M19 11l.8 2.8L22.5 14l-2.7 1.2L19 18l-.8-2.8L15.5 14l2.7-1.2L19 11z" fill="currentColor"/>
                    <path d="M5 13l.6 2.1L7.5 16l-2.1.9L5 19l-.6-2.1L2.5 16l2.1-.9L5 13z" fill="currentColor"/>
                </svg>
                <span class="xfusion-cor-readiness__btn-label"><?php echo $btnLabel; ?></span>
            </button>
        </div>
    </div>

    <div class="xfusion-cor-readiness__status" style="display:none;" role="status" aria-live="polite"></div>
</div>
<script>
(function () {
    var root = document.getElementById(<?php echo wp_json_encode($instanceId); ?>);
    if (!root) return;
    var btn = root.querySelector('.xfusion-cor-readiness__btn');
    var btnLabelEl = root.querySelector('.xfusion-cor-readiness__btn-label');
    var statusEl = root.querySelector('.xfusion-cor-readiness__status');
    if (!btn || !btnLabelEl || !statusEl) return;

    var defaultBtnLabel = <?php echo wp_json_encode($btnLabel); ?>;

    function showStatus(msg, isError) {
        statusEl.style.display = 'block';
        statusEl.className = 'xfusion-cor-readiness__status' + (isError ? ' xfusion-cor-readiness__status--error' : ' xfusion-cor-readiness__status--success');
        statusEl.textContent = msg;
    }

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btnLabelEl.textContent = <?php echo wp_json_encode(trim($sendingLabel)); ?>;
        statusEl.style.display = 'none';

        var fd = new FormData();
        fd.append('action', 'xfusion_core_readiness_generate_all');
        fd.append('nonce', <?php echo wp_json_encode($nonce); ?>);
        fd.append('user_id', root.getAttribute('data-user-id') || '0');

        fetch(<?php echo wp_json_encode($ajaxUrl); ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.j || !res.j.success) {
                    var err = (res.j && res.j.data && res.j.data.message) ? res.j.data.message : 'Request failed.';
                    btn.disabled = false;
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
                btn.disabled = false;
                btnLabelEl.textContent = defaultBtnLabel;
                showStatus('Network error. Please try again.', true);
            });
    });
})();
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

    $userId = (int) get_current_user_id();
    $postedUserId = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    if ($postedUserId > 0 && $postedUserId !== $userId) {
        if (! current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'xfusion')], 403);
        }
        $userId = $postedUserId;
    }

    $categories = xfusion_cor_readiness_categories();
    $results = [];
    $successCount = 0;
    $skippedCount = 0;
    $failedCount = 0;

    foreach ($categories as $title) {
        $groupId = xfusion_send_eval_group_id_from_category($title);
        if ($groupId < 1) {
            $results[$title] = [
                'ok' => false,
                'skipped' => true,
                'message' => sprintf(
                    /* translators: %s: category title */
                    __('Course scoring group not found for category: %s', 'xfusion'),
                    $title
                ),
            ];
            ++$skippedCount;
            continue;
        }

        $processed = xfusion_send_eval_process_group($userId, $groupId, true);
        $results[$title] = $processed;

        if ($processed['ok']) {
            ++$successCount;
        } elseif ($processed['skipped']) {
            ++$skippedCount;
        } else {
            ++$failedCount;
        }
    }

    $total = count($categories);

    if ($successCount < 1) {
        $messages = array_values(array_filter(array_map(
            static fn (array $row): string => isset($row['message']) ? (string) $row['message'] : '',
            $results
        )));

        wp_send_json_error([
            'message' => $messages[0] ?? __('No insights could be generated. Check that all categories have answers and cooldown periods have passed.', 'xfusion'),
            'results' => $results,
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'failed_count' => $failedCount,
        ], $failedCount > 0 ? 502 : 429);
    }

    $message = sprintf(
        /* translators: 1: success count, 2: total categories */
        _n(
            'Generated insights for %1$d of %2$d category.',
            'Generated insights for %1$d of %2$d categories.',
            $total,
            'xfusion'
        ),
        $successCount,
        $total
    );

    if ($skippedCount > 0 || $failedCount > 0) {
        $message .= ' ' . __('Some categories were skipped (cooldown, missing answers, or errors).', 'xfusion');
    }

    wp_send_json_success([
        'message' => $message,
        'results' => $results,
        'success_count' => $successCount,
        'skipped_count' => $skippedCount,
        'failed_count' => $failedCount,
        'reload' => true,
    ]);
}

add_shortcode('xfusion_core_readiness', 'xfusion_cor_readiness_dashboard_shortcode');
