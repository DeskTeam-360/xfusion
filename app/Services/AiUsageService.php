<?php

namespace App\Services;

use App\Models\ArpAiAssessment;
use App\Models\CompanyEmployee;
use App\Models\ArrAiAssessment;
use App\Models\ArrAiSynthesis;
use App\Models\IrrAiAssessment;
use App\Models\IrrAiSynthesis;
use App\Models\OneOnOneAiBrief;
use App\Models\OneOnOneAiSynthesis;
use App\Models\QbrAiAssessment;
use App\Models\QbrAiSynthesis;
use App\Support\WordpressPublicUrl;
use Illuminate\Support\Collection;

/**
 * Every AI Assessment/Synthesis/Brief generated across ARP, QBR, ARR, IRR,
 * and 1-on-1, flattened into one shape — backs the AI Generations admin
 * page and the dashboard's AI usage summary.
 */
class AiUsageService
{
    /**
     * @return Collection<int, array{module: string, type: string, company_id: ?int, company_name: ?string, record_label: string, insight_model: string, tokens_used: int, cost_usd: float, created_at: ?\Illuminate\Support\Carbon, wp_url: ?string}>
     */
    public function allRows(): Collection
    {
        $wpBase = rtrim(WordpressPublicUrl::base(), '/');

        return collect()
            ->concat($this->qbrRows($wpBase))
            ->concat($this->arpRows($wpBase))
            ->concat($this->arrRows($wpBase))
            ->concat($this->irrRows($wpBase))
            ->concat($this->oneOnOneRows($wpBase))
            ->sortByDesc(fn ($r) => $r['created_at'])
            ->values();
    }

    /**
     * @return array{tokens: int, cost: float, count: int}
     */
    public function summarize(Collection $rows): array
    {
        return [
            'tokens' => (int) $rows->sum('tokens_used'),
            'cost' => (float) $rows->sum('cost_usd'),
            'count' => $rows->count(),
        ];
    }

    /**
     * All-time / this-month / last-month summaries for a given row set.
     *
     * @return array{all: array, this_month: array, last_month: array}
     */
    public function periodSummaries(Collection $rows): array
    {
        $now = now();
        $lastMonth = $now->copy()->subMonthNoOverflow();

        return [
            'all' => $this->summarize($rows),
            'this_month' => $this->summarize($rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->isSameMonth($now))),
            'last_month' => $this->summarize($rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->isSameMonth($lastMonth))),
        ];
    }

    /**
     * Per-company totals (plus current employee count), sorted by cost
     * descending.
     *
     * @return Collection<int, array{company_id: ?int, company_name: string, employee_count: int, tokens: int, cost: float, count: int}>
     */
    public function perCompany(Collection $rows): Collection
    {
        $companyIds = $rows->pluck('company_id')->filter()->unique()->values();

        $employeeCounts = $companyIds->isEmpty()
            ? collect()
            : CompanyEmployee::whereIn('company_id', $companyIds)
                ->selectRaw('company_id, count(*) as c')
                ->groupBy('company_id')
                ->pluck('c', 'company_id');

        return $rows
            ->groupBy(fn ($r) => $r['company_id'] ?? $r['company_name'])
            ->map(function (Collection $group) use ($employeeCounts) {
                $companyId = $group->first()['company_id'] ?? null;

                return array_merge(
                    [
                        'company_id' => $companyId,
                        'company_name' => $group->first()['company_name'] ?: '—',
                        'employee_count' => $companyId ? (int) ($employeeCounts[$companyId] ?? 0) : 0,
                    ],
                    $this->summarize($group)
                );
            })
            ->values()
            ->sortByDesc('cost')
            ->values();
    }

    private function row(string $module, string $type, ?int $companyId, ?string $companyName, string $recordLabel, $model, ?string $wpUrl): array
    {
        return [
            'module' => $module,
            'type' => $type,
            'company_id' => $companyId,
            'company_name' => $companyName ?: '—',
            'record_label' => $recordLabel,
            'insight_model' => $model->insight_model ?? '—',
            'tokens_used' => (int) ($model->tokens_used ?? 0),
            'cost_usd' => (float) ($model->cost_usd ?? 0),
            'created_at' => $model->created_at,
            'wp_url' => $wpUrl,
        ];
    }

    private function qbrRows(string $wpBase): Collection
    {
        $out = collect();

        QbrAiAssessment::with('qbr.company:id,title')->get()->each(function (QbrAiAssessment $a) use (&$out, $wpBase) {
            $qbr = $a->qbr;
            $out->push($this->row('QBR', 'Assessment (Step 3)', $qbr?->company?->id, $qbr?->company?->title, $qbr ? 'Q'.$qbr->quarter.' '.$qbr->year : '—', $a,
                $qbr ? $wpBase.'/quarterly-business-review/?qbr_id='.$qbr->id : null));
        });

        QbrAiSynthesis::with('qbr.company:id,title')->get()->each(function (QbrAiSynthesis $a) use (&$out, $wpBase) {
            $qbr = $a->qbr;
            $out->push($this->row('QBR', 'Synthesis (Step 6)', $qbr?->company?->id, $qbr?->company?->title, $qbr ? 'Q'.$qbr->quarter.' '.$qbr->year : '—', $a,
                $qbr ? $wpBase.'/quarterly-business-review/?qbr_id='.$qbr->id : null));
        });

        return $out;
    }

    private function arpRows(string $wpBase): Collection
    {
        $out = collect();

        ArpAiAssessment::with('arp.company:id,title')->get()->each(function (ArpAiAssessment $a) use (&$out, $wpBase) {
            $arp = $a->arp;
            $out->push($this->row('ARP', 'Readiness Review (Step 6)', $arp?->company?->id, $arp?->company?->title, $arp ? 'ARP '.$arp->year : '—', $a,
                $arp ? $wpBase.'/annual-readiness-plan/?arp_id='.$arp->id : null));
        });

        return $out;
    }

    private function arrRows(string $wpBase): Collection
    {
        $out = collect();

        ArrAiAssessment::with('arr.company:id,title')->get()->each(function (ArrAiAssessment $a) use (&$out, $wpBase) {
            $arr = $a->arr;
            $out->push($this->row('ARR', 'Assessment (Step 3)', $arr?->company?->id, $arr?->company?->title, $arr ? 'ARR '.$arr->year : '—', $a,
                $arr ? $wpBase.'/annual-readiness-review/?arr_id='.$arr->id : null));
        });

        ArrAiSynthesis::with('arr.company:id,title')->get()->each(function (ArrAiSynthesis $a) use (&$out, $wpBase) {
            $arr = $a->arr;
            $out->push($this->row('ARR', 'Synthesis (Step 6)', $arr?->company?->id, $arr?->company?->title, $arr ? 'ARR '.$arr->year : '—', $a,
                $arr ? $wpBase.'/annual-readiness-review/?arr_id='.$arr->id : null));
        });

        return $out;
    }

    private function irrRows(string $wpBase): Collection
    {
        $out = collect();

        IrrAiAssessment::with('review.company:id,title')->get()->each(function (IrrAiAssessment $a) use (&$out, $wpBase) {
            $review = $a->review;
            $out->push($this->row('IRR', 'Assessment (Step 3)', $review?->company?->id, $review?->company?->title, $review ? 'IRR '.$review->year : '—', $a,
                $review ? $wpBase.'/individual-readiness-review/?irr_id='.$review->id : null));
        });

        IrrAiSynthesis::with('review.company:id,title')->get()->each(function (IrrAiSynthesis $a) use (&$out, $wpBase) {
            $review = $a->review;
            $out->push($this->row('IRR', 'Synthesis (Step 6)', $review?->company?->id, $review?->company?->title, $review ? 'IRR '.$review->year : '—', $a,
                $review ? $wpBase.'/individual-readiness-review/?irr_id='.$review->id : null));
        });

        return $out;
    }

    private function oneOnOneRows(string $wpBase): Collection
    {
        $out = collect();

        OneOnOneAiBrief::with('conversation.oneOnOne.company:id,title')->get()->each(function (OneOnOneAiBrief $a) use (&$out, $wpBase) {
            $conversation = $a->conversation;
            $out->push($this->row('1-on-1', 'Meeting Brief (Step 2)', $conversation?->oneOnOne?->company?->id, $conversation?->oneOnOne?->company?->title, $conversation ? 'Meeting #'.$conversation->id : '—', $a,
                $conversation ? $wpBase.'/1-on-1-alignment/?conversation_id='.$conversation->id : null));
        });

        OneOnOneAiSynthesis::with('conversation.oneOnOne.company:id,title')->get()->each(function (OneOnOneAiSynthesis $a) use (&$out, $wpBase) {
            $conversation = $a->conversation;
            $out->push($this->row('1-on-1', 'Meeting Synthesis (Step 6)', $conversation?->oneOnOne?->company?->id, $conversation?->oneOnOne?->company?->title, $conversation ? 'Meeting #'.$conversation->id : '—', $a,
                $conversation ? $wpBase.'/1-on-1-alignment/?conversation_id='.$conversation->id : null));
        });

        return $out;
    }
}
