<?php

namespace App\Services;

/**
 * Builds a consistent commitment_summary block for AI Meeting Synthesis™.
 *
 * Card (items): Employee / Leader / Open counts only.
 * Details: human-readable commitment list (no duplicate raw lines).
 */
class SynthesisCommitmentSummaryNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $commitments
     * @param  array<string, mixed>  $llmBlock
     * @return array{items: list<string>, details: string, employee_count: int, leader_count: int, open_count: int}
     */
    public function fromCommitments(array $commitments, array $llmBlock = []): array
    {
        $rows = $this->sanitizeRows($commitments);
        $employeeCount = count(array_filter($rows, static fn (array $r) => $r['owner_role'] === 'employee'));
        $leaderCount = count(array_filter($rows, static fn (array $r) => $r['owner_role'] === 'leader'));
        $openCount = count(array_filter($rows, static fn (array $r) => $r['status'] !== 'done'));

        $llmDetails = trim((string) ($llmBlock['details'] ?? ''));

        return [
            'employee_count' => $employeeCount,
            'leader_count' => $leaderCount,
            'open_count' => $openCount,
            'items' => [
                "Employee Commitments: {$employeeCount} active",
                "Leader Commitments: {$leaderCount} active",
                "Open Commitments: {$openCount} total",
            ],
            'details' => $this->formatDetails($rows, $llmDetails),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $commitments
     * @return list<array{id: int, title: string, owner_role: string, status: string}>
     */
    private function sanitizeRows(array $commitments): array
    {
        $rows = [];
        $seen = [];

        foreach ($commitments as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $role = strtolower(trim((string) ($row['owner_role'] ?? 'shared')));
            if (! in_array($role, ['employee', 'leader', 'shared'], true)) {
                $role = 'shared';
            }

            $status = strtolower(trim((string) ($row['status'] ?? 'open')));
            if (! in_array($status, ['open', 'in_progress', 'done'], true)) {
                $status = 'open';
            }

            $dedupeKey = $id > 0
                ? 'id:'.$id
                : strtolower($title).'|'.$role.'|'.$status;

            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $rows[] = [
                'id' => $id,
                'title' => $title,
                'owner_role' => $role,
                'status' => $status,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{id: int, title: string, owner_role: string, status: string}>  $rows
     */
    private function formatDetails(array $rows, string $llmNarrative): string
    {
        if ($rows === []) {
            return $llmNarrative !== ''
                ? $llmNarrative
                : 'No commitments were saved before synthesis generation.';
        }

        $lines = ['Commitments on record:', ''];

        foreach ($rows as $row) {
            $lines[] = '• '.$row['title'];
            $lines[] = '  Status: '.$this->formatStatus($row['status']).' · Owner: '.$this->formatOwner($row['owner_role']);
            $lines[] = '';
        }

        $block = rtrim(implode("\n", $lines));

        if ($llmNarrative === '' || $this->narrativeDuplicatesList($llmNarrative, $rows)) {
            return $block;
        }

        return $block."\n\n".$llmNarrative;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'in_progress' => 'In Progress',
            'done' => 'Done',
            default => 'Open',
        };
    }

    private function formatOwner(string $role): string
    {
        return match ($role) {
            'employee' => 'Employee',
            'leader' => 'Leader',
            default => 'Shared',
        };
    }

    /**
     * @param  list<array{title: string}>  $rows
     */
    private function narrativeDuplicatesList(string $narrative, array $rows): bool
    {
        $lower = strtolower($narrative);
        $hits = 0;

        foreach ($rows as $row) {
            if (str_contains($lower, strtolower($row['title']))) {
                $hits++;
            }
        }

        return $hits >= min(2, count($rows));
    }
}
