<?php
/**
 * Redirect pengguna tidak login dari topic tertentu.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_redirect_single_topic(): void
{
    if (!is_singular('sfwd-topic')) {
        return;
    }

    global $post;
    if (!$post) {
        return;
    }

    /** @var list<int> $redirect_topic_ids */
    $redirect_topic_ids = [
        8002, 10033, 10562, 10574, 10242, 10576, 10047, 10135, 10137, 10224, 10233, 10186, 10215, 10039, 10196, 10101, 10106, 10578, 10204, 10580, 10260, 10582, 10140, 10125, 10144, 10584, 10237, 10228, 10220, 10564, 10142, 10131, 10120, 10571, 10569, 10116, 10193, 10190, 10182, 10566, 10207, 10211, 10199, 10586, 10588, 10590, 10592, 10594, 10596, 10598, 10600, 10602, 10604, 10606, 10608, 10610, 10612, 10614, 10617, 10619, 10621, 10623, 10625, 10627, 10629, 10631, 10633, 10635, 10637, 10639, 10641, 10643, 10645, 10110, 10647, 10649, 10651, 10653, 10655, 10657, 10247, 10659, 10661, 10663, 10665, 10667, 10669, 10671, 10673, 10675, 10677, 10679, 10681, 10683, 10685, 10687, 10689, 10721, 10723, 10725, 10727, 10729, 10731, 10738, 10740, 12892, 13134,
    ];

    if (in_array((int) $post->ID, $redirect_topic_ids, true) && !is_user_logged_in()) {
        wp_safe_redirect(home_url('/you-dont-have-access/'));
        exit;
    }
}

add_action('template_redirect', 'xfusion_redirect_single_topic');
