<?php
/**
 * Utilitas maintenance indeks pencarian LearnDash (_search_index).
 * Sebelumnya bisa dipanggil via ?reset_index / ?reindex — sekarang wajib administrator.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

add_action('init', static function (): void {
    if (!isset($_GET['reset_index'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to reset the search index.', 'xfusion'), 403);
    }

    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
            '_search_index'
        )
    );

    echo esc_html__('Search index cleared.', 'xfusion');
    exit;
});

add_action('init', static function (): void {
    if (!isset($_GET['reindex'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to reindex topics.', 'xfusion'), 403);
    }

    $posts = get_posts([
        'post_type'      => 'sfwd-topic',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $ids = [];
    foreach ($posts as $post_id) {
        wp_update_post(['ID' => $post_id]);
        $ids[] = (int) $post_id;
    }

    echo esc_html__('Reindex triggered.', 'xfusion') . ' ';
    echo esc_html(implode(',', $ids));
    exit;
});
