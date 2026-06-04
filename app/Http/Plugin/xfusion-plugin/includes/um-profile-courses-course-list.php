<?php
/**
 * Ultimate Member profile — blok Course List (course_group.tools = 0).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_um_profile_course_list_html(int $user_id): string
{
    global $wpdb;

    $output = '';
    $cg_list = $wpdb->get_results(
        'SELECT * FROM wp_course_groups WHERE tools = 0 ORDER BY order_group'
    );

    $output .= "<h2 style='text-align: center'>Course List</h2>";

    foreach ($cg_list as $cg) {
        $accordion_id = 'accordion_' . uniqid();

        $heading = sprintf(
            '%s - %s',
            isset($cg->title) ? (string) $cg->title : '',
            isset($cg->sub_title) ? (string) $cg->sub_title : ''
        );

        $output .= "<div class='accordion-item'>";
        $output .= "<div class=' accordion-box accordion-header' onclick=\"toggleAccordion('" . esc_attr($accordion_id) . "')\" style='font-size: 26px; cursor: pointer; margin: 0;'>" . esc_html($heading) . '</div>';

        $output .= "<div id='" . esc_attr($accordion_id) . "' class='accordion-content' style='display: none; padding: 10px;'>";

        $q_list = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM wp_course_group_details WHERE course_group_id = %d ORDER BY orders',
                (int) $cg->id
            )
        );

        if (count($q_list) == 0) {
            $output .= "<div style='font-size: 24px'>Coming soon </div>";
        }

        $output .= "<div class='profile-notes' style='gap: 10px'>";
        foreach ($q_list as $q) {
            $temp_id = (int) $q->course_list_id;
            $c_list = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM wp_course_lists WHERE id = %d',
                    $temp_id
                )
            );
            if (empty($c_list[0])) {
                continue;
            }

            $form_id = $c_list[0]->wp_gf_form_id;
            $link_child = $c_list[0]->url;

            try {
                $subquery = $wpdb->prepare(
                    "
                            SELECT created_by, MAX(date_created) as max_date
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by IS NOT NULL
                            GROUP BY created_by
                        ",
                    $form_id
                );

                $query = $wpdb->prepare(
                    "
                            SELECT id, created_by, date_created
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by = %d AND created_by IS NOT NULL AND status ='active'
                            AND (created_by, date_created) IN ($subquery)
                        ",
                    $form_id,
                    $user_id
                );

                $entry_id = $wpdb->get_var($query);
            } catch (Exception $e) {
                $entry_id = false;
            }

            if (isset($c_list[0]->legacy) && $c_list[0]->legacy == 1 && !$entry_id) {
                continue;
            }

            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gf_form_meta WHERE form_id = %d", $form_id));

            $data_entry = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gf_entry_meta WHERE form_id = %d AND entry_id = %d",
                $form_id,
                $entry_id
            ));

            $array_entry = [];
            if ($data && isset($data->display_meta)) {
                $fields = json_decode($data->display_meta);
                if (isset($fields->fields)) {
                    foreach ($fields->fields as $field) {
                        $array_entry[$field->id] = null;
                    }
                }
            }

            foreach ($data_entry as $entry) {
                $array_entry[$entry->meta_key] = $entry->meta_value;
            }

            $title_display = $c_list[0]->page_title;
            if ($entry_id && isset($c_list[0]->legacy) && $c_list[0]->legacy == 1) {
                $title_display .= ' (legacy)';
            }

            $link_open = esc_url($link_child) . '?dataId=' . (int) $entry_id . '&btn-close=true';

            if ($entry_id) {
                $output .= '<a onclick="openWindowXfusion(\'' . esc_js($link_open) . '\')"
                            class="note-column" style="color: #666; font-weight: bold">
                            <span>' . esc_html($title_display) . '</span>
                        </a>';
            } else {
                $output .= '<a href="' . esc_url($link_child) . '" class="note-column" style="color: red; pointer-events: none;">
                            <span>' . esc_html($title_display) . '</span>
                        </a>';
            }
        }

        $output .= "</div></div></div>";
    }

    return $output;
}
