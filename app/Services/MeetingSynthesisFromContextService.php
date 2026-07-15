<?php

namespace App\Services;

/**
 * Builds Step 6 AI Meeting Synthesis™ JSON from prep, notes, and commitments
 * when the Xfusion-llm service is unavailable.
 */
class MeetingSynthesisFromContextService
{
    /**
     * @param  array<string, mixed>  $preparations
     * @param  list<array<string, mixed>>  $notes
     * @param  list<array<string, mixed>>  $commitments
     * @return array<string, mixed>
     */
    public function compose(array $preparations, array $notes, array $commitments): array
    {
        $employeePrep = is_array($preparations['employee'] ?? null) ? $preparations['employee'] : [];
        $leaderPrep = is_array($preparations['leader'] ?? null) ? $preparations['leader'] : [];
        $noteLines = $this->noteLines($notes);
        $commitmentLines = $this->commitmentLines($commitments);
        $employeeCount = $this->countByRole($commitments, 'employee');
        $leaderCount = $this->countByRole($commitments, 'leader');
        $openCount = $this->countOpen($commitments);

        $meetingItems = array_values(array_filter(array_merge(
            $noteLines !== [] ? ['Conversation notes captured across '.count($notes).' section(s).'] : [],
            $commitmentLines !== [] ? [count($commitments).' commitment(s) recorded for follow-through.'] : [],
            $this->prepHighlights($employeePrep, $leaderPrep),
        )));
        if ($meetingItems === []) {
            $meetingItems[] = 'Meeting context was limited — review preparation and notes together to complete this record.';
        }

        $developmentItems = $this->developmentItems($employeePrep, $leaderPrep, $notes);
        $riskItems = $this->riskItems($notes);
        $opportunityItems = $this->opportunityItems($notes, $commitments);
        $coachingItems = $this->coachingTopics($employeePrep, $leaderPrep, $notes);
        $followUpItems = $this->followUpItems($commitments);

        $hasContext = $noteLines !== [] || $employeePrep !== [] || $leaderPrep !== [] || $commitmentLines !== [];

        return [
            'meeting_summary' => [
                'items' => array_slice($meetingItems, 0, 4),
                'details' => $this->paragraphs(array_merge($noteLines, $commitmentLines)),
            ],
            'alignment_summary' => [
                'score' => $hasContext ? 4.0 : null,
                'label' => $hasContext ? 'Aligned' : 'Review together',
                'items' => array_slice(array_values(array_filter([
                    $employeePrep !== [] ? 'Employee preparation captured.' : null,
                    $leaderPrep !== [] ? 'Leader preparation captured.' : null,
                    $notes !== [] ? 'Shared conversation notes available.' : null,
                ])), 0, 4),
                'details' => 'Alignment themes should be confirmed together using the preparation and notes captured in this wizard.',
            ],
            'development_summary' => [
                'items' => array_slice($developmentItems, 0, 4),
                'details' => $this->paragraphs($developmentItems),
            ],
            'commitment_summary' => [
                'items' => array_slice($commitmentLines, 0, 8),
                'details' => $commitmentLines !== []
                    ? implode("\n\n", $commitmentLines)
                    : 'No commitments were saved before synthesis generation.',
                'employee_count' => $employeeCount,
                'leader_count' => $leaderCount,
                'open_count' => $openCount,
            ],
            'emerging_risks' => [
                'items' => array_slice($riskItems, 0, 4),
                'details' => $this->paragraphs($riskItems),
            ],
            'emerging_opportunities' => [
                'items' => array_slice($opportunityItems, 0, 4),
                'details' => $this->paragraphs($opportunityItems),
            ],
            'suggested_coaching_topics' => [
                'items' => array_slice($coachingItems, 0, 4),
                'details' => $this->paragraphs($coachingItems),
            ],
            'recommended_follow_up' => [
                'items' => array_slice($followUpItems, 0, 4),
                'details' => $this->paragraphs($followUpItems),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     * @return list<string>
     */
    private function noteLines(array $notes): array
    {
        $lines = [];
        foreach ($notes as $note) {
            if (! is_array($note)) {
                continue;
            }
            $section = trim((string) ($note['section'] ?? 'general'));
            $text = trim((string) ($note['note'] ?? ''));
            if ($text === '') {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', $section));
            $lines[] = "{$label}: ".$this->truncate($text, 180);
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $commitments
     * @return list<string>
     */
    private function commitmentLines(array $commitments): array
    {
        $lines = [];
        foreach ($commitments as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $role = trim((string) ($row['owner_role'] ?? 'shared'));
            $status = trim((string) ($row['status'] ?? 'open'));
            $lines[] = "{$title} ({$role}, {$status})";
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $employeePrep
     * @param  array<string, mixed>  $leaderPrep
     * @return list<string>
     */
    private function prepHighlights(array $employeePrep, array $leaderPrep): array
    {
        $items = [];
        foreach (['employee' => $employeePrep, 'leader' => $leaderPrep] as $role => $prep) {
            foreach ($prep as $field => $value) {
                $text = trim((string) $value);
                if ($text === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', (string) $field));
                $items[] = ucfirst($role)." prep — {$label}: ".$this->truncate($text, 100);
                if (count($items) >= 2) {
                    break 2;
                }
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $employeePrep
     * @param  array<string, mixed>  $leaderPrep
     * @param  list<array<string, mixed>>  $notes
     * @return list<string>
     */
    private function developmentItems(array $employeePrep, array $leaderPrep, array $notes): array
    {
        $items = [];
        foreach (['development', 'growth', 'support'] as $needle) {
            foreach ([$employeePrep, $leaderPrep] as $prep) {
                foreach ($prep as $field => $value) {
                    if (! str_contains((string) $field, $needle)) {
                        continue;
                    }
                    $text = trim((string) $value);
                    if ($text !== '') {
                        $items[] = $this->truncate($text, 120);
                    }
                }
            }
            foreach ($notes as $note) {
                if (! is_array($note) || ! str_contains((string) ($note['section'] ?? ''), $needle)) {
                    continue;
                }
                $text = trim((string) ($note['note'] ?? ''));
                if ($text !== '') {
                    $items[] = $this->truncate($text, 120);
                }
            }
        }

        if ($items === []) {
            $items[] = 'Use preparation and conversation notes to identify development themes for the next cycle.';
        }

        return array_values(array_unique($items));
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     * @return list<string>
     */
    private function riskItems(array $notes): array
    {
        $items = [];
        foreach ($notes as $note) {
            if (! is_array($note)) {
                continue;
            }
            $section = (string) ($note['section'] ?? '');
            if (! str_contains($section, 'barrier') && ! str_contains($section, 'risk')) {
                continue;
            }
            $text = trim((string) ($note['note'] ?? ''));
            if ($text !== '') {
                $items[] = $this->truncate($text, 120);
            }
        }

        if ($items === []) {
            $items[] = 'No explicit risks were captured in conversation notes.';
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     * @param  list<array<string, mixed>>  $commitments
     * @return list<string>
     */
    private function opportunityItems(array $notes, array $commitments): array
    {
        $items = [];
        foreach ($notes as $note) {
            if (! is_array($note)) {
                continue;
            }
            $section = (string) ($note['section'] ?? '');
            if (! str_contains($section, 'opportunit') && ! str_contains($section, 'future')) {
                continue;
            }
            $text = trim((string) ($note['note'] ?? ''));
            if ($text !== '') {
                $items[] = $this->truncate($text, 120);
            }
        }

        if ($commitments !== []) {
            $items[] = 'Follow through on new commitments to convert discussion into measurable progress.';
        }

        if ($items === []) {
            $items[] = 'Explore opportunities surfaced during preparation and the live conversation.';
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $employeePrep
     * @param  array<string, mixed>  $leaderPrep
     * @param  list<array<string, mixed>>  $notes
     * @return list<string>
     */
    private function coachingTopics(array $employeePrep, array $leaderPrep, array $notes): array
    {
        $items = [];
        foreach (array_merge($employeePrep, $leaderPrep) as $field => $value) {
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            $items[] = ucwords(str_replace('_', ' ', (string) $field));
        }

        foreach ($notes as $note) {
            if (! is_array($note)) {
                continue;
            }
            $section = trim((string) ($note['section'] ?? ''));
            if ($section !== '') {
                $items[] = ucwords(str_replace('_', ' ', $section));
            }
        }

        $items = array_values(array_unique(array_filter($items)));

        if ($items === []) {
            $items[] = 'Strategic communication';
            $items[] = 'Accountability';
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $commitments
     * @return list<string>
     */
    private function followUpItems(array $commitments): array
    {
        $items = [];
        foreach ($commitments as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $items[] = 'Review progress on: '.$title;
            if (count($items) >= 3) {
                break;
            }
        }

        if ($items === []) {
            $items[] = 'Review this synthesis together in your next 1-on-1.';
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $commitments
     */
    private function countByRole(array $commitments, string $role): int
    {
        return count(array_filter($commitments, static function ($row) use ($role) {
            return is_array($row) && ($row['owner_role'] ?? '') === $role;
        }));
    }

    /**
     * @param  list<array<string, mixed>>  $commitments
     */
    private function countOpen(array $commitments): int
    {
        return count(array_filter($commitments, static function ($row) {
            if (! is_array($row)) {
                return false;
            }
            $status = (string) ($row['status'] ?? 'open');

            return $status !== 'done';
        }));
    }

    /**
     * @param  list<string>  $lines
     */
    private function paragraphs(array $lines): string
    {
        $filtered = array_values(array_filter($lines, static fn ($line) => trim($line) !== ''));

        return implode("\n\n", $filtered);
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }
}
