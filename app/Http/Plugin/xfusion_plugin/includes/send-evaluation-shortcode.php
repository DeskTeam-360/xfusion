<?php
/**
 * Shortcode: kirim jawaban course scoring group ke XFusion-llm evaluation API.
 *
 * Usage:
 *   [send_evaluation category="Customer Service"]
 *   [send_evaluation category="1"]  (numeric = group id)
 *   [send_evaluation category="My Group" user_id="89"]  (admin only)
 *
 * `category` harus sama dengan `title` di wp_course_scoring_groups (atau id grup).
 * Form_id diambil dari wp_course_scoring_group_details; semua field pertanyaan GF per form ikut dikumpulkan.
 * Hanya Q&A yang jawabannya tidak kosong yang dikirim ke API.
 * Hasil disimpan ke post type result-evaluation (ACF: user_id, created_at, company_information, evaluation).
 * company_information dikirim 0 (kosong) sampai diisi nanti.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_SEND_EVAL_NONCE_ACTION = 'xfusion_send_evaluation';

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
 * Field GF yang bukan elemen struktural (semua pertanyaan/input pada form).
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
 * Unique form_id dari wp_course_scoring_group_details untuk satu grup.
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
 * Hanya Q&A yang jawabannya tidak kosong.
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
    $savedPostId = 0;
    if (function_exists('xfusion_result_evaluation_save_post') && $apiData !== []) {
        $savedPostId = xfusion_result_evaluation_save_post(
            $userId,
            $groupId,
            $collected['group_title'],
            $apiData,
            $body
        );
    }

    if ($savedPostId < 1) {
        wp_send_json_error([
            'message' => __('Evaluation received but failed to save result-evaluation post.', 'xfusion'),
            'evaluation' => $apiData,
        ], 500);
    }

    wp_send_json_success([
        'message' => $result['message'],
        'group_id' => $groupId,
        'group_title' => $collected['group_title'],
        'evaluation' => $apiData,
        'result_post_id' => $savedPostId,
        'result_edit_url' => current_user_can('edit_post', $savedPostId)
            ? get_edit_post_link($savedPostId, 'raw')
            : '',
    ]);
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
            'button_label' => __('Send Evaluation', 'xfusion'),
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
    $sendingLabel = esc_attr__('Sending…', 'xfusion');

    ob_start();
    ?>
<div class="<?php echo esc_attr($wrapClass); ?>" id="<?php echo esc_attr($instanceId); ?>" data-category="<?php echo esc_attr($category); ?>" data-user-id="<?php echo (int) $uid; ?>" data-group-title="<?php echo esc_attr($groupTitle); ?>" style="margin:1rem 0;padding:1rem;border:1px solid #e5e7eb;border-radius:0.5rem;background:#fff;">
    <p style="margin:0 0 0.75rem;font-size:0.95rem;color:#374151;">
        <strong><?php echo esc_html($groupTitle); ?></strong>
        <span style="display:block;font-size:0.85rem;color:#6b7280;margin-top:0.25rem;">
            <?php esc_html_e('Sends all answered questions from every Gravity Form linked to this scoring group.', 'xfusion'); ?>
        </span>
    </p>
    <button type="button" class="xfusion-send-eval__btn button" style="cursor:pointer;">
        <?php echo esc_html($btnLabel); ?>
    </button>
    <div class="xfusion-send-eval__status" style="margin-top:0.75rem;font-size:0.9rem;display:none;" role="status" aria-live="polite"></div>
    <p class="xfusion-send-eval__hint" style="margin:0.75rem 0 0;font-size:0.85rem;color:#6b7280;">
        <?php esc_html_e('The styled evaluation result is saved and viewable in WP Admin → Result Evaluations.', 'xfusion'); ?>
    </p>
</div>
<script>
(function () {
    var root = document.getElementById(<?php echo wp_json_encode($instanceId); ?>);
    if (!root) return;
    var btn = root.querySelector('.xfusion-send-eval__btn');
    var statusEl = root.querySelector('.xfusion-send-eval__status');
    if (!btn || !statusEl) return;

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
                btn.disabled = false;
                btn.textContent = prev;
                if (!res.j || !res.j.success) {
                    var err = (res.j && res.j.data && res.j.data.message) ? res.j.data.message : 'Request failed.';
                    showStatus(err, true);
                    return;
                }
                var msg = (res.j.data && res.j.data.message) ? res.j.data.message : 'Done.';
                var ev = res.j.data && res.j.data.evaluation;
                var editUrl = (res.j.data && res.j.data.result_edit_url) ? res.j.data.result_edit_url : '';
                var postId = (res.j.data && res.j.data.result_post_id) ? res.j.data.result_post_id : 0;
                if (ev && ev.evaluation && typeof ev.evaluation.score !== 'undefined') {
                    msg += ' Score: ' + ev.evaluation.score + '/100.';
                }
                if (editUrl) {
                    msg += ' <a href="' + editUrl + '" target="_blank" rel="noopener">'
                        + <?php echo wp_json_encode(__('View styled result in admin', 'xfusion')); ?>
                        + '</a>';
                    showStatus(msg, false, true);
                } else if (postId) {
                    msg += ' (Saved #' + postId + ')';
                    showStatus(msg, false, false);
                } else {
                    showStatus(msg, false, false);
                }
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
