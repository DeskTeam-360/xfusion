<?php
/**
 * Shortcode UM: gabung Course List + Tool List.
 *
 * Shortcode: [xfusion_um_profile_courses]
 * Pecahan: um-profile-courses-shared.php | course-list | tool-list
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/**
 * Konten Course List + Tool List untuk profil Ultimate Member.
 */
function xfusion_um_profile_courses_render(): string
{
    $output = '';

    if (!function_exists('um_is_myprofile')) {
        return $output;
    }

    $user_id = xfusion_um_profile_courses_resolve_user_id();

    if (!UM()->options()->get('profile_empty_text')) {
        return $output;
    }

    $emo = UM()->options()->get('profile_empty_text_emo');
    if ($emo) {
        $emo = '<i class="um-faicon-frown-o"></i>';
    } else {
        $emo = false;
    }

    if (um_is_myprofile()) {
        $output .= xfusion_um_profile_courses_inline_styles_and_toggle();
        $output .= xfusion_um_profile_course_list_html($user_id);
        $output .= xfusion_um_profile_tool_list_html($user_id);
    } else {
        $output .= '<p class="um-profile-note">' . $emo . '<span>' . __('This user has not added any information to their profile yet.', 'ultimate-member') . '</span></p>';
    }

    return $output;
}

function xfusion_um_profile_courses_shortcode($atts): string
{
    return xfusion_um_profile_courses_render();
}

add_shortcode('xfusion_um_profile_courses', 'xfusion_um_profile_courses_shortcode');
