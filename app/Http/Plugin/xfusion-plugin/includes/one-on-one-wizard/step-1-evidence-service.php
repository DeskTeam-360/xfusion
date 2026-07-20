<?php
/**
 * Step 1 — Continuous Evidence™ data loaders (read-only accordion).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

function xfoo_wizard_format_evidence_datetime(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '';
    }

    try {
        $dt = new DateTimeImmutable($value);

        return $dt->format('M j, Y · g:i A');
    } catch (Throwable) {
        return $value;
    }
}

function xfoo_wizard_format_evidence_status_label(string $status): string
{
    $key = strtolower(str_replace(' ', '_', trim($status)));

    return match ($key) {
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'done' => 'Done',
        'open' => 'Open',
        default => ucwords(str_replace('_', ' ', $key)),
    };
}

/** @return array<string, string> */
function xfoo_wizard_behavioral_driver_labels(): array
{
    return [
        'get_real' => 'Get Real™',
        'fill_buckets' => 'Fill Buckets™',
        'be_intentional' => 'Be Intentional™',
        'foster_grit' => 'Foster Grit™',
        'drive_growth' => 'Drive Growth™',
    ];
}

/** @return array<string, string> */
function xfoo_wizard_self_assessment_labels(): array
{
    return [
        'alignment' => 'Alignment',
        'accountability' => 'Accountability',
        'communication' => 'Communication',
        'leadership' => 'Leadership',
        'execution' => 'Execution',
    ];
}

/**
 * Latest unified evaluation metadata for an employee.
 *
 * @return array{evaluated_at: string}
 */
function xfoo_wizard_evidence_unified_meta(int $userId): array
{
    if ($userId < 1 || ! function_exists('xfusion_result_evaluation_latest_unified')) {
        return ['evaluated_at' => ''];
    }

    $latest = xfusion_result_evaluation_latest_unified($userId);
    if ($latest === null) {
        return ['evaluated_at' => ''];
    }

    return ['evaluated_at' => (string) ($latest['evaluated_at'] ?? '')];
}

/**
 * FUSION Performance Insight — Recommended Focus Area.
 *
 * @return array{recommended_focus_area: string, evaluated_at: string}
 */
function xfoo_wizard_evidence_individual_insights(int $userId): array
{
    $meta = xfoo_wizard_evidence_unified_meta($userId);
    $focus = '';

    if ($userId > 0 && function_exists('xfusion_cor_unified_recommended_focus_area_for_user')) {
        $focus = xfusion_cor_unified_recommended_focus_area_for_user($userId);
    }

    return [
        'recommended_focus_area' => $focus,
        'evaluated_at' => $meta['evaluated_at'],
    ];
}

/**
 * Latest Overall Insight (key_observation) from unified evaluation.
 *
 * @return array{key_observation: string, evaluated_at: string}
 */
function xfoo_wizard_evidence_ai_insight(int $userId): array
{
    $meta = xfoo_wizard_evidence_unified_meta($userId);
    $text = '';

    if ($userId > 0 && function_exists('xfusion_cor_unified_key_observation_for_user')) {
        $text = xfusion_cor_unified_key_observation_for_user($userId);
    }

    return [
        'key_observation' => $text,
        'evaluated_at' => $meta['evaluated_at'],
    ];
}

/**
 * Recent GF submissions linked to course lists (activities or tools).
 *
 * @return list<array{entry_id: int, title: string, page_url: string, submitted_at: string}>
 */
function xfoo_wizard_evidence_recent_submissions(int $userId, bool $tools, int $limit = 3): array
{
    global $wpdb;

    if ($userId < 1 || $limit < 1) {
        return [];
    }

    $sql = "
        SELECT e.id AS entry_id, e.date_created, e.source_url, cl.page_title, cl.url
        FROM {$wpdb->prefix}gf_entry e
        INNER JOIN {$wpdb->prefix}course_lists cl ON cl.wp_gf_form_id = e.form_id
        INNER JOIN {$wpdb->prefix}course_group_details cgd ON cgd.course_list_id = cl.id
        INNER JOIN {$wpdb->prefix}course_groups cg ON cg.id = cgd.course_group_id AND cg.tools = %d
        WHERE e.created_by = %d AND e.status = 'active'
        ORDER BY e.date_created DESC
        LIMIT %d
    ";

    $rows = $wpdb->get_results($wpdb->prepare($sql, $tools ? 1 : 0, $userId, $limit), ARRAY_A);
    if (! is_array($rows)) {
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $entryId = (int) ($row['entry_id'] ?? 0);
        $baseUrl = trim((string) ($row['url'] ?? ''));
        if ($baseUrl === '') {
            $baseUrl = trim((string) ($row['source_url'] ?? ''));
        }

        $pageUrl = $baseUrl;
        if ($pageUrl !== '' && $entryId > 0) {
            $pageUrl = add_query_arg(
                [
                    'dataId' => $entryId,
                    'btn-close' => 'true',
                ],
                $pageUrl
            );
        }

        $out[] = [
            'entry_id' => $entryId,
            'title' => (string) ($row['page_title'] ?? 'Untitled'),
            'page_url' => $pageUrl,
            'submitted_at' => (string) ($row['date_created'] ?? ''),
        ];
    }

    return $out;
}

/**
 * Course scoring group averages for a fixed label map (behavioral drivers or self-assessments).
 *
 * @param  array<string, string>  $labels
 * @return list<array{slug: string, title: string, average: float|null}>
 */
function xfoo_wizard_evidence_scoring_groups_for_user(int $userId, array $labels): array
{
    global $wpdb;

    if ($userId < 1 || $labels === [] || ! function_exists('xfusion_csg_group_score_stats') || ! function_exists('xfusion_cor_unified_category_key')) {
        return [];
    }

    $groups = $wpdb->get_results(
        "SELECT id, title FROM {$wpdb->prefix}course_scoring_groups ORDER BY id ASC",
        ARRAY_A
    );

    if (! is_array($groups)) {
        return [];
    }

    $bySlug = [];
    foreach ($groups as $group) {
        $groupId = (int) ($group['id'] ?? 0);
        $title = (string) ($group['title'] ?? '');
        $slug = xfusion_cor_unified_category_key($title);

        if (! isset($labels[$slug]) || $groupId < 1) {
            continue;
        }

        $stats = xfusion_csg_group_score_stats($groupId, $userId);
        $bySlug[$slug] = [
            'slug' => $slug,
            'title' => $labels[$slug],
            'average' => $stats['average'] ?? null,
        ];
    }

    $ordered = [];
    foreach (array_keys($labels) as $slug) {
        if (isset($bySlug[$slug])) {
            $ordered[] = $bySlug[$slug];
        }
    }

    return $ordered;
}

/**
 * FUSION Behavioral Driver scores (5 drivers).
 *
 * @return list<array{slug: string, title: string, average: float|null}>
 */
function xfoo_wizard_evidence_behavioral_drivers(int $userId): array
{
    return xfoo_wizard_evidence_scoring_groups_for_user($userId, xfoo_wizard_behavioral_driver_labels());
}

/**
 * Self-assessment dimension scores (Alignment, Accountability, Communication, Leadership, Execution).
 *
 * @return list<array{slug: string, title: string, average: float|null}>
 */
function xfoo_wizard_evidence_self_assessments(int $userId): array
{
    return xfoo_wizard_evidence_scoring_groups_for_user($userId, xfoo_wizard_self_assessment_labels());
}

/**
 * Employee-centric evidence blocks (insights, activities, scores, tools).
 *
 * @return array<string, mixed>
 */
function xfoo_wizard_evidence_employee_blocks(int $employeeId): array
{
    if ($employeeId < 1) {
        return [
            'individual_insights' => ['recommended_focus_area' => '', 'evaluated_at' => ''],
            'activities' => [],
            'behavioral_drivers' => [],
            'self_assessments' => [],
            'ai_insight' => ['key_observation' => '', 'evaluated_at' => ''],
            'development_tools' => [],
        ];
    }

    return [
        'individual_insights' => xfoo_wizard_evidence_individual_insights($employeeId),
        'activities' => xfoo_wizard_evidence_recent_submissions($employeeId, false, 3),
        'behavioral_drivers' => xfoo_wizard_evidence_behavioral_drivers($employeeId),
        'self_assessments' => xfoo_wizard_evidence_self_assessments($employeeId),
        'ai_insight' => xfoo_wizard_evidence_ai_insight($employeeId),
        'development_tools' => xfoo_wizard_evidence_recent_submissions($employeeId, true, 3),
    ];
}

/**
 * Build labeled field rows for read-only display.
 *
 * @param  array<string, string>  $values
 * @param  array<string, string>  $labels
 * @return list<array{slug: string, label: string, value: string, type: string}>
 */
function xfoo_wizard_evidence_format_fields(array $values, array $labels, string $role = ''): array
{
    $config = $role !== '' ? xfoo_preparation_gf_role_config($role) : null;
    $rows = [];

    foreach ($labels as $slug => $label) {
        $value = trim((string) ($values[$slug] ?? ''));
        if ($value === '') {
            continue;
        }

        $type = 'text';
        if ($config !== null && isset($config['fields'][$slug]['type'])) {
            $type = (string) $config['fields'][$slug]['type'];
        } elseif (isset(xfoo_conversation_gf_mapping()['fields'][$slug]['type'])) {
            $type = (string) xfoo_conversation_gf_mapping()['fields'][$slug]['type'];
        }

        $rows[] = [
            'slug' => $slug,
            'label' => $label,
            'value' => $value,
            'type' => $type,
        ];
    }

    return $rows;
}

/**
 * Step 3–5 snapshot for one prior conversation.
 *
 * @return array<string, mixed>
 */
function xfoo_wizard_evidence_meeting_detail(int $conversationId): array
{
    $prepLabels = xfoo_preparation_gf_field_labels_for_js();
    $convLabels = xfoo_conversation_gf_field_labels();

    $draft = xfoo_wizard_load_draft_data($conversationId, 'evidence');
    $employeeValues = $draft['employee'];
    $leaderValues = $draft['leader'];
    $conversationValues = $draft['conversation'];

    $commitments = xfoo_wizard_get_commitments($conversationId);
    $commitmentRows = [];
    if ($commitments['success']) {
        $formatted = xfoo_wizard_format_commitments_for_ui($commitments['data'] ?? []);
        $driverLabels = xfoo_wizard_behavioral_driver_labels();
        foreach (['employee', 'leader'] as $ownerRole) {
            foreach ($formatted[$ownerRole] ?? [] as $row) {
                $driver = (string) ($row['behavioral_driver'] ?? '');
                $commitmentRows[] = array_merge($row, [
                    'owner_role' => $ownerRole,
                    'behavioral_driver_label' => $driverLabels[$driver] ?? $driver,
                ]);
            }
        }
    }

    return [
        'conversation_id' => $conversationId,
        'preparation' => [
            'employee' => xfoo_wizard_evidence_format_fields($employeeValues, $prepLabels['employee'] ?? [], 'employee'),
            'leader' => xfoo_wizard_evidence_format_fields($leaderValues, $prepLabels['leader'] ?? [], 'leader'),
        ],
        'conversation' => xfoo_wizard_evidence_format_fields($conversationValues, $convLabels),
        'commitments' => $commitmentRows,
    ];
}

/**
 * Resolve employee user ID for a conversation (WP DB fallback when API unavailable).
 */
function xfoo_wizard_evidence_employee_id_for_conversation(int $conversationId): int
{
    if ($conversationId < 1) {
        return 0;
    }

    global $wpdb;

    $employeeId = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT oo.employee_user_id
         FROM wp_fusion_one_on_one_conversations c
         INNER JOIN wp_fusion_one_on_ones oo ON oo.id = c.one_on_one_id
         WHERE c.id = %d
         LIMIT 1',
        $conversationId
    ));

    return $employeeId > 0 ? $employeeId : 0;
}

/**
 * Empty evidence payload with optional employee-centric blocks.
 *
 * @return array<string, mixed>
 */
function xfoo_wizard_evidence_empty_payload(int $conversationId, int $employeeId = 0): array
{
    if ($employeeId < 1) {
        $employeeId = xfoo_wizard_evidence_employee_id_for_conversation($conversationId);
    }

    return array_merge(
        [
            'employee_id' => $employeeId,
            'current_conversation_id' => $conversationId,
            'previous_meetings' => [],
            'commitments' => [],
        ],
        xfoo_wizard_evidence_employee_blocks($employeeId)
    );
}

/**
 * Evidence summary from Laravel + meeting detail payloads for all prior meetings.
 *
 * @return array{success: bool, message?: string, data?: array<string, mixed>}
 */
function xfoo_wizard_load_evidence_summary(int $conversationId): array
{
    if ($conversationId < 1) {
        return ['success' => false, 'message' => 'conversation_id is required.'];
    }

    $employeeId = xfoo_wizard_evidence_employee_id_for_conversation($conversationId);
    $meetings = [];
    $commitments = [];

    $result = xfoo_wizard_fusion_api_request('GET', "/conversations/{$conversationId}/evidence");
    if ($result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        if ((int) ($data['employee_id'] ?? 0) > 0) {
            $employeeId = (int) $data['employee_id'];
        }

        foreach ($data['previous_meetings'] ?? [] as $meeting) {
            if (! is_array($meeting)) {
                continue;
            }
            $mid = (int) ($meeting['id'] ?? 0);
            if ($mid < 1) {
                continue;
            }
            $meetings[] = array_merge($meeting, [
                'detail' => xfoo_wizard_evidence_meeting_detail($mid),
            ]);
        }

        $driverLabels = xfoo_wizard_behavioral_driver_labels();
        foreach ($data['commitments'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $driver = (string) ($row['behavioral_driver'] ?? '');
            $commitments[] = [
                'id' => (int) ($row['id'] ?? 0),
                'conversation_id' => (int) ($row['conversation_id'] ?? 0),
                'title' => (string) ($row['title'] ?? ''),
                'priority' => (string) ($row['priority'] ?? 'medium'),
                'behavioral_driver' => $driver,
                'behavioral_driver_label' => $driverLabels[$driver] ?? $driver,
                'success_indicator' => (string) ($row['success_indicator'] ?? ''),
                'owner_role' => (string) ($row['owner_role'] ?? 'shared'),
                'status' => (string) ($row['status'] ?? 'open'),
                'status_label' => xfoo_wizard_format_evidence_status_label((string) ($row['status'] ?? 'open')),
                'due_date' => (string) ($row['due_date'] ?? ''),
                'due_date_label' => xfoo_wizard_format_evidence_datetime((string) ($row['due_date'] ?? '')) ?: (string) ($row['due_date'] ?? ''),
                'meeting' => is_array($row['meeting'] ?? null) ? $row['meeting'] : [],
            ];
        }
    }

    $employeeBlocks = xfoo_wizard_evidence_employee_blocks($employeeId);

    return [
        'success' => true,
        'data' => array_merge(
            [
                'employee_id' => $employeeId,
                'current_conversation_id' => $conversationId,
                'previous_meetings' => $meetings,
                'commitments' => $commitments,
            ],
            $employeeBlocks
        ),
    ];
}

add_action('wp_ajax_xfoo_wizard_load_evidence', 'xfoo_wizard_ajax_load_evidence');

function xfoo_wizard_ajax_load_evidence(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_GET['conversation_id']) ? absint($_GET['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    $result = xfoo_wizard_load_evidence_summary($conversationId);
    if (! $result['success']) {
        wp_send_json_success(xfoo_wizard_evidence_empty_payload($conversationId));
    }

    wp_send_json_success($result['data'] ?? xfoo_wizard_evidence_empty_payload($conversationId));
}

/**
 * Structured evidence bundle for AI Meeting Brief (all 12 Step 1 sections).
 *
 * @return array<string, mixed>
 */
function xfoo_wizard_evidence_bundle_for_brief(int $conversationId): array
{
    $summary = xfoo_wizard_load_evidence_summary($conversationId);
    $data = $summary['success']
        ? (is_array($summary['data'] ?? null) ? $summary['data'] : [])
        : xfoo_wizard_evidence_empty_payload($conversationId);

    $meetings = [];
    foreach ($data['previous_meetings'] ?? [] as $meeting) {
        if (! is_array($meeting)) {
            continue;
        }
        $leader = is_array($meeting['leader'] ?? null) ? $meeting['leader'] : [];
        $meetings[] = [
            'id' => (int) ($meeting['id'] ?? 0),
            'date' => xfoo_wizard_format_evidence_datetime((string) ($meeting['held_at'] ?? $meeting['scheduled_at'] ?? '')),
            'date_raw' => (string) ($meeting['held_at'] ?? $meeting['scheduled_at'] ?? ''),
            'status' => xfoo_wizard_format_evidence_status_label((string) ($meeting['status'] ?? '')),
            'status_raw' => (string) ($meeting['status'] ?? ''),
            'leader_name' => (string) ($leader['name'] ?? ''),
            'detail' => is_array($meeting['detail'] ?? null) ? $meeting['detail'] : [],
        ];
    }

    $placeholder = static fn (string $title): array => [
        'status' => 'placeholder',
        'title' => $title,
        'note' => 'Evidence source not yet connected.',
    ];

    return [
        'conversation_id' => $conversationId,
        'employee_id' => (int) ($data['employee_id'] ?? 0),
        'generated_from' => 'step_1_continuous_evidence',
        'sections' => [
            'previous_1_on_1' => [
                'title' => 'Previous 1-on-1',
                'data' => ['meetings' => $meetings],
            ],
            'previous_commitments' => [
                'title' => 'Previous Commitments',
                'data' => ['items' => is_array($data['commitments'] ?? null) ? $data['commitments'] : []],
            ],
            'individual_insights' => [
                'title' => 'Individual Insights™',
                'data' => is_array($data['individual_insights'] ?? null) ? $data['individual_insights'] : [],
            ],
            'activities' => [
                'title' => 'Activities',
                'data' => ['items' => is_array($data['activities'] ?? null) ? $data['activities'] : []],
            ],
            'self_assessments' => [
                'title' => 'Self-Assessments',
                'data' => ['scores' => is_array($data['self_assessments'] ?? null) ? $data['self_assessments'] : []],
            ],
            'development_tools' => [
                'title' => 'Development Tools',
                'data' => ['items' => is_array($data['development_tools'] ?? null) ? $data['development_tools'] : []],
            ],
            'behavioral_driver_trends' => [
                'title' => 'Behavioral Driver Trends',
                'data' => ['scores' => is_array($data['behavioral_drivers'] ?? null) ? $data['behavioral_drivers'] : []],
            ],
            'ai_insight_trends' => [
                'title' => 'AI Insight Trends',
                'data' => is_array($data['ai_insight'] ?? null) ? $data['ai_insight'] : [],
            ],
            'qbr_priorities' => [
                'title' => 'QBR Priorities',
                'data' => $placeholder('QBR Priorities'),
            ],
            'arp_priorities' => [
                'title' => 'ARP Priorities',
                'data' => $placeholder('ARP Priorities'),
            ],
            'previous_360' => [
                'title' => 'Previous 360 Review™',
                'data' => $placeholder('Previous 360 Review™'),
            ],
            'organizational_context' => [
                'title' => 'Organizational Context',
                'data' => $placeholder('Organizational Context'),
            ],
        ],
    ];
}

add_action('wp_ajax_xfoo_wizard_generate_brief', 'xfoo_wizard_ajax_generate_brief');

add_action('wp_ajax_xfoo_wizard_preview_brief_bundle', 'xfoo_wizard_ajax_preview_brief_bundle');

function xfoo_wizard_ajax_preview_brief_bundle(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_REQUEST['conversation_id']) ? absint($_REQUEST['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    wp_send_json_success(xfoo_wizard_evidence_bundle_for_brief($conversationId));
}

function xfoo_wizard_ajax_generate_brief(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_POST['conversation_id']) ? absint($_POST['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    $bundle = xfoo_wizard_evidence_bundle_for_brief($conversationId);
    $result = xfoo_wizard_fusion_api_request('POST', "/conversations/{$conversationId}/generate-brief", [], [
        'evidence_context' => $bundle,
        'force_refresh' => true,
    ]);

    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];
        wp_send_json_error(['message' => $result['error'] ?? ($body['message'] ?? 'Failed to generate AI Meeting Brief.')], 200);
    }

    $body = is_array($result['body']) ? $result['body'] : [];
    wp_send_json_success([
        'brief' => $body['data'] ?? null,
        'meta' => $body['meta'] ?? [],
        'evidence_context' => $bundle,
    ]);
}

/**
 * JS: accordion UI + fetch evidence on expand.
 */
function xfoo_wizard_evidence_js(): string
{
    return <<<'JS'
window.xfwEvidenceCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };

var xfwResetEvidenceCache = function () {
    window.xfwEvidenceCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };
};

var xfwFormatEvidenceDate = function (iso) {
    if (!iso) {
        return '—';
    }
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
        return String(iso);
    }
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

var xfwFormatEvidenceDateTime = function (iso) {
    if (!iso) {
        return '—';
    }
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
        return String(iso);
    }
    var date = d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    var time = d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    return date + ' · ' + time;
};

var xfwFormatEvidenceStatus = function (status) {
    var key = String(status || '').toLowerCase().replace(/\s+/g, '_');
    var labels = {
        scheduled: 'Scheduled',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled',
        done: 'Done',
        open: 'Open',
    };
    return labels[key] || String(status || '—').replace(/_/g, ' ').replace(/\b\w/g, function (ch) { return ch.toUpperCase(); });
};

var xfwStatusBadgeForEvidence = function (status) {
    var key = String(status || '').toLowerCase().replace(/\s+/g, '_');
    if (key === 'completed' || key === 'done') {
        return 'green';
    }
    if (key === 'in_progress') {
        return 'blue';
    }
    if (key === 'cancelled') {
        return 'gray';
    }
    return 'amber';
};

var xfwEvidenceEsc = function (s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
};

var xfwEvidenceEmptyDefaults = function () {
    return {
        previous_meetings: [],
        commitments: [],
        individual_insights: { recommended_focus_area: '', evaluated_at: '' },
        activities: [],
        behavioral_drivers: [],
        self_assessments: [],
        ai_insight: { key_observation: '', evaluated_at: '' },
        development_tools: [],
    };
};

var xfwEvidenceNoData = function (message) {
    return '<p class="xfw-muted xfw-evidence-empty">' + xfwEvidenceEsc(message) + '</p>';
};

var xfwEvidenceEmptyMessages = {
    previous_meetings: 'No previous 1-on-1 meeting data is available yet.',
    commitments: 'No commitment records are available yet.',
    individual_insights: 'No Recommended Focus Area is available yet.',
    activities: 'No recent activity submissions are available yet.',
    behavioral_drivers: 'No behavioral driver scores are available yet.',
    self_assessments: 'No self-assessment scores are available yet.',
    ai_insight: 'No Overall Insight is available yet.',
    development_tools: 'No development tool submissions are available yet.',
    default: 'No data is available for this section yet.',
};

var xfwEvidenceDummyMessages = {
    qbr_priorities: 'Current Quarterly Business Review\u2122 priorities and progress will appear here once this evidence source is connected.',
    arp_priorities: 'Annual Readiness Plan\u2122 priorities and strategic context will appear here once this evidence source is connected.',
    previous_360: 'Most recent 360 Review\u2122 feedback themes and insights will appear here once this evidence source is connected.',
    organizational_context: 'Role, team, organizational goals, and readiness priorities will appear here once this evidence source is connected.',
};

var xfwRenderEvidenceDummyPanel = function (key) {
    var msg = xfwEvidenceDummyMessages[key] || 'This evidence section will be populated automatically once it is connected.';
    return '<p class="xfw-muted xfw-evidence-empty">' + xfwEvidenceEsc(msg) + '</p>';
};

var xfwRenderEvidenceFields = function (rows) {
    if (!rows || !rows.length) {
        return xfwEvidenceNoData('No data is available for this section yet.');
    }
    return '<dl class="xfw-evidence-dl">' + rows.map(function (row) {
        var val = row.type === 'scale'
            ? '<span class="xfw-evidence-scale">' + xfwEvidenceEsc(row.value) + ' / 5</span>'
            : '<div class="xfw-evidence-text">' + xfwEvidenceEsc(row.value).replace(/\n/g, '<br>') + '</div>';
        return '<dt>' + xfwEvidenceEsc(row.label) + '</dt><dd>' + val + '</dd>';
    }).join('') + '</dl>';
};

var xfwRenderMeetingDetail = function (meeting) {
    var detail = meeting.detail || {};
    var prep = detail.preparation || {};
    var leaderName = meeting.leader && meeting.leader.name ? meeting.leader.name : 'Leader';
    var dateLabel = xfwFormatEvidenceDateTime(meeting.held_at || meeting.scheduled_at);

    return '<div class="xfw-evidence-meeting">' +
        '<div class="xfw-evidence-meeting-head">' +
        '<strong>' + xfwEvidenceEsc(dateLabel) + '</strong>' +
        '<span class="xfw-muted">with ' + xfwEvidenceEsc(leaderName) + '</span>' +
        '<span class="xfw-badge ' + xfwStatusBadgeForEvidence(meeting.status) + '">' + xfwEvidenceEsc(xfwFormatEvidenceStatus(meeting.status)) + '</span>' +
        '</div>' +
        '<div class="xfw-evidence-section">' +
        '<h5>Step 3 — Employee Preparation</h5>' + xfwRenderEvidenceFields(prep.employee || []) +
        '</div>' +
        '<div class="xfw-evidence-section">' +
        '<h5>Step 3 — Leader Preparation</h5>' + xfwRenderEvidenceFields(prep.leader || []) +
        '</div>' +
        '<div class="xfw-evidence-section">' +
        '<h5>Step 4 — Conversation Notes</h5>' + xfwRenderEvidenceFields(detail.conversation || []) +
        '</div>' +
        '<div class="xfw-evidence-section">' +
        '<h5>Step 5 — Commitments</h5>' + xfwRenderEvidenceCommitments(detail.commitments || []) +
        '</div>' +
        '</div>';
};

var xfwRenderEvidenceCommitments = function (rows) {
    if (!rows || !rows.length) {
        return xfwEvidenceNoData(xfwEvidenceEmptyMessages.commitments);
    }
    return '<div class="xfw-evidence-commitments">' + rows.map(function (row) {
        var driver = row.behavioral_driver_label || row.behavioral_driver || '';
        var meta = [];
        if (row.priority) {
            meta.push('Priority: ' + row.priority);
        }
        if (driver) {
            meta.push(driver);
        }
        if (row.status) {
            meta.push('Status: ' + xfwFormatEvidenceStatus(row.status));
        }
        if (row.due_date) {
            meta.push('Due: ' + (row.due_date_label || xfwFormatEvidenceDate(row.due_date)));
        }
        if (row.owner_role) {
            meta.push('Owner: ' + row.owner_role);
        }
        var meeting = row.meeting || {};
        if (meeting.leader_name || meeting.meeting_at) {
            meta.push('Meeting: ' + xfwFormatEvidenceDateTime(meeting.meeting_at || meeting.held_at || meeting.scheduled_at) +
                (meeting.leader_name ? ' (' + meeting.leader_name + ')' : ''));
        }
        return '<div class="xfw-evidence-commitment">' +
            '<div class="xfw-evidence-commitment-title">' + xfwEvidenceEsc(row.title || 'Untitled') + '</div>' +
            (meta.length ? '<div class="xfw-evidence-commitment-meta">' + meta.map(function (part) {
                return '<span>' + xfwEvidenceEsc(part) + '</span>';
            }).join('<span class="xfw-muted"> · </span>') + '</div>' : '') +
            (row.success_indicator ? '<div class="xfw-evidence-commitment-indicator">' + xfwEvidenceEsc(row.success_indicator) + '</div>' : '') +
            '</div>';
    }).join('') + '</div>';
};

var xfwRenderPreviousMeetingsPanel = function (meetings) {
    if (!meetings || !meetings.length) {
        return xfwEvidenceNoData(xfwEvidenceEmptyMessages.previous_meetings);
    }
    return meetings.map(function (m) {
        return xfwRenderMeetingDetail(m);
    }).join('');
};

var xfwRenderAllCommitmentsPanel = function (rows) {
    return xfwRenderEvidenceCommitments(rows);
};

var xfwRenderIndividualInsightsPanel = function (data) {
    var block = data || {};
    var text = block.recommended_focus_area || '';
    if (!text) {
        return xfwEvidenceNoData(xfwEvidenceEmptyMessages.individual_insights);
    }
    var html = '<div class="xfw-evidence-section">' +
        '<h5>Recommended Focus Area</h5>' +
        '<div class="xfw-evidence-text">' + xfwEvidenceEsc(text).replace(/\n/g, '<br>') + '</div>';
    if (block.evaluated_at) {
        html += '<p class="xfw-muted" style="margin-top:.75rem">From evaluation on ' + xfwEvidenceEsc(xfwFormatEvidenceDate(block.evaluated_at)) + '</p>';
    }
    html += '</div>';
    return html;
};

var xfwRenderSubmissionLinks = function (rows, emptyMsg) {
    if (!rows || !rows.length) {
        return xfwEvidenceNoData(emptyMsg);
    }
    return '<ul class="xfw-evidence-links">' + rows.map(function (row) {
        var title = row.title || 'Untitled';
        var dateLabel = xfwFormatEvidenceDate(row.submitted_at);
        var url = row.page_url || '';
        if (url) {
            return '<li><a href="' + xfwEvidenceEsc(url) + '" target="_blank" rel="noopener" class="xfw-link">' +
                xfwEvidenceEsc(title) + '</a>' +
                (dateLabel !== '—' ? ' <span class="xfw-muted">(' + xfwEvidenceEsc(dateLabel) + ')</span>' : '') +
                '</li>';
        }
        return '<li>' + xfwEvidenceEsc(title) +
            (dateLabel !== '—' ? ' <span class="xfw-muted">(' + xfwEvidenceEsc(dateLabel) + ')</span>' : '') +
            '</li>';
    }).join('') + '</ul>';
};

var xfwRenderActivitiesPanel = function (rows) {
    return xfwRenderSubmissionLinks(rows, xfwEvidenceEmptyMessages.activities);
};

var xfwRenderDevelopmentToolsPanel = function (rows) {
    return xfwRenderSubmissionLinks(rows, xfwEvidenceEmptyMessages.development_tools);
};

var xfwRenderBehavioralDriversPanel = function (rows, emptyMessage) {
    if (!rows || !rows.length) {
        return xfwEvidenceNoData(emptyMessage || xfwEvidenceEmptyMessages.behavioral_drivers);
    }
    return '<div class="xfw-evidence-driver-grid">' + rows.map(function (row) {
        var score = row.average !== null && row.average !== undefined
            ? String(row.average)
            : '—';
        return '<div class="xfw-evidence-driver">' +
            '<div class="xfw-evidence-driver-title">' + xfwEvidenceEsc(row.title || row.slug || '') + '</div>' +
            '<div class="xfw-evidence-driver-score">' + xfwEvidenceEsc(score) + '</div>' +
            '</div>';
    }).join('') + '</div>';
};

var xfwRenderAiInsightPanel = function (data) {
    var block = data || {};
    var text = block.key_observation || '';
    if (!text) {
        return xfwEvidenceNoData(xfwEvidenceEmptyMessages.ai_insight);
    }
    var html = '<div class="xfw-evidence-section">' +
        '<h5>Overall Insight</h5>' +
        '<div class="xfw-evidence-text">' + xfwEvidenceEsc(text).replace(/\n/g, '<br>') + '</div>';
    if (block.evaluated_at) {
        html += '<p class="xfw-muted" style="margin-top:.75rem">From evaluation on ' + xfwEvidenceEsc(xfwFormatEvidenceDate(block.evaluated_at)) + '</p>';
    }
    html += '</div>';
    return html;
};

var xfwRenderEvidencePanel = function (key, data) {
    data = data || xfwEvidenceEmptyDefaults();
    if (key === 'previous_meetings') {
        return xfwRenderPreviousMeetingsPanel(data.previous_meetings || []);
    }
    if (key === 'commitments') {
        return xfwRenderAllCommitmentsPanel(data.commitments || []);
    }
    if (key === 'individual_insights') {
        return xfwRenderIndividualInsightsPanel(data.individual_insights || {});
    }
    if (key === 'activities') {
        return xfwRenderActivitiesPanel(data.activities || []);
    }
    if (key === 'behavioral_drivers') {
        return xfwRenderBehavioralDriversPanel(data.behavioral_drivers || [], xfwEvidenceEmptyMessages.behavioral_drivers);
    }
    if (key === 'self_assessments') {
        return xfwRenderBehavioralDriversPanel(data.self_assessments || [], xfwEvidenceEmptyMessages.self_assessments);
    }
    if (key === 'ai_insight') {
        return xfwRenderAiInsightPanel(data.ai_insight || {});
    }
    if (key === 'development_tools') {
        return xfwRenderDevelopmentToolsPanel(data.development_tools || []);
    }
    return xfwEvidenceNoData(xfwEvidenceEmptyMessages.default);
};

var xfwBindEvidenceAccordions = function () {
    var main = root.querySelector('#xfw-main');
    if (!main) {
        return;
    }

    var toggleItem = function (item, row, panel) {
        var isOpen = item.classList.contains('open');
        if (isOpen) {
            item.classList.remove('open');
            panel.classList.add('xfw-hidden');
            row.setAttribute('aria-expanded', 'false');
            return;
        }
        item.classList.add('open');
        panel.classList.remove('xfw-hidden');
        row.setAttribute('aria-expanded', 'true');
        if (panel.dataset.loaded === '1') {
            return;
        }
        if (panel.dataset.evidenceDummy === '1') {
            var dummyKey = panel.dataset.evidenceKey || '';
            panel.innerHTML = xfwRenderEvidenceDummyPanel(dummyKey);
            panel.dataset.loaded = '1';
            return;
        }
        panel.innerHTML = '<p class="xfw-muted">Loading…</p>';
        loadWizardEvidence(true).then(function (data) {
            var key = panel.dataset.evidenceKey || '';
            panel.innerHTML = xfwRenderEvidencePanel(key, data);
            panel.dataset.loaded = '1';
        });
    };

    main.querySelectorAll('.xfw-evidence-accordion-toggle').forEach(function (row) {
        row.addEventListener('click', function () {
            var item = row.closest('.xfw-evidence-accordion-item');
            var panel = item ? item.querySelector('.xfw-evidence-accordion-panel') : null;
            if (!panel) {
                return;
            }
            toggleItem(item, row, panel);
        });
        row.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            e.preventDefault();
            var item = row.closest('.xfw-evidence-accordion-item');
            var panel = item ? item.querySelector('.xfw-evidence-accordion-panel') : null;
            if (!panel) {
                return;
            }
            toggleItem(item, row, panel);
        });
    });
};

var loadWizardEvidence = function (force) {
    if (!window.XFW_WIZARD) {
        return Promise.resolve(null);
    }
    var cid = typeof xfwGetActiveConversationId === 'function' ? xfwGetActiveConversationId() : 0;
    if (!cid) {
        return Promise.resolve(null);
    }
    if (!force && window.xfwEvidenceCache.loaded && window.xfwEvidenceCache.conversationId === cid) {
        return Promise.resolve(window.xfwEvidenceCache.data);
    }
    if (window.xfwEvidenceCache.loading && window.xfwEvidenceCache.conversationId === cid && window.xfwEvidenceCache._promise) {
        return window.xfwEvidenceCache._promise;
    }

    window.xfwEvidenceCache.loading = true;
    window.xfwEvidenceCache.conversationId = cid;

    var url = window.XFW_WIZARD.ajaxUrl + '?action=xfoo_wizard_load_evidence&nonce=' +
        encodeURIComponent(window.XFW_WIZARD.nonce) + '&conversation_id=' + cid;

    window.xfwEvidenceCache._promise = fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (json && json.success && json.data) {
                window.xfwEvidenceCache.data = json.data;
                console.log('[XFW Step 1] evidence summary (UI / accordion)', json.data);
            } else {
                window.xfwEvidenceCache.data = xfwEvidenceEmptyDefaults();
            }
            window.xfwEvidenceCache.loaded = true;
            return window.xfwEvidenceCache.data;
        })
        .catch(function () {
            window.xfwEvidenceCache.data = xfwEvidenceEmptyDefaults();
            window.xfwEvidenceCache.loaded = true;
            return window.xfwEvidenceCache.data;
        })
        .finally(function () {
            window.xfwEvidenceCache.loading = false;
        });

    return window.xfwEvidenceCache._promise;
};

var initEvidenceStep = function () {
    xfwBindEvidenceAccordions();
};

window.xfwResetEvidenceCache = xfwResetEvidenceCache;
JS;
}
