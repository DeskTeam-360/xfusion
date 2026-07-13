<?php

namespace App\Services;

/**
 * Builds the Step 2 AI Meeting Brief™ JSON from Step 1 continuous evidence.
 *
 * Each section returns:
 *   items: string[]  — bullet summaries shown on cards
 *   details: string  — full narrative opened via "View Details"
 */
class MeetingBriefFromEvidenceService
{
    /**
     * @param  array<string, mixed>  $evidence
     * @return array<string, array{items: list<string>, details: string}>
     */
    public function compose(array $evidence): array
    {
        $sections = is_array($evidence['sections'] ?? null) ? $evidence['sections'] : [];

        return [
            'alignment_snapshot' => $this->alignmentSnapshot($sections),
            'development_snapshot' => $this->developmentSnapshot($sections),
            'commitment_review' => $this->commitmentReview($sections),
            'behavioral_trends' => $this->behavioralTrends($sections),
            'suggested_discussion_areas' => $this->suggestedDiscussionAreas($sections),
            'emerging_opportunities' => $this->emergingOpportunities($sections),
            'potential_barriers' => $this->potentialBarriers($sections),
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function alignmentSnapshot(array $sections): array
    {
        $insights = $this->sectionData($sections, 'individual_insights');
        $commitments = $this->sectionData($sections, 'previous_commitments');
        $previous = $this->sectionData($sections, 'previous_1_on_1');
        $qbr = $this->sectionData($sections, 'qbr_priorities');
        $arp = $this->sectionData($sections, 'arp_priorities');

        $items = [];
        $focus = trim((string) ($insights['recommended_focus_area'] ?? ''));
        if ($focus !== '') {
            $items[] = 'Recommended focus: '.$this->truncate($focus, 120);
        }

        $openCount = $this->countOpenCommitments($commitments);
        if ($openCount > 0) {
            $items[] = "{$openCount} open commitment(s) carry forward into this meeting.";
        }

        if ($this->hasPreviousMeetings($previous)) {
            $items[] = 'Prior 1-on-1 context is available to support continuity and alignment.';
        }

        if ($this->isPlaceholder($qbr) || $this->isPlaceholder($arp)) {
            $items[] = 'Organizational priority context will deepen as QBR and ARP evidence sources connect.';
        }

        if ($items === []) {
            $items[] = 'Alignment evidence is still building — use this meeting to establish shared priorities.';
        }

        $details = $this->paragraphs([
            $focus !== '' ? "Recommended Focus Area: {$focus}" : null,
            $this->commitmentSummaryDetails($commitments),
            $this->previousMeetingSummaryDetails($previous),
            $this->placeholderNote($qbr, 'QBR Priorities'),
            $this->placeholderNote($arp, 'ARP Priorities'),
        ]);

        return ['items' => array_slice($items, 0, 4), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function developmentSnapshot(array $sections): array
    {
        $insights = $this->sectionData($sections, 'individual_insights');
        $drivers = $this->sectionData($sections, 'behavioral_driver_trends');
        $activities = $this->sectionData($sections, 'activities');
        $tools = $this->sectionData($sections, 'development_tools');
        $self = $this->sectionData($sections, 'self_assessments');
        $review360 = $this->sectionData($sections, 'previous_360');

        $items = [];
        $focus = trim((string) ($insights['recommended_focus_area'] ?? ''));
        if ($focus !== '') {
            $items[] = 'Development focus aligns with the latest performance insight.';
        }

        $topDriver = $this->topBehavioralDriver($drivers);
        if ($topDriver !== null) {
            $items[] = "Strongest behavioral signal: {$topDriver['title']} ({$topDriver['average']}).";
        }

        $activityCount = is_array($activities['items'] ?? null) ? count($activities['items']) : 0;
        if ($activityCount > 0) {
            $items[] = "{$activityCount} recent learning activit".($activityCount === 1 ? 'y' : 'ies').' support current development.';
        }

        $toolCount = is_array($tools['items'] ?? null) ? count($tools['items']) : 0;
        if ($toolCount > 0) {
            $items[] = "{$toolCount} recent development tool submission(s) recorded.";
        }

        if ($items === []) {
            $items[] = 'Development evidence is limited — explore growth themes directly in the conversation.';
        }

        $details = $this->paragraphs([
            $focus !== '' ? "Recommended Focus Area: {$focus}" : null,
            $this->behavioralDriversDetails($drivers),
            $this->listSectionDetails('Recent Activities', $activities),
            $this->listSectionDetails('Development Tools', $tools),
            $this->placeholderNote($self, 'Self-Assessments'),
            $this->placeholderNote($review360, 'Previous 360 Review™'),
        ]);

        return ['items' => array_slice($items, 0, 4), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function commitmentReview(array $sections): array
    {
        $commitments = $this->sectionData($sections, 'previous_commitments');
        $rows = is_array($commitments['items'] ?? null) ? $commitments['items'] : [];

        $items = [];
        $total = count($rows);
        if ($total === 0) {
            $items[] = 'No prior commitments on record for this employee.';
        } else {
            $items[] = "{$total} commitment record(s) across prior 1-on-1 meetings.";
            $open = $this->countOpenCommitments($commitments);
            $done = $total - $open;
            if ($open > 0) {
                $items[] = "{$open} commitment(s) remain open or in progress.";
            }
            if ($done > 0) {
                $items[] = "{$done} commitment(s) marked done.";
            }
            $high = $this->countByPriority($rows, 'high');
            if ($high > 0) {
                $items[] = "{$high} high-priority commitment(s) need attention.";
            }
        }

        $details = $this->commitmentSummaryDetails($commitments, true);

        return ['items' => array_slice($items, 0, 4), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function behavioralTrends(array $sections): array
    {
        $drivers = $this->sectionData($sections, 'behavioral_driver_trends');
        $ai = $this->sectionData($sections, 'ai_insight_trends');
        $self = $this->sectionData($sections, 'self_assessments');

        $items = [];
        $scores = is_array($drivers['scores'] ?? null) ? $drivers['scores'] : [];
        foreach ($scores as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            $avg = $row['average'] ?? null;
            if ($title === '' || $avg === null) {
                continue;
            }
            $items[] = "{$title}: {$avg}";
        }

        if ($items === []) {
            $items[] = 'Behavioral driver scores are not yet available.';
        }

        $observation = trim((string) ($ai['key_observation'] ?? ''));
        if ($observation !== '') {
            $items[] = 'Latest Overall Insight informs behavioral interpretation.';
        }

        $details = $this->paragraphs([
            $this->behavioralDriversDetails($drivers),
            $observation !== '' ? "Overall Insight: {$observation}" : null,
            $this->placeholderNote($self, 'Self-Assessments'),
        ]);

        return ['items' => array_slice($items, 0, 4), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function suggestedDiscussionAreas(array $sections): array
    {
        $insights = $this->sectionData($sections, 'individual_insights');
        $commitments = $this->sectionData($sections, 'previous_commitments');
        $ai = $this->sectionData($sections, 'ai_insight_trends');
        $org = $this->sectionData($sections, 'organizational_context');

        $items = [];
        $focus = trim((string) ($insights['recommended_focus_area'] ?? ''));
        if ($focus !== '') {
            $items[] = 'Explore progress and support related to the recommended focus area.';
        }

        $open = $this->countOpenCommitments($commitments);
        if ($open > 0) {
            $items[] = "Review status of {$open} open commitment(s) and agree on next steps.";
        }

        $observation = trim((string) ($ai['key_observation'] ?? ''));
        if ($observation !== '') {
            $items[] = 'Discuss themes surfaced in the latest Overall Insight.';
        }

        $items[] = 'Confirm priorities, barriers, and support needed before closing the meeting.';

        $details = $this->paragraphs([
            $focus !== '' ? "Recommended Focus Area: {$focus}" : null,
            $observation !== '' ? "Overall Insight: {$observation}" : null,
            $this->commitmentSummaryDetails($commitments, true),
            $this->placeholderNote($org, 'Organizational Context'),
            'Use these areas to structure a focused, open, and productive alignment conversation.',
        ]);

        return ['items' => array_slice($items, 0, 5), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function emergingOpportunities(array $sections): array
    {
        $ai = $this->sectionData($sections, 'ai_insight_trends');
        $activities = $this->sectionData($sections, 'activities');
        $tools = $this->sectionData($sections, 'development_tools');
        $drivers = $this->sectionData($sections, 'behavioral_driver_trends');

        $items = [];
        $topDriver = $this->topBehavioralDriver($drivers);
        if ($topDriver !== null) {
            $items[] = "Build on momentum in {$topDriver['title']} during this cycle.";
        }

        if (is_array($activities['items'] ?? null) && $activities['items'] !== []) {
            $items[] = 'Recent learning activities create openings for applied practice.';
        }

        if (is_array($tools['items'] ?? null) && $tools['items'] !== []) {
            $items[] = 'Completed development tools can be translated into workplace habits.';
        }

        $observation = trim((string) ($ai['key_observation'] ?? ''));
        if ($observation !== '') {
            $items[] = 'Overall Insight highlights growth patterns worth reinforcing.';
        }

        if ($items === []) {
            $items[] = 'Opportunities will become clearer as more evidence accumulates.';
        }

        $details = $this->paragraphs([
            $observation !== '' ? "Overall Insight: {$observation}" : null,
            $this->listSectionDetails('Recent Activities', $activities),
            $this->listSectionDetails('Development Tools', $tools),
            $this->behavioralDriversDetails($drivers),
        ]);

        return ['items' => array_slice($items, 0, 3), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array{items: list<string>, details: string}
     */
    private function potentialBarriers(array $sections): array
    {
        $commitments = $this->sectionData($sections, 'previous_commitments');
        $drivers = $this->sectionData($sections, 'behavioral_driver_trends');
        $ai = $this->sectionData($sections, 'ai_insight_trends');

        $items = [];
        $open = $this->countOpenCommitments($commitments);
        if ($open > 0) {
            $items[] = "{$open} open commitment(s) may compete with new priorities.";
        }

        $lowDriver = $this->lowestBehavioralDriver($drivers);
        if ($lowDriver !== null) {
            $items[] = "Lower signal in {$lowDriver['title']} ({$lowDriver['average']}) may indicate friction.";
        }

        $observation = trim((string) ($ai['key_observation'] ?? ''));
        if ($observation !== '') {
            $items[] = 'Review Overall Insight for risks or obstacles called out by AI analysis.';
        }

        if ($items === []) {
            $items[] = 'No major barriers flagged in current evidence — validate assumptions in conversation.';
        }

        $details = $this->paragraphs([
            $this->commitmentSummaryDetails($commitments, true),
            $this->behavioralDriversDetails($drivers),
            $observation !== '' ? "Overall Insight: {$observation}" : null,
        ]);

        return ['items' => array_slice($items, 0, 3), 'details' => $details];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    private function sectionData(array $sections, string $key): array
    {
        if (! isset($sections[$key]) || ! is_array($sections[$key])) {
            return [];
        }

        $data = $sections[$key]['data'] ?? [];
        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    private function hasPreviousMeetings(array $previous): bool
    {
        return is_array($previous['meetings'] ?? null) && $previous['meetings'] !== [];
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function isPlaceholder(array $section): bool
    {
        return ($section['status'] ?? '') === 'placeholder';
    }

    /**
     * @param  array<string, mixed>  $commitments
     */
    private function countOpenCommitments(array $commitments): int
    {
        $rows = is_array($commitments['items'] ?? null) ? $commitments['items'] : [];
        $count = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $status = (string) ($row['status'] ?? 'open');
            if (in_array($status, ['open', 'in_progress'], true)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function countByPriority(array $rows, string $priority): int
    {
        $count = 0;
        foreach ($rows as $row) {
            if ((string) ($row['priority'] ?? '') === $priority) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $drivers
     * @return array{title: string, average: string}|null
     */
    private function topBehavioralDriver(array $drivers): ?array
    {
        $scores = is_array($drivers['scores'] ?? null) ? $drivers['scores'] : [];
        $best = null;
        foreach ($scores as $row) {
            if (! is_array($row) || $row['average'] === null) {
                continue;
            }
            if ($best === null || (float) $row['average'] > (float) $best['average']) {
                $best = [
                    'title' => (string) ($row['title'] ?? ''),
                    'average' => (string) $row['average'],
                ];
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>  $drivers
     * @return array{title: string, average: string}|null
     */
    private function lowestBehavioralDriver(array $drivers): ?array
    {
        $scores = is_array($drivers['scores'] ?? null) ? $drivers['scores'] : [];
        $low = null;
        foreach ($scores as $row) {
            if (! is_array($row) || $row['average'] === null) {
                continue;
            }
            if ($low === null || (float) $row['average'] < (float) $low['average']) {
                $low = [
                    'title' => (string) ($row['title'] ?? ''),
                    'average' => (string) $row['average'],
                ];
            }
        }

        return $low;
    }

    /**
     * @param  array<string, mixed>  $commitments
     */
    private function commitmentSummaryDetails(array $commitments, bool $listAll = false): string
    {
        $rows = is_array($commitments['items'] ?? null) ? $commitments['items'] : [];
        if ($rows === []) {
            return 'No commitment records are available yet.';
        }

        $lines = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? 'Untitled'));
            $status = (string) ($row['status'] ?? 'open');
            $priority = (string) ($row['priority'] ?? 'medium');
            $lines[] = "- {$title} [{$status}, {$priority} priority]";
            if (! $listAll && count($lines) >= 8) {
                $lines[] = '- …';
                break;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    private function previousMeetingSummaryDetails(array $previous): ?string
    {
        $meetings = is_array($previous['meetings'] ?? null) ? $previous['meetings'] : [];
        if ($meetings === []) {
            return null;
        }

        $lines = ['Previous 1-on-1 meetings on record:'];
        foreach (array_slice($meetings, 0, 5) as $meeting) {
            if (! is_array($meeting)) {
                continue;
            }
            $date = (string) ($meeting['date'] ?? 'Unknown date');
            $leader = (string) ($meeting['leader_name'] ?? 'Leader');
            $status = (string) ($meeting['status'] ?? '');
            $lines[] = "- {$date} with {$leader}".($status !== '' ? " ({$status})" : '');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $drivers
     */
    private function behavioralDriversDetails(array $drivers): ?string
    {
        $scores = is_array($drivers['scores'] ?? null) ? $drivers['scores'] : [];
        if ($scores === []) {
            return null;
        }

        $lines = ['Behavioral Driver scores (1–5):'];
        foreach ($scores as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = (string) ($row['title'] ?? '');
            $avg = $row['average'] ?? null;
            $lines[] = $avg !== null ? "- {$title}: {$avg}" : "- {$title}: no score";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function listSectionDetails(string $label, array $section): ?string
    {
        $items = is_array($section['items'] ?? null) ? $section['items'] : [];
        if ($items === []) {
            return null;
        }

        $lines = ["{$label}:"];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? 'Untitled'));
            $date = trim((string) ($item['submitted_at'] ?? ''));
            $lines[] = $date !== '' ? "- {$title} ({$date})" : "- {$title}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function placeholderNote(array $section, string $label): ?string
    {
        if (! $this->isPlaceholder($section)) {
            return null;
        }

        return "{$label}: evidence source not yet connected for this employee.";
    }

    /**
     * @param  list<string|null>  $parts
     */
    private function paragraphs(array $parts): string
    {
        $filtered = array_values(array_filter($parts, static fn ($p) => is_string($p) && trim($p) !== ''));

        return $filtered === [] ? 'No additional detail is available for this section yet.' : implode("\n\n", $filtered);
    }

    private function truncate(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return rtrim(substr($text, 0, $max - 1)).'…';
    }
}
