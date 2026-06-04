<?php
/**
 * Shortcode [mark_complete_button topic_id="" gravity_form_id=""]
 * — Submit Gravity Forms (jika ada), lalu setelah sukses AJAX GF baru mark topic LearnDash selesai.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_custom_mark_complete_button($atts): string
{
    $atts = shortcode_atts([
        'topic_id'         => '',
        'gravity_form_id'  => '',
    ], $atts, 'mark_complete_button');

    if (empty($atts['topic_id'])) {
        return '';
    }

    $topic_id = (int) $atts['topic_id'];
    $gf_id    = $atts['gravity_form_id'] !== '' && $atts['gravity_form_id'] !== null
        ? (int) $atts['gravity_form_id']
        : 0;

    return sprintf(
        '<button type="button" class="mark-as-complete" data-topic-id="%s" data-gravity-form-id="%s">Mark as Complete</button>',
        esc_attr((string) $topic_id),
        esc_attr((string) ($gf_id > 0 ? $gf_id : ''))
    );
}

add_shortcode('mark_complete_button', 'xfusion_custom_mark_complete_button');

add_action('wp_ajax_mark_topic_complete', 'xfusion_mark_topic_complete');
add_action('wp_ajax_nopriv_mark_topic_complete', 'xfusion_mark_topic_complete');

function xfusion_mark_topic_complete(): void
{
    check_ajax_referer('xfusion_mark_topic_complete', 'nonce');

    if (!isset($_POST['topic_id'], $_POST['user_id'])) {
        wp_send_json_error('Invalid request');
        return;
    }

    $user_id = (int) $_POST['user_id'];
    $topic_id = (int) $_POST['topic_id'];

    if (!function_exists('learndash_is_topic_complete') || !function_exists('learndash_process_mark_complete')) {
        wp_send_json_error('LearnDash not available');
        return;
    }

    if (learndash_is_topic_complete($user_id, $topic_id)) {
        wp_send_json_success('Topic already completed');
        return;
    }

    if (learndash_process_mark_complete($user_id, $topic_id)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to mark topic as complete');
    }
}

add_action('wp_enqueue_scripts', 'xfusion_enqueue_mark_topic_complete_scripts');

function xfusion_enqueue_mark_topic_complete_scripts(): void
{
    wp_register_script(
        'xfusion-mark-topic-complete',
        false,
        ['jquery'],
        null,
        true
    );
    wp_enqueue_script('xfusion-mark-topic-complete');

    wp_localize_script('xfusion-mark-topic-complete', 'xfusionMarkTopic', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'user_id'  => get_current_user_id(),
        'nonce'    => wp_create_nonce('xfusion_mark_topic_complete'),
    ]);

    $inline = <<<'JS'
(function ($) {
    function resolveGravityFormId($btn) {
        var explicit = $btn.attr('data-gravity-form-id');
        if (explicit && String(explicit).trim() !== '') {
            return String(parseInt(explicit, 10));
        }
        var wrap = document.querySelector('.gform_wrapper');
        if (!wrap || !wrap.id) {
            return '';
        }
        var m = wrap.id.match(/^gform_wrapper_(\d+)$/);
        return m ? m[1] : '';
    }

    function enableGfInputsForSubmit(wrapper) {
        if (!wrapper) {
            return;
        }
        wrapper.querySelectorAll(
            'input[name^="input_"][disabled], select[name^="input_"][disabled], textarea[name^="input_"][disabled]'
        ).forEach(function (el) {
            el.removeAttribute('disabled');
        });
    }

    function findGfSubmitControl(formId) {
        var fid = String(formId);
        var byId = document.getElementById('gform_submit_button_' + fid);
        if (byId) {
            return byId;
        }
        var wrap = document.getElementById('gform_wrapper_' + fid);
        if (!wrap) {
            return null;
        }
        var inside = wrap.querySelector(
            'input[type="submit"].gform_button, button[type="submit"].gform_button, input.gform_next_button, button.gform_next_button'
        );
        return inside || null;
    }

    function markLearnDashTopic(topicId) {
        return $.ajax({
            url: xfusionMarkTopic.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'mark_topic_complete',
                topic_id: topicId,
                user_id: xfusionMarkTopic.user_id,
                nonce: xfusionMarkTopic.nonce
            }
        });
    }

    $(function () {
        $(document).on('click', '.mark-as-complete', function () {
            var $btn = $(this);
            var topicId = $btn.data('topic-id');
            var formId = resolveGravityFormId($btn);

            function finishLd(response) {
                if (!response || !response.success) {
                    alert(
                        typeof response.data === 'string'
                            ? response.data
                            : (response.data && response.data.message) || 'Error'
                    );
                }
            }

            if (!formId) {
                markLearnDashTopic(topicId).done(finishLd);
                return;
            }

            var wrap = document.getElementById('gform_wrapper_' + formId);
            var formEl = document.getElementById('gform_' + formId);
            if (!wrap || !formEl) {
                markLearnDashTopic(topicId).done(finishLd);
                return;
            }

            enableGfInputsForSubmit(wrap);

            var submitCtl = findGfSubmitControl(formId);
            if (!submitCtl) {
                markLearnDashTopic(topicId).done(finishLd);
                return;
            }

            var pendingNs = '.xfusionPendingLdMark';

            function xfLdConfirm(event, loadedFormId) {
                if (String(loadedFormId) !== String(formId)) {
                    return;
                }
                $(document).off('gform_confirmation_loaded' + pendingNs, xfLdConfirm);
                markLearnDashTopic(topicId).done(finishLd);
            }

            $(document).off('gform_confirmation_loaded' + pendingNs);
            $(document).on('gform_confirmation_loaded' + pendingNs, xfLdConfirm);

            if (typeof submitCtl.click === 'function') {
                submitCtl.click();
            } else {
                $(document).off('gform_confirmation_loaded' + pendingNs, xfLdConfirm);
                markLearnDashTopic(topicId).done(finishLd);
            }
        });
    });
})(jQuery);
JS;

    wp_add_inline_script('xfusion-mark-topic-complete', $inline, 'after');
}
