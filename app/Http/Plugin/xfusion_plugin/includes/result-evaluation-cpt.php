<?php
/**
 * Post type result-evaluation — simpan hasil evaluasi AI (field ACF).
 *
 * ACF fields (nama field — buat di WP admin, tipe Textarea kecuali disebut lain):
 *   - user_id (Number)
 *   - created_at (Text)
 *   - company_information (Number)
 *   - evaluated_at (Text) — waktu selesai evaluasi di API
 *   - scoring_group_id (Number)
 *   - scoring_group_title (Text)
 *   - evaluation_input (Textarea, JSON) — payload IN ke API (question_answers, dll.)
 *   - evaluation (Textarea, JSON) — payload OUT (score, strengths, improvements, evaluator_notes)
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

const XFUSION_RESULT_EVAL_POST_TYPE = 'result-evaluation';

/** ACF field names — harus sama dengan field group di WP admin. */
const XFUSION_RESULT_EVAL_ACF_USER_ID = 'user_id';
const XFUSION_RESULT_EVAL_ACF_CREATED_AT = 'created_at';
const XFUSION_RESULT_EVAL_ACF_COMPANY_INFO = 'company_information';
const XFUSION_RESULT_EVAL_ACF_EVALUATED_AT = 'evaluated_at';
const XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_ID = 'scoring_group_id';
const XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_TITLE = 'scoring_group_title';
const XFUSION_RESULT_EVAL_ACF_EVALUATION_INPUT = 'evaluation_input';
const XFUSION_RESULT_EVAL_ACF_EVALUATION = 'evaluation';

/** Field ACF bertipe textarea JSON — nilai harus string, bukan array. */
const XFUSION_RESULT_EVAL_ACF_JSON_TEXTAREAS = [
    XFUSION_RESULT_EVAL_ACF_EVALUATION_INPUT,
    XFUSION_RESULT_EVAL_ACF_EVALUATION,
];

add_action('init', 'xfusion_register_result_evaluation_post_type');

function xfusion_register_result_evaluation_post_type(): void
{
    register_post_type(XFUSION_RESULT_EVAL_POST_TYPE, [
        'labels' => [
            'name' => __('Result Evaluations', 'xfusion'),
            'singular_name' => __('Result Evaluation', 'xfusion'),
            'add_new_item' => __('Add Result Evaluation', 'xfusion'),
            'edit_item' => __('Edit Result Evaluation', 'xfusion'),
            'all_items' => __('Result Evaluations', 'xfusion'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-awards',
        'menu_position' => 27,
        'supports' => ['title', 'revisions'],
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}

/**
 * Normalisasi nilai sebelum disimpan ke ACF (textarea tidak boleh menerima array).
 *
 * @param mixed $value
 */
function xfusion_result_evaluation_normalize_acf_value(string $fieldName, $value): string|int|float
{
    if (in_array($fieldName, XFUSION_RESULT_EVAL_ACF_JSON_TEXTAREAS, true)) {
        if (is_array($value) || is_object($value)) {
            $json = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            return is_string($json) ? $json : '{}';
        }

        return is_string($value) ? $value : '';
    }

    if ($fieldName === XFUSION_RESULT_EVAL_ACF_USER_ID
        || $fieldName === XFUSION_RESULT_EVAL_ACF_COMPANY_INFO
        || $fieldName === XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_ID) {
        return (int) $value;
    }

    if (is_array($value) || is_object($value)) {
        $json = wp_json_encode($value, JSON_UNESCAPED_UNICODE);

        return is_string($json) ? $json : '';
    }

    return is_scalar($value) ? (string) $value : '';
}

/**
 * @param mixed $value
 */
function xfusion_result_evaluation_update_acf(int $postId, string $fieldName, $value): void
{
    $normalized = xfusion_result_evaluation_normalize_acf_value($fieldName, $value);

    if (function_exists('update_field')) {
        update_field($fieldName, $normalized, $postId);

        return;
    }

    update_post_meta($postId, $fieldName, $normalized);
}

/**
 * Nama tampilan untuk judul post: first_name dari user meta, lalu fallback WP user.
 */
function xfusion_result_evaluation_user_first_name(int $userId): string
{
    if ($userId < 1) {
        return '';
    }

    $firstName = trim((string) get_user_meta($userId, 'first_name', true));
    if ($firstName !== '') {
        return $firstName;
    }

    $user = get_userdata($userId);
    if ($user instanceof \WP_User) {
        $displayParts = preg_split('/\s+/', trim($user->display_name), 2);
        $fromDisplay = is_array($displayParts) && isset($displayParts[0]) ? trim((string) $displayParts[0]) : '';
        if ($fromDisplay !== '') {
            return $fromDisplay;
        }

        if (! empty($user->user_nicename)) {
            return (string) $user->user_nicename;
        }

        if (! empty($user->user_login)) {
            return (string) $user->user_login;
        }
    }

    return sprintf(__('User %d', 'xfusion'), $userId);
}

/**
 * Simpan hasil evaluasi ke post type result-evaluation + ACF.
 *
 * @param array<string, mixed> $apiData Response /api/v1/evaluation/evaluate (OUT)
 * @param array<string, mixed> $requestBody Body yang dikirim ke API (IN)
 * @return int Post ID atau 0 jika gagal
 */
function xfusion_result_evaluation_save_post(
    int $userId,
    int $groupId,
    string $groupTitle,
    array $apiData,
    array $requestBody = []
): int {
    if ($userId < 1) {
        return 0;
    }

    $eval = isset($apiData['evaluation']) && is_array($apiData['evaluation']) ? $apiData['evaluation'] : [];
    $score = isset($eval['score']) ? (int) $eval['score'] : 0;
    $createdAt = isset($apiData['created_at']) ? (string) $apiData['created_at'] : gmdate('c');
    $evaluatedAt = isset($apiData['evaluated_at']) ? (string) $apiData['evaluated_at'] : gmdate('c');
    $companyInfo = isset($apiData['company_information']) ? (int) $apiData['company_information'] : 0;

    $employeeName = xfusion_result_evaluation_user_first_name($userId);

    $title = sprintf(
        /* translators: 1: group title, 2: employee first name, 3: score */
        __('Evaluation: %1$s — %2$s — Score %3$d', 'xfusion'),
        $groupTitle !== '' ? $groupTitle : '#' . $groupId,
        $employeeName,
        $score
    );

    $postId = wp_insert_post([
        'post_type' => XFUSION_RESULT_EVAL_POST_TYPE,
        'post_status' => 'publish',
        'post_author' => $userId,
        'post_title' => $title,
        'post_content' => '',
    ], true);

    if (is_wp_error($postId)) {
        return 0;
    }

    $postId = (int) $postId;
    if ($postId < 1) {
        return 0;
    }

    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_USER_ID, $userId);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_CREATED_AT, $createdAt);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_EVALUATED_AT, $evaluatedAt);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_COMPANY_INFO, $companyInfo);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_ID, $groupId);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_TITLE, $groupTitle);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_EVALUATION_INPUT, $requestBody);
    xfusion_result_evaluation_update_acf($postId, XFUSION_RESULT_EVAL_ACF_EVALUATION, $eval);

    return $postId;
}

/**
 * Post lama: konversi array → JSON string saat field textarea dibuka di admin.
 *
 * @param mixed $value
 * @return mixed
 */
function xfusion_result_evaluation_acf_load_json_textarea($value, $postId, array $field)
{
    if (is_array($value) || is_object($value)) {
        $json = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return is_string($json) ? $json : '';
    }

    return $value;
}

foreach (XFUSION_RESULT_EVAL_ACF_JSON_TEXTAREAS as $xfusionEvalJsonField) {
    add_filter(
        'acf/load_value/name=' . $xfusionEvalJsonField,
        'xfusion_result_evaluation_acf_load_json_textarea',
        10,
        3
    );
}

/**
 * @return mixed
 */
function xfusion_result_evaluation_get_acf_value(int $postId, string $fieldName)
{
    if (function_exists('get_field')) {
        return get_field($fieldName, $postId);
    }

    return get_post_meta($postId, $fieldName, true);
}

/**
 * @return array<string, mixed>|null
 */
function xfusion_result_evaluation_parse_json_field(int $postId, string $fieldName): ?array
{
    $raw = xfusion_result_evaluation_get_acf_value($postId, $fieldName);

    if (is_array($raw)) {
        return $raw;
    }

    if (! is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

/**
 * Evaluasi terakhir untuk user + scoring group.
 *
 * @return array{
 *   post_id: int,
 *   group_title: string,
 *   evaluated_at: string,
 *   evaluation: array<string, mixed>
 * }|null
 */
function xfusion_result_evaluation_latest_for_group(int $userId, int $groupId): ?array
{
    if ($userId < 1 || $groupId < 1) {
        return null;
    }

    $posts = get_posts([
        'post_type' => XFUSION_RESULT_EVAL_POST_TYPE,
        'post_status' => 'publish',
        'author' => $userId,
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_ID,
                'value' => (string) $groupId,
                'compare' => '=',
            ],
        ],
    ]);

    if ($posts === []) {
        return null;
    }

    $post = $posts[0];
    $evaluation = xfusion_result_evaluation_parse_json_field($post->ID, XFUSION_RESULT_EVAL_ACF_EVALUATION);
    if ($evaluation === null) {
        return null;
    }

    $evaluatedAt = (string) xfusion_result_evaluation_get_acf_value($post->ID, XFUSION_RESULT_EVAL_ACF_EVALUATED_AT);
    $groupTitle = (string) xfusion_result_evaluation_get_acf_value($post->ID, XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_TITLE);

    return [
        'post_id' => (int) $post->ID,
        'group_title' => $groupTitle,
        'evaluated_at' => $evaluatedAt,
        'evaluation' => $evaluation,
    ];
}

/**
 * @return array{accent: string, bg: string, label: string}
 */
function xfusion_result_evaluation_score_theme(int $score): array
{
    if ($score >= 80) {
        return ['accent' => '#16a34a', 'bg' => '#ecfdf5', 'label' => __('Excellent', 'xfusion')];
    }
    if ($score >= 60) {
        return ['accent' => '#ca8a04', 'bg' => '#fefce8', 'label' => __('Progressing', 'xfusion')];
    }

    return ['accent' => '#dc2626', 'bg' => '#fef2f2', 'label' => __('Needs improvement', 'xfusion')];
}

/**
 * Kartu hasil evaluasi (admin & reuse).
 *
 * @param array{
 *   score: int,
 *   strengths: string,
 *   improvements: string,
 *   evaluator_notes: string,
 *   group_title?: string,
 *   evaluated_at?: string,
 *   post_id?: int
 * } $data
 */
function xfusion_result_evaluation_render_card(array $data): string
{
    $score = max(0, min(100, (int) ($data['score'] ?? 0)));
    $theme = xfusion_result_evaluation_score_theme($score);
    $strengths = (string) ($data['strengths'] ?? '');
    $improvements = (string) ($data['improvements'] ?? '');
    $notes = (string) ($data['evaluator_notes'] ?? '');
    $groupTitle = (string) ($data['group_title'] ?? '');
    $evaluatedAt = (string) ($data['evaluated_at'] ?? '');
    $postId = isset($data['post_id']) ? (int) $data['post_id'] : 0;
    $dateLabel = $evaluatedAt !== '' ? esc_html($evaluatedAt) : '—';

    ob_start();
    ?>
<div class="xfusion-eval-card" style="--xf-eval-accent:<?php echo esc_attr($theme['accent']); ?>;--xf-eval-bg:<?php echo esc_attr($theme['bg']); ?>;">
    <div class="xfusion-eval-card__header">
        <div class="xfusion-eval-card__score-ring" aria-label="<?php echo esc_attr(sprintf(__('Score %d out of 100', 'xfusion'), $score)); ?>">
            <span class="xfusion-eval-card__score-value"><?php echo (int) $score; ?></span>
            <span class="xfusion-eval-card__score-max">/100</span>
        </div>
        <div class="xfusion-eval-card__meta">
            <?php if ($groupTitle !== '') : ?>
                <h2 class="xfusion-eval-card__title"><?php echo esc_html($groupTitle); ?></h2>
            <?php else : ?>
                <h2 class="xfusion-eval-card__title"><?php esc_html_e('Evaluation result', 'xfusion'); ?></h2>
            <?php endif; ?>
            <p class="xfusion-eval-card__badge"><?php echo esc_html($theme['label']); ?></p>
            <p class="xfusion-eval-card__date">
                <span><?php esc_html_e('Evaluated', 'xfusion'); ?>:</span> <?php echo $dateLabel; ?>
                <?php if ($postId > 0) : ?>
                    <span class="xfusion-eval-card__ref">#<?php echo (int) $postId; ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="xfusion-eval-card__body">
        <section class="xfusion-eval-card__section xfusion-eval-card__section--strengths">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Strengths', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($strengths !== '' ? $strengths : '—'); ?></p>
        </section>
        <section class="xfusion-eval-card__section xfusion-eval-card__section--improvements">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Improvements', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($improvements !== '' ? $improvements : '—'); ?></p>
        </section>
        <section class="xfusion-eval-card__section xfusion-eval-card__section--notes">
            <h3 class="xfusion-eval-card__section-title"><?php esc_html_e('Evaluator notes', 'xfusion'); ?></h3>
            <p class="xfusion-eval-card__section-text"><?php echo esc_html($notes !== '' ? $notes : '—'); ?></p>
        </section>
    </div>
</div>
    <?php

    return (string) ob_get_clean();
}

function xfusion_result_evaluation_admin_card_css(): string
{
    return <<<'CSS'
.xfusion-result-eval-admin-wrap{margin:12px 0 20px;max-width:960px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card{box-sizing:border-box;border:1px solid #c3c4c7;border-radius:8px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;line-height:1.5;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__header{display:flex;gap:16px;align-items:center;padding:20px 20px 16px;background:var(--xf-eval-bg,#f6f7f7);border-bottom:1px solid #dcdcde;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-ring{flex-shrink:0;width:80px;height:80px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#fff;border:4px solid var(--xf-eval-accent,#646970);box-shadow:0 2px 6px rgba(0,0,0,.06);}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-value{font-size:26px;font-weight:700;color:var(--xf-eval-accent,#1d2327);line-height:1;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__score-max{font-size:11px;font-weight:600;color:#646970;margin-top:2px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__meta{min-width:0;flex:1;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__title{margin:0 0 6px;font-size:18px;font-weight:600;color:#1d2327;line-height:1.3;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__badge{display:inline-block;margin:0 0 6px;padding:3px 10px;font-size:12px;font-weight:600;border-radius:999px;background:var(--xf-eval-accent,#646970);color:#fff;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__date{margin:0;font-size:12px;color:#646970;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__ref{margin-left:6px;color:#a7aaad;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__body{padding:4px 20px 20px;display:flex;flex-direction:column;gap:12px;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section{margin:0;padding:12px 14px;border-radius:6px;background:#f6f7f7;border-left:4px solid #c3c4c7;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--strengths{border-left-color:#00a32a;background:#edfaef;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--improvements{border-left-color:#dba617;background:#fcf9e8;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section--notes{border-left-color:#2271b1;background:#f0f6fc;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section-title{margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#1d2327;}
.xfusion-result-eval-admin-wrap .xfusion-eval-card__section-text{margin:0;font-size:13px;color:#1d2327;white-space:pre-wrap;}
CSS;
}

add_action('admin_enqueue_scripts', 'xfusion_result_evaluation_admin_enqueue_styles');

function xfusion_result_evaluation_admin_enqueue_styles(string $hook): void
{
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen === null || $screen->post_type !== XFUSION_RESULT_EVAL_POST_TYPE) {
        return;
    }

    wp_register_style('xfusion-result-eval-admin', false, [], '1.0');
    wp_enqueue_style('xfusion-result-eval-admin');
    wp_add_inline_style('xfusion-result-eval-admin', xfusion_result_evaluation_admin_card_css());
}

/**
 * Tampilkan kartu evaluasi di bawah judul post (halaman edit result-evaluation).
 *
 * @param \WP_Post $post
 */
function xfusion_result_evaluation_admin_after_title(\WP_Post $post): void
{
    if ($post->post_type !== XFUSION_RESULT_EVAL_POST_TYPE) {
        return;
    }

    $evaluation = xfusion_result_evaluation_parse_json_field($post->ID, XFUSION_RESULT_EVAL_ACF_EVALUATION);

    echo '<div class="xfusion-result-eval-admin-wrap">';

    if ($evaluation === null) {
        echo '<div class="notice notice-info inline" style="margin:0;"><p>';
        esc_html_e('No AI evaluation data on this post yet. Run Send Evaluation on the front end to generate a result.', 'xfusion');
        echo '</p></div></div>';

        return;
    }

    $groupTitle = (string) xfusion_result_evaluation_get_acf_value($post->ID, XFUSION_RESULT_EVAL_ACF_SCORING_GROUP_TITLE);
    $evaluatedAt = (string) xfusion_result_evaluation_get_acf_value($post->ID, XFUSION_RESULT_EVAL_ACF_EVALUATED_AT);

    echo xfusion_result_evaluation_render_card([
        'score' => (int) ($evaluation['score'] ?? 0),
        'strengths' => (string) ($evaluation['strengths'] ?? ''),
        'improvements' => (string) ($evaluation['improvements'] ?? ''),
        'evaluator_notes' => (string) ($evaluation['evaluator_notes'] ?? ''),
        'group_title' => $groupTitle,
        'evaluated_at' => $evaluatedAt,
        'post_id' => (int) $post->ID,
    ]);

    echo '</div>';
}

add_action('edit_form_after_title', 'xfusion_result_evaluation_admin_after_title');
