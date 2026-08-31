<?php
/**
 * Tool Library catalog — [fusion_tool_library group="Leadership Development"]
 *
 * Renders just the tool list for one wp_course_groups category - no title/
 * description wrapper, since that's supplied by the page itself (e.g. an
 * Elementor section/column). Two display styles:
 *
 *   style="grid"  (default) - icon + title + 5 COR capability tag chips,
 *                  for a category like "Leadership Development" whose tools
 *                  are tagged via wp_fusion_tool_scoring_group_tags.
 *   style="pills" - a plain vertical list of tool name buttons, for a
 *                  per-Behavioral-Driver column (e.g. "Get Real") that
 *                  doesn't need icons or capability tags at all.
 *
 * Usage:
 *   [fusion_tool_library group="Leadership Development"]
 *   [fusion_tool_library group="Get Real" style="pills"]
 *   [fusion_tool_library group_id="4"]   (match by id instead of title)
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

/** Fixed legend order/colors - same 5 capabilities everywhere in FUSION. */
function xfusion_tool_library_capabilities(): array
{
    return [
        'Alignment' => ['short' => 'A', 'color' => '#16a34a'],
        'Accountability' => ['short' => 'Ac', 'color' => '#f59e0b'],
        'Communication' => ['short' => 'C', 'color' => '#2563eb'],
        'Leadership' => ['short' => 'L', 'color' => '#7c3aed'],
        'Execution' => ['short' => 'E', 'color' => '#dc2626'],
    ];
}

/** @return object|null {id, title, icon} */
function xfusion_tool_library_find_group(int $groupId, string $groupTitle)
{
    global $wpdb;

    if ($groupId > 0) {
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, title, icon FROM {$wpdb->prefix}course_groups WHERE id = %d",
            $groupId
        ));
    }

    if ($groupTitle !== '') {
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, title, icon FROM {$wpdb->prefix}course_groups WHERE title = %s",
            $groupTitle
        ));
    }

    return null;
}

/** @return list<object> each {id, course_title, page_title, icon} */
function xfusion_tool_library_find_tools(int $groupId): array
{
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare(
        "SELECT cl.id, cl.course_title, cl.page_title, cl.icon
         FROM {$wpdb->prefix}course_group_details cgd
         INNER JOIN {$wpdb->prefix}course_lists cl ON cl.id = cgd.course_list_id
         WHERE cgd.course_group_id = %d
         ORDER BY cgd.id",
        $groupId
    )) ?: [];
}

function xfusion_tool_library_shortcode($atts = []): string
{
    global $wpdb;

    $atts = shortcode_atts([
        'group' => '',
        'group_id' => '0',
        'style' => 'grid',
    ], $atts, 'fusion_tool_library');

    $groupId = absint($atts['group_id']);
    $groupTitle = sanitize_text_field($atts['group']);
    $style = sanitize_key($atts['style']) === 'pills' ? 'pills' : 'grid';

    if ($groupId < 1 && $groupTitle === '') {
        return '<p>' . esc_html__('fusion_tool_library: specify a "group" title or "group_id".', 'xfusion') . '</p>';
    }

    $courseGroup = xfusion_tool_library_find_group($groupId, $groupTitle);
    if (! $courseGroup) {
        return '<p>' . esc_html__('Tool Library category not found.', 'xfusion') . '</p>';
    }

    $tools = xfusion_tool_library_find_tools((int) $courseGroup->id);
    if (empty($tools)) {
        return '<p>' . esc_html__('No tools have been added to this category yet.', 'xfusion') . '</p>';
    }

    if ($style === 'pills') {
        ob_start();
        ?>
        <div class="xfusion-tool-library-pills">
            <?php foreach ($tools as $tool) : ?>
                <div class="xfusion-tool-library-pill"><?php echo esc_html($tool->page_title ?: $tool->course_title); ?></div>
            <?php endforeach; ?>
        </div>
        <style>
            .xfusion-tool-library-pills{display:flex;flex-direction:column;gap:.6rem}
            .xfusion-tool-library-pill{background:#1e2a4a;color:#fff;font-weight:600;text-align:center;border-radius:.35rem;padding:.65rem .9rem;font-size:.95rem}
        </style>
        <?php

        return (string) ob_get_clean();
    }

    $toolIds = array_map(fn ($t) => (int) $t->id, $tools);
    $placeholders = implode(',', array_fill(0, count($toolIds), '%d'));

    $tags = $wpdb->get_results($wpdb->prepare(
        "SELECT t.course_list_id, csg.title AS capability_title
         FROM {$wpdb->prefix}fusion_tool_scoring_group_tags t
         INNER JOIN {$wpdb->prefix}course_scoring_groups csg ON csg.id = t.course_scoring_group_id
         WHERE t.course_list_id IN ({$placeholders})",
        ...$toolIds
    ));

    $tagsByTool = [];
    foreach ($tags as $tag) {
        $tagsByTool[(int) $tag->course_list_id][] = $tag->capability_title;
    }

    $capabilities = xfusion_tool_library_capabilities();

    ob_start();
    ?>
    <div class="xfusion-tool-library-grid">
        <?php foreach ($tools as $tool) : ?>
            <?php $active = $tagsByTool[(int) $tool->id] ?? []; ?>
            <div class="xfusion-tool-library-row">
                <div class="xfusion-tool-library-row-main">
                    <?php if (! empty($tool->icon)) : ?>
                        <?php if (str_starts_with((string) $tool->icon, 'http')) : ?>
                            <img class="xfusion-tool-library-icon" src="<?php echo esc_url($tool->icon); ?>" alt="" width="28" height="28">
                        <?php else : ?>
                            <span class="xfusion-tool-library-icon xfusion-tool-library-icon-emoji"><?php echo esc_html($tool->icon); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="xfusion-tool-library-title"><?php echo esc_html($tool->page_title ?: $tool->course_title); ?></span>
                </div>
                <div class="xfusion-tool-library-tags">
                    <?php foreach ($capabilities as $title => $meta) : ?>
                        <?php $isActive = in_array($title, $active, true); ?>
                        <span class="xfusion-tool-library-tag<?php echo $isActive ? ' active' : ''; ?>"
                              style="<?php echo $isActive ? 'background:' . esc_attr($meta['color']) . ';border-color:' . esc_attr($meta['color']) : ''; ?>"
                              title="<?php echo esc_attr($title); ?>">
                            <?php echo $isActive ? '&#10003;' : ''; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
        .xfusion-tool-library-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem}
        @media (max-width:640px){.xfusion-tool-library-grid{grid-template-columns:1fr}}
        .xfusion-tool-library-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;background:#fff;border:1px solid #e5e7eb;border-radius:.5rem;padding:.65rem .85rem}
        .xfusion-tool-library-row-main{display:flex;align-items:center;gap:.6rem;min-width:0}
        .xfusion-tool-library-icon{width:28px;height:28px;flex-shrink:0;object-fit:contain}
        .xfusion-tool-library-icon-emoji{font-size:1.4rem;line-height:1;display:inline-flex;align-items:center;justify-content:center}
        .xfusion-tool-library-title{font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .xfusion-tool-library-tags{display:flex;gap:.3rem;flex-shrink:0}
        .xfusion-tool-library-tag{width:22px;height:22px;border-radius:.3rem;border:1.5px solid #e5e7eb;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700}
    </style>
    <?php

    return (string) ob_get_clean();
}

add_shortcode('fusion_tool_library', 'xfusion_tool_library_shortcode');
