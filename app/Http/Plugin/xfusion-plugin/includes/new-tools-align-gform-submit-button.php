<?php

/**
 * CSS for specific LearnDash topic pages.
 *
 * @package XFusion
 */

if (! defined('ABSPATH')) {
    exit;
}

$topic_positions = [
    'situational-leadership-scenarios-tactics-exercise-new' => 70,
    'leadership-impact-legacy-roadmap-new' => 70,
    'conflict-resolution-tool-new' => 160,
    'communication-tracker-new' => 150,
    'psychological-safety-inventory-new' => 90,
    'performance-improvement-plan-case-studies-new' => 160,
    'p-e-s-t-situational-analysis-tool-new' => 70,
    'sop-building-tool-new' => 130,
    'raci-accountability-matrix-new' => 150,
    'stop-start-continue-exercise-new' => 70,
    'rubric-for-assessing-meeting-effectiveness-new' => 70,
    'self-awareness-and-our-performance-narrative-new' => 13,
    'self-reflective-awareness-checklist-new' => 130,
    'fear-and-vulnerability-exercise-new' => 130,
    'emotional-comfort-zone-new' => 35,
    'cognitive-checkin-exercises-new' => 90,
    'abc-worksheet-new' => 90,
    'individual-performance-plan-template-new' => 90,
    'work-identification-prioritization-new' => 90,
    'smart-goal-worksheet-new' => 90,
    'development-vs-performance-goal-setting-activity-new' => 70,
    'accomplishment-tracking-new' => 90,
    'saying-no-scripts-worksheet-new' => 70,
    'meaningful-relationships-activity-new' => 130,
    'social-media-detox-new' => 90,
    'problem-solving-template-new' => 70,
    'swot-analysis-new' => 70,
    'igniting-my-purpose-new' => 15,
    'legacy-roadmap-new' => 160,
];

add_action('wp_head', function () use ($topic_positions) {
    if (!is_singular('sfwd-topic')) {
        return;
    }

    $slug = get_post_field('post_name', get_queried_object_id());

    if (!array_key_exists($slug, $topic_positions)) {
        return;
    }

    $top = $topic_positions[$slug];
?>
    <style>
        input[type="submit"].gform_button {
            position: relative !important;
            top: <?php echo esc_html($top); ?>px !important;
        }
    </style>
<?php
});
