<?php
/**
 * wp-admin: AI Meeting Synthesis history (all generated versions).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'xfusion_oo_synthesis_history_register_admin_menu', 83);

function xfusion_oo_synthesis_history_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'fusion_one_on_one_ai_syntheses';
}

function xfusion_oo_synthesis_history_admin_url(int $synthesisId = 0, int $conversationId = 0): string
{
    $args = ['page' => 'xfusion-oo-synthesis-history'];
    if ($synthesisId > 0) {
        $args['id'] = $synthesisId;
    }
    if ($conversationId > 0 && $synthesisId === 0) {
        $args['conversation_id'] = $conversationId;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

/**
 * @return array<string, string>
 */
function xfusion_oo_synthesis_history_section_labels(): array
{
    return [
        'meeting_summary' => __('Meeting Summary™', 'xfusion'),
        'alignment_summary' => __('Alignment Summary™', 'xfusion'),
        'development_summary' => __('Development Summary™', 'xfusion'),
        'commitment_summary' => __('Commitment Summary™', 'xfusion'),
        'emerging_risks' => __('Emerging Risks™', 'xfusion'),
        'emerging_opportunities' => __('Emerging Opportunities™', 'xfusion'),
        'suggested_coaching_topics' => __('Suggested Coaching Topics™', 'xfusion'),
        'recommended_follow_up' => __('Recommended Follow-up™', 'xfusion'),
    ];
}

function xfusion_oo_synthesis_history_register_admin_menu(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'xfusion-llm-prompts',
        __('1-on-1 Synthesis History', 'xfusion'),
        __('1-on-1 Synthesis History', 'xfusion'),
        'manage_options',
        'xfusion-oo-synthesis-history',
        'xfusion_oo_synthesis_history_admin_page'
    );
}

function xfusion_oo_synthesis_history_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'xfusion'));
    }

    $synthesisId = isset($_GET['id']) ? absint((string) $_GET['id']) : 0;

    echo '<div class="wrap xfusion-oo-synthesis-history-wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

    if ($synthesisId > 0) {
        xfusion_oo_synthesis_history_admin_detail_page($synthesisId);
        echo '</div>';

        return;
    }

    xfusion_oo_synthesis_history_admin_list_page();
    echo '</div>';
}

function xfusion_oo_synthesis_history_admin_list_page(): void
{
    global $wpdb;

    $synthesesTable = xfusion_oo_synthesis_history_table_name();
    $conversationsTable = $wpdb->prefix . 'fusion_one_on_one_conversations';
    $pairsTable = $wpdb->prefix . 'fusion_one_on_ones';
    $companiesTable = $wpdb->prefix . 'companies';

    $perPage = 25;
    $page = isset($_GET['paged']) ? max(1, absint((string) $_GET['paged'])) : 1;
    $offset = ($page - 1) * $perPage;
    $conversationFilter = isset($_GET['conversation_id']) ? absint((string) $_GET['conversation_id']) : 0;

    $where = '1=1';
    $params = [];
    if ($conversationFilter > 0) {
        $where .= ' AND s.conversation_id = %d';
        $params[] = $conversationFilter;
    }

    $countSql = "SELECT COUNT(*) FROM {$synthesesTable} s WHERE {$where}";
    $total = $params === []
        ? (int) $wpdb->get_var($countSql)
        : (int) $wpdb->get_var($wpdb->prepare($countSql, ...$params));

    $listSql = "
        SELECT
            s.id,
            s.conversation_id,
            s.insight_model,
            s.tokens_used,
            s.cost_usd,
            s.created_at,
            c.scheduled_at,
            oo.leader_user_id,
            oo.employee_user_id,
            co.title AS company_title,
            (SELECT MAX(s2.id) FROM {$synthesesTable} s2 WHERE s2.conversation_id = s.conversation_id) AS latest_synthesis_id
        FROM {$synthesesTable} s
        INNER JOIN {$conversationsTable} c ON c.id = s.conversation_id
        INNER JOIN {$pairsTable} oo ON oo.id = c.one_on_one_id
        LEFT JOIN {$companiesTable} co ON co.id = oo.company_id
        WHERE {$where}
        ORDER BY s.id DESC
        LIMIT %d OFFSET %d
    ";

    $listParams = array_merge($params, [$perPage, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($listSql, ...$listParams));

    echo '<p class="description">';
    esc_html_e('Every AI Meeting Synthesis generation is stored here. Regenerating in the wizard keeps previous versions (read-only).', 'xfusion');
    echo '</p>';

    if ($conversationFilter > 0) {
        echo '<p><a href="' . esc_url(xfusion_oo_synthesis_history_admin_url()) . '">&larr; ';
        esc_html_e('Show all conversations', 'xfusion');
        echo '</a></p>';
        echo '<p class="description">' . esc_html(sprintf(
            /* translators: %d: conversation id */
            __('Filtered to conversation #%d.', 'xfusion'),
            $conversationFilter
        )) . '</p>';
    }

    if ($rows === []) {
        echo '<p>' . esc_html__('No AI Meeting Syntheses have been generated yet.', 'xfusion') . '</p>';

        return;
    }

    $userLabel = 'xfusion_oo_brief_history_user_label';

    echo '<table class="widefat striped xfusion-oo-synthesis-history-list">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('ID', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Meeting', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Pair', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Company', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Model', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Tokens', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Est. cost', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Generated', 'xfusion') . '</th>';
    echo '<th>' . esc_html__('Version', 'xfusion') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $viewUrl = xfusion_oo_synthesis_history_admin_url((int) $row->id);
        $conversationUrl = xfusion_oo_synthesis_history_admin_url(0, (int) $row->conversation_id);
        $leader = function_exists($userLabel) ? $userLabel((int) $row->leader_user_id) : '#' . (int) $row->leader_user_id;
        $employee = function_exists($userLabel) ? $userLabel((int) $row->employee_user_id) : '#' . (int) $row->employee_user_id;
        $isLatest = (int) $row->id === (int) $row->latest_synthesis_id;
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
        echo '<td><a href="' . esc_url($conversationUrl) . '">#' . (int) $row->conversation_id . '</a>';
        if (! empty($row->scheduled_at)) {
            echo '<br><span class="description">' . esc_html((string) $row->scheduled_at) . '</span>';
        }
        echo '</td>';
        echo '<td>' . esc_html($leader) . ' / ' . esc_html($employee) . '</td>';
        echo '<td>' . esc_html((string) ($row->company_title ?? '—')) . '</td>';
        echo '<td><code>' . esc_html($model) . '</code></td>';
        echo '<td>' . esc_html(number_format_i18n((int) $row->tokens_used)) . '</td>';
        echo '<td>' . esc_html($costLabel) . '</td>';
        echo '<td>' . esc_html((string) $row->created_at) . '</td>';
        echo '<td>' . ($isLatest
            ? '<span class="xfusion-oo-synthesis-badge xfusion-oo-synthesis-badge--latest">' . esc_html__('Latest', 'xfusion') . '</span>'
            : '<span class="xfusion-oo-synthesis-badge">' . esc_html__('Archived', 'xfusion') . '</span>') . '</td>';
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

function xfusion_oo_synthesis_history_admin_detail_page(int $synthesisId): void
{
    global $wpdb;

    $synthesesTable = xfusion_oo_synthesis_history_table_name();
    $conversationsTable = $wpdb->prefix . 'fusion_one_on_one_conversations';
    $pairsTable = $wpdb->prefix . 'fusion_one_on_ones';
    $companiesTable = $wpdb->prefix . 'companies';

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT
            s.id,
            s.conversation_id,
            s.synthesis,
            s.insight_model,
            s.tokens_used,
            s.cost_usd,
            s.created_at,
            c.scheduled_at,
            oo.leader_user_id,
            oo.employee_user_id,
            co.title AS company_title,
            (SELECT MAX(s2.id) FROM {$synthesesTable} s2 WHERE s2.conversation_id = s.conversation_id) AS latest_synthesis_id
        FROM {$synthesesTable} s
        INNER JOIN {$conversationsTable} c ON c.id = s.conversation_id
        INNER JOIN {$pairsTable} oo ON oo.id = c.one_on_one_id
        LEFT JOIN {$companiesTable} co ON co.id = oo.company_id
        WHERE s.id = %d
        LIMIT 1",
        $synthesisId
    ));

    echo '<p><a href="' . esc_url(xfusion_oo_synthesis_history_admin_url()) . '">&larr; ';
    esc_html_e('Back to list', 'xfusion');
    echo '</a></p>';

    if ($row === null) {
        echo '<div class="notice notice-error inline"><p>';
        esc_html_e('Synthesis record not found.', 'xfusion');
        echo '</p></div>';

        return;
    }

    $userLabel = 'xfusion_oo_brief_history_user_label';
    $normalize = 'xfusion_oo_brief_history_normalize_section';
    $conversationId = (int) $row->conversation_id;
    $isLatest = (int) $row->id === (int) $row->latest_synthesis_id;
    $leader = function_exists($userLabel) ? $userLabel((int) $row->leader_user_id) : '#' . (int) $row->leader_user_id;
    $employee = function_exists($userLabel) ? $userLabel((int) $row->employee_user_id) : '#' . (int) $row->employee_user_id;
    $model = trim((string) ($row->insight_model ?? ''));
    $costUsd = (float) ($row->cost_usd ?? 0);
    $costLabel = function_exists('xfusion_llm_format_cost_usd')
        ? xfusion_llm_format_cost_usd($costUsd)
        : '$' . number_format($costUsd, 4);

    $synthesis = json_decode((string) $row->synthesis, true);
    if (! is_array($synthesis)) {
        $synthesis = [];
    }

    echo '<div class="xfusion-oo-synthesis-detail">';

    echo '<h2>' . esc_html(sprintf(
        /* translators: %d: synthesis id */
        __('Synthesis #%d', 'xfusion'),
        (int) $row->id
    ));
    if ($isLatest) {
        echo ' <span class="xfusion-oo-synthesis-badge xfusion-oo-synthesis-badge--latest">' . esc_html__('Latest for this meeting', 'xfusion') . '</span>';
    } else {
        echo ' <span class="xfusion-oo-synthesis-badge">' . esc_html__('Archived version', 'xfusion') . '</span>';
    }
    echo '</h2>';

    echo '<dl class="xfusion-oo-synthesis-meta">';
    echo '<dt>' . esc_html__('Conversation', 'xfusion') . '</dt>';
    echo '<dd><a href="' . esc_url(xfusion_oo_synthesis_history_admin_url(0, $conversationId)) . '">#' . esc_html((string) $conversationId) . '</a></dd>';
    echo '<dt>' . esc_html__('Pair', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html($leader) . ' / ' . esc_html($employee) . '</dd>';
    echo '<dt>' . esc_html__('Company', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html((string) ($row->company_title ?? '—')) . '</dd>';
    if (! empty($row->scheduled_at)) {
        echo '<dt>' . esc_html__('Scheduled', 'xfusion') . '</dt>';
        echo '<dd>' . esc_html((string) $row->scheduled_at) . '</dd>';
    }
    echo '<dt>' . esc_html__('Model', 'xfusion') . '</dt>';
    echo '<dd><code>' . esc_html($model !== '' ? $model : '—') . '</code></dd>';
    echo '<dt>' . esc_html__('Tokens', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html(number_format_i18n((int) $row->tokens_used)) . '</dd>';
    echo '<dt>' . esc_html__('Est. cost', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html($costLabel) . '</dd>';
    echo '<dt>' . esc_html__('Generated', 'xfusion') . '</dt>';
    echo '<dd>' . esc_html((string) $row->created_at) . '</dd>';
    echo '</dl>';

    xfusion_oo_synthesis_history_render_conversation_versions($conversationId, (int) $row->id);

    $labels = xfusion_oo_synthesis_history_section_labels();
    echo '<div class="xfusion-oo-synthesis-sections">';
    echo '<h3>' . esc_html__('Synthesis content', 'xfusion') . '</h3>';

    foreach ($labels as $key => $label) {
        if (! array_key_exists($key, $synthesis)) {
            continue;
        }
        $raw = $synthesis[$key];
        echo '<div class="xfusion-oo-synthesis-section">';
        echo '<h4>' . esc_html($label) . '</h4>';

        if ($key === 'alignment_summary' && is_array($raw)) {
            $score = isset($raw['score']) && is_numeric($raw['score']) ? (float) $raw['score'] : null;
            $alignLabel = trim((string) ($raw['label'] ?? ''));
            if ($score !== null) {
                echo '<p><strong>' . esc_html(number_format($score, 1)) . ' / 5</strong>';
                if ($alignLabel !== '') {
                    echo ' — ' . esc_html($alignLabel);
                }
                echo '</p>';
            } elseif ($alignLabel !== '') {
                echo '<p>' . esc_html($alignLabel) . '</p>';
            }
        }

        if ($key === 'commitment_summary' && is_array($raw)) {
            $counts = array_filter([
                isset($raw['employee_count']) ? sprintf(__('Employee: %d', 'xfusion'), (int) $raw['employee_count']) : '',
                isset($raw['leader_count']) ? sprintf(__('Leader: %d', 'xfusion'), (int) $raw['leader_count']) : '',
                isset($raw['open_count']) ? sprintf(__('Open: %d', 'xfusion'), (int) $raw['open_count']) : '',
            ]);
            if ($counts !== []) {
                echo '<p class="description">' . esc_html(implode(' · ', $counts)) . '</p>';
            }
        }

        $normalized = function_exists($normalize)
            ? $normalize($raw)
            : ['items' => [], 'details' => ''];

        if ($normalized['items'] !== []) {
            echo '<ul>';
            foreach ($normalized['items'] as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul>';
        }

        if ($normalized['details'] !== '' && $normalized['details'] !== implode("\n\n", $normalized['items'])) {
            echo '<div class="xfusion-oo-synthesis-details">';
            echo wpautop(esc_html($normalized['details']));
            echo '</div>';
        }

        if ($normalized['items'] === [] && $normalized['details'] === '' && $key !== 'alignment_summary' && $key !== 'commitment_summary') {
            echo '<p class="description">' . esc_html__('No content for this section.', 'xfusion') . '</p>';
        }

        echo '</div>';
    }

    echo '</div>';

    echo '<details class="xfusion-oo-synthesis-raw" style="margin-top:1.5rem">';
    echo '<summary>' . esc_html__('Raw JSON', 'xfusion') . '</summary>';
    echo '<pre class="xfusion-oo-synthesis-json">' . esc_html(wp_json_encode($synthesis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    echo '</details>';

    echo '</div>';
}

function xfusion_oo_synthesis_history_render_conversation_versions(int $conversationId, int $currentSynthesisId): void
{
    global $wpdb;

    $synthesesTable = xfusion_oo_synthesis_history_table_name();
    $versions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, insight_model, tokens_used, created_at
         FROM {$synthesesTable}
         WHERE conversation_id = %d
         ORDER BY id DESC",
        $conversationId
    ));

    if ($versions === [] || count($versions) < 2) {
        return;
    }

    echo '<div class="xfusion-oo-synthesis-versions">';
    echo '<h3>' . esc_html__('Other versions for this meeting', 'xfusion') . '</h3>';
    echo '<ul>';
    foreach ($versions as $version) {
        $id = (int) $version->id;
        if ($id === $currentSynthesisId) {
            continue;
        }
        $url = xfusion_oo_synthesis_history_admin_url($id);
        echo '<li><a href="' . esc_url($url) . '">#' . esc_html((string) $id) . '</a> — ';
        echo esc_html((string) $version->created_at);
        echo ' · <code>' . esc_html((string) ($version->insight_model ?? '—')) . '</code>';
        echo ' · ' . esc_html(number_format_i18n((int) $version->tokens_used)) . ' tokens';
        echo '</li>';
    }
    echo '</ul></div>';
}

add_action('admin_head', static function (): void {
    if (! isset($_GET['page']) || (string) $_GET['page'] !== 'xfusion-oo-synthesis-history') {
        return;
    }
    echo '<style>
        .xfusion-oo-synthesis-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:#e5e7eb;color:#374151}
        .xfusion-oo-synthesis-badge--latest{background:#dcfce7;color:#166534}
        .xfusion-oo-synthesis-meta{display:grid;grid-template-columns:160px 1fr;gap:.35rem 1rem;max-width:720px;margin:1rem 0 1.5rem}
        .xfusion-oo-synthesis-meta dt{font-weight:600;margin:0}
        .xfusion-oo-synthesis-meta dd{margin:0}
        .xfusion-oo-synthesis-sections{margin-top:1.5rem}
        .xfusion-oo-synthesis-section{border:1px solid #dcdcde;border-radius:4px;padding:1rem;margin-bottom:1rem;background:#fff}
        .xfusion-oo-synthesis-section h4{margin:0 0 .5rem}
        .xfusion-oo-synthesis-details{margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f0f0f1;font-size:13px;color:#50575e}
        .xfusion-oo-synthesis-json{max-height:480px;overflow:auto;background:#f6f7f7;padding:1rem;border:1px solid #dcdcde}
        .xfusion-oo-synthesis-versions ul{margin:.5rem 0 0 1.2rem}
    </style>';
});
