<?php

namespace App\Services;

/**
 * Builds Step 3 AI Organizational Assessment™ JSON directly from the Step 1/2
 * evidence snapshot when Xfusion-llm is unavailable — same fallback role as
 * MeetingSynthesisFromContextService for 1-on-1.
 */
class QbrAssessmentFromEvidenceService
{
    /**
     * @param  array<string, mixed>  $evidence
     * @return array<string, mixed>
     */
    public function compose(array $evidence): array
    {
        $score = $evidence['overall_readiness_score'] ?? null;
        $corTrends = $evidence['cor_capability_trends'] ?? [];

        $capabilityAssessments = array_map(function ($row) {
            $score5 = $row['score'] ?? null;
            $label = $score5 === null ? 'No data'
                : ($score5 >= 4.0 ? 'Strength' : ($score5 >= 3.0 ? 'Developing' : 'Opportunity'));

            return [
                'capability' => $row['capability'],
                'score' => $score5 !== null ? round($score5 * 20, 0) : null,
                'label' => $label,
            ];
        }, $corTrends);

        $strengths = array_values(array_map(
            fn ($c) => ucfirst($c['capability']).' is trending as a relative strength this quarter.',
            array_filter($capabilityAssessments, fn ($c) => $c['label'] === 'Strength')
        ));
        $opportunities = array_values(array_map(
            fn ($c) => ucfirst($c['capability']).' shows room for improvement this quarter.',
            array_filter($capabilityAssessments, fn ($c) => $c['label'] === 'Opportunity')
        ));

        if ($strengths === []) {
            $strengths[] = 'Insufficient evaluation data this quarter to identify a clear strength — review Individual Insights™ coverage.';
        }
        if ($opportunities === []) {
            $opportunities[] = 'No clear opportunity area surfaced from current evidence — revisit once more evaluations are completed.';
        }

        $risks = [];
        $oneOnOneRate = $evidence['one_on_one_completion']['rate'] ?? null;
        if ($oneOnOneRate !== null && $oneOnOneRate < 70) {
            $risks[] = '1-on-1 completion rate is below 70% — alignment conversations may be falling behind.';
        }
        $commitmentRate = $evidence['commitment_completion']['rate'] ?? null;
        if ($commitmentRate !== null && $commitmentRate < 60) {
            $risks[] = 'Prior quarter commitment completion is below 60% — follow-through may need reinforcement.';
        }
        if ($risks === []) {
            $risks[] = 'No emerging risks identified from the evidence gathered this quarter.';
        }

        $opportunitySignals = [];
        $objectivesProgress = $evidence['qbr_objectives_progress']['progress'] ?? null;
        if ($objectivesProgress !== null && $objectivesProgress >= 70) {
            $opportunitySignals[] = 'Strong progress on ARP objectives ('.$objectivesProgress.'%) — consider raising ambition for next quarter.';
        } else {
            $opportunitySignals[] = 'Use this review to re-confirm ARP objective ownership and timelines.';
        }

        return [
            'overall_readiness' => [
                'score' => $score,
                'label' => $score === null ? 'No data' : ($score >= 70 ? 'Strong' : ($score >= 50 ? 'Moderate Strength' : 'Needs Attention')),
                'trend' => $evidence['overall_readiness_trend'] ?? null,
            ],
            'confidence_level' => [
                'percent' => count(array_filter($corTrends, fn ($c) => $c['score'] !== null)) > 0 ? 60 : 20,
                'label' => 'Based on data completeness — generated without AI (Xfusion-llm unavailable).',
            ],
            'cor_capability_assessment' => $capabilityAssessments,
            'top_strengths' => array_slice($strengths, 0, 5),
            'top_opportunities' => array_slice($opportunities, 0, 5),
            'emerging_risks' => array_slice($risks, 0, 5),
            'emerging_opportunities' => array_slice($opportunitySignals, 0, 5),
        ];
    }
}
