<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * IRR Step 6 — AI Development Synthesis™. Always inserts a new row on
 * regenerate (previous versions kept, read-only) — same pattern as
 * IrrAiAssessment / ArpAiAssessment / QbrAiSynthesis.
 */
class IrrAiSynthesis extends Model
{
    use HasFactory;

    protected $table = 'wp_fusion_360_ai_syntheses';

    protected $fillable = [
        'review_id',
        'synthesis',
        'insight_model',
        'tokens_used',
        'cost_usd',
        'prompt_version_id',
        'prompt_version_label',
    ];

    protected $casts = [
        'synthesis' => 'array',
    ];

    public function review()
    {
        return $this->belongsTo(IrrReview::class, 'review_id');
    }
}
