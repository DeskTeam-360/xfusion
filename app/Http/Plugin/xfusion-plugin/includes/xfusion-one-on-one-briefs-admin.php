<?php
/**
 * wp-admin: AI Meeting Brief history (all generated versions).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('admin_menu', 'xfusion_oo_brief_history_register_admin_menu', 82);

function xfusion_oo_brief_history_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'fusion_one_on_one_ai_briefs';
}

function xfusion_oo_brief_history_admin_url(int $briefId = 0, int $conversationId = 0): string
{
    $args = ['page' => 'xfusion-oo-brief-history'];
    if ($briefId > 0) {
        $args['id'] = $briefId;
    }
    if ($conversationId > 0 && $briefId === 0) {
        $args['conversation_id'] = $conversationId;
    }

    return add_query_arg($args, admin_url('admin.php'));
}

function xfusion_oo_brief_history_user_label(int $userId): string
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

/**
 * @return array<string, string>
 */
function xfusion_oo_brief_history_section_labels(): array
{
    return [
        'alignment_snapshot' => __('Alignment Snapshot™', 'xfusion'),
        'development_snapshot' => __('Development Snapshot™', 'xfusion'),
        'commitment_review' => __('Commitment Review™', 'xfusion'),
        'behavioral_trends' => __('Behavioral Trends™', 'xfusion'),
        'suggested_discussion_areas' => __('Suggested Discussion Areas™', 'xfusion'),
        'emerging_opportunities' => __('Emerging Opportunities™', 'xfusion'),
        'potential_barriers' => __('Potential Barriers™', 'xfusion'),
    ];
}

/**
 * @param mixed $raw
 * @return array{items: list<string>, details: string}
 */
function xfusion_oo_brief_history_normalize_section($raw): array
{
    if ($raw === null || $raw === '') {
        return ['items' => [], 'details' => ''];
    }
    if (is_array($raw)) {
        $isList = $raw === [] || array_keys($raw) === range(0, count($raw) - 1);
        if ($isList) {
            $items = array_map(static fn ($v) => is_scalar($v) ? (string) $v : wp_json_encode($v), $raw);

            return ['items' => $items, 'details' => implode("\n\n", $items)];
        }
        $items = [];
        if (isset($raw['items']) && is_array($raw['items'])) {
            $items = array_map(static fn ($v) => is_scalar($v) ? (string) $v : wp_json_encode($v), $raw['items']);
        }
        $details = isset($raw['details']) ? trim((string) $raw['details']) : '';

        return ['items' => $items, 'details' => $details !== '' ? $details : implode("\n\n", $items)];
    }

    return ['items' => [(string) $raw], 'details' => (string) $raw];
}

function xfusion_oo_brief_history_register_admin_menu(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    add_submenu_page(
        'xfusion-llm-prompts',
        __('1-on-1 Brief History', 'xfusion'),
        __('1-on-1 Brief History', 'xfusion'),
        'manage_options',
        'xfusion-oo-brief-history',
        'xfusion_oo_brief_history_admin_page'
    );
}

function xfusion_oo_brief_history_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'xfusion'));
    }

    $briefId = isset($_GET['id']) ? absint((string) $_GET['id']) : 0;

    echo '<div class="wrap xfusion-oo-brief-history-wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';

    if ($briefId > 0) {
        xfusion_oo_brief_history_admin_detail_page($briefId);
        echo '</div>';

        return;
    }

    xfusion_oo_brief_history_admin_list_page();
    echo '</div>';
}

function xfusion_oo_brief_history_admin_list_page(): void
{
    global $wpdb;

    $briefsTable = xfusion_oo_brief_history_table_name();
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
        $where .= ' AND b.conversation_id = %d';
        $params[] = $conversationFilter;
    }

    $countSql = "SELECT COUNT(*) FROM {$briefsTable} b WHERE {$where}";
    $total = $params === []
        ? (int) $wpdb->get_var($countSql)
        : (int) $wpdb->get_var($wpdb->prepare($countSql, ...$params));

    $listSql = "
        SELECT
            b.id,
            b.conversation_id,
            b.insight_model,
            b.tokens_used,
            b.cost_usd,
            b.created_at,
            c.scheduled_at,
            c.status AS conversation_status,
            oo.leader_user_id,
            oo.employee_user_id,
            co.title AS company_title,
            (SELECT MAX(b2.id) FROM {$briefsTable} b2 WHERE b2.conversation_id = b.conversation_id) AS latest_brief_id
        FROM {$briefsTable} b
        INNER JOIN {$conversationsTable} c ON c.id = b.conversation_id
        INNER JOIN {$pairsTable} oo ON oo.id = c.one_on_one_id
        LEFT JOIN {$companiesTable} co ON co.id = oo.company_id
        WHERE {$where}
        ORDER BY b.id DESC
        LIMIT %d OFFSET %d
    ";

    $listParams = array_merge($params, [$perPage, $offset]);
    $rows = $wpdb->get_results($wpdb->prepare($listSql, ...$listParams));

    echo '<p class="description">';
    esc_html_e('Every AI Meeting Brief generation is stored here. Regenerating in the wizard keeps previous versions (read-only).', 'xfusion');
    echo '</p>';

    if ($conversationFilter > 0) {
        echo '<p><a href="' . esc_url(xfusion_oo_brief_history_admin_url()) . '">&larr; ';
        esc_html_e('Show all conversations', 'xfusion');
        echo '</a></p>';
        echo '<p class="description">' . esc_html(sprintf(
            /* translators: %d: conversation id */
            __('Filtered to conversation #%d.', 'xfusion'),
            $conversationFilter
        )) . '</p>';
    }

    if ($rows === []) {
        echo '<p>' . esc_html__('No AI Meeting Briefs have been generated yet.', 'xfusion') . '</p>';

        return;
    }

    echo '<table class="widefat striped xfusion-oo-brief-history-list">';
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
        $viewUrl = xfusion_oo_brief_history_admin_url((int) $row->id);
        $conversationUrl = xfusion_oo_brief_history_admin_url(0, (int) $row->conversation_id);
        $leader = xfusion_oo_brief_history_user_label((int) $row->leader_user_id);
        $employee = xfusion_oo_brief_history_user_label((int) $row->employee_user_id);
        $isLatest = (int) $row->id === (int) $row->latest_brief_id;
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
            ? '<span class="xfusion-oo-brief-badge xfusion-oo-brief-badge--latest">' . esc_html__('Latest', 'xfusion') . '</span>'
            : '<span class="xfusion-oo-brief-badge">' . esc_html__('Archived', 'xfusion') . '</span>') . '</td>';
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

function xfusion_oo_brief_history_admin_detail_page(int $briefId): void
{
    global $wpdb;

    $briefsTable = xfusion_oo_brief_history_table_name();
    $conversationsTable = $wpdb->prefix . 'fusion_one_on_one_conversations';
    $pairsTable = $wpdb->prefix . 'fusion_one_on_ones';
    $companiesTable = $wpdb->prefix . 'companies';

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT
            b.id,
            b.conversation_id,
            b.brief,
            b.insight_model,
            b.tokens_used,
            b.cost_usd,
            b.created_at,
            c.scheduled_at,
            c.status AS conversation_status,
            oo.leader_user_id,
            oo.employee_user_id,
            co.title AS company_title,
            (SELECT MAX(b2.id) FROM {$briefsTable} b2 WHERE b2.conversation_id = b.conversation_id) AS latest_brief_id
        FROM {$briefsTable} b
        INNER JOIN {$conversationsTable} c ON c.id = b.conversation_id
        INNER JOIN {$pairsTable} oo ON oo.id = c.one_on_one_id
        LEFT JOIN {$companiesTable} co ON co.id = oo.company_id
        WHERE b.id = %d
        LIMIT 1",
        $briefId
    ));

    $backUrl = xfusion_oo_brief_history_admin_url();

    echo '<p><a href="' . esc_url($backUrl) . '">&larr; ';
    esc_html_e('Back to list', 'xfusion');
    echo '</a></p>';

    if ($row === null) {
        echo '<div class="notice notice-error inline"><p>';
        esc_html_e('Brief record not found.', 'xfusion');
        echo '</p></div>';

        return;
    }

    $conversationId = (int) $row->conversation_id;
    $isLatest = (int) $row->id === (int) $row->latest_brief_id;
    $leader = xfusion_oo_brief_history_user_label((int) $row->leader_user_id);
    $employee = xfusion_oo_brief_history_user_label((int) $row->employee_user_id);
    $model = trim((string) ($row->insight_model ?? ''));
    $costUsd = (float) ($row->cost_usd ?? 0);
    $costLabel = function_exists('xfusion_llm_format_cost_usd')
        ? xfusion_llm_format_cost_usd($costUsd)
        : '$' . number_format($costUsd, 4);

    $brief = json_decode((string) $row->brief, true);
    if (! is_array($brief)) {
        $brief = [];
    }

    echo '<div class="xfusion-oo-brief-detail">';

    echo '<h2>' . esc_html(sprintf(
        /* translators: %d: brief id */
        __('Brief #%d', 'xfusion'),
        (int) $row->id
    ));
    if ($isLatest) {
        echo ' <span class="xfusion-oo-brief-badge xfusion-oo-brief-badge--latest">' . esc_html__('Latest for this meeting', 'xfusion') . '</span>';
    } else {
        echo ' <span class="xfusion-oo-brief-badge">' . esc_html__('Archived version', 'xfusion') . '</span>';
    }
    echo '</h2>';

    echo '<dl class="xfusion-oo-brief-meta">';
    echo '<dt>' . esc_html__('Conversation', 'xfusion') . '</dt>';
    echo '<dd><a href="' . esc_url(xfusion_oo_brief_history_admin_url(0, $conversationId)) . '">#' . esc_html((string) $conversationId) . '</a></dd>';
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

    xfusion_oo_brief_history_render_conversation_versions($conversationId, (int) $row->id);
    xfusion_oo_brief_history_render_brief_sections($brief);

    echo '<details class="xfusion-oo-brief-raw" style="margin-top:1.5rem">';
    echo '<summary>' . esc_html__('Raw JSON', 'xfusion') . '</summary>';
    echo '<pre class="xfusion-oo-brief-json">' . esc_html(wp_json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    echo '</details>';

    echo '</div>';
}

function xfusion_oo_brief_history_render_conversation_versions(int $conversationId, int $currentBriefId): void
{
    global $wpdb;

    $briefsTable = xfusion_oo_brief_history_table_name();
    $versions = $wpdb->get_results($wpdb->prepare(
        "SELECT id, insight_model, tokens_used, created_at
         FROM {$briefsTable}
         WHERE conversation_id = %d
         ORDER BY id DESC",
        $conversationId
    ));

    if ($versions === [] || count($versions) < 2) {
        return;
    }

    echo '<div class="xfusion-oo-brief-versions">';
    echo '<h3>' . esc_html__('Other versions for this meeting', 'xfusion') . '</h3>';
    echo '<ul>';
    foreach ($versions as $version) {
        $id = (int) $version->id;
        if ($id === $currentBriefId) {
            continue;
        }
        $url = xfusion_oo_brief_history_admin_url($id);
        echo '<li><a href="' . esc_url($url) . '">#' . esc_html((string) $id) . '</a> — ';
        echo esc_html((string) $version->created_at);
        echo ' · <code>' . esc_html((string) ($version->insight_model ?? '—')) . '</code>';
        echo ' · ' . esc_html(number_format_i18n((int) $version->tokens_used)) . ' tokens';
        echo '</li>';
    }
    echo '</ul></div>';
}

/**
 * @param array<string, mixed> $brief
 */
function xfusion_oo_brief_history_render_brief_sections(array $brief): void
{
    $labels = xfusion_oo_brief_history_section_labels();

    echo '<div class="xfusion-oo-brief-sections">';
    echo '<h3>' . esc_html__('Brief content', 'xfusion') . '</h3>';

    foreach ($labels as $key => $label) {
        if (! array_key_exists($key, $brief)) {
            continue;
        }
        $normalized = xfusion_oo_brief_history_normalize_section($brief[$key]);
        echo '<div class="xfusion-oo-brief-section">';
        echo '<h4>' . esc_html($label) . '</h4>';

        if ($normalized['items'] !== []) {
            echo '<ul>';
            foreach ($normalized['items'] as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul>';
        }

        if ($normalized['details'] !== '' && $normalized['details'] !== implode("\n\n", $normalized['items'])) {
            echo '<div class="xfusion-oo-brief-details">';
            echo wpautop(esc_html($normalized['details']));
            echo '</div>';
        }

        if ($normalized['items'] === [] && $normalized['details'] === '') {
            echo '<p class="description">' . esc_html__('No content for this section.', 'xfusion') . '</p>';
        }

        echo '</div>';
    }

    echo '</div>';
}

add_action('admin_head', static function (): void {
    if (! isset($_GET['page']) || (string) $_GET['page'] !== 'xfusion-oo-brief-history') {
        return;
    }
    echo '<style>
        .xfusion-oo-brief-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:#e5e7eb;color:#374151}
        .xfusion-oo-brief-badge--latest{background:#dcfce7;color:#166534}
        .xfusion-oo-brief-meta{display:grid;grid-template-columns:160px 1fr;gap:.35rem 1rem;max-width:720px;margin:1rem 0 1.5rem}
        .xfusion-oo-brief-meta dt{font-weight:600;margin:0}
        .xfusion-oo-brief-meta dd{margin:0}
        .xfusion-oo-brief-sections{margin-top:1.5rem}
        .xfusion-oo-brief-section{border:1px solid #dcdcde;border-radius:4px;padding:1rem;margin-bottom:1rem;background:#fff}
        .xfusion-oo-brief-section h4{margin:0 0 .5rem}
        .xfusion-oo-brief-details{margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f0f0f1;font-size:13px;color:#50575e}
        .xfusion-oo-brief-json{max-height:480px;overflow:auto;background:#f6f7f7;padding:1rem;border:1px solid #dcdcde}
        .xfusion-oo-brief-versions ul{margin:.5rem 0 0 1.2rem}
    </style>';
});
