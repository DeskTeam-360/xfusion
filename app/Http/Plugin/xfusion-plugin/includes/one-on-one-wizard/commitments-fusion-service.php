<?php
/**
 * Step 5 commitments — save to wp_fusion_one_on_one_commitments via Laravel API.
 *
 * UI fields priority, behavioral_driver, success_indicator map to dedicated DB columns
 * (see database/sql/wp_fusion_one_on_one_wizard.sql).
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/** @return list<string> */
function xfoo_wizard_behavioral_driver_slugs(): array
{
    return ['get_real', 'fill_buckets', 'be_intentional', 'foster_grit', 'drive_growth'];
}

/**
 * @return string One of the known driver slugs, or empty string.
 */
function xfoo_wizard_sanitize_behavioral_driver(mixed $value): string
{
    $driver = strtolower(trim((string) $value));

    return in_array($driver, xfoo_wizard_behavioral_driver_slugs(), true) ? $driver : '';
}

/**
 * @return array{priority?: string, behavioral_driver?: string, success_indicator?: string}
 */
function xfoo_wizard_commitment_fields_from_row(array $row): array
{
    $priority = isset($row['priority']) ? sanitize_key((string) $row['priority']) : 'medium';
    if (! in_array($priority, ['high', 'medium', 'low'], true)) {
        $priority = 'medium';
    }

    return [
        'priority' => $priority,
        'behavioral_driver' => xfoo_wizard_sanitize_behavioral_driver($row['behavioral_driver'] ?? ''),
        'success_indicator' => isset($row['success_indicator']) ? sanitize_textarea_field((string) $row['success_indicator']) : '',
    ];
}

/**
 * Fallback for rows saved before dedicated columns existed.
 *
 * @return array{priority?: string, behavioral_driver?: string, success_indicator?: string}
 */
function xfoo_wizard_commitment_meta_decode(?string $description): array
{
    if ($description === null || $description === '') {
        return [];
    }

    $decoded = json_decode($description, true);
    if (! is_array($decoded)) {
        return ['success_indicator' => $description];
    }

    return array_filter([
        'priority' => isset($decoded['priority']) ? (string) $decoded['priority'] : null,
        'status' => isset($decoded['status']) ? (string) $decoded['status'] : null,
        'behavioral_driver' => isset($decoded['behavioral_driver']) ? (string) $decoded['behavioral_driver'] : null,
        'success_indicator' => isset($decoded['success_indicator']) ? (string) $decoded['success_indicator'] : null,
    ], static fn ($v) => $v !== null && $v !== '');
}

/**
 * @return array{ok: bool, code: int, body: mixed, error: ?string}
 */
function xfoo_wizard_fusion_api_request(string $method, string $path, array $query = [], array $body = []): array
{
    if (function_exists('xfusion_oo_api_request')) {
        return xfusion_oo_api_request($method, $path, $query, $body);
    }

    return ['ok' => false, 'code' => 0, 'body' => null, 'error' => 'Laravel API bridge not loaded.'];
}

/**
 * @return array{success: bool, data?: list<array<string, mixed>>, message?: string}
 */
function xfoo_wizard_get_commitments(int $conversationId): array
{
    $result = xfoo_wizard_fusion_api_request('GET', "/conversations/{$conversationId}/commitments");

    if (! $result['ok']) {
        $body = is_array($result['body']) ? $result['body'] : [];

        return ['success' => false, 'message' => $result['error'] ?? ($body['message'] ?? 'Failed to load commitments.')];
    }

    $body = is_array($result['body']) ? $result['body'] : [];
    $rows = $body['data'] ?? [];

    return ['success' => true, 'data' => is_array($rows) ? $rows : []];
}

/**
 * Normalize one UI row before save.
 *
 * @param  array<string, mixed>  $row
 * @return array<string, mixed>|null
 */
function xfoo_wizard_normalize_commitment_row(string $ownerRole, array $row): ?array
{
    $title = isset($row['title']) ? sanitize_text_field((string) $row['title']) : '';
    if ($title === '') {
        return null;
    }

    $dueDate = isset($row['due_date']) ? sanitize_text_field((string) $row['due_date']) : '';
    if ($dueDate !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        $parsed = strtotime($dueDate);
        $dueDate = $parsed ? gmdate('Y-m-d', $parsed) : '';
    }

    $fields = xfoo_wizard_commitment_fields_from_row($row);
    $status = isset($row['status']) ? sanitize_key((string) $row['status']) : 'open';
    if (! in_array($status, ['open', 'in_progress', 'done'], true)) {
        $status = 'open';
    }

    $metaJson = wp_json_encode([
        'priority' => $fields['priority'],
        'status' => $status,
        'behavioral_driver' => $fields['behavioral_driver'],
        'success_indicator' => $fields['success_indicator'],
    ]);

    return [
        'id' => isset($row['id']) ? absint($row['id']) : 0,
        'title' => $title,
        'owner_role' => $ownerRole,
        'owner_user_id' => get_current_user_id(),
        'due_date' => $dueDate !== '' ? $dueDate : null,
        'priority' => $fields['priority'],
        'status' => $status,
        'behavioral_driver' => $ownerRole === 'employee' ? ($fields['behavioral_driver'] !== '' ? $fields['behavioral_driver'] : null) : null,
        'success_indicator' => $fields['success_indicator'] !== '' ? $fields['success_indicator'] : null,
        'description' => $metaJson !== false ? $metaJson : null,
    ];
}

/**
 * Create or update one commitment row.
 *
 * @param  array<string, mixed>  $row
 * @return array{id: int, action: string}|WP_Error
 */
function xfoo_wizard_upsert_commitment(int $conversationId, array $row)
{
    $id = (int) ($row['id'] ?? 0);
    $body = [
        'title' => $row['title'],
        'description' => $row['description'] ?? null,
        'priority' => $row['priority'] ?? 'medium',
        'status' => $row['status'] ?? 'open',
        'behavioral_driver' => $row['behavioral_driver'] ?? null,
        'success_indicator' => $row['success_indicator'] ?? null,
        'owner_role' => $row['owner_role'],
        'owner_user_id' => (int) ($row['owner_user_id'] ?? 0) ?: null,
        'due_date' => $row['due_date'] ?? null,
    ];

    if ($id > 0) {
        // POST only for updates — PATCH often drops JSON body on WP/hosting; create already uses POST.
        $result = xfoo_wizard_fusion_api_request('POST', "/commitments/{$id}", [], $body);
        if (! $result['ok']) {
            $msg = is_array($result['body']) ? ($result['body']['message'] ?? 'Update failed.') : ($result['error'] ?? 'Update failed.');

            return new WP_Error('commitment_update_failed', (string) $msg);
        }

        $saved = is_array($result['body']) ? ($result['body']['data'] ?? []) : [];

        return ['id' => (int) ($saved['id'] ?? $id), 'action' => 'updated'];
    }

    $result = xfoo_wizard_fusion_api_request('POST', "/conversations/{$conversationId}/commitments", [], $body);
    if (! $result['ok']) {
        $msg = is_array($result['body']) ? ($result['body']['message'] ?? 'Create failed.') : ($result['error'] ?? 'Create failed.');

        return new WP_Error('commitment_create_failed', (string) $msg);
    }

    $saved = is_array($result['body']) ? ($result['body']['data'] ?? []) : [];

    return ['id' => (int) ($saved['id'] ?? 0), 'action' => 'created'];
}

/**
 * @param  list<array<string, mixed>>  $employeeRows
 * @param  list<array<string, mixed>>  $leaderRows
 * @return array{saved: list<array<string, mixed>>, errors: list<array{scope: string, message: string}>}
 */
function xfoo_wizard_save_commitments_batch(int $conversationId, array $employeeRows, array $leaderRows): array
{
    $saved = [];
    $errors = [];

    foreach (['employee' => $employeeRows, 'leader' => $leaderRows] as $role => $rows) {
        if (! is_array($rows)) {
            continue;
        }

        foreach ($rows as $index => $rawRow) {
            if (! is_array($rawRow)) {
                continue;
            }

            $row = xfoo_wizard_normalize_commitment_row($role, $rawRow);
            if ($row === null) {
                continue;
            }

            $result = xfoo_wizard_upsert_commitment($conversationId, $row);
            if (is_wp_error($result)) {
                $errors[] = ['scope' => "commitments:{$role}:{$index}", 'message' => $result->get_error_message()];
                continue;
            }

            $saved[] = array_merge(['scope' => "commitments:{$role}"], $result);
        }
    }

    return ['saved' => $saved, 'errors' => $errors];
}

function xfoo_wizard_format_commitment_due_date(mixed $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $raw = (string) $value;
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw, $matches)) {
        return $matches[0];
    }

    $parsed = strtotime($raw);

    return $parsed ? gmdate('Y-m-d', $parsed) : '';
}

function xfoo_wizard_commitment_pick_field(array $row, array $meta, string $field, string $default = ''): string
{
    $fromMeta = trim((string) ($meta[$field] ?? ''));
    if ($fromMeta !== '') {
        return $fromMeta;
    }

    $fromColumn = trim((string) ($row[$field] ?? ''));

    return $fromColumn !== '' ? $fromColumn : $default;
}

/**
 * Shape API rows for the wizard UI.
 *
 * @param  list<array<string, mixed>>  $rows
 * @return array{employee: list<array<string, mixed>>, leader: list<array<string, mixed>>}
 */
function xfoo_wizard_format_commitments_for_ui(array $rows): array
{
    $out = ['employee' => [], 'leader' => []];

    foreach ($rows as $row) {
        if (! is_array($row)) {
            continue;
        }

        $role = (string) ($row['owner_role'] ?? 'employee');
        if (! isset($out[$role])) {
            $role = 'employee';
        }

        $meta = xfoo_wizard_commitment_meta_decode(isset($row['description']) ? (string) $row['description'] : null);

        $out[$role][] = [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) ($row['title'] ?? ''),
            'priority' => xfoo_wizard_commitment_pick_field($row, $meta, 'priority', 'medium'),
            'behavioral_driver' => xfoo_wizard_commitment_pick_field($row, $meta, 'behavioral_driver'),
            'due_date' => xfoo_wizard_format_commitment_due_date($row['due_date'] ?? ''),
            'success_indicator' => xfoo_wizard_commitment_pick_field($row, $meta, 'success_indicator'),
            'status' => xfoo_wizard_commitment_pick_field($row, $meta, 'status', 'open'),
        ];
    }

    return $out;
}

add_action('wp_ajax_xfoo_wizard_get_commitments', 'xfoo_wizard_ajax_get_commitments');

function xfoo_wizard_ajax_get_commitments(): void
{
    check_ajax_referer('xfoo_wizard_save_draft', 'nonce');

    if (! is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized.'], 401);
    }

    $conversationId = isset($_GET['conversation_id']) ? absint($_GET['conversation_id']) : 0;
    if ($conversationId < 1) {
        wp_send_json_error(['message' => 'conversation_id is required.'], 422);
    }

    $result = xfoo_wizard_get_commitments($conversationId);
    if (! $result['success']) {
        wp_send_json_error(['message' => $result['message'] ?? 'Failed to load commitments.'], 200);
    }

    wp_send_json_success(xfoo_wizard_format_commitments_for_ui($result['data'] ?? []));
}

/**
 * JS helpers: render editable rows, load existing, collect for save.
 */
function xfoo_wizard_commitments_js(): string
{
    $drivers = [
        ['get_real', 'Get Real™'],
        ['fill_buckets', 'Fill Buckets™'],
        ['be_intentional', 'Be Intentional™'],
        ['foster_grit', 'Foster Grit™'],
        ['drive_growth', 'Drive Growth™'],
    ];

    $driversJs = wp_json_encode($drivers);

    return <<<JS
var XFW_DRIVERS = {$driversJs};

var xfwEscHtml = function (str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
};

var commitmentPriorityOptions = function (selected) {
    return ['high', 'medium', 'low'].map(function (p) {
        var label = p.charAt(0).toUpperCase() + p.slice(1);
        var sel = p === (selected || 'medium') ? ' selected' : '';
        return '<option value="' + p + '"' + sel + '>' + label + '</option>';
    }).join('');
};

var commitmentDriverOptions = function (selected) {
    return '<option value="">—</option>' + XFW_DRIVERS.map(function (d) {
        var sel = d[0] === selected ? ' selected' : '';
        return '<option value="' + d[0] + '"' + sel + '>' + d[1] + '</option>';
    }).join('');
};

var commitmentStatusOptions = function (selected) {
    return [
        ['open', 'Open'],
        ['in_progress', 'In Progress'],
        ['done', 'Done'],
    ].map(function (s) {
        var sel = s[0] === (selected || 'open') ? ' selected' : '';
        return '<option value="' + s[0] + '"' + sel + '>' + s[1] + '</option>';
    }).join('');
};

var commitmentRowHtml = function (role, data) {
    data = data || {};
    var id = data.id ? ' data-commitment-id="' + data.id + '"' : '';
    var thirdCol = role === 'employee'
        ? '<select class="xfw-input" data-field="behavioral_driver">' + commitmentDriverOptions(data.behavioral_driver || '') + '</select>'
        : '<span class="xfw-muted">—</span>';
    return '<tr class="xfw-commit-row" data-role="' + role + '"' + id + '>' +
        '<td><textarea class="xfw-input" rows="2" data-field="title" placeholder="Describe the commitment...">' + xfwEscHtml(data.title) + '</textarea></td>' +
        '<td><select class="xfw-input" data-field="priority">' + commitmentPriorityOptions(data.priority || 'medium') + '</select></td>' +
        '<td>' + thirdCol + '</td>' +
        '<td><input class="xfw-input" type="date" data-field="due_date" value="' + xfwEscHtml(data.due_date) + '"></td>' +
        '<td><textarea class="xfw-input" rows="2" data-field="success_indicator" placeholder="How will success be measured?">' + xfwEscHtml(data.success_indicator) + '</textarea></td>' +
        '<td><select class="xfw-input" data-field="status">' + commitmentStatusOptions(data.status || 'open') + '</select></td>' +
        '</tr>';
};

var addCommitmentRow = function (role) {
    var body = root.querySelector('.xfw-commit-tbody[data-role="' + role + '"]');
    if (!body) {
        return;
    }
    var empty = body.querySelector('.xfw-commit-empty');
    if (empty) {
        body.innerHTML = '';
    }
    body.insertAdjacentHTML('beforeend', commitmentRowHtml(role, {}));
    var rows = body.querySelectorAll('.xfw-commit-row');
    var last = rows[rows.length - 1];
    if (last) {
        var title = last.querySelector('[data-field="title"]');
        if (title) {
            title.focus();
        }
        last.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

var renderCommitmentRows = function (role, rows, force) {
    var body = root.querySelector('.xfw-commit-tbody[data-role="' + role + '"]');
    if (!body) {
        return;
    }
    if (!force && body.querySelector('.xfw-commit-row')) {
        return;
    }
    if (!rows || !rows.length) {
        body.innerHTML = '<tr class="xfw-commit-empty"><td colspan="6" class="xfw-muted">No commitments yet. Click + Add Commitment.</td></tr>';
        return;
    }
    body.innerHTML = rows.map(function (row) { return commitmentRowHtml(role, row); }).join('');
};

var applyCommitmentsData = function (data, force) {
    if (!data) {
        return;
    }
    var payload = data;
    if (!Array.isArray(payload.employee) && !Array.isArray(payload.leader) && payload.data) {
        payload = payload.data;
    }
    renderCommitmentRows('employee', payload.employee || [], force);
    renderCommitmentRows('leader', payload.leader || [], force);
    if (typeof window.xfwRenderSidebar === 'function' && typeof STEPS !== 'undefined' && STEPS[current] && STEPS[current].key === 'commitments') {
        window.xfwRenderSidebar();
    }
};

window.xfwCommitmentsCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };

var xfwResetCommitmentsCache = function () {
    window.xfwCommitmentsCache = { loaded: false, loading: false, data: null, conversationId: 0, _promise: null };
};

var fetchCommitments = function (force) {
    if (!window.XFW_WIZARD) {
        return Promise.resolve(null);
    }
    var cid = typeof xfwGetActiveConversationId === 'function'
        ? xfwGetActiveConversationId()
        : parseInt(window.XFW_WIZARD.conversationId, 10);
    if (cid > 0) {
        window.XFW_WIZARD.conversationId = cid;
        if (root) {
            root.dataset.conversationId = String(cid);
        }
    }
    if (!cid) {
        return Promise.resolve(null);
    }
    if (!force && window.xfwCommitmentsCache.loaded && window.xfwCommitmentsCache.conversationId === cid) {
        return Promise.resolve(window.xfwCommitmentsCache.data);
    }
    if (window.xfwCommitmentsCache.loading && window.xfwCommitmentsCache.conversationId === cid && window.xfwCommitmentsCache._promise) {
        return window.xfwCommitmentsCache._promise;
    }

    window.xfwCommitmentsCache.loading = true;
    window.xfwCommitmentsCache.conversationId = cid;

    var url = window.XFW_WIZARD.ajaxUrl + '?action=xfoo_wizard_get_commitments&nonce=' +
        encodeURIComponent(window.XFW_WIZARD.nonce) + '&conversation_id=' + cid;

    window.xfwCommitmentsCache._promise = fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json || !json.success || !json.data) {
                window.xfwCommitmentsCache.data = { employee: [], leader: [] };
            } else {
                var payload = json.data;
                if (!Array.isArray(payload.employee) && !Array.isArray(payload.leader) && payload.data) {
                    payload = payload.data;
                }
                window.xfwCommitmentsCache.data = payload;
            }
            window.xfwCommitmentsCache.loaded = true;
            return window.xfwCommitmentsCache.data;
        })
        .catch(function () {
            window.xfwCommitmentsCache.data = { employee: [], leader: [] };
            window.xfwCommitmentsCache.loaded = true;
            return window.xfwCommitmentsCache.data;
        })
        .finally(function () {
            window.xfwCommitmentsCache.loading = false;
        });

    return window.xfwCommitmentsCache._promise;
};

var preloadCommitments = function (force) {
    return fetchCommitments(force).then(function (data) {
        if (data && typeof STEPS !== 'undefined' && STEPS[current] && STEPS[current].key === 'commitments') {
            applyCommitmentsData(data, true);
        }
        return data;
    });
};

var loadCommitments = function (force) {
    return fetchCommitments(!!force).then(function (data) {
        applyCommitmentsData(data, true);
        return data;
    });
};

var collectCommitmentRows = function (role) {
    var rows = [];
    var fields = ['title', 'priority', 'behavioral_driver', 'due_date', 'success_indicator', 'status'];
    root.querySelectorAll('.xfw-commit-row[data-role="' + role + '"]').forEach(function (tr) {
        var row = { owner_role: role };
        if (tr.dataset.commitmentId) {
            row.id = parseInt(tr.dataset.commitmentId, 10);
        }
        fields.forEach(function (field) {
            var el = tr.querySelector('[data-field="' + field + '"]');
            if (el) {
                row[field] = el.value;
            }
        });
        if ((row.title || '').trim() !== '') {
            rows.push(row);
        }
    });
    return rows;
};

var initCommitmentsStep = function () {
    if (window.xfwCommitmentsCache && window.xfwCommitmentsCache.loaded && window.xfwCommitmentsCache.data) {
        applyCommitmentsData(window.xfwCommitmentsCache.data, true);
        return;
    }
    loadCommitments(true);
};

if (root && !root.dataset.commitmentsDelegated) {
    root.dataset.commitmentsDelegated = '1';
    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-add-commitment]');
        if (!btn || !root.contains(btn)) {
            return;
        }
        e.preventDefault();
        addCommitmentRow(btn.getAttribute('data-add-commitment'));
    });
}
JS;
}
