<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArpAiAssessment;
use App\Models\ArrAiAssessment;
use App\Models\ArrAiSynthesis;
use App\Models\IrrAiAssessment;
use App\Models\IrrAiSynthesis;
use App\Models\OneOnOneAiBrief;
use App\Models\OneOnOneAiSynthesis;
use App\Models\QbrAiAssessment;
use App\Models\QbrAiSynthesis;
use App\Support\WordpressPublicUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Single Laravel-side list of every AI generation across ARP/QBR/ARR/IRR
 * and 1-on-1 — an alternative to the separate WordPress "History" admin
 * pages, useful when wp-admin access is unavailable or just to see
 * everything in one place.
 */
class AiGenerationController extends Controller
{
    public function index(Request $request)
    {
        $wpBase = rtrim(WordpressPublicUrl::base(), '/');

        $rows = collect()
            ->concat($this->qbrRows($wpBase))
            ->concat($this->arpRows($wpBase))
            ->concat($this->arrRows($wpBase))
            ->concat($this->irrRows($wpBase))
            ->concat($this->oneOnOneRows($wpBase))
            ->sortByDesc(fn ($r) => $r['created_at'])
            ->values();

        $module = (string) $request->query('module', '');
        if ($module !== '') {
            $rows = $rows->filter(fn ($r) => $r['module'] === $module)->values();
        }

        $now = now();
        $lastMonth = $now->copy()->subMonthNoOverflow();
        $summarize = fn (Collection $set) => [
            'tokens' => (int) $set->sum('tokens_used'),
            'cost' => (float) $set->sum('cost_usd'),
            'count' => $set->count(),
        ];

        $stats = [
            'all' => $summarize($rows),
            'this_month' => $summarize($rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->isSameMonth($now))),
            'last_month' => $summarize($rows->filter(fn ($r) => $r['created_at'] && $r['created_at']->isSameMonth($lastMonth))),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $total = $rows->count();
        $paged = $rows->forPage($page, $perPage)->values();
        $lastPage = (int) max(1, ceil($total / $perPage));

        return view('admin.ai-generation.index', [
            'rows' => $paged,
            'page' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'module' => $module,
            'stats' => $stats,
        ]);
    }

    private function row(string $module, string $type, ?string $companyName, string $recordLabel, $model, ?string $wpUrl): array
    {
        return [
            'module' => $module,
            'type' => $type,
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
            $out->push($this->row('QBR', 'Assessment (Step 3)', $qbr?->company?->title, $qbr ? 'Q'.$qbr->quarter.' '.$qbr->year : '—', $a,
                $qbr ? $wpBase.'/quarterly-business-review/?qbr_id='.$qbr->id : null));
        });

        QbrAiSynthesis::with('qbr.company:id,title')->get()->each(function (QbrAiSynthesis $a) use (&$out, $wpBase) {
            $qbr = $a->qbr;
            $out->push($this->row('QBR', 'Synthesis (Step 6)', $qbr?->company?->title, $qbr ? 'Q'.$qbr->quarter.' '.$qbr->year : '—', $a,
                $qbr ? $wpBase.'/quarterly-business-review/?qbr_id='.$qbr->id : null));
        });

        return $out;
    }

    private function arpRows(string $wpBase): Collection
    {
        $out = collect();

        ArpAiAssessment::with('arp.company:id,title')->get()->each(function (ArpAiAssessment $a) use (&$out, $wpBase) {
            $arp = $a->arp;
            $out->push($this->row('ARP', 'Readiness Review (Step 6)', $arp?->company?->title, $arp ? 'ARP '.$arp->year : '—', $a,
                $arp ? $wpBase.'/annual-readiness-plan/?arp_id='.$arp->id : null));
        });

        return $out;
    }

    private function arrRows(string $wpBase): Collection
    {
        $out = collect();

        ArrAiAssessment::with('arr.company:id,title')->get()->each(function (ArrAiAssessment $a) use (&$out, $wpBase) {
            $arr = $a->arr;
            $out->push($this->row('ARR', 'Assessment (Step 3)', $arr?->company?->title, $arr ? 'ARR '.$arr->year : '—', $a,
                $arr ? $wpBase.'/annual-readiness-review/?arr_id='.$arr->id : null));
        });

        ArrAiSynthesis::with('arr.company:id,title')->get()->each(function (ArrAiSynthesis $a) use (&$out, $wpBase) {
            $arr = $a->arr;
            $out->push($this->row('ARR', 'Synthesis (Step 6)', $arr?->company?->title, $arr ? 'ARR '.$arr->year : '—', $a,
                $arr ? $wpBase.'/annual-readiness-review/?arr_id='.$arr->id : null));
        });

        return $out;
    }

    private function irrRows(string $wpBase): Collection
    {
        $out = collect();

        IrrAiAssessment::with('review.company:id,title')->get()->each(function (IrrAiAssessment $a) use (&$out, $wpBase) {
            $review = $a->review;
            $out->push($this->row('IRR', 'Assessment (Step 3)', $review?->company?->title, $review ? 'IRR '.$review->year : '—', $a,
                $review ? $wpBase.'/individual-readiness-review/?irr_id='.$review->id : null));
        });

        IrrAiSynthesis::with('review.company:id,title')->get()->each(function (IrrAiSynthesis $a) use (&$out, $wpBase) {
            $review = $a->review;
            $out->push($this->row('IRR', 'Synthesis (Step 6)', $review?->company?->title, $review ? 'IRR '.$review->year : '—', $a,
                $review ? $wpBase.'/individual-readiness-review/?irr_id='.$review->id : null));
        });

        return $out;
    }

    private function oneOnOneRows(string $wpBase): Collection
    {
        $out = collect();

        OneOnOneAiBrief::with('conversation.oneOnOne.company:id,title')->get()->each(function (OneOnOneAiBrief $a) use (&$out, $wpBase) {
            $conversation = $a->conversation;
            $out->push($this->row('1-on-1', 'Meeting Brief (Step 2)', $conversation?->oneOnOne?->company?->title, $conversation ? 'Meeting #'.$conversation->id : '—', $a,
                $conversation ? $wpBase.'/1-on-1-alignment/?conversation_id='.$conversation->id : null));
        });

        OneOnOneAiSynthesis::with('conversation.oneOnOne.company:id,title')->get()->each(function (OneOnOneAiSynthesis $a) use (&$out, $wpBase) {
            $conversation = $a->conversation;
            $out->push($this->row('1-on-1', 'Meeting Synthesis (Step 6)', $conversation?->oneOnOne?->company?->title, $conversation ? 'Meeting #'.$conversation->id : '—', $a,
                $conversation ? $wpBase.'/1-on-1-alignment/?conversation_id='.$conversation->id : null));
        });

        return $out;
    }
}
