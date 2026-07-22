<?php

namespace App\Services;

/**
 * Builds Step 6 AI Organizational Synthesis™ JSON from the evidence,
 * assessment, leadership context/discussion, and commitments when
 * Xfusion-llm is unavailable.
 */
class QbrSynthesisFromContextService
{
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

        return [
            'executive_summary' => $executiveSummary,
            'organizational_readiness_summary' => [
                'score' => $score,
                'trend' => $trend,
                'narrative' => $score !== null
                    ? 'Readiness is '.($trend === 'up' ? 'improving' : ($trend === 'down' ? 'declining' : 'holding steady')).' based on available evidence.'
                    : 'Not enough evaluation coverage this quarter to establish a readiness trend.',
            ],
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
            'recommended_areas_of_attention' => array_slice(array_values(array_filter([
                $opportunities[0] ?? null,
                $risks[0] ?? null,
            ])), 0, 3),
            'leadership_context_considered' => $leadershipContext !== null && trim($leadershipContext) !== '',
            'discussion_notes_considered' => $discussionNotes !== null && trim(strip_tags($discussionNotes)) !== '',
        ];
    }
}
