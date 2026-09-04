<?php

namespace App\Services;

/**
 * Builds Step 6 AI Organizational Synthesis™ JSON from the evidence,
 * assessment, leadership context/discussion, and commitments when
 * Xfusion-llm is unavailable. Mirrors the shape/logic of
 * Xfusion-llm's routers/qbr.py::_normalize_synthesis() so the UI gets the
 * same schema regardless of which path produced it.
 */
class QbrSynthesisFromContextService
{
    private const COR_CAPABILITY_TITLES = [
        'alignment' => 'Alignment',
        'accountability' => 'Accountability',
        'communication' => 'Communication',
        'leadership' => 'Leadership',
        'execution' => 'Execution',
    ];

    /**
     * @param  array<string, mixed>  $evidence
     * @param  array<string, mixed>  $assessment
     * @param  string|null  $leadershipContext
     * @param  string|null  $discussionNotes
     * @param  list<array<string, mixed>>  $commitments
     * @return array<string, mixed>
     */
    public function compose(array $evidence, array $assessment, ?string $leadershipContext, ?string $discussionNotes, array $commitments): array
    {
        $score = $evidence['overall_readiness_score'] ?? ($assessment['overall_readiness']['score'] ?? null);
        $trend = $evidence['overall_readiness_trend'] ?? ($assessment['overall_readiness']['trend'] ?? null);

        $executiveSummary = $score !== null
            ? "This quarter's organizational readiness score is {$score}/100".($trend ? " ({$trend} vs last quarter)" : '').'. '.count($commitments).' commitment(s) have been established for the upcoming quarter.'
            : 'Insufficient evidence was available to compute a numeric readiness score this quarter.';

        $strengths = $assessment['top_strengths'] ?? [];
        $opportunities = $assessment['top_opportunities'] ?? [];
        $risks = $assessment['emerging_risks'] ?? [];

        $commitmentLines = array_values(array_map(
            fn ($c) => ($c['title'] ?? 'Untitled commitment').' ('.($c['status'] ?? 'open').')',
            $commitments
        ));

        $leadershipConsidered = $leadershipContext !== null && trim($leadershipContext) !== '';
        $discussionConsidered = $discussionNotes !== null && trim(strip_tags($discussionNotes)) !== '';

        return [
            'executive_summary' => $executiveSummary,
            'organizational_readiness_summary' => [
                'score' => $score,
                'trend' => $trend,
                'narrative' => $score !== null
                    ? 'Readiness is '.($trend === 'up' ? 'improving' : ($trend === 'down' ? 'declining' : 'holding steady')).' based on available evidence.'
                    : 'Not enough evaluation coverage this quarter to establish a readiness trend.',
            ],
            'confidence_level' => $this->confidenceLevel($evidence, $assessment, $commitments, $leadershipConsidered, $discussionConsidered),
            'data_completeness' => $this->dataCompleteness($evidence),
            'organizational_strengths' => array_slice($strengths, 0, 5),
            'organizational_opportunities' => array_slice($opportunities, 0, 5),
            'key_risks' => array_slice($risks, 0, 5),
            'quarterly_focus' => $commitmentLines !== [] ? array_slice($commitmentLines, 0, 5) : ['No commitments recorded yet — add them in Step 5 before publishing.'],
            'commitment_summary' => [
                'total' => count($commitments),
                'high_priority' => count(array_filter($commitments, fn ($c) => ($c['priority'] ?? '') === 'high')),
                'in_progress' => count(array_filter($commitments, fn ($c) => ($c['status'] ?? '') === 'in_progress')),
                'not_started' => count(array_filter($commitments, fn ($c) => ($c['status'] ?? '') === 'open')),
            ],
            'recommended_areas_of_attention' => $this->recommendedAreasOfAttention($assessment, $opportunities, $risks),
            'leadership_context_considered' => $leadershipConsidered,
            'discussion_notes_considered' => $discussionConsidered,
        ];
    }

    /** % of Step 1 evidence sources marked available — a plain count, never fabricated. */
    private function dataCompleteness(array $evidence): array
    {
        $sources = $evidence['evidence_sources'] ?? [];
        if (! is_array($sources) || $sources === []) {
            return ['percent' => 0, 'label' => 'No evidence sources available yet.'];
        }

        $total = count($sources);
        $available = count(array_filter($sources, fn ($s) => is_array($s) && ! empty($s['available'])));
        $percent = $total > 0 ? (int) round($available / $total * 100) : 0;

        $label = match (true) {
            $percent >= 80 => 'High — most evidence sources are current.',
            $percent >= 50 => 'Medium — some evidence sources are still missing.',
            default => 'Low — most evidence sources are not yet available.',
        };

        return ['percent' => $percent, 'label' => $label];
    }

    /** How complete this quarter's inputs are, as a plain signal count — not an AI judgment call. */
    private function confidenceLevel(array $evidence, array $assessment, array $commitments, bool $leadershipConsidered, bool $discussionConsidered): array
    {
        $signals = [
            $evidence !== [] && ($evidence['overall_readiness_score'] ?? null) !== null,
            $assessment !== [],
            $commitments !== [],
            $leadershipConsidered,
            $discussionConsidered,
        ];
        $percent = (int) round((count(array_filter($signals)) / count($signals)) * 100);

        $label = match (true) {
            $percent >= 80 => 'High confidence — based on complete quarterly inputs.',
            $percent >= 50 => 'Medium confidence — some quarterly inputs are missing.',
            default => 'Low confidence — most quarterly inputs are missing.',
        };

        return ['percent' => $percent, 'label' => $label];
    }

    /** Which COR capabilities need attention is decided by the actual Step 3 scores (lowest first), not invented here. */
    private function recommendedAreasOfAttention(array $assessment, array $opportunities, array $risks): array
    {
        $caps = $assessment['cor_capability_assessment'] ?? [];
        $scored = array_values(array_filter(
            is_array($caps) ? $caps : [],
            fn ($c) => is_array($c) && ($c['score'] ?? null) !== null && isset(self::COR_CAPABILITY_TITLES[$c['capability'] ?? ''])
        ));
        usort($scored, fn ($a, $b) => $a['score'] <=> $b['score']);

        $out = [];
        foreach (array_slice($scored, 0, 3) as $c) {
            $capability = $c['capability'];
            $title = self::COR_CAPABILITY_TITLES[$capability];
            $out[] = [
                'capability' => $capability,
                'title' => $title,
                'description' => "{$title} scored lowest of the five COR capabilities this quarter and needs continued focus.",
            ];
        }

        if ($out === []) {
            foreach (array_slice(array_values(array_filter([$opportunities[0] ?? null, $risks[0] ?? null])), 0, 3) as $text) {
                $out[] = ['capability' => null, 'title' => 'Focus Area', 'description' => $text];
            }
        }

        return $out;
    }
}
