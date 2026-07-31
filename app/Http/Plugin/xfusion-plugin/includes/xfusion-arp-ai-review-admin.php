<?php
/**
 * wp-admin: ARP AI Readiness Review™ history (all generated versions).
 *
 * Every "Generate AI Insights" click on Step 6 of the ARP wizard inserts a
 * new row in wp_fusion_arp_ai_assessments (previous versions are kept,
 * read-only) — this page is the only place to browse that history, since
 * the wizard itself only ever shows the latest one. Structurally identical
 * to the 1-on-1 Brief History admin page (xfusion-one-on-one-briefs-admin.php).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'xfusion_arp_ai_review_history_register_admin_menu', 83);

function xfusion_arp_ai_review_history_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'fusion_arp_ai_assessments';
}

function xfusion_arp_ai_review_history_admin_url(int $assessmentId = 0, int $arpId = 0): string
{
    $args = ['page' => 'xfusion-arp-ai-review-history'];
    if ($assessmentId > 0) {
        $args['id'] = $assessmentId;
    }
    if ($arpId > 0 && $assessmentId === 0) {
        $args['arp_id'] = $arpId;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

function xfusion_arp_ai_review_history_user_label(int $userId): string
{
    if ($userId < 1) {
        return '—';
    }
    $user = get_userdata($userId);
    if ($user instanceof WP_User) {
        $name = trim((string) $user->display_name);
        if ($name !== '') {
            return $name;
        }
    }

    return '#' . $userId;
}

function xfusion_arp_ai_review_history_register_admin_menu(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'xfusion-llm-prompts',
        __('ARP AI Review History', 'xfusion'),
        __('ARP AI Review History', 'xfusion'),
        'manage_options',
        'xfusion-arp-ai-review-history',
        'xfusion_arp_ai_review_history_admin_page'
    );
}

function xfusion_arp_ai_review_history_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'xfusion'));
    }

    $assessmentId = isset($_GET['id']) ? absint((string) $_GET['id']) : 0;

    echo '<div class="wrap xfusion-arp-ai-review-wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

    if ($assessmentId > 0) {
        xfusion_arp_ai_review_history_admin_detail_page($assessmentId);
        echo '</div>';

        return;
    }

    xfusion_arp_ai_review_history_admin_list_page();
    echo '</div>';
}

function xfusion_arp_ai_review_history_admin_list_page(): void
{
    global $wpdb;

    $assessmentsTable = xfusion_arp_ai_review_history_table_name();
    $arpsTable = $wpdb->prefix . 'fusion_arps';
    $groupsTable = $wpdb->prefix . 'company_groups';
    $companiesTable = $wpdb->prefix . 'companies';

    $perPage = 25;
    $page = isset($_GET['paged']) ? max(1, absint((string) $_GET['paged'])) : 1;
    $offset = ($page - 1) * $perPage;
    $arpFilter = isset($_GET['arp_id']) ? absint((string) $_GET['arp_id']) : 0;

    $where = '1=1';
    $params = [];
    if ($arpFilter > 0) {
        $where .= ' AND a.arp_id = %d';
        $params[] = $arpFilter;
    }

    $countSql = "SELECT COUNT(*) FROM {$assessmentsTable} a WHERE {$where}";
    $total = $params === []
        ? (int) $wpdb->get_var($countSql)
        : (int) $wpdb->get_var($wpdb->prepare($countSql, ...$params));

    $listSql = "
        SELECT
            a.id,
            a.arp_id,
            a.insight_model,
            a.tokens_used,
            a.cost_usd,
            a.created_at,
            p.year,
            p.status AS arp_status,
            p.created_by,
            g.title AS group_title,
            co.title AS company_title,
            (SELECT MAX(a2.id) FROM {$assessmentsTable} a2 WHERE a2.arp_id = a.arp_id) AS latest_assessment_id
        FROM {$assessmentsTable} a
        INNER JOIN {$arpsTable} p ON p.id = a.arp_id
        LEFT JOIN {$groupsTable} g ON g.id = p.company_group_id
        LEFT JOIN {$companiesTable} co ON co.id = p.company_id
        WHERE {$where}
        ORDER BY a.id DESC
        LIMIT %d OFFSET %d
    ";

    $listParams = array_merge($params, [$perPage, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($listSql, ...$listParams));

    echo '<p class="description">';
    esc_html_e('Every AI Readiness Review™ generation (ARP Step 6) is stored here. Regenerating in the wizard keeps previous versions (read-only).', 'xfusion');
    echo '</p>';

    if ($arpFilter > 0) {
        echo '<p><a href="' . esc_url(xfusion_arp_ai_review_history_admin_url()) . '">&larr; ';
        esc_html_e('Show all ARPs', 'xfusion');
        echo '</a></p>';
        echo '<p class="description">' . esc_html(sprintf(
            /* translators: %d: ARP id */
            __('Filtered to ARP #%d.', 'xfusion'),
            $arpFilter
        )) . '</p>';
    }

    if ($rows === []) {
        echo '<p>' . esc_html__('No AI Readiness Reviews have been generated yet.', 'xfusion') . '</p>';

        return;
    }

    echo '<table class="widefat striped xfusion-arp-ai-review-list">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('ID', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('ARP', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Group', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Company', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Model', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Tokens', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Est. cost', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Generated', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Version', 'xfusion') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $viewUrl = xfusion_arp_ai_review_history_admin_url((int) $row->id);
        $arpUrl = xfusion_arp_ai_review_history_admin_url(0, (int) $row->arp_id);
        $isLatest = (int) $row->id === (int) $row->latest_assessment_id;
        $model = trim((string) ($row->insight_model ?? ''));
        if ($model === '') {
            $model = '—';
        }
        $costUsd = (float) ($row->cost_usd ?? 0);
        $costLabel = function_exists('xfusion_llm_format_cost_usd')
            ? xfusion_llm_format_cost_usd($costUsd)
            : '$' . number_format($costUsd, 4);

        echo '<tr>';
        echo '<td><a href="' . esc_url($viewUrl) . '">#' . (int) $row->id . '</a></td>';
        echo '<td><a href="' . esc_url($arpUrl) . '">#' . (int) $row->arp_id . '</a>';
        if (! empty($row->year)) {
            echo '<br><span class="description">' . esc_html((string) $row->year) . '</span>';
        }
        echo '</td>';
        echo '<td>' . esc_html((string) ($row->group_title ?? '—')) . '</td>';
        echo '<td>' . esc_html((string) ($row->company_title ?? '—')) . '</td>';
        echo '<td><code>' . esc_html($model) . '</code></td>';
        echo '<td>' . esc_html(number_format_i18n((int) $row->tokens_used)) . '</td>';
        echo '<td>' . esc_html($costLabel) . '</td>';
        echo '<td>' . esc_html((string) $row->created_at) . '</td>';
        echo '<td>' . ($isLatest
            ? '<span class="xfusion-arp-ai-review-badge xfusion-arp-ai-review-badge--latest">' . esc_html__('Latest', 'xfusion') . '</span>'
            : '<span class="xfusion-arp-ai-review-badge">' . esc_html__('Archived', 'xfusion') . '</span>') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $totalPages,
            'current' => $page,
        ]);
        echo '</div></div>';
    }
}

function xfusion_arp_ai_review_history_admin_detail_page(int $assessmentId): void
{
    global $wpdb;

    $assessmentsTable = xfusion_arp_ai_review_history_table_name();
    $arpsTable = $wpdb->prefix . 'fusion_arps';
    $groupsTable = $wpdb->prefix . 'company_groups';
    $companiesTable = $wpdb->prefix . 'companies';

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT
            a.id,
            a.arp_id,
            a.assessment,
            a.leadership_context,
            a.insight_model,
            a.tokens_used,
            a.cost_usd,
            a.created_at,
            p.year,
            p.status AS arp_status,
            p.created_by,
            g.title AS group_title,
            co.title AS company_title,
            (SELECT MAX(a2.id) FROM {$assessmentsTable} a2 WHERE a2.arp_id = a.arp_id) AS latest_assessment_id
        FROM {$assessmentsTable} a
        INNER JOIN {$arpsTable} p ON p.id = a.arp_id
        LEFT JOIN {$groupsTable} g ON g.id = p.company_group_id
        LEFT JOIN {$companiesTable} co ON co.id = p.company_id
        WHERE a.id = %d
        LIMIT 1",
        $assessmentId
    ));

    $backUrl = xfusion_arp_ai_review_history_admin_url();

    echo '<p><a href="' . esc_url($backUrl) . '">&larr; ';
    esc_html_e('Back to list', 'xfusion');
    echo '</a></p>';

    if ($row === null) {
        echo '<div class="notice notice-error inline"><p>';
        esc_html_e('Assessment record not found.', 'xfusion');
        echo '</p></div>';

        return;
    }

    $arpId = (int) $row->arp_id;
    $isLatest = (int) $row->id === (int) $row->latest_assessment_id;
    $creator = xfusion_arp_ai_review_history_user_label((int) $row->created_by);
    $model = trim((string) ($row->insight_model ?? ''));
    $costUsd = (float) ($row->cost_usd ?? 0);
    $costLabel = function_exists('xfusion_llm_format_cost_usd')
        ? xfusion_llm_format_cost_usd($costUsd)
        : '$' . number_format($costUsd, 4);

    $assessment = json_decode((string) $row->assessment, true);
    if (! is_array($assessment)) {
        $assessment = [];
    }

    echo '<div class="xfusion-arp-ai-review-detail">';

    echo '<h2>' . esc_html(sprintf(
        /* translators: %d: assessment id */
        __('Assessment #%d', 'xfusion'),
        (int) $row->id
    ));
    if ($isLatest) {
        echo ' <span class="xfusion-arp-ai-review-badge xfusion-arp-ai-review-badge--latest">' . esc_html__('Latest for this ARP', 'xfusion') . '</span>';
    } else {
        echo ' <span class="xfusion-arp-ai-review-badge">' . esc_html__('Archived version', 'xfusion') . '</span>';
    }
    echo '</h2>';

    echo '<dl class="xfusion-arp-ai-review-meta">';
    echo '<dt>' . esc_html__('ARP', 'xfusion') . '</dt>';
    echo '<dd><a href="' . esc_url(xfusion_arp_ai_review_history_admin_url(0, $arpId)) . '">#' . esc_html((string) $arpId) . '</a>';
    if (! empty($row->year)) {
        echo ' &middot; ' . esc_html((string) $row->year);
    }
    echo '</dd>';
    echo '<dt>' . esc_html__('Group', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html((string) ($row->group_title ?? '—')) . '</dd>';
    echo '<dt>' . esc_html__('Company', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html((string) ($row->company_title ?? '—')) . '</dd>';
    echo '<dt>' . esc_html__('Created by', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html($creator) . '</dd>';
    echo '<dt>' . esc_html__('Model', 'xfusion') . '</dt>';
    echo '<dd><code>' . esc_html($model !== '' ? $model : '—') . '</code></dd>';
    echo '<dt>' . esc_html__('Tokens', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html(number_format_i18n((int) $row->tokens_used)) . '</dd>';
    echo '<dt>' . esc_html__('Est. cost', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html($costLabel) . '</dd>';
    echo '<dt>' . esc_html__('Generated', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html((string) $row->created_at) . '</dd>';
    echo '</dl>';

    if (trim((string) ($row->leadership_context ?? '')) !== '') {
        echo '<div class="xfusion-arp-ai-review-section">';
        echo '<h3>' . esc_html__('Leadership Context™', 'xfusion') . '</h3>';
        echo wpautop(esc_html((string) $row->leadership_context));
        echo '</div>';
    }

    xfusion_arp_ai_review_history_render_arp_versions($arpId, (int) $row->id);
    xfusion_arp_ai_review_history_render_assessment($assessment);

    echo '<details class="xfusion-arp-ai-review-raw" style="margin-top:1.5rem">';
    echo '<summary>' . esc_html__('Raw JSON', 'xfusion') . '</summary>';
    echo '<pre class="xfusion-arp-ai-review-json">' . esc_html(wp_json_encode($assessment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    echo '</details>';

    echo '</div>';
}

function xfusion_arp_ai_review_history_render_arp_versions(int $arpId, int $currentAssessmentId): void
{
    global $wpdb;

    $assessmentsTable = xfusion_arp_ai_review_history_table_name();
    $versions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, insight_model, tokens_used, created_at
         FROM {$assessmentsTable}
         WHERE arp_id = %d
         ORDER BY id DESC",
        $arpId
    ));

    if ($versions === [] || count($versions) < 2) {
        return;
    }

    echo '<div class="xfusion-arp-ai-review-versions">';
    echo '<h3>' . esc_html__('Other versions for this ARP', 'xfusion') . '</h3>';
    echo '<ul>';
    foreach ($versions as $version) {
        $id = (int) $version->id;
        if ($id === $currentAssessmentId) {
            continue;
        }
        $url = xfusion_arp_ai_review_history_admin_url($id);
        echo '<li><a href="' . esc_url($url) . '">#' . esc_html((string) $id) . '</a> — ';
        echo esc_html((string) $version->created_at);
        echo ' · <code>' . esc_html((string) ($version->insight_model ?? '—')) . '</code>';
        echo ' · ' . esc_html(number_format_i18n((int) $version->tokens_used)) . ' tokens';
        echo '</li>';
    }
    echo '</ul></div>';
}

/**
 * Renders the Step 6 UI assessment schema — see ArpReadinessReviewNormalizer
 * on the Laravel side for the authoritative shape (strategic_alignment,
 * readiness_assessment, gaps, priority_alignment, risk_summary, focus_areas).
 *
 * @param array<string, mixed> $assessment
 */
function xfusion_arp_ai_review_history_render_assessment(array $assessment): void
{
    if ($assessment === []) {
        echo '<p class="description">' . esc_html__('No assessment content stored.', 'xfusion') . '</p>';

        return;
    }

    echo '<div class="xfusion-arp-ai-review-sections">';
    echo '<h3>' . esc_html__('Assessment content', 'xfusion') . '</h3>';

    echo '<div class="xfusion-arp-ai-review-scores">';
    xfusion_arp_ai_review_history_render_score_block(
        __('Strategic Alignment', 'xfusion'),
        is_array($assessment['strategic_alignment'] ?? null) ? $assessment['strategic_alignment'] : []
    );
    xfusion_arp_ai_review_history_render_score_block(
        __('Readiness Assessment', 'xfusion'),
        is_array($assessment['readiness_assessment'] ?? null) ? $assessment['readiness_assessment'] : []
    );
    xfusion_arp_ai_review_history_render_score_block(
        __('Priority Alignment', 'xfusion'),
        is_array($assessment['priority_alignment'] ?? null) ? $assessment['priority_alignment'] : []
    );
    echo '</div>';

    $gaps = is_array($assessment['gaps'] ?? null) ? $assessment['gaps'] : [];
    if ($gaps !== []) {
        echo '<div class="xfusion-arp-ai-review-section">';
        echo '<h4>' . esc_html__('Gaps', 'xfusion') . '</h4>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Area', 'xfusion') . '</th>';
        echo '<th>' . esc_html__('Description', 'xfusion') . '</th>';
        echo '<th>' . esc_html__('Impact', 'xfusion') . '</th>';
        echo '<th>' . esc_html__('Priority', 'xfusion') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($gaps as $gap) {
            if (! is_array($gap)) {
                continue;
            }
            echo '<tr>';
            echo '<td>' . esc_html((string) ($gap['area'] ?? '—')) . '</td>';
            echo '<td>' . esc_html((string) ($gap['description'] ?? '—')) . '</td>';
            echo '<td>' . esc_html((string) ($gap['impact'] ?? '—')) . '</td>';
            echo '<td>' . esc_html((string) ($gap['priority'] ?? '—')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    $riskSummary = is_array($assessment['risk_summary'] ?? null) ? $assessment['risk_summary'] : [];
    if ($riskSummary !== []) {
        echo '<div class="xfusion-arp-ai-review-section">';
        echo '<h4>' . esc_html__('Risk Summary', 'xfusion') . '</h4>';
        echo '<p>';
        echo esc_html__('High', 'xfusion') . ': ' . (int) ($riskSummary['high'] ?? 0) . ' &middot; ';
        echo esc_html__('Medium', 'xfusion') . ': ' . (int) ($riskSummary['medium'] ?? 0) . ' &middot; ';
        echo esc_html__('Low', 'xfusion') . ': ' . (int) ($riskSummary['low'] ?? 0) . ' &middot; ';
        echo esc_html__('Strengths', 'xfusion') . ': ' . (int) ($riskSummary['strengths'] ?? 0);
        echo '</p></div>';
    }

    $focusAreas = is_array($assessment['focus_areas'] ?? null) ? $assessment['focus_areas'] : [];
    if ($focusAreas !== []) {
        echo '<div class="xfusion-arp-ai-review-section">';
        echo '<h4>' . esc_html__('Focus Areas', 'xfusion') . '</h4>';
        echo '<ul>';
        foreach ($focusAreas as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            echo '<li>' . esc_html((string) $item) . '</li>';
        }
        echo '</ul></div>';
    }

    echo '</div>';
}

/**
 * @param array<string, mixed> $block
 */
function xfusion_arp_ai_review_history_render_score_block(string $title, array $block): void
{
    if ($block === [] || ! isset($block['score'])) {
        return;
    }

    echo '<div class="xfusion-arp-ai-review-score-card">';
    echo '<h4>' . esc_html($title) . '</h4>';
    echo '<div class="xfusion-arp-ai-review-score-value">' . esc_html((string) $block['score']) . '</div>';
    if (! empty($block['label'])) {
        echo '<div class="xfusion-arp-ai-review-score-label">' . esc_html((string) $block['label']) . '</div>';
    }
    if (! empty($block['summary'])) {
        echo '<p class="description">' . esc_html((string) $block['summary']) . '</p>';
    }
    if (! empty($block['strengths']) && is_array($block['strengths'])) {
        echo '<ul>';
        foreach ($block['strengths'] as $strength) {
            if (! is_scalar($strength)) {
                continue;
            }
            echo '<li>' . esc_html((string) $strength) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

add_action('admin_head', static function (): void {
    if (! isset($_GET['page']) || (string) $_GET['page'] !== 'xfusion-arp-ai-review-history') {
        return;
    }
    echo '<style>
        .xfusion-arp-ai-review-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:#e5e7eb;color:#374151}
        .xfusion-arp-ai-review-badge--latest{background:#dcfce7;color:#166534}
        .xfusion-arp-ai-review-meta{display:grid;grid-template-columns:160px 1fr;gap:.35rem 1rem;max-width:720px;margin:1rem 0 1.5rem}
        .xfusion-arp-ai-review-meta dt{font-weight:600;margin:0}
        .xfusion-arp-ai-review-meta dd{margin:0}
        .xfusion-arp-ai-review-sections{margin-top:1.5rem}
        .xfusion-arp-ai-review-scores{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}
        .xfusion-arp-ai-review-score-card{border:1px solid #dcdcde;border-radius:4px;padding:1rem;background:#fff}
        .xfusion-arp-ai-review-score-card h4{margin:0 0 .35rem}
        .xfusion-arp-ai-review-score-value{font-size:1.75rem;font-weight:700;color:#1e2a52}
        .xfusion-arp-ai-review-score-label{font-weight:600;color:#5f9a3f;margin-bottom:.35rem}
        .xfusion-arp-ai-review-section{border:1px solid #dcdcde;border-radius:4px;padding:1rem;margin-bottom:1rem;background:#fff}
        .xfusion-arp-ai-review-section h4{margin:0 0 .5rem}
        .xfusion-arp-ai-review-json{max-height:480px;overflow:auto;background:#f6f7f7;padding:1rem;border:1px solid #dcdcde}
        .xfusion-arp-ai-review-versions ul{margin:.5rem 0 0 1.2rem}
    </style>';
});
