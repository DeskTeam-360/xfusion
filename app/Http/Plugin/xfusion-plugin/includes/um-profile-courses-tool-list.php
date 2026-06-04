<?php
/**
 * Ultimate Member profile — blok Tool List (course_group.tools = 1) + script accordion / tanggal lokal.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_um_profile_tool_list_html(int $user_id): string
{
    global $wpdb;

    $output = '';

    $cg_list = $wpdb->get_results(
        'SELECT * FROM wp_course_groups WHERE tools = 1 ORDER BY order_group'
    );

    $output .= "<h2 style='text-align: center'>Tool List</h2>";

    foreach ($cg_list as $cg) {
        $output .= "<div class='accordion-item'>";
        $title = isset($cg->title) ? (string) $cg->title : '';
        $output .= "<div class='accordion-tools accordion-box accordion-header' style='font-size: 26px; margin: 0'>" . esc_html($title) . '</div>';
        $output .= "<div class='panel-tools accordion-content' style='display: none; flex-direction: column'>";

        $q_list = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM wp_course_group_details WHERE course_group_id = %d ORDER BY orders',
                (int) $cg->id
            )
        );

        if (count($q_list) == 0) {
            $output .= "<div style='font-size: 20px; padding: 10px;'>Coming soon</div>";
        }

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
                $query = $wpdb->prepare(
                    "
                            SELECT id, created_by, date_created
                            FROM {$wpdb->prefix}gf_entry
                            WHERE form_id = %d AND created_by = %d AND created_by IS NOT NULL AND status ='active'
                        ",
                    $form_id,
                    $user_id
                );
                $entry_ids = $wpdb->get_results($query);
            } catch (Exception $e) {
                $entry_ids = [];
            }

            $c = count($entry_ids);

            $output .= "<div class='accordion-tools accordion-box' style='font-size: 22px; border: 1px solid #ccc;'> "
                . esc_html((string) $c_list[0]->page_title) . ' (' . (int) $c . ')</div>';
            $output .= "<div class='panel-tools' style='display: none;margin-bottom: 10px; flex-direction: row; flex-wrap: wrap'>";

            foreach ($entry_ids as $entry_row) {
                $timestamp = $entry_row->date_created;
                $formatted_date = date('F j, Y H:i:s', strtotime($timestamp));
                $link_open = esc_url($link_child) . '?dataId=' . (int) $entry_row->id . '&btn-close=true';

                $output .= '<a onclick="openWindowXfusion(\'' . esc_js($link_open) . '\')"
                            target="_blank" class="note-column"
                            style="color: #666; font-weight: bold; margin: 10px 10px 0 0; display: inline-block;"
                            data-timestamp="' . strtotime($timestamp) . '">
                            <span class="localized-time">' . esc_html($formatted_date) . '</span>
                        </a>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".note-column").forEach(function (element) {
        let timestamp = element.getAttribute("data-timestamp");
        if (timestamp) {
            let date = new Date(timestamp * 1000);
            let options = { year: "numeric", month: "long", day: "numeric", hour: "2-digit", minute: "2-digit" };
            let formattedDate = date.toLocaleString(undefined, options);
            var localized = element.querySelector(".localized-time");
            if (localized) {
                localized.innerText = formattedDate;
            }
        }
    });

    var acc = document.getElementsByClassName("accordion-tools");
    for (var i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function () {
            this.classList.toggle("active-tools");
            var panel = this.nextElementSibling;
            if (panel.style.display === "flex" || panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "flex";
            }
        });
    }
});
</script>';

    return $output;
}
