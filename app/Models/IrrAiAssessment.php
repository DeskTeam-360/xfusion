<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * IRR Step 3 — AI Development Assessment™. Always inserts a new row on
 * regenerate (previous versions kept, read-only) — same pattern as
 * wp_fusion_arp_ai_assessments / wp_fusion_qbr_ai_assessments.
 */
class IrrAiAssessment extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_360_ai_assessments';

    protected $fillable = [
        'review_id',
        'assessment',
        'insight_model',
        'tokens_used',
        'cost_usd',
        'prompt_version_id',
        'prompt_version_label',
    ];

    protected $casts = [
        'assessment' => 'array',
    ];

    public function review()
    {
        return $this->belongsTo(IrrReview::class, 'review_id');
    }
}
