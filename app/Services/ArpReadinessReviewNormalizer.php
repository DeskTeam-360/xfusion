<?php

namespace App\Services;

/**
 * Normalizes LLM JSON into the Step 6 UI assessment schema.
 */
class ArpReadinessReviewNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw): array
    {
        $strategic = is_array($raw['strategic_alignment'] ?? null) ? $raw['strategic_alignment'] : [];
        $readiness = is_array($raw['readiness_assessment'] ?? null) ? $raw['readiness_assessment'] : [];
        $priority = is_array($raw['priority_alignment'] ?? null) ? $raw['priority_alignment'] : [];
        $risks = is_array($raw['risk_summary'] ?? null) ? $raw['risk_summary'] : [];

        $strategicScore = $this->score($strategic['score'] ?? null, 84);
        $readinessScore = $this->score($readiness['score'] ?? null, 76);
        $priorityScore = $this->score($priority['score'] ?? null, 82);

        return [
            'strategic_alignment' => [
                'score' => $strategicScore,
                'label' => $this->string($strategic['label'] ?? '', 'Strong Alignment'),
                'color' => $this->donutColor($strategicScore, $strategic['color'] ?? null),
                'summary' => $this->string($strategic['summary'] ?? '', ''),
                'strengths' => $this->stringList($strategic['strengths'] ?? [], 6),
            ],
            'readiness_assessment' => [
                'score' => $readinessScore,
                'label' => $this->string($readiness['label'] ?? '', 'Readiness Score'),
                'color' => $this->donutColor($readinessScore, $readiness['color'] ?? null),
                'summary' => $this->string($readiness['summary'] ?? '', ''),
                'strengths_count' => max(0, (int) ($readiness['strengths_count'] ?? 0)),
                'development_count' => max(0, (int) ($readiness['development_count'] ?? 0)),
                'critical_gaps_count' => max(0, (int) ($readiness['critical_gaps_count'] ?? 0)),
            ],
            'gaps' => $this->normalizeGaps($raw['gaps'] ?? []),
            'priority_alignment' => [
                'score' => $priorityScore,
                'label' => $this->string($priority['label'] ?? '', 'Alignment Score'),
                'color' => $this->donutColor($priorityScore, $priority['color'] ?? null),
                'summary' => $this->string($priority['summary'] ?? '', ''),
                'dimensions' => $this->normalizeDimensions($priority['dimensions'] ?? []),
            ],
            'risk_summary' => [
                'high' => max(0, (int) ($risks['high'] ?? 0)),
                'medium' => max(0, (int) ($risks['medium'] ?? 0)),
                'low' => max(0, (int) ($risks['low'] ?? 0)),
                'strengths' => max(0, (int) ($risks['strengths'] ?? 0)),
            ],
            'focus_areas' => $this->stringList($raw['focus_areas'] ?? [], 8),
        ];
    }

    /**
     * @param  mixed  $gaps
     * @return list<array{area: string, description: string, impact: string, priority: string}>
     */
    private function normalizeGaps(mixed $gaps): array
    {
        if (! is_array($gaps)) {
            return [];
        }

        $out = [];
        foreach ($gaps as $gap) {
            if (! is_array($gap)) {
                continue;
            }
            $area = $this->string($gap['area'] ?? '', '');
            if ($area === '') {
                continue;
            }
            $out[] = [
                'area' => $area,
                'description' => $this->string($gap['description'] ?? '', ''),
                'impact' => $this->level($gap['impact'] ?? 'Medium'),
                'priority' => $this->level($gap['priority'] ?? 'Medium'),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $dimensions
     * @return list<array{label: string, percent: int}>
     */
    private function normalizeDimensions(mixed $dimensions): array
    {
        if (! is_array($dimensions)) {
            return [];
        }

        $out = [];
        foreach ($dimensions as $dim) {
            if (! is_array($dim)) {
                continue;
            }
            $label = $this->string($dim['label'] ?? '', '');
            if ($label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'percent' => min(100, max(0, (int) ($dim['percent'] ?? 0))),
            ];
        }

        return $out;
    }

    /**
     * @param  mixed  $list
     * @return list<string>
     */
    private function stringList(mixed $list, int $max): array
    {
        if (! is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }
            $out[] = $text;
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    private function string(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }

    private function score(mixed $value, int $fallback): int
    {
        if (! is_numeric($value)) {
            return min(100, max(0, $fallback));
        }

        return min(100, max(0, (int) round((float) $value)));
    }

    private function level(mixed $value): string
    {
        $key = strtolower(trim((string) $value));

        return match ($key) {
            'high' => 'High',
            'low' => 'Low',
            default => 'Medium',
        };
    }

    private function donutColor(int $score, mixed $override): string
    {
        $color = trim((string) $override);
        if ($color !== '' && str_starts_with($color, '#')) {
            return $color;
        }

        if ($score >= 80) {
            return '#5f9a3f';
        }
        if ($score >= 65) {
            return '#c4a035';
        }

        return '#ea580c';
    }
}
